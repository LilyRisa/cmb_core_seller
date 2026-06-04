<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Models\GeoExclusionTemplate;
use Illuminate\Database\Eloquent\Collection;

class GeoExclusionTemplateService
{
    /** @return Collection<int,GeoExclusionTemplate> */
    public function list(): Collection
    {
        return GeoExclusionTemplate::query()->orderByDesc('id')->get();
    }

    /** @param array<int,array<string,mixed>> $payload */
    public function create(?int $userId, string $name, array $payload): GeoExclusionTemplate
    {
        return GeoExclusionTemplate::create([
            'created_by' => $userId,
            'name' => $name,
            'payload' => array_values($payload),
        ]);
    }

    public function delete(GeoExclusionTemplate $template): void
    {
        $template->delete();
    }
}
