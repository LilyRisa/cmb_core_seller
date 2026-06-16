<?php

namespace CMBcoreSeller\Integrations\Embedding\Image\Clip;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Embedding\Image\DTO\ImageVectorDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Gọi CLIP sidecar (FastAPI) `POST /embed` với ảnh base64. */
class ClipEmbedder implements ImageEmbedder
{
    private string $url;

    private string $model;

    private int $dim;

    private int $timeout;

    /** @param  array{url?:string,model?:string,dim?:int,timeout?:int}|null  $config */
    public function __construct(?array $config = null)
    {
        $config ??= (array) config('integrations.image_embedding.clip', []);
        $this->url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->model = (string) ($config['model'] ?? 'clip_vit_b32');
        $this->dim = (int) ($config['dim'] ?? 512);
        $this->timeout = (int) ($config['timeout'] ?? 30);
    }

    public function enabled(): bool
    {
        return $this->url !== '';
    }

    public function embedImage(string $bytes, string $mime): ImageVectorDTO
    {
        if (! $this->enabled()) {
            throw new RuntimeException('CLIP embedder chưa cấu hình (IMAGE_EMBEDDING_URL trống).');
        }
        $res = Http::timeout($this->timeout)->acceptJson()
            ->post($this->url.'/embed', ['image_base64' => base64_encode($bytes), 'mime' => $mime]);

        if (! $res->successful() || ! is_array($res->json('vector'))) {
            throw new RuntimeException('CLIP embed lỗi: HTTP '.$res->status());
        }

        $vector = array_map('floatval', (array) $res->json('vector'));

        return new ImageVectorDTO(
            vector: array_values($vector),
            dim: (int) ($res->json('dim') ?? count($vector)),
            model: (string) ($res->json('model') ?? $this->model),
        );
    }

    public function modelKey(): string
    {
        return $this->model;
    }

    public function dimension(): int
    {
        return $this->dim;
    }
}
