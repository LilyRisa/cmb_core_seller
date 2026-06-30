<?php

namespace CMBcoreSeller\Modules\EInvoice\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Modules\EInvoice\Http\Resources\EInvoiceAccountResource;
use CMBcoreSeller\Modules\EInvoice\Models\EInvoiceAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EInvoiceAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.view'), 403, 'Bạn không có quyền xem HĐĐT.');

        return response()->json(['data' => EInvoiceAccountResource::collection(
            EInvoiceAccount::query()->orderByDesc('is_default')->orderBy('id')->get()
        )]);
    }

    public function store(Request $request, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền cấu hình HĐĐT.');
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_mode' => ['sometimes', 'in:hsm,mtt'],
            'templates' => ['sometimes', 'nullable', 'array'],
            'seller_info' => ['sometimes', 'nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
        abort_unless($registry->has($data['provider']), 422, 'Nhà cung cấp HĐĐT không được hỗ trợ.');

        $account = DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                EInvoiceAccount::query()->update(['is_default' => false]);
            }

            return EInvoiceAccount::query()->create([
                'provider' => $data['provider'], 'name' => $data['name'],
                'credentials' => $data['credentials'] ?? [],
                'default_mode' => $data['default_mode'] ?? 'hsm',
                'templates' => $data['templates'] ?? null, 'seller_info' => $data['seller_info'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false), 'is_active' => true,
            ]);
        });

        $this->runVerify($registry, $account);

        return response()->json(['data' => new EInvoiceAccountResource($account->refresh())], 201);
    }

    public function update(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'credentials' => ['sometimes', 'nullable', 'array'],
            'default_mode' => ['sometimes', 'in:hsm,mtt'],
            'templates' => ['sometimes', 'nullable', 'array'],
            'seller_info' => ['sometimes', 'nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $credChanged = false;
        DB::transaction(function () use ($account, &$data, &$credChanged) {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                EInvoiceAccount::query()->where('id', '!=', $account->getKey())->update(['is_default' => false]);
            }
            if (array_key_exists('credentials', $data)) {
                $incoming = array_filter((array) ($data['credentials'] ?? []), fn ($v) => $v !== null && $v !== '');
                $credChanged = $incoming !== [];
                $data['credentials'] = array_merge((array) ($account->credentials ?? []), $incoming);
            }
            $account->forceFill($data)->save();
        });

        if ($credChanged) {
            $this->runVerify($registry, $account->refresh());
        }

        return response()->json(['data' => new EInvoiceAccountResource($account->refresh())]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        EInvoiceAccount::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function verify(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        $result = $this->runVerify($registry, $account);

        return response()->json(['data' => [
            'ok' => $result['ok'], 'message' => $result['message'],
            'error_code' => $result['error_code'] ?? null, 'expires_at' => $result['expires_at'] ?? null,
            'verified_at' => now()->toIso8601String(),
            'account' => new EInvoiceAccountResource($account->refresh()),
        ]]);
    }

    public function companyInfo(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        abort_unless($registry->has($account->provider), 422, 'Nhà cung cấp chưa đăng ký.');
        $info = $registry->for($account->provider)->getCompanyInfo($account->toConnectorArray());
        // Cache IsInvoiceWithCode để mapper Phần B chọn path /code đúng.
        $account->forceFill(['is_invoice_with_code' => $info->isInvoiceWithCode])->save();

        return response()->json(['data' => $info->toArray()]);
    }

    public function templates(Request $request, int $id, EInvoiceRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('einvoice.config'), 403, 'Bạn không có quyền.');
        $account = EInvoiceAccount::query()->findOrFail($id);
        abort_unless($registry->has($account->provider), 422, 'Nhà cung cấp chưa đăng ký.');
        $year = (int) ($request->query('year') ?: now()->year);
        $list = $registry->for($account->provider)->templates($account->toConnectorArray(), $year);

        return response()->json(['data' => array_map(fn ($t) => $t->toArray(), $list)]);
    }

    /** @return array{ok:bool,message:string,error_code?:?string,expires_at?:?string} */
    private function runVerify(EInvoiceRegistry $registry, EInvoiceAccount $account): array
    {
        if (! $registry->has($account->provider)) {
            return ['ok' => false, 'message' => 'Nhà cung cấp HĐĐT chưa được đăng ký.', 'error_code' => 'unregistered', 'expires_at' => null];
        }
        try {
            $result = $registry->for($account->provider)->verifyCredentials($account->toConnectorArray());
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Lỗi kiểm tra: '.$e->getMessage(), 'error_code' => 'network', 'expires_at' => null];
        }
        $meta = (array) ($account->meta ?? []);
        $meta['last_verified_at'] = now()->toIso8601String();
        $meta['last_verify_ok'] = (bool) $result['ok'];
        $meta['last_verify_error'] = $result['ok'] ? null : $result['message'];
        $account->forceFill(['meta' => $meta])->save();

        return $result;
    }
}
