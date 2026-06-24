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
