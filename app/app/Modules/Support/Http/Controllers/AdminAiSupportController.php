<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\CredentialProbe;
use CMBcoreSeller\Modules\Support\Rules\SafeProviderUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Test kết nối "nháp" (chưa lưu) cho form cấu hình AI Trợ giúp (Hỏi AI). Trang FE
 * (`/admin/ai-support`) đọc/ghi thẳng `/admin/system-settings` (không có controller CRUD
 * riêng) — route/controller NÀY chỉ phục vụ gate "Lưu" theo docs/superpowers/specs/
 * 2026-07-21-admin-panel-ux-redesign-design.md §5.4, không đụng tới system_setting.
 * Chat/embedding của Support luôn OpenAI-compatible (xem SupportAiClient) ⇒ không cần
 * tham số adapter như trang Nhà cung cấp AI.
 *
 * base_url do admin nhập ⇒ endpoint này tự làm request HTTP thật ra đó (qua
 * CredentialProbe), nên áp SafeProviderUrl chống SSRF giống AdminAiProviderController::
 * testDraft() (Messaging) — cùng gotcha, cùng cách chặn.
 */
class AdminAiSupportController extends Controller
{
    public function testDraft(Request $request, CredentialProbe $probe): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'string', Rule::in(['chat', 'embedding'])],
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl],
            'api_key' => ['nullable', 'string', 'max:512'],
            'model' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $data['kind'] === 'chat'
            ? $probe->probeChat('openai_compatible', $data['base_url'] ?? null, $data['api_key'] ?? null, $data['model'] ?? null)
            : $probe->probeEmbedding($data['base_url'] ?? null, $data['api_key'] ?? null, $data['model'] ?? null);

        return response()->json(['data' => $result]);
    }
}
