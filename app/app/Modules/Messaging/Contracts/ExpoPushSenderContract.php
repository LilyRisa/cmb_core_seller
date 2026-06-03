<?php

namespace CMBcoreSeller\Modules\Messaging\Contracts;

use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;

/**
 * Gửi Expo Push notification tới 1 thiết bị mobile — SPEC 0029.
 *
 * Implementation phải:
 *  - POST tới Expo Push HTTP API v2 (batch endpoint).
 *  - Trả `true` khi gửi OK; `false` khi lỗi không-nghiêm-trọng.
 *  - Xoá row MobileDevice + trả `false` khi Expo báo `DeviceNotRegistered`.
 */
interface ExpoPushSenderContract
{
    /** Đã cấu hình đủ để gửi (config gate)? */
    public function isConfigured(): bool;

    /**
     * @param  array<string, mixed>  $payload  vd ['title'=>..,'body'=>..,'data'=>[..]]
     */
    public function send(MobileDevice $device, array $payload): bool;
}
