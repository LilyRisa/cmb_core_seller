<?php

namespace CMBcoreSeller\Modules\Billing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest as PaymentCheckoutRequest;
use CMBcoreSeller\Integrations\Payments\Exceptions\GatewayNotConfigured;
use CMBcoreSeller\Integrations\Payments\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Modules\Billing\Http\Resources\InvoiceResource;
use CMBcoreSeller\Modules\Billing\Http\Resources\PlanResource;
use CMBcoreSeller\Modules\Billing\Http\Resources\SubscriptionResource;
use CMBcoreSeller\Modules\Billing\Models\BillingProfile;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\BillingService;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Billing\Services\VoucherService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/v1/billing/* — gói thuê bao, hoá đơn, usage, hồ sơ xuất hoá đơn.
 *
 * RBAC:
 *   - `billing.view` (owner/admin/accountant) — đọc.
 *   - `billing.manage` (owner only) — checkout / cancel / update profile.
 *
 * Checkout luồng gateway thật (SePay/VNPay) sẽ wire ở PR2/PR3 — endpoint này v1 trả về
 * thông tin invoice tạo được; FE sẽ điều hướng theo `gateway` đã chọn khi PR2/PR3 wire.
 */
class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billing,
        protected SubscriptionService $subscriptions,
        protected UsageService $usage,
        protected CurrentTenant $tenant,
        protected PaymentRegistry $payments,
        protected VoucherService $vouchers,
    ) {}

    /** GET /billing/plans — catalogue gói (không tenant-scoped). */
    public function plans(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem gói.');
        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get();

        return response()->json(['data' => PlanResource::collection($plans)]);
    }

    /** GET /billing/subscription — subscription đang dùng của tenant + usage. */
    public function subscription(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem gói.');
        $tenantId = (int) $this->tenant->id();
        $sub = $this->subscriptions->currentFor($tenantId)
            ?? $this->subscriptions->ensureTrialFallback($tenantId);

        if ($sub === null) {
            // System chưa seed plans — trả empty state để FE biết hiển thị "chưa cấu hình".
            return response()->json([
                'data' => null,
                'meta' => ['usage' => ['channel_accounts' => ['used' => 0, 'limit' => 0], 'features' => []]],
            ]);
        }
        $sub->loadMissing('plan');

        return response()->json([
            'data' => (new SubscriptionResource($sub))->toArray($request),
            'meta' => [
                'usage' => $this->buildUsage($tenantId, $sub->plan),
            ],
        ]);
    }

    /** GET /billing/usage — hạn mức hiện tại + đã dùng. */
    public function usage(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem hạn mức.');
        $tenantId = (int) $this->tenant->id();
        $sub = $this->subscriptions->currentFor($tenantId)
            ?? $this->subscriptions->ensureTrialFallback($tenantId);

        return response()->json(['data' => $this->buildUsage($tenantId, $sub?->plan)]);
    }

    /** POST /billing/checkout — tạo invoice cho upgrade + tạo CheckoutSession qua gateway đã chọn. */
    public function checkout(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.manage'), 403, 'Chỉ chủ shop được thanh toán.');
        $data = $request->validate([
            'plan_code' => ['required', 'string', 'in:'.implode(',', Plan::CODES)],
            'cycle' => ['required', 'string', 'in:monthly,yearly'],
            'gateway' => ['required', 'string', 'in:sepay,vnpay,momo'],
            'voucher_code' => ['nullable', 'string', 'max:64'],   // SPEC 0023
        ]);

        // Cổng chưa đăng ký (config `INTEGRATIONS_PAYMENTS` chưa bật) ⇒ 422.
        if (! $this->payments->has($data['gateway'])) {
            return response()->json([
                'error' => [
                    'code' => 'GATEWAY_UNAVAILABLE',
                    'message' => $data['gateway'] === 'momo'
                        ? 'MoMo sắp ra mắt — vui lòng chọn SePay hoặc VNPay.'
                        : 'Cổng thanh toán chưa được bật. Vui lòng chọn cổng khác.',
                ],
            ], 422);
        }

        $tenantId = (int) $this->tenant->id();
        $userId = (int) $request->user()->getKey();
        $invoice = $this->billing->createUpgradeInvoice(
            $tenantId,
            $data['plan_code'],
            $data['cycle'],
            $data['voucher_code'] ?? null,
            $userId,
        );

        try {
            $connector = $this->payments->for($data['gateway']);
            $session = $connector->checkout(new PaymentCheckoutRequest(
                tenantId: $tenantId,
                invoiceId: (int) $invoice->getKey(),
                reference: $invoice->code,
                amount: (int) $invoice->total,
                description: "Thanh toán hoá đơn {$invoice->code}",
            ));

            return response()->json([
                'data' => [
                    'invoice' => (new InvoiceResource($invoice->load('lines')))->toArray($request),
                    'gateway' => $data['gateway'],
                    'checkout' => $session->toArray(),
                ],
            ], 201);
        } catch (GatewayNotConfigured $e) {
            return response()->json(['error' => [
                'code' => 'GATEWAY_UNAVAILABLE',
                'message' => $e->getMessage(),
            ]], 422);
        } catch (UnsupportedOperation $e) {
            return response()->json(['error' => [
                'code' => 'GATEWAY_UNAVAILABLE',
                'message' => $e->getMessage(),
            ]], 422);
        }
    }

    /**
     * POST /billing/vouchers/validate — preview discount khi user gõ code ở /settings/plan.
     * Trả `{ valid, discount, total_after, code, name }` hoặc 422 với code envelope.
     * SPEC 0023.
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.manage'), 403, 'Chỉ chủ shop được áp mã.');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'plan_code' => ['required', 'string', 'in:'.implode(',', Plan::CODES)],
            'cycle' => ['required', 'string', 'in:monthly,yearly'],
        ]);

        $voucher = $this->vouchers->validate($data['code'], $data['plan_code']);

        $plan = Plan::query()->where('code', $data['plan_code'])->firstOrFail();
        $totals = $this->billing->computeInvoice($plan, $data['cycle']);
        $discount = $this->vouchers->previewDiscount($voucher, (int) $totals['subtotal']);

        return response()->json(['data' => [
            'valid' => true,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'kind' => $voucher->kind,
            'discount' => $discount,
            'subtotal' => (int) $totals['subtotal'],
            'total_after' => max(0, (int) $totals['total'] - $discount),
        ]]);
    }

    /** GET /billing/invoices — danh sách hoá đơn của tenant. */
    public function invoices(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem hoá đơn.');
        $q = Invoice::query()->orderByDesc('id');
        if ($status = $request->query('status')) {
            $q->whereIn('status', explode(',', (string) $status));
        }
        $page = $q->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => InvoiceResource::collection($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** GET /billing/invoices/{id} */
    public function invoiceShow(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem hoá đơn.');
        $invoice = Invoice::query()->with('lines')->findOrFail($id);

        return response()->json(['data' => (new InvoiceResource($invoice))->toArray($request)]);
    }

    /** GET /billing/invoices/{id}/payment-status — UX polling cho SePay. */
    public function invoicePaymentStatus(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem.');
        $invoice = Invoice::query()->findOrFail($id);

        return response()->json(['data' => [
            'id' => $invoice->getKey(),
            'status' => $invoice->status,
            'paid_at' => $invoice->paid_at?->toIso8601String(),
        ]]);
    }

    /** POST /billing/subscription/cancel — set cancel_at = current_period_end. */
    public function cancel(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.manage'), 403, 'Chỉ chủ shop được huỷ gói.');
        $tenantId = (int) $this->tenant->id();
        $sub = $this->subscriptions->currentFor($tenantId);
        if ($sub === null) {
            return response()->json(['error' => ['code' => 'NO_ACTIVE_SUBSCRIPTION', 'message' => 'Không có gói nào đang dùng.']], 422);
        }
        if ($sub->status === Subscription::STATUS_TRIALING) {
            return response()->json(['error' => ['code' => 'CANNOT_CANCEL_TRIAL', 'message' => 'Gói dùng thử sẽ tự hết hạn — không cần huỷ.']], 422);
        }
        $sub->forceFill([
            'cancel_at' => $sub->current_period_end,
            'cancelled_at' => now(),
        ])->save();

        return response()->json(['data' => (new SubscriptionResource($sub->fresh('plan')))->toArray($request)]);
    }

    /** GET /billing/billing-profile */
    public function profileShow(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403, 'Bạn không có quyền xem hồ sơ.');
        $tenantId = (int) $this->tenant->id();
        $profile = BillingProfile::query()->firstOrCreate(['tenant_id' => $tenantId]);

        return response()->json(['data' => $this->serializeProfile($profile)]);
    }

    /** PATCH /billing/billing-profile */
    public function profileUpdate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.manage'), 403, 'Chỉ chủ shop được sửa hồ sơ.');
        $data = $request->validate([
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'billing_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);
        $tenantId = (int) $this->tenant->id();
        $profile = BillingProfile::query()->firstOrCreate(['tenant_id' => $tenantId]);
        $profile->forceFill($data)->save();

        return response()->json(['data' => $this->serializeProfile($profile->fresh())]);
    }

    /**
     * @return array<string, array{used:int,limit:int}|bool>
     */
    protected function buildUsage(int $tenantId, ?Plan $plan): array
    {
        return [
            'channel_accounts' => [
                'used' => $this->usage->channelAccounts($tenantId),
                'limit' => $plan?->maxChannelAccounts() ?? 0,
            ],
            'features' => $plan !== null ? ($plan->features ?? []) : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeProfile(BillingProfile $profile): array
    {
        return [
            'id' => $profile->getKey(),
            'company_name' => $profile->company_name,
            'tax_code' => $profile->tax_code,
            'billing_address' => $profile->billing_address,
            'contact_email' => $profile->contact_email,
            'contact_phone' => $profile->contact_phone,
        ];
    }
}
