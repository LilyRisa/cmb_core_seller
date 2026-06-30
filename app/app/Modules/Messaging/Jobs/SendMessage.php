<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\UtilityTemplateConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Exceptions\ConversationClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloApiException;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageFailed;
use CMBcoreSeller\Modules\Messaging\Events\MessageSent;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Messaging\Services\UtilityTemplateService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gửi 1 outbound message qua connector. Idempotent: nếu `delivery_status`
 * đã `sent`/`delivered`/`read` ⇒ return ngay (retry sau echo-back đã ack).
 *
 * Queue: `messaging-outbound` (tries 4, backoff 5/30/120/600).
 * Tôn trọng 429/Retry-After ở connector level (S2+).
 *
 * Lỗi vĩnh viễn (KHÔNG retry — tránh treo ~12 phút theo backoff vô ích):
 *   - `ConversationClosed`: mark failed `conversation_closed`.
 *   - `OutboundWindowClosed`: ngoài cửa sổ 24h ⇒ mark failed `outbound_window_closed`.
 *   - exception khác: retry, hết tries ⇒ mark failed `send_failed`.
 */
class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public int $messageId)
    {
        $this->onQueue('messaging-outbound');
    }

    public function backoff(): array
    {
        return [5, 30, 120, 600];
    }

    public function handle(MessagingRegistry $registry): void
    {
        $message = Message::withoutGlobalScope(TenantScope::class)->find($this->messageId);
        if (! $message) {
            return;
        }
        // Idempotent: đã sent/delivered/read ⇒ skip
        if (in_array($message->delivery_status, [
            Message::STATUS_SENT, Message::STATUS_DELIVERED, Message::STATUS_READ,
        ], true)) {
            return;
        }

        $conversation = Conversation::withoutGlobalScope(TenantScope::class)->find($message->conversation_id);
        if (! $conversation) {
            $this->markFailed($message, 'conversation_not_found');

            return;
        }

        // Guard: hội thoại bị chặn ⇒ không gửi (tránh gửi nhầm auto-reply/AI cho
        // người dùng đã bị block). Dừng vĩnh viễn — không retry.
        if ($conversation->blocked_at !== null) {
            $this->markFailed($message, 'conversation_blocked');

            return;
        }

        if (! $registry->has($conversation->provider)) {
            $this->markFailed($message, 'unknown_provider');

            return;
        }
        $connector = $registry->for($conversation->provider);

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($conversation->channel_account_id);
        if (! $account) {
            $this->markFailed($message, 'channel_account_missing');

            return;
        }
        if ($account->status !== ChannelAccount::STATUS_ACTIVE) {
            $this->markFailed($message, 'channel_account_inactive');

            return;
        }

        // Tin tương tác (nút bấm): chỉ connector có NĂNG LỰC này mới gửi được — kiểm
        // theo TÊN NĂNG LỰC (interface + capability), KHÔNG phải tên sàn (luật vàng).
        // Thiếu năng lực ⇒ fail VĨNH VIỄN (không retry — retry không đổi kết quả).
        if ($message->kind === Message::KIND_INTERACTIVE
            && ! ($connector instanceof InteractiveMessagingConnector && $connector->supports('outbound.interactive'))) {
            $this->markFailed($message, 'interactive_unsupported');

            return;
        }

        // Tin utility-template: chỉ connector có năng lực `outbound.utility_template`
        // (Facebook Messenger Utility Messages) mới gửi được — gate theo NĂNG LỰC,
        // không tên sàn. Thiếu ⇒ fail VĨNH VIỄN (retry không đổi kết quả).
        if ($message->kind === Message::KIND_UTILITY_TEMPLATE
            && ! ($connector instanceof UtilityTemplateConnector && $connector->supports('outbound.utility_template'))) {
            $this->markFailed($message, 'utility_template_unsupported');

            return;
        }

        $auth = new MessagingAuthContext(
            channelAccountId: $account->id,
            provider: $conversation->provider,
            externalShopId: $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
            extra: (array) ($account->meta ?? []),
        );

        $opts = (array) ($message->meta ?? []);

        try {
            $result = match (true) {
                $message->kind === Message::KIND_TEXT => $connector->sendText(
                    $auth, $conversation->external_conversation_id, (string) $message->body, $opts
                ),
                $message->kind === Message::KIND_TEMPLATE => $connector->sendTemplate(
                    $auth, $conversation->external_conversation_id,
                    (string) ($opts['template_code'] ?? ''),
                    (array) ($opts['vars'] ?? []),
                    $opts,
                ),
                in_array($message->kind, [Message::KIND_IMAGE, Message::KIND_VIDEO, Message::KIND_AUDIO, Message::KIND_FILE], true) => $this->sendMediaForMessage($connector, $auth, $conversation->external_conversation_id, $message, $opts),
                $message->kind === Message::KIND_INTERACTIVE && $connector instanceof InteractiveMessagingConnector => $connector->sendInteractive(
                    $auth, $conversation->external_conversation_id, (array) ($opts['interactive'] ?? []), $opts
                ),
                $message->kind === Message::KIND_UTILITY_TEMPLATE && $connector instanceof UtilityTemplateConnector => $this->sendUtilityTemplateForMessage(
                    $connector, $auth, $conversation->external_conversation_id, $message, $opts
                ),
                default => throw new \RuntimeException("Kind [{$message->kind}] không hỗ trợ ở S1."),
            };

            $message->update([
                'external_message_id' => $result->externalMessageId,
                'delivery_status' => Message::STATUS_SENT,
                'sent_at' => $result->sentAt?->toDateTimeString() ?? now(),
            ]);

            MessageSent::dispatch($message->id, $message->conversation_id);

            // Self-recovery: gửi thành công → xoá cờ tier-block cũ (nếu OA đã nâng gói).
            if ($account->provider === 'zalo_oa' && is_array($account->meta) && ($account->meta['zalo_send_blocked'] ?? false)) {
                $meta = $account->meta;
                unset($meta['zalo_send_blocked'], $meta['zalo_send_blocked_reason'], $meta['zalo_send_blocked_at']);
                $account->forceFill(['meta' => $meta])->save();
            }
        } catch (ConversationClosed $e) {
            // Lỗi vĩnh viễn — không retry.
            $this->markFailed($message, 'conversation_closed');
            $this->fail($e);
        } catch (OutboundWindowClosed $e) {
            // Ngoài cửa sổ 24h của Messenger: retry VÔ NGHĨA (chỉ mở lại khi buyer nhắn mới)
            // ⇒ fail NGAY, không treo ~12 phút theo backoff. failure_code rõ để FE báo đúng.
            $this->markFailed($message, 'outbound_window_closed');
            $this->fail($e);
        } catch (Throwable $e) {
            // Lỗi OA chưa đủ gói (tier / pricing / permission): fail vĩnh viễn, KHÔNG retry.
            // Lưu cờ vào account.meta để FE hiển thị cảnh báo nâng gói.
            if ($e instanceof ZaloApiException && $e->isTierOrPermissionBlocked()) {
                Log::warning('messaging.send.failed', ['message_id' => $message->id, 'error' => $e->getMessage()]);
                $meta = $account->meta ?? [];
                $meta['zalo_send_blocked'] = true;
                $meta['zalo_send_blocked_reason'] = $e->getMessage();
                $meta['zalo_send_blocked_at'] = now()->toIso8601String();
                $account->forceFill(['meta' => $meta])->save();
                $this->markFailed($message, 'provider_permission');

                return;
            }

            Log::warning('messaging.send.failed', [
                'message_id' => $message->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->markFailed($message, 'send_failed');
            }
            throw $e;
        }
    }

    private function sendMediaForMessage($connector, MessagingAuthContext $auth, string $externalConvId, Message $message, array $opts)
    {
        // withoutGlobalScope: job chạy KHÔNG có CurrentTenant ⇒ TenantScope sẽ ràng
        // attachments về tenant_id=0 và không thấy gì (dù attachment đã tạo cùng message).
        // Phải bỏ scope như cách job nạp Message/Conversation/ChannelAccount ở trên.
        $attachment = $message->attachments()->withoutGlobalScope(TenantScope::class)->first();
        if (! $attachment) {
            throw new \RuntimeException('Media message thiếu attachment.');
        }

        // Outbound: sàn (vd Facebook) cần URL public — sinh signed URL từ storage
        // nếu attachment chưa có external_url.
        $externalUrl = $attachment->external_url;
        if (! $externalUrl && $attachment->storage_path) {
            $externalUrl = app(MediaStorage::class)->temporaryUrl($attachment);
        }

        $media = new MediaRefDTO(
            kind: MessageKind::from($attachment->kind),
            mime: $attachment->mime,
            sizeBytes: $attachment->size_bytes,
            externalUrl: $externalUrl,
            storagePath: $attachment->storage_path,
            filename: $attachment->filename,
            width: $attachment->width,
            height: $attachment->height,
            durationMs: $attachment->duration_ms,
        );

        return $connector->sendMedia($auth, $externalConvId, $media, $opts);
    }

    /**
     * Gửi utility template: nạp `UtilityTemplate` theo meta, dựng ref + vars rồi gọi
     * connector. Template không còn / chưa duyệt ⇒ ném (job mark failed `send_failed`).
     *
     * @param  array<string, mixed>  $opts
     */
    private function sendUtilityTemplateForMessage(UtilityTemplateConnector $connector, MessagingAuthContext $auth, string $externalConvId, Message $message, array $opts)
    {
        $templateId = (int) ($opts['utility_template_id'] ?? 0);
        $template = UtilityTemplate::withoutGlobalScope(TenantScope::class)->find($templateId);
        if (! $template || ! $template->isApproved() || ! $template->external_template_id) {
            throw new \RuntimeException('Utility template không khả dụng (chưa duyệt / đã xoá).');
        }

        $vars = array_map('strval', array_values((array) ($opts['vars'] ?? [])));

        return $connector->sendUtilityTemplate(
            $auth,
            $externalConvId,
            app(UtilityTemplateService::class)->refFor($template),
            $vars,
            $opts,
        );
    }

    private function markFailed(Message $message, string $code): void
    {
        $message->update([
            'delivery_status' => Message::STATUS_FAILED,
            'failure_code' => $code,
        ]);
        MessageFailed::dispatch($message->id, $message->conversation_id, $code);
    }
}
