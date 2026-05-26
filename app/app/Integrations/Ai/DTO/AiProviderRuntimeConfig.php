<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Credentials + cấu hình runtime của 1 AI provider (super-admin nhập). `apiKey`
 * đã giải mã ở tầng Module trước khi đưa vào đây — connector chỉ dùng, không lưu.
 */
final readonly class AiProviderRuntimeConfig
{
    /**
     * @param  list<array{kind:string, unit:int, micro_vnd:int}>  $pricing
     * @param  array<string, mixed>  $adapterConfig  Cấu hình riêng theo adapter (vd
     *                                               `custom_http`: method/headers/request_template/
     *                                               response_path/usage). Rỗng với adapter chuẩn.
     */
    public function __construct(
        public ?string $apiKey,
        public ?string $baseUrl = null,
        public ?string $defaultModel = null,
        public array $pricing = [],
        public array $adapterConfig = [],
    ) {}
}
