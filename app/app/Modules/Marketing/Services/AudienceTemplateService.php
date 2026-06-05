<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Models\AudienceTemplate;
use Illuminate\Database\Eloquent\Collection;

class AudienceTemplateService
{
    /** @return Collection<int,AudienceTemplate> */
    public function list(): Collection
    {
        return AudienceTemplate::query()->orderByDesc('id')->get();
    }

    /** @param array<string,mixed> $payload */
    public function create(?int $userId, string $name, array $payload): AudienceTemplate
    {
        return AudienceTemplate::create([
            'created_by' => $userId,
            'name' => $name,
            'payload' => $payload,
        ]);
    }

    public function delete(AudienceTemplate $template): void
    {
        $template->delete();
    }
}
