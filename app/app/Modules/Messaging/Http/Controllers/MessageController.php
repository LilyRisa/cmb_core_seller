<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\OutboundWindowGuard;
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
    ) {}

    public function sendText(int $conversationId, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'message_tag' => ['nullable', 'string', 'max:64'],
        ]);

        $conv = Conversation::query()->findOrFail($conversationId);

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

        // Ghi message với status pending. SendMessage job sẽ gọi connector + update.
        $message = DB::transaction(function () use ($conv, $data, $request) {
            $message = Message::create([
                'tenant_id' => $conv->tenant_id,
                'conversation_id' => $conv->id,
                'external_message_id' => null,                 // chưa có cho tới khi sàn echo back
                'direction' => Message::DIRECTION_OUTBOUND,
                'kind' => Message::KIND_TEXT,
                'body' => $data['body'],
                'sent_by_user_id' => $request->user()->id,
                'sent_by_ai' => false,
                'delivery_status' => Message::STATUS_PENDING,
                'meta' => array_filter([
                    'message_tag' => $data['message_tag'] ?? null,
                ]),
            ]);

            // Cập nhật conversation header (preview + last_outbound_at — sẽ "sent" sau khi job xong)
            $conv->update([
                'last_message_at' => $message->created_at,
                'last_outbound_at' => $message->created_at,
                'last_message_preview' => \Illuminate\Support\Str::limit($data['body'], 197),
                'message_count' => $conv->message_count + 1,
            ]);

            return $message;
        });

        // Dispatch SendMessage job (S2 sẽ implement đầy đủ; S1 inline-dispatch placeholder)
        \CMBcoreSeller\Modules\Messaging\Jobs\SendMessage::dispatch($message->id);

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
        $template = \CMBcoreSeller\Modules\Messaging\Models\MessageTemplate::query()
            ->where('id', $data['template_id'])
            ->where('enabled', true)
            ->firstOrFail();

        // S3 sẽ implement TemplateResolver đầy đủ. S1: substitute đơn giản {{var}}.
        $body = $template->body;
        foreach ((array) ($data['vars'] ?? []) as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        // Re-use sendText path
        $request->merge(['body' => $body, 'message_tag' => $data['message_tag'] ?? null]);
        return $this->sendText($conversationId, $request);
    }
}
