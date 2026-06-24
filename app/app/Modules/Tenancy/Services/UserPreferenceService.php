<?php

namespace CMBcoreSeller\Modules\Tenancy\Services;

use CMBcoreSeller\Modules\Tenancy\Models\UserPreference;

class UserPreferenceService
{
    /** @return array<string,mixed> */
    public function all(int $userId): array
    {
        return UserPreference::query()
            ->where('user_id', $userId)
            ->pluck('value', 'key')
            ->all();
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array{ui_shell:string,ui_open_tabs:mixed,ui_active_tab:mixed,ui_desktop_bg:mixed}
     */
    public static function shape(array $raw): array
    {
        return [
            'ui_shell' => $raw['ui_shell'] ?? 'v1',
            'ui_open_tabs' => $raw['ui_open_tabs'] ?? [],
            'ui_active_tab' => $raw['ui_active_tab'] ?? null,
            'ui_desktop_bg' => $raw['ui_desktop_bg'] ?? null, // SPEC 0039 — URL hình nền (null = gradient mặc định)
        ];
    }

    /**
     * @param  array<string,mixed>  $values
     * @return array<string,mixed>
     */
    public function putMany(int $userId, array $values): array
    {
        foreach ($values as $key => $value) {
            UserPreference::updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['value' => $value],
            );
        }

        return $this->all($userId);
    }
}
