<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Products\Services\ListingTaxonomyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thin proxy controller exposing a marketplace's listing taxonomy
 * (categories / attributes / brands) for a connected shop. Delegates all
 * resolution + caching to {@see ListingTaxonomyService}.
 */
final class ListingTaxonomyController extends Controller
{
    public function __construct(private ListingTaxonomyService $svc) {}

    public function categories(Request $r, string $provider): JsonResponse
    {
        return response()->json([
            'data' => $this->svc->categories(
                (int) $r->query('channel_account_id'),
                $provider,
                $r->query('parent_id'),
            ),
        ]);
    }

    public function attributes(Request $r, string $provider): JsonResponse
    {
        return response()->json([
            'data' => $this->svc->attributes(
                (int) $r->query('channel_account_id'),
                $provider,
                (string) $r->query('category_id'),
            ),
        ]);
    }

    public function brands(Request $r, string $provider): JsonResponse
    {
        return response()->json([
            'data' => $this->svc->brands(
                (int) $r->query('channel_account_id'),
                $provider,
                (string) $r->query('category_id'),
            ),
        ]);
    }

    public function shippingOptions(Request $r, string $provider): JsonResponse
    {
        return response()->json([
            'data' => $this->svc->shippingOptions((int) $r->query('channel_account_id'), $provider),
        ]);
    }
}
