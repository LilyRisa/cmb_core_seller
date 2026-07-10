<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin config cho "Chế độ trải nghiệm Pro" (SPEC — pro trial experience mode).
 * Đọc/ghi 4 key system_setting `billing.pro_trial.*` (catalog-registered — xem
 * `SystemSettingsCatalog`), nguồn duy nhất cho `ProTrialSettings`.
 */
class AdminProTrialController extends Controller
{
    public function __construct(protected SystemSettingService $settings) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => [
            'enabled' => ProTrialSettings::enabled(),
            'duration_days' => ProTrialSettings::durationDays(),
            'window_start' => optional(ProTrialSettings::windowStart())->toDateString(),
            'window_end' => optional(ProTrialSettings::windowEnd())->toDateString(),
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'window_start' => ['nullable', 'date_format:Y-m-d'],
            'window_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:window_start'],
        ]);

        $adminId = (int) $request->user()->getKey();
        $this->settings->set('billing.pro_trial.enabled', $data['enabled'], $adminId);
        $this->settings->set('billing.pro_trial.duration_days', $data['duration_days'], $adminId);
        $this->settings->set('billing.pro_trial.window_start', $data['window_start'] ?? '', $adminId);
        $this->settings->set('billing.pro_trial.window_end', $data['window_end'] ?? '', $adminId);

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => $adminId,
            'action' => 'admin.pro_trial.settings',
            'auditable_type' => 'system_setting',
            'auditable_id' => 0,
            'changes' => $data,
            'ip' => $request->ip(),
        ]);

        return $this->show();
    }
}
