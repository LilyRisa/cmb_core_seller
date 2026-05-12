<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\CarrierAccountResource;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** /api/v1/carrier-accounts + /api/v1/carriers — tenant ĐVVC credentials. See SPEC 0006 §6. */
class CarrierAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền xem ĐVVC.');

        return response()->json(['data' => CarrierAccountResource::collection(CarrierAccount::query()->orderByDesc('is_default')->orderBy('id')->get())]);
    }

    /** GET /api/v1/carriers — carrier codes available in this deployment. */
    public function carriers(Request $request, CarrierRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.view'), 403, 'Bạn không có quyền.');
        $out = [];
        foreach ($registry->carriers() as $code) {
            $c = $registry->for($code);
            $out[] = [
                'code' => $code,
                'name' => $c->displayName(),
                'capabilities' => $c instanceof AbstractCarrierConnector ? $c->capabilities() : [],
                'needs_credentials' => $code !== 'manual',
            ];
        }

        return response()->json(['data' => $out]);
    }

    public function store(Request $request, CarrierRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_service' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_default' => ['sometimes', 'boolean'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);
        abort_unless($registry->has($data['carrier']), 422, 'ĐVVC không được hỗ trợ.');
        $account = DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                CarrierAccount::query()->update(['is_default' => false]);
            }

            return CarrierAccount::query()->create([   // tenant_id auto-filled by BelongsToTenant
                'carrier' => $data['carrier'], 'name' => $data['name'],
                'credentials' => $data['credentials'] ?? [], 'default_service' => $data['default_service'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false), 'is_active' => true, 'meta' => $data['meta'] ?? null,
            ]);
        });

        return response()->json(['data' => new CarrierAccountResource($account)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        $account = CarrierAccount::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_service' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);
        DB::transaction(function () use ($account, $data) {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                CarrierAccount::query()->where('id', '!=', $account->getKey())->update(['is_default' => false]);
            }
            $account->forceFill($data)->save();
        });

        return response()->json(['data' => new CarrierAccountResource($account->refresh())]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.carriers'), 403, 'Bạn không có quyền cấu hình ĐVVC.');
        CarrierAccount::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
