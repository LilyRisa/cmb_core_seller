<?php

namespace CMBcoreSeller\Http\Controllers;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Generic image upload — POST /api/v1/media/image (multipart `image`, optional `folder`).
 *
 * Used when there is no addressable owner row to attach the image to yet, e.g. the
 * "quick-add product" line of a manual order (an ad-hoc order_item that isn't linked to a
 * SKU — see ManualOrderService / docs/03-domain/manual-orders-and-finance.md). The caller
 * gets back the stored object key + public URL and persists the URL on the owning record.
 * (SKU images keep their dedicated POST /skus/{id}/image which also cleans up the old object.)
 */
class MediaController extends Controller
{
    public function upload(Request $request, MediaUploader $uploader, CurrentTenant $tenant): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->can('orders.create') || $user?->can('products.manage'), 403, 'Bạn không có quyền tải ảnh.');
        $mimes = implode(',', (array) config('media.images.mimes', ['jpg', 'jpeg', 'png', 'webp']));
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:'.$mimes, 'max:'.(int) config('media.images.max_kb', 5120)],
            'folder' => ['sometimes', 'string', 'max:40'],
        ]);
        $folder = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($data['folder'] ?? 'misc'))) ?: 'misc';
        $stored = $uploader->storeImage($request->file('image'), (int) $tenant->id(), $folder);

        return response()->json(['data' => $stored]);
    }
}
