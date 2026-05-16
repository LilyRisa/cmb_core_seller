<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\Broadcast;
use CMBcoreSeller\Modules\Notifications\Notifications\BroadcastNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * SPEC 0023 §3.9 — gửi broadcast email tới user của tenant theo audience.
 *
 * Audience kinds:
 *   - 'all_owners': owner mọi tenant (status=active).
 *   - 'all_admins_and_owners': owner + admin mọi tenant.
 *   - 'tenant_ids': owner+admin của các tenant trong list.
 *
 * Mỗi recipient dispatch 1 notification job qua queue `notifications` (BroadcastNotification).
 * Limit 5000 recipients/broadcast (BROADCAST_AUDIENCE_TOO_LARGE).
 */
class BroadcastService
{
    public function send(array $audience, string $subject, string $bodyMarkdown, int $adminUserId): Broadcast
    {
        $userIds = $this->resolveRecipientUserIds($audience);
        if (count($userIds) > Broadcast::MAX_RECIPIENTS) {
            throw new HttpResponseException(response()->json([
                'error' => [
                    'code' => 'BROADCAST_AUDIENCE_TOO_LARGE',
                    'message' => 'Số người nhận vượt giới hạn '.Broadcast::MAX_RECIPIENTS.'. Vui lòng chia nhỏ theo tenant_ids.',
                    'details' => ['recipient_count' => count($userIds)],
                ],
            ], 422));
        }

        $broadcast = DB::transaction(function () use ($audience, $subject, $bodyMarkdown, $adminUserId, $userIds) {
            return Broadcast::query()->create([
                'subject' => $subject,
                'body_markdown' => $bodyMarkdown,
                'audience' => $audience,
                'recipient_count' => count($userIds),
                'sent_count' => 0, 'skipped_count' => 0,
                'created_by_user_id' => $adminUserId,
            ]);
        });

        if ($userIds === []) {
            return $broadcast;
        }

        $users = User::query()->whereIn('id', $userIds)->get();
        $sent = 0;
        foreach ($users as $user) {
            Notification::send($user, new BroadcastNotification($subject, $bodyMarkdown, (int) $broadcast->getKey()));
            $sent++;
        }

        $broadcast->forceFill([
            'sent_count' => $sent,
            'skipped_count' => count($userIds) - $sent,
            'sent_at' => now(),
        ])->save();

        return $broadcast->fresh();
    }

    /**
     * Resolve audience → list user_id (distinct).
     *
     * @return array<int, int>
     */
    public function resolveRecipientUserIds(array $audience): array
    {
        $kind = (string) ($audience['kind'] ?? '');
        $roles = match ($kind) {
            Broadcast::AUDIENCE_ALL_OWNERS => [Role::Owner->value],
            Broadcast::AUDIENCE_ALL_ADMINS_AND_OWNERS => [Role::Owner->value, Role::Admin->value],
            Broadcast::AUDIENCE_TENANT_IDS => [Role::Owner->value, Role::Admin->value],
            default => [],
        };
        if ($roles === []) {
            return [];
        }

        $query = TenantUser::query()->whereIn('role', $roles)
            ->whereHas('tenant', fn ($q) => $q->where('status', '!=', 'suspended'));

        if ($kind === Broadcast::AUDIENCE_TENANT_IDS) {
            $tenantIds = collect($audience['tenant_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->unique()->all();
            if ($tenantIds === []) {
                return [];
            }
            $query->whereIn('tenant_id', $tenantIds);
        }

        return $query->pluck('user_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
    }
}
