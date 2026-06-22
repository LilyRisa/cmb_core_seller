<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Modules\Products\Models\ProductPushBatch;
use CMBcoreSeller\Modules\Products\Models\ProductPushJob;
use CMBcoreSeller\Modules\Products\Services\ListingPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Thin HTTP surface for publishing listing drafts and reading push progress.
 *
 * FormRequest-light validation → {@see ListingPushService} → JSON envelope.
 */
class ListingPushController extends Controller
{
    public function push(int $id, Request $r): JsonResponse
    {
        $batch = app(ListingPushService::class)->push([$id], (int) $r->user()->id);

        return response()->json(['data' => ['batch_id' => $batch->id]]);
    }

    public function bulkPush(Request $r): JsonResponse
    {
        $data = $r->validate([
            'listing_ids' => ['required', 'array', 'min:1'],
            'listing_ids.*' => ['integer'],
        ]);

        $batch = app(ListingPushService::class)->push(
            array_map('intval', $data['listing_ids']),
            (int) $r->user()->id,
        );

        return response()->json(['data' => ['batch_id' => $batch->id]]);
    }

    public function batch(int $id): JsonResponse
    {
        $batch = ProductPushBatch::findOrFail($id);

        $jobs = ProductPushJob::where('product_push_batch_id', $batch->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ProductPushJob $j) => [
                'listing_id' => $j->listing_draft_id,
                'status' => $j->status,
                'step_label' => $j->step_label,
                'progress' => $j->progress,
                // error lưu dạng mảng (['message' => ...]); trả CHUỖI message đúng hợp
                // đồng API (FE render thẳng) — tránh "Objects are not valid as a React child".
                'error' => is_array($j->error) ? ($j->error['message'] ?? null) : $j->error,
            ])->all();

        return response()->json(['data' => [
            'id' => $batch->id,
            'total' => $batch->total,
            'succeeded' => $batch->succeeded,
            'failed' => $batch->failed,
            'status' => $batch->status,
            'jobs' => $jobs,
        ]]);
    }
}
