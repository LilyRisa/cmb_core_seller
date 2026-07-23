<?php

namespace CMBcoreSeller\Modules\Notifications\Contracts;

/**
 * Đầu mối tạo/fan-out thông báo in-app cho các module khác (Plan C, 2026-07-23) — theo luật
 * module: chỉ phụ thuộc Contract, không chạm Services/ nội bộ. Cài đặt:
 * {@see \CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher}.
 */
interface NotificationDispatcherContract
{
    /**
     * @param  array{type:string,level?:string,title:string,body?:?string,action_url?:?string,data?:array<string,mixed>,dedup_key?:?string}  $payload
     * @param  list<int>|null  $userIds  null ⇒ tất cả thành viên tenant
     * @return int số bản ghi đã tạo
     */
    public function dispatch(int $tenantId, array $payload, ?array $userIds = null): int;

    /** Tenant này đã từng nhận thông báo `type`+`dedupKey` chưa? (bất kể đã đọc hay chưa — dùng để kiểm tra quyền xem, không phải dedup gửi). */
    public function hasReceived(int $tenantId, string $type, string $dedupKey): bool;
}
