<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Exceptions\AttachmentInvalid;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessageTemplate;
use CMBcoreSeller\Modules\Messaging\Services\MediaRelayService;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;
use CMBcoreSeller\Modules\Messaging\Services\OutboundWindowGuard;
use CMBcoreSeller\Modules\Messaging\Services\TemplateContextBuilder;
use CMBcoreSeller\Modules\Messaging\Services\TemplateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Send tin nhắn outbound. S1 implement text + template; media (multipart upload
 * → MinIO → connector.sendMedia) ở S2 cùng connector thật.
 *
 * Flow: validate → window guard → ghi `messages` (status='pending') →
 * dispatch `SendMessage` job → trả 202. Pattern "ghi DB trước, dispatch sau"
 * đảm bảo retry không tạo row duplicate (SPEC-0024 §4.1).
 *
 * Rate limit: per tenant 60/phút, per user 30/phút (08-security-and-privacy §6b §10).
 * Áp ở routes group qua `throttle:60,1` + `throttle:30,1` đè lên.
 */
class MessageController extends Controller
{
    public function __construct(
        private MessagingRegistry $registry,
        private OutboundWindowGuard $guard,
        private TemplateResolver $templateResolver,
        private TemplateContextBuilder $templateContext,
        private MediaRelayService $media,
        private OutboundMessageService $outbound,
    ) {}

