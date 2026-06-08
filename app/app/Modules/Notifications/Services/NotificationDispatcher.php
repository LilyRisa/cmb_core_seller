<?php

namespace CMBcoreSeller\Modules\Notifications\Services;

use CMBcoreSeller\Modules\Notifications\Events\NotificationCreated;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Tạo & fan-out thông báo in-app (SPEC 0036). Nhận tenant_id TƯỜNG MINH (chạy được
 * trong queued listener KHÔNG có tenant context) → mặc định gửi cho TẤT CẢ user của
 * tenant. Dedup theo `dedup_key`: chỉ bỏ qua khi user còn bản CHƯA đọc cùng key
 * (đã đọc rồi thì event mới được tạo lại — vd reconnect lặp sau khi user đã xử lý).
 */
class NotificationDispatcher
{
    /**
     * @param  array{type:string,level?:string,title:string,body?:?string,action_url?:?string,data?:array<string,mixed>,dedup_key?:?string}  $payload
     * @param  list<int>|null  $userIds  null ⇒ tất cả thành viên tenant
     * @return int số bản ghi đã tạo
     */
    public function dispatch(int $tenantId, array $payload, ?array $userIds = null): int
    {
        $userIds ??= Tenant::query()->whereKey($tenantId)->first()
            ?->users()->pluck('users.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        if ($userIds === []) {
            return 0;
        }

        $dedup = $payload['dedup_key'] ?? null;
        $created = 0;

        foreach (array_values(array_unique($userIds)) as $userId) {
            if ($dedup !== null && $this->hasUnreadDuplicate($tenantId, $userId, $dedup)) {
                continue;
            }

            // tenant_id set tường minh (BelongsToTenant không tự điền khi chạy ngoài request).
            $notification = Notification::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $payload['type'],
                'level' => $payload['level'] ?? Notification::LEVEL_INFO,
                'title' => $payload['title'],
                'body' => $payload['body'] ?? null,
                'action_url' => $payload['action_url'] ?? null,
                'data' => $payload['data'] ?? null,
                'dedup_key' => $dedup,
            ]);
            $created++;

            event(new NotificationCreated((int) $notification->getKey(), $tenantId, $userId));
        }

        return $created;
    }

    /** Còn 1 thông báo CHƯA đọc cùng dedup_key cho user này? (bỏ global scope vì không có tenant context). */
    private function hasUnreadDuplicate(int $tenantId, int $userId, string $dedupKey): bool
    {
        return Notification::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('dedup_key', $dedupKey)
            ->whereNull('read_at')
            ->exists();
    }
}
