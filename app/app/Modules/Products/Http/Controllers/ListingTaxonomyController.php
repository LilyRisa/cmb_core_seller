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

    public function searchCategories(Request $r, string $provider): JsonResponse
    {
        return response()->json([
            'data' => $this->svc->searchCategories(
                (int) $r->query('channel_account_id'),
                $provider,
                (string) $r->query('q', ''),
            ),
        ]);
    }

    public function categoryPath(Request $r, string $provider): JsonResponse
    {
        return response()->json([
            'data' => $this->svc->categoryPath(
                (int) $r->query('channel_account_id'),
                $provider,
                (string) $r->query('category_id'),
            ),
        ]);
    }

    public function listingLimits(Request $r, string $provider): JsonResponse
    {
        $limits = (array) config("integrations.listing_limits.$provider", []);

        return response()->json([
            'data' => [
                'max_images' => (int) ($limits['max_images'] ?? 9),
                'max_videos' => (int) ($limits['max_videos'] ?? 1),
                'title_min_length' => (int) ($limits['title_min_length'] ?? 0),
                'title_max_length' => (int) ($limits['title_max_length'] ?? 255),
            ],
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
        $q = trim((string) $r->query('q', ''));

        return response()->json([
            'data' => $this->svc->brands(
                (int) $r->query('channel_account_id'),
                $provider,
                (string) $r->query('category_id'),
                $q !== '' ? $q : null,
                // Mặc định (chưa gõ) hiện danh sách ngắn; khi tìm thì trả nhiều kết quả hơn.
                $q !== '' ? 50 : 10,
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
