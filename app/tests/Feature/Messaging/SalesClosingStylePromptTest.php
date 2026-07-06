<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task B2: `withClosingStyle` chèn chỉ dẫn phong cách chốt sale (B1:
 * `sales_closing_style`/`sales_closing_note`) vào system prompt — ĐỨNG SAU
 * persona/business-info, KHÔNG đụng bước classify intent (xem
 * IntentClassifier::classify — không nhận systemPromptExtra).
 */
class SalesClosingStylePromptTest extends TestCase
{
    use RefreshDatabase;

    private function seedConvWithStyle(string $style, string $note = ''): Conversation
    {
        $tenantId = 1;
        MessagingSetting::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId,
            'settings' => array_filter([
                'sales_closing_style' => $style,
                'sales_closing_note' => $note,
            ], fn ($v) => $v !== ''),
        ]);

        return new Conversation(['tenant_id' => $tenantId, 'channel_account_id' => null]);
    }

    private function invokePrivate(object $obj, string $method, array $args)
    {
        $m = new \ReflectionMethod($obj, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($obj, $args);
    }

    public function test_closing_style_directive_injected_for_fast_close(): void
    {
        $conv = $this->seedConvWithStyle('fast_close');
        $svc = app(AiSuggestionService::class);
        $extra = $this->invokePrivate($svc, 'withClosingStyle', ['', $conv]);

        $this->assertStringContainsString('chốt', mb_strtolower($extra));
        $this->assertStringContainsString('giao hàng', mb_strtolower($extra));
    }

    public function test_default_style_adds_nothing(): void
    {
        $conv = $this->seedConvWithStyle('default');
        $svc = app(AiSuggestionService::class);

        $this->assertSame('', $this->invokePrivate($svc, 'withClosingStyle', ['', $conv]));
    }

    public function test_note_appended_when_present(): void
    {
        $conv = $this->seedConvWithStyle('consultative', 'Ưu tiên tư vấn size trước khi chốt.');
        $svc = app(AiSuggestionService::class);
        $extra = $this->invokePrivate($svc, 'withClosingStyle', ['', $conv]);

        $this->assertStringContainsString('TƯ VẤN', $extra);
        $this->assertStringContainsString('Ghi chú chốt sale của shop: Ưu tiên tư vấn size trước khi chốt.', $extra);
    }

    public function test_appends_after_existing_extra_with_blank_line_separator(): void
    {
        $conv = $this->seedConvWithStyle('scarcity');
        $svc = app(AiSuggestionService::class);
        $extra = $this->invokePrivate($svc, 'withClosingStyle', ['PERSONA BASE', $conv]);

        $this->assertStringStartsWith("PERSONA BASE\n\n", $extra);
        $this->assertStringContainsString('QUYẾT ĐỊNH', $extra);
    }

    public function test_no_messaging_setting_row_returns_extra_unchanged(): void
    {
        $conv = new Conversation(['tenant_id' => 999, 'channel_account_id' => null]);
        $svc = app(AiSuggestionService::class);

        $this->assertSame('BASE', $this->invokePrivate($svc, 'withClosingStyle', ['BASE', $conv]));
    }
}
