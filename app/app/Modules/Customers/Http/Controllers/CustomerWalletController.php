<?php

namespace CMBcoreSeller\Modules\Customers\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Http\Resources\CustomerWalletTransactionResource;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Ví trả trước của khách — nạp tiền (yêu cầu số tiền + hóa đơn) + lịch sử giao dịch. SPEC 2026-06-26. */
class CustomerWalletController extends Controller
{
    public function topup(Request $request, CurrentTenant $tenant, CustomerWallet $wallet, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.post'), 403, 'Bạn không có quyền nạp tiền vào ví khách.');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'payment_method' => ['required', 'in:cash,bank,ewallet'],
            'invoice_ref' => ['required', 'string', 'max:120'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $customer = Customer::query()->findOrFail($id);
        try {
            $tx = $wallet->topup((int) $tenant->id(), (int) $customer->getKey(), (int) $data['amount'], $data['payment_method'], $data['invoice_ref'], $data['note'] ?? null, $request->user()->getKey());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        return response()->json(['data' => [
            'balance' => (int) $customer->refresh()->prepaid_balance,
            'transaction' => new CustomerWalletTransactionResource($tx),
        ]]);
    }

    public function transactions(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('customers.view'), 403, 'Bạn không có quyền.');
        Customer::query()->findOrFail($id); // tenant-scoped guard
        $page = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
            ->where('customer_id', $id)->orderByDesc('id')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => CustomerWalletTransactionResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }
}
