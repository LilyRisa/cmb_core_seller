<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\Contracts\UtilityTemplateConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateStatusDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use RuntimeException;

/**
 * Vòng đời utility template (SPEC-0032): submit lên provider → đồng bộ trạng thái
 * duyệt → resolve template `approved` cho notifier/composer. Cô lập mọi tương tác
 * connector (luật vàng: gate qua {@see UtilityTemplateConnector}, không tên sàn).
 */
class UtilityTemplateService
{
    public function __construct(private readonly MessagingRegistry $registry) {}

    /**
     * Submit template lên provider để duyệt. Idempotent: đã có `external_template_id`
     * ⇒ chỉ re-sync (không tạo trùng trên Meta).
     */
    public function submit(UtilityTemplate $template): UtilityTemplate
    {
        if ($template->external_template_id) {
            return $this->syncStatus($template);
        }

        [$connector, $auth] = $this->connectorFor($template);

        $ref = $connector->createUtilityTemplate($auth, new UtilityTemplateDTO(
            name: $this->externalName($template),
            language: $template->language,
            body: $template->body,
            buttons: (array) ($template->buttons ?? []),
            examples: $this->examples($template),
        ));

        $template->forceFill([
            'external_template_id' => $ref->externalTemplateId,
            'status' => $this->mapStatus($ref->status),
            'reject_reason' => null,
        ])->save();

        return $template;
    }

    /** Đồng bộ trạng thái duyệt từ provider. No-op nếu chưa submit. */
    public function syncStatus(UtilityTemplate $template): UtilityTemplate
    {
        if (! $template->external_template_id) {
            return $template;
        }

        [$connector, $auth] = $this->connectorFor($template);

        $status = $connector->syncUtilityTemplateStatus($auth, $template->external_template_id);

        $template->forceFill([
            'status' => $this->mapStatus($status->status),
            'reject_reason' => $status->status === UtilityTemplateStatusDTO::REJECTED ? $status->reason : null,
        ])->save();

        return $template;
    }

    /**
     * Template `approved` cho (channel account, code, language) — dùng để gửi.
     * Bỏ qua scope tenant (gọi từ job/notifier không có CurrentTenant).
     */
    public function resolveApproved(int $channelAccountId, string $code, string $language = 'vi'): ?UtilityTemplate
    {
        return UtilityTemplate::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $channelAccountId)
            ->where('code', $code)
            ->where('language', $language)
            ->where('status', UtilityTemplate::STATUS_APPROVED)
            ->where('enabled', true)
            ->first();
    }

    /** Ref DTO để gửi (connector cần external id + name + language). */
    public function refFor(UtilityTemplate $template): UtilityTemplateRefDTO
    {
        return new UtilityTemplateRefDTO(
            externalTemplateId: (string) $template->external_template_id,
            name: $this->externalName($template),
            language: $template->language,
            status: UtilityTemplateStatusDTO::APPROVED,
        );
    }

    /**
     * Connector (phải hỗ trợ utility template) + auth context cho Page của template.
     *
     * @return array{0: UtilityTemplateConnector, 1: MessagingAuthContext}
     */
    private function connectorFor(UtilityTemplate $template): array
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($template->channel_account_id);
        if (! $account) {
            throw new RuntimeException('Channel account không tồn tại cho utility template.');
        }

        if (! $this->registry->has($account->provider)) {
            throw new RuntimeException("Provider [{$account->provider}] chưa bật messaging.");
        }
        $connector = $this->registry->for($account->provider);
        if (! ($connector instanceof UtilityTemplateConnector && $connector->supports('outbound.utility_template'))) {
            throw new RuntimeException("Provider [{$account->provider}] không hỗ trợ utility template.");
        }

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->getKey(),
            provider: $account->provider,
            externalShopId: $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
            extra: (array) ($account->meta ?? []),
        );

        return [$connector, $auth];
    }

    /**
     * Tên template phía Meta: chỉ chữ thường + số + gạch dưới. Ghép code + language
     * để duy nhất per-Page (vd `order_confirmation_vi`).
     */
    private function externalName(UtilityTemplate $template): string
    {
        $name = strtolower($template->code.'_'.$template->language);

        return (string) preg_replace('/[^a-z0-9_]/', '_', $name);
    }

    /** Giá trị mẫu cho {{1}},{{2}}… (Meta bắt buộc khi submit). */
    private function examples(UtilityTemplate $template): array
    {
        $vars = array_values((array) ($template->variables ?? []));

        return array_map(fn ($v): string => 'mẫu '.(string) $v, $vars);
    }

    private function mapStatus(string $connectorStatus): string
    {
        return match ($connectorStatus) {
            UtilityTemplateStatusDTO::APPROVED => UtilityTemplate::STATUS_APPROVED,
            UtilityTemplateStatusDTO::REJECTED => UtilityTemplate::STATUS_REJECTED,
            default => UtilityTemplate::STATUS_PENDING,
        };
    }
}
