<?php

namespace CMBcoreSeller\Modules\Marketing\Events;

use CMBcoreSeller\Modules\Marketing\Services\AdMonitorEvaluator;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát bởi {@see AdMonitorEvaluator} sau khi
 * AdMonitor tự động tác động lên 1 entity: tạm dừng (`pause`) hoặc tăng ngân sách
 * (`increase`) (SPEC 0036). Email cho Owner/Admin vẫn giữ như cũ; event này để
 * Notifications tạo thông báo in-app cho mọi thành viên.
 *
 * Mang scalar — tenant_id + actionId tường minh.
 */
class AdMonitorActionTaken
{
    use Dispatchable;

    public function __construct(
        public int $tenantId,
        public int $actionId,
        public string $name,
        public string $type,   // pause|increase
    ) {}
}
