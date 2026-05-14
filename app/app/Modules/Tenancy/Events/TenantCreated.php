<?php

namespace CMBcoreSeller\Modules\Tenancy\Events;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Phát ra khi một `Tenant` mới được tạo (qua AuthController::register, hoặc bất cứ luồng
 * tạo tenant tương lai). Module Billing nghe để khởi động trial 14 ngày (SPEC 0018 §3.1).
 *
 * Đây là event cross-module — module Tenancy là module nền (xem
 * docs/01-architecture/modules.md §3), nên việc nó phát event được tiêu thụ bởi Billing là hợp lệ:
 * Billing không import Tenancy internals, Tenancy không biết Billing tồn tại.
 */
class TenantCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Tenant $tenant) {}
}
