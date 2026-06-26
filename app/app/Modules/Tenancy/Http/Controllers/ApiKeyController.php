<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Quản lý API key bên thứ 3 — CHỈ chủ gian hàng (owner). Key = Sanctum PAT gắn `tenant_id`,
 * `kind='api_key'`, abilities `['*']` (thao tác như web), có thể đặt hạn. Token plaintext trả 1 lần.
 * SPEC 2026-06-26.
 */
class ApiKeyController extends Controller
{
    public function index(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($tenant->isOwner(), 403, 'Chỉ chủ gian hàng được quản lý API key.');

        $keys = PersonalAccessToken::query()
            ->where('tenant_id', $tenant->id())->where('kind', 'api_key')
            ->orderByDesc('id')->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_four' => $t->getAttribute('last_four'),
                'abilities' => $t->abilities,
                'expires_at' => $t->expires_at?->toIso8601String(),
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ])->all();

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($tenant->isOwner(), 403, 'Chỉ chủ gian hàng được tạo API key.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ]);

        $owner = $request->user();
        $expiresAt = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
        $new = $owner->createToken($data['name'], ['*'], $expiresAt);
        // Gắn tenant + kind + 4 ký tự cuối (gợi nhớ) cho token vừa tạo.
        $new->accessToken->forceFill([
            'tenant_id' => $tenant->id(),
            'kind' => 'api_key',
            'last_four' => substr($new->plainTextToken, -4),
        ])->save();

        return response()->json(['data' => [
            'id' => $new->accessToken->id,
            'name' => $data['name'],
            'token' => $new->plainTextToken,   // CHỈ TRẢ 1 LẦN — không lưu plaintext.
            'expires_at' => $expiresAt?->toIso8601String(),
        ]], 201);
    }

    public function destroy(Request $request, CurrentTenant $tenant, int $id): JsonResponse
    {
        abort_unless($tenant->isOwner(), 403, 'Chỉ chủ gian hàng được xóa API key.');
        $deleted = PersonalAccessToken::query()
            ->where('id', $id)->where('tenant_id', $tenant->id())->where('kind', 'api_key')
            ->delete();
        abort_if($deleted === 0, 404, 'Không tìm thấy API key.');

        return response()->json(['data' => ['deleted' => true]]);
    }
}
