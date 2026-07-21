<?php

namespace CMBcoreSeller\Integrations\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Test kết nối "nháp" (draft) — gọi thẳng API provider bằng credentials ĐANG NHẬP trên
 * form admin, KHÔNG cần lưu trước. Dùng để gate nút "Lưu" ở 3 trang cấu hình AI chưa có
 * test-before-save (Nhà cung cấp AI / AI Trợ giúp / AI Marketing) — xem
 * docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.4.
 *
 * KHÁC với AdminAiProviderController::test() (Messaging) / AdminTranscriptionController::test()
 * / AdminVisualRerankController::test(): những controller đó test provider ĐÃ LƯU, resolve qua
 * AiAssistantRegistry (bắt buộc có row DB theo `code`). Class này test THẲNG giá trị chưa lưu,
 * nên không thể đi qua registry — tự làm request HTTP tối giản, mirror đúng request/response
 * shape của ClaudeConnector::generateReply()/OpenAiConnector::generateReply()/embed().
 *
 * Sống ở tầng Integrations (không thuộc module nào) vì được 3 module dùng chung
 * (Messaging, Support, Marketing) — modules không được `use` Services/ của nhau
 * (docs/01-architecture/extensibility-rules.md), nhưng module nào cũng được phép import
 * tầng Integrations, đúng pattern AiAssistantRegistry đã dùng.
 *
 * Chỉ hỗ trợ adapter có request/response shape CỐ ĐỊNH: anthropic, openai_compatible.
 * `custom_http` (template do admin tự định nghĩa) và `manual` (stub, không có backend thật)
 * KHÔNG probe được chung ⇒ trả ok:false kèm lý do; trang gọi phải tự bỏ qua gate cho 2 loại đó.
 */
class CredentialProbe
{
    /** @return array{ok:bool, message:?string} */
    public function probeChat(string $adapter, ?string $baseUrl, ?string $apiKey, ?string $model): array
    {
        if (! $apiKey) {
            return ['ok' => false, 'message' => 'Chưa nhập API key.'];
        }

        return match ($adapter) {
            'anthropic' => $this->probeAnthropicChat($baseUrl, $apiKey, $model),
            'openai_compatible' => $this->probeOpenAiChat($baseUrl, $apiKey, $model),
            default => ['ok' => false, 'message' => "Adapter [{$adapter}] không hỗ trợ test nháp."],
        };
    }

    /** @return array{ok:bool, message:?string} */
    public function probeEmbedding(?string $baseUrl, ?string $apiKey, ?string $model): array
    {
        if (! $apiKey) {
            return ['ok' => false, 'message' => 'Chưa nhập API key.'];
        }
        if (! $model) {
            return ['ok' => false, 'message' => 'Chưa nhập model embedding.'];
        }

        $base = $this->openAiBase($baseUrl);

        try {
            $response = Http::withToken($apiKey)
                ->withoutRedirecting()
                ->connectTimeout((int) config('ai.http.connect_timeout', 10))
                ->timeout(20)
                ->post($base.'/v1/embeddings', ['model' => $model, 'input' => 'ping']);

            if (! $response->successful()) {
                $error = (array) $response->json('error');

                return ['ok' => false, 'message' => 'Lỗi '.$response->status().': '.($error['message'] ?? $response->body())];
            }

            $dim = count((array) $response->json('data.0.embedding', []));
            if ($dim === 0) {
                return ['ok' => false, 'message' => 'Provider trả vector rỗng — kiểm tra lại model embedding.'];
            }

            return ['ok' => true, 'message' => "OK (dim {$dim})"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối: '.$e->getMessage()];
        }
    }

    /** @return array{ok:bool, message:?string} */
    private function probeAnthropicChat(?string $baseUrl, string $apiKey, ?string $model): array
    {
        if (! $model) {
            return ['ok' => false, 'message' => 'Chưa nhập model.'];
        }
        $base = rtrim($baseUrl ?: 'https://api.anthropic.com', '/');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->withoutRedirecting()
                ->connectTimeout((int) config('ai.http.connect_timeout', 10))
                ->timeout(20)
                ->post($base.'/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 8,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                ]);

            if (! $response->successful()) {
                $error = (array) $response->json('error');

                return ['ok' => false, 'message' => 'Lỗi '.$response->status().': '.($error['message'] ?? $response->body())];
            }

            return ['ok' => true, 'message' => 'Kết nối OK.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối: '.$e->getMessage()];
        }
    }

    /** @return array{ok:bool, message:?string} */
    private function probeOpenAiChat(?string $baseUrl, string $apiKey, ?string $model): array
    {
        if (! $model) {
            return ['ok' => false, 'message' => 'Chưa nhập model.'];
        }
        $base = $this->openAiBase($baseUrl);

        try {
            $response = Http::withToken($apiKey)
                ->withoutRedirecting()
                ->connectTimeout((int) config('ai.http.connect_timeout', 10))
                ->timeout(20)
                ->post($base.'/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 8,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                ]);

            if (! $response->successful()) {
                $error = (array) $response->json('error');

                return ['ok' => false, 'message' => 'Lỗi '.$response->status().': '.($error['message'] ?? $response->body())];
            }

            return ['ok' => true, 'message' => 'Kết nối OK.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối: '.$e->getMessage()];
        }
    }

    private function openAiBase(?string $baseUrl): string
    {
        $base = rtrim($baseUrl ?: 'https://api.openai.com', '/');
        if (str_ends_with($base, '/v1')) {
            $base = substr($base, 0, -3);
        }

        return $base;
    }
}
