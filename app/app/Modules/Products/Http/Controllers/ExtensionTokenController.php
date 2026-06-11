<?php

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Cấp / thu hồi Sanctum personal access token cho Chrome Extension "copy sản phẩm".
 *
 * Token chỉ mang DUY NHẤT ability `copy-product:push` (không phải `*`), nên chỉ
 * gọi được route đã gate `abilities:copy-product:push` (tạo sản phẩm), không đụng
 * được các endpoint nghiệp vụ khác. Token KHÔNG hết hạn: gọi {@see createToken}
 * không truyền `expiresAt` ⇒ phụ thuộc `config('sanctum.expiration')` = null.
 */
class ExtensionTokenController extends Controller
{
    /** Mint a non-expiring token scoped to `copy-product:push`. */
    public function store(Request $r): JsonResponse
    {
        $name = trim((string) $r->input('name')) ?: 'Chrome Extension';

        $token = $r->user()->createToken($name, ['copy-product:push']);

        return response()->json(['data' => [
            'id' => $token->accessToken->id,
            'token' => $token->plainTextToken,
        ]]);
    }

    /** Thu hồi 1 extension token theo id — chỉ token của chính user. */
    public function destroy(Request $r, int $id): Response
    {
        $r->user()->tokens()->where('id', $id)->delete();

        return response()->noContent();
    }
}
