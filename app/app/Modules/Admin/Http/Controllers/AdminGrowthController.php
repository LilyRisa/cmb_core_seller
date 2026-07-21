<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Admin\Services\AdminGrowthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

/** /api/v1/admin/growth/* — báo cáo tăng trưởng theo nguồn UTM (SPEC 2026-07-22). */
class AdminGrowthController extends Controller
{
    public function __construct(protected AdminGrowthService $service) {}

    /** GET /api/v1/admin/growth/attribution */
    public function attribution(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'group_by' => ['nullable', 'string', 'in:utm_source,utm_campaign,utm_medium'],
        ]);

        $rows = $this->service->attribution(
            $data['group_by'] ?? 'utm_source',
            isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null,
            isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null,
        );

        // JSON_PRESERVE_ZERO_FRACTION: conversion_rate là float (vd. 100.0%) — mặc định
        // json_encode() làm tròn số nguyên "100.0" thành "100", khiến FE/test nhận int thay vì float.
        return response()->json(['data' => $rows], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
