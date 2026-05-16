<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Billing\Models\VoucherRedemption;
use CMBcoreSeller\Modules\Billing\Services\VoucherService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * /api/v1/admin/vouchers — CRUD + grant voucher. SPEC 0023.
 */
class AdminVoucherController extends Controller
{
    public function __construct(protected VoucherService $vouchers) {}

    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $kind = (string) $request->query('kind', '');
        $active = $request->boolean('active', false);
        $expired = $request->boolean('expired', false);
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $query = Voucher::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%");
            });
        }
        if (in_array($kind, Voucher::KINDS, true)) {
            $query->where('kind', $kind);
        }
        if ($active) {
            $query->where('is_active', true);
        }
        if ($expired) {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (Voucher $v) => $this->resource($v))->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $voucher = Voucher::query()->findOrFail($id);
        $redemptions = VoucherRedemption::query()->where('voucher_id', $voucher->getKey())
            ->orderByDesc('id')->limit(50)->get();

        return response()->json(['data' => array_merge($this->resource($voucher), [
            'recent_redemptions' => $redemptions->map(fn (VoucherRedemption $r) => [
                'id' => $r->id,
                'tenant_id' => $r->tenant_id,
                'user_id' => $r->user_id,
                'invoice_id' => $r->invoice_id,
                'subscription_id' => $r->subscription_id,
                'discount_amount' => $r->discount_amount,
                'granted_days' => $r->granted_days,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
        ])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:vouchers,code', 'regex:/^[A-Z0-9_-]+$/i'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'kind' => ['required', Rule::in(Voucher::KINDS)],
            'value' => ['required', 'integer'],
            'valid_plans' => ['nullable', 'array'],
            'valid_plans.*' => ['string', Rule::in(Plan::CODES)],
            'max_redemptions' => ['nullable', 'integer', 'min:-1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);

        // value sanity check theo kind
        $this->validateValueForKind($data['kind'], $data['value']);

        $voucher = Voucher::query()->create(array_merge($data, [
            'code' => strtoupper($data['code']),
            'is_active' => $data['is_active'] ?? true,
            'max_redemptions' => $data['max_redemptions'] ?? -1,
            'created_by_user_id' => (int) $request->user()->getKey(),
        ]));

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => (int) $request->user()->getKey(),
            'action' => 'admin.voucher.create',
            'auditable_type' => $voucher->getMorphClass(),
            'auditable_id' => $voucher->getKey(),
            'changes' => ['code' => $voucher->code, 'kind' => $voucher->kind, 'value' => $voucher->value],
            'ip' => $request->ip(),
        ]);

        return response()->json(['data' => $this->resource($voucher)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $voucher = Voucher::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'valid_plans' => ['nullable', 'array'],
            'valid_plans.*' => ['string', Rule::in(Plan::CODES)],
            'max_redemptions' => ['nullable', 'integer', 'min:-1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);

        $before = $voucher->only(['name', 'description', 'is_active', 'max_redemptions', 'expires_at']);
        $voucher->forceFill($data)->save();

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => (int) $request->user()->getKey(),
            'action' => 'admin.voucher.update',
            'auditable_type' => $voucher->getMorphClass(),
            'auditable_id' => $voucher->getKey(),
            'changes' => ['before' => $before, 'after' => $voucher->fresh()->only(array_keys($before))],
            'ip' => $request->ip(),
        ]);

        return response()->json(['data' => $this->resource($voucher->fresh())]);
    }

    /** Soft delete = set is_active=false (giữ history voucher_redemptions). */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $voucher = Voucher::query()->findOrFail($id);
        $voucher->forceFill(['is_active' => false])->save();

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => (int) $request->user()->getKey(),
            'action' => 'admin.voucher.disable',
            'auditable_type' => $voucher->getMorphClass(),
            'auditable_id' => $voucher->getKey(),
            'changes' => ['code' => $voucher->code],
            'ip' => $request->ip(),
        ]);

        return response()->json(['data' => $this->resource($voucher->fresh())]);
    }

    public function grant(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $voucher = Voucher::query()->findOrFail($id);
        Tenant::query()->findOrFail($data['tenant_id']);

        $result = $this->vouchers->grant(
            $voucher,
            (int) $data['tenant_id'],
            (int) $request->user()->getKey(),
            $data['reason'],
        );

        AuditLog::query()->create([
            'tenant_id' => (int) $data['tenant_id'],
            'user_id' => (int) $request->user()->getKey(),
            'action' => 'admin.voucher.grant',
            'auditable_type' => $voucher->getMorphClass(),
            'auditable_id' => $voucher->getKey(),
            'changes' => array_merge(['reason' => $data['reason']], $result['applied']),
            'ip' => $request->ip(),
        ]);

        return response()->json(['data' => [
            'voucher' => $this->resource($voucher->fresh()),
            'applied' => $result['applied'],
            'redemption_id' => $result['redemption']->getKey(),
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Voucher $v): array
    {
        return [
            'id' => $v->id, 'code' => $v->code, 'name' => $v->name, 'description' => $v->description,
            'kind' => $v->kind, 'value' => $v->value,
            'valid_plans' => $v->valid_plans ?? [],
            'max_redemptions' => $v->max_redemptions, 'redemption_count' => $v->redemption_count,
            'starts_at' => $v->starts_at?->toIso8601String(),
            'expires_at' => $v->expires_at?->toIso8601String(),
            'is_active' => $v->is_active,
            'is_in_window' => $v->isInWindow(),
            'is_exhausted' => $v->isExhausted(),
            'created_at' => $v->created_at?->toIso8601String(),
        ];
    }

    private function validateValueForKind(string $kind, int $value): void
    {
        match ($kind) {
            Voucher::KIND_PERCENT => $value < 1 || $value > 100
                ? abort(response()->json(['error' => ['code' => 'INVALID_VALUE', 'message' => 'Phần trăm phải 1-100.']], 422))
                : null,
            Voucher::KIND_FIXED => $value < 1
                ? abort(response()->json(['error' => ['code' => 'INVALID_VALUE', 'message' => 'Số tiền giảm phải > 0.']], 422))
                : null,
            Voucher::KIND_FREE_DAYS => $value < 1 || $value > 365
                ? abort(response()->json(['error' => ['code' => 'INVALID_VALUE', 'message' => 'Số ngày tặng phải 1-365.']], 422))
                : null,
            Voucher::KIND_PLAN_UPGRADE => Plan::query()->where('id', $value)->where('is_active', true)->exists()
                ? null
                : abort(response()->json(['error' => ['code' => 'INVALID_VALUE', 'message' => 'Plan ID không tồn tại.']], 422)),
            default => null,
        };
    }
}