    public function sendText(int $conversationId, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'message_tag' => ['nullable', 'string', 'max:64'],
        ]);

        $conv = Conversation::query()->findOrFail($conversationId);

        if ($blocked = $this->assertNotBlocked($conv)) {
            return $blocked;
        }

        // Resolve connector + window guard
        if (! $this->registry->has($conv->provider)) {
            return response()->json([
                'error' => ['code' => 'UNKNOWN_MESSAGING_PROVIDER', 'message' => "Provider [{$conv->provider}] không khả dụng."],
            ], 422);
        }

        $connector = $this->registry->for($conv->provider);

        if (! $connector->supports('outbound.text')) {
            return response()->json([
                'error' => ['code' => 'UNSUPPORTED', 'message' => "Provider [{$conv->provider}] không hỗ trợ gửi text."],
            ], 422);
        }

        try {
            $this->guard->assertCanSend($connector, $conv, [
                'message_tag' => $data['message_tag'] ?? null,
            ]);
        } catch (OutboundWindowClosed $e) {
            return response()->json([
                'error' => [
                    'code' => 'OUTBOUND_WINDOW_CLOSED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // Ghi message pending + dispatch — qua OutboundMessageService (entry point
        // duy nhất, dùng chung với AI-accept để không lệch logic header/idempotency).
        $message = $this->outbound->queueText($conv, [
            'body' => $data['body'],
            'sent_by_user_id' => $request->user()->id,
            'sent_by_ai' => false,
            'message_tag' => $data['message_tag'] ?? null,
            'template_id' => $request->input('template_id'),
        ]);

        return response()->json([
            'data' => (new MessageResource($message))->toArray($request),
        ], 202);
    }

    public function sendTemplate(int $conversationId, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'template_id' => ['required', 'integer'],
            'vars' => ['nullable', 'array'],
            'message_tag' => ['nullable', 'string', 'max:64'],
        ]);

        $conv = Conversation::query()->findOrFail($conversationId);
        $template = MessageTemplate::query()
            ->where('id', $data['template_id'])
            ->where('enabled', true)
            ->firstOrFail();

        // Template scope (optional): nếu khai báo providers thì conversation phải khớp.
        $scopeProviders = (array) ($template->scope['providers'] ?? []);
        if ($scopeProviders !== [] && ! in_array($conv->provider, $scopeProviders, true)) {
            return response()->json([
                'error' => ['code' => 'TEMPLATE_SCOPE_MISMATCH', 'message' => "Template không áp dụng cho provider [{$conv->provider}]."],
            ], 422);
        }

        // Resolve body qua TemplateResolver (S3) với context dựng từ conversation.
        $context = $this->templateContext->forConversation($conv, (array) ($data['vars'] ?? []));
        $rendered = $this->templateResolver->resolve($template->body, $context);

        // Re-use sendText path; ghi lại template_id vào meta để audit/analytics.
        $request->merge([
            'body' => $rendered->text,
            'message_tag' => $data['message_tag'] ?? null,
            'template_id' => $template->id,
        ]);

        return $this->sendText($conversationId, $request);
    }

    /**
     * Gửi media (image/video/file): upload multipart → lưu object storage →
     * ghi message+attachment pending → dispatch SendMessage. SPEC-0024 §6.1.
     */
    public function sendMedia(int $conversationId, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'kind' => ['required', 'in:image,video,file'],
            'file' => ['required', 'file'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'message_tag' => ['nullable', 'string', 'max:64'],
        ]);

        $conv = Conversation::query()->findOrFail($conversationId);

        if ($blocked = $this->assertNotBlocked($conv)) {
            return $blocked;
        }

        $kind = $data['kind'];

        if (! $this->registry->has($conv->provider)) {
            return response()->json([
                'error' => ['code' => 'UNKNOWN_MESSAGING_PROVIDER', 'message' => "Provider [{$conv->provider}] không khả dụng."],
            ], 422);
        }
        $connector = $this->registry->for($conv->provider);

        if (! $connector->supports("outbound.{$kind}")) {
            return response()->json([
                'error' => ['code' => 'UNSUPPORTED', 'message' => "Provider [{$conv->provider}] không hỗ trợ gửi [{$kind}]."],
            ], 422);
        }

        try {
            $this->guard->assertCanSend($connector, $conv, ['message_tag' => $data['message_tag'] ?? null]);
        } catch (OutboundWindowClosed $e) {
            return response()->json([
                'error' => ['code' => 'OUTBOUND_WINDOW_CLOSED', 'message' => $e->getMessage()],
            ], 422);
        }

        // Validate + lưu file. Sai MIME/size ⇒ 422 ATTACHMENT_INVALID.
        try {
            $stored = $this->media->storeUpload((int) $conv->tenant_id, (int) $conv->id, $request->file('file'), $kind);
        } catch (AttachmentInvalid $e) {
            return response()->json([
                'error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()],
            ], 422);
        }

        $message = DB::transaction(function () use ($conv, $data, $kind, $stored, $request) {
            $message = Message::create([
                'tenant_id' => $conv->tenant_id,
                'conversation_id' => $conv->id,
                'external_message_id' => null,
                'direction' => Message::DIRECTION_OUTBOUND,
                'kind' => $kind,
                'body' => $data['caption'] ?? null,
                'attachments_count' => 1,
                'sent_by_user_id' => $request->user()->id,
                'sent_by_ai' => false,
                'delivery_status' => Message::STATUS_PENDING,
                'meta' => array_filter(['message_tag' => $data['message_tag'] ?? null]),
            ]);

            MessageAttachment::create([
                'tenant_id' => $conv->tenant_id,
                'message_id' => $message->id,
                'kind' => $kind,
                'mime' => $stored['mime'],
                'size_bytes' => $stored['size_bytes'],
                'storage_path' => $stored['storage_path'],
                'checksum' => $stored['checksum'],
                'filename' => $stored['filename'],
                'status' => MessageAttachment::STATUS_DOWNLOADED,
            ]);

            $conv->update([
                'last_message_at' => $message->created_at,
                'last_outbound_at' => $message->created_at,
                'last_message_preview' => '['.$kind.']'.($data['caption'] ?? '' ? ' '.$data['caption'] : ''),
                'message_count' => $conv->message_count + 1,
            ]);

            return $message;
        });

        SendMessage::dispatch($message->id);

        return response()->json([
            'data' => (new MessageResource($message->load('attachments')))->toArray($request),
        ], 202);
    }

    private function assertNotBlocked(Conversation $conv): ?JsonResponse
    {
        if ($conv->blocked_at !== null) {
            return response()->json([
                'error' => ['code' => 'CONVERSATION_BLOCKED', 'message' => 'Hội thoại đã bị chặn — bỏ chặn để gửi tin.'],
            ], 422);
        }

        return null;
    }
}
