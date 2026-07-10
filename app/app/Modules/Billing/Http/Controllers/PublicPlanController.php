<?php

namespace CMBcoreSeller\Modules\Billing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Http\JsonResponse;

/** Bảng giá công khai cho trang marketing (không auth). Chỉ catalog Plan, không lộ dữ liệu tenant. SPEC 2026-06-26. */
class PublicPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()->where('is_active', true)
            ->whereIn('code', Plan::CODES)
            ->orderBy('sort_order')->get()
            ->map(fn (Plan $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'description' => $p->description,
                'price_monthly' => (int) $p->price_monthly,
                'price_yearly' => (int) $p->price_yearly,
                'currency' => $p->currency,
                'trial_days' => (int) $p->trial_days,
                'features' => $p->features ?? [],
                'limits' => $p->limits ?? [],
            ])->all();

        return response()->json(['data' => $plans]);
    }
}
