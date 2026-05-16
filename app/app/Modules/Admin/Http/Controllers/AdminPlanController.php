<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * /api/v1/admin/plans — sửa catalog gói thuê bao (SPEC 0023 §3.4).
 *
 * Không cho tạo plan mới / xoá plan (v1) — chỉ sửa plan có sẵn. `code` và `currency`
 * immutable để tránh phá subscription/invoice đã tồn tại.
 */
class AdminPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()->orderBy('sort_order')->get();

        return response()->json(['data' => $plans->map(fn (Plan $p) => $this->resource($p))->all()]);
    }

    public function show(int $id): JsonResponse
    {
        $plan = Plan::query()->findOrFail($id);

        return response()->json(['data' => $this->resource($plan)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::query()->findOrFail($id);

        if ($request->has('code') && $request->input('code') !== $plan->code) {
            $this->reject('PLAN_IMMUTABLE_FIELD', '`code` của plan không được đổi.');
        }
        if ($request->has('currency') && $request->input('currency') !== $plan->currency) {
            $this->reject('PLAN_IMMUTABLE_FIELD', '`currency` cố định là VND.');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'price_monthly' => ['sometimes', 'integer', 'min:0'],
            'price_yearly' => ['sometimes', 'integer', 'min:0'],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'limits' => ['sometimes', 'array'],
            'limits.max_channel_accounts' => ['nullable', 'integer', 'min:-1'],
            'features' => ['sometimes', 'array'],
        ]);

        $before = $plan->only(['name', 'is_active', 'price_monthly', 'price_yearly', 'trial_days', 'limits', 'features']);
        $plan->forceFill($data)->save();

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => (int) $request->user()->getKey(),
            'action' => 'admin.plan.update',
            'auditable_type' => $plan->getMorphClass(),
            'auditable_id' => $plan->getKey(),
            'changes' => ['before' => $before, 'after' => $plan->fresh()->only(array_keys($before))],
            'ip' => $request->ip(),
        ]);

        return response()->json(['data' => $this->resource($plan->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Plan $p): array
    {
        return [
            'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'description' => $p->description,
            'is_active' => $p->is_active, 'sort_order' => $p->sort_order,
            'price_monthly' => $p->price_monthly, 'price_yearly' => $p->price_yearly,
            'currency' => $p->currency, 'trial_days' => $p->trial_days,
            'limits' => $p->limits ?? [], 'features' => $p->features ?? [],
        ];
    }

    /**
     * @return never
     */
    private function reject(string $code, string $message): void
    {
        throw new HttpResponseException(response()->json([
            'error' => ['code' => $code, 'message' => $message],
        ], 422));
    }
}
