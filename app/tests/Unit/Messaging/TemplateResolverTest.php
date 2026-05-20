<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\TemplateResolver;
use Tests\TestCase;

/**
 * Test `TemplateResolver` (SPEC-0024 §5.5 / §9.1) — interpolation thuần, không DB.
 */
class TemplateResolverTest extends TestCase
{
    private function r(): TemplateResolver
    {
        return new TemplateResolver;
    }

    public function test_replaces_simple_variable(): void
    {
        $out = $this->r()->resolve('Chào {{customer.name}}!', ['customer.name' => 'Anh Khang']);
        $this->assertSame('Chào Anh Khang!', $out->text);
        $this->assertFalse($out->hasMissing());
        $this->assertSame('Anh Khang', $out->used['customer.name']);
    }

    public function test_allows_whitespace_inside_braces(): void
    {
        $out = $this->r()->resolve('Đơn {{ order.code }} đã giao', ['order.code' => 'SO-123']);
        $this->assertSame('Đơn SO-123 đã giao', $out->text);
    }

    public function test_missing_variable_is_tracked_and_blanked(): void
    {
        $out = $this->r()->resolve('Chào {{customer.name}}', []);
        $this->assertSame('Chào ', $out->text);
        $this->assertTrue($out->hasMissing());
        $this->assertSame(['customer.name'], $out->missing);
    }

    public function test_default_used_when_variable_missing(): void
    {
        $out = $this->r()->resolve('Chào {{customer.name|Quý khách}}', []);
        $this->assertSame('Chào Quý khách', $out->text);
        $this->assertFalse($out->hasMissing());
    }

    public function test_default_ignored_when_variable_present(): void
    {
        $out = $this->r()->resolve('Chào {{customer.name|Quý khách}}', ['customer.name' => 'Chị Lan']);
        $this->assertSame('Chào Chị Lan', $out->text);
    }

    public function test_empty_string_value_falls_back_to_default(): void
    {
        $out = $this->r()->resolve('Chào {{customer.name|Quý khách}}', ['customer.name' => '']);
        $this->assertSame('Chào Quý khách', $out->text);
    }

    public function test_repeated_variable_resolved_each_occurrence(): void
    {
        $out = $this->r()->resolve('{{a}} & {{a}}', ['a' => 'X']);
        $this->assertSame('X & X', $out->text);
    }

    public function test_invalid_token_left_untouched(): void
    {
        $out = $this->r()->resolve('giữ {{ }} nguyên', ['' => 'x']);
        $this->assertSame('giữ {{ }} nguyên', $out->text);
    }

    public function test_declared_variables_unique_in_order(): void
    {
        $vars = $this->r()->declaredVariables('{{a}} {{b.c}} {{a}} {{ d | def }}');
        $this->assertSame(['a', 'b.c', 'd'], $vars);
    }
}
