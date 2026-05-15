<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Http\Resources\CustomerReceiptResource;
use CMBcoreSeller\Modules\Accounting\Models\CustomerReceipt;
use CMBcoreSeller\Modules\Accounting\Services\ArService;
use CMBcoreSeller\Modules\Accounting\Services\CustomerReceiptService;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ArController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly ArService $ar,
        private readonly CustomerReceiptService $receipts,
    ) {}

    /** GET /accounting/ar/aging — danh sách công nợ phải thu kèm aging buckets. */
    public function aging(Request $request): JsonResponse
    {
        $tenantId = (int) $this->tenant->id();
        $rows = $this->ar->agingByCustomer($tenantId);
        $customerIds = array_column($rows, 'customer_id');
        $customers = $customerIds
            ? Customer::query()->whereIn('id', $customerIds)->get()->keyBy('id')
            : collect();
        $data = array_map(function ($r) use ($customers) {
            $c = $customers->get($r['customer_id']);
            return array_merge($r, [
                'customer_name' => $c?->name,
                'customer_phone' => null, // mask — phone không cần ở Aging list
                'reputation_label' => $c?->reputation_label,
            ]);
        }, $rows);
        // sort theo total desc
        usort($data, fn ($a, $b) => $b['total'] <=> $a['total']);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total_balance' => array_sum(array_column($data, 'total')),
                'total_b0_30' => array_sum(array_column($data, 'b0_30')),
                'total_b31_60' => array_sum(array_column($data, 'b31_60')),
                'total_b61_90' => array_sum(array_column($data, 'b61_90')),
                'total_b90p' => array_sum(array_column($data, 'b90p')),
            ],
        ]);
    }

    /** GET /accounting/ar/customers/{id}/balance — chi tiết một khách. */
    public function balance(int $customerId): JsonResponse
    {
        $tenantId = (int) $this->tenant->id();
        $balances = $this->ar->balancesByCustomer($tenantId, $customerId);

        return response()->json(['data' => $balances[$customerId] ?? ['customer_id' => $customerId, 'debit' => 0, 'credit' => 0, 'balance' => 0]]);
    }

    /** GET /accounting/customer-receipts — list */
    public function listReceipts(Request $request): AnonymousResourceCollection
    {
        $q = CustomerReceipt::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('received_at', 'desc')
            ->orderBy('id', 'desc');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($c = $request->query('customer_id')) {
            $q->where('customer_id', (int) $c);
        }
        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        return CustomerReceiptResource::collection($q->paginate($perPage));
    }

    public function createReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|integer|min:1',
            'received_at' => 'required|date',
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,bank,ewallet',
            'applied_orders' => 'nullable|array',
            'applied_orders.*.order_id' => 'required_with:applied_orders|integer|min:1',
            'applied_orders.*.applied_amount' => 'required_with:applied_orders|integer|min:1',
            'memo' => 'nullable|string|max:500',
        ]);
        $row = $this->receipts->create(
            (int) $this->tenant->id(),
            $request->only(['customer_id', 'received_at', 'amount', 'payment_method', 'applied_orders', 'memo', 'cash_account_id']),
            (int) $request->user()->getKey(),
        );

        return (new CustomerReceiptResource($row))->response()->setStatusCode(201);
    }

    public function confirmReceipt(Request $request, int $id): CustomerReceiptResource
    {
        $row = CustomerReceipt::query()->where('tenant_id', $this->tenant->id())->findOrFail($id);
        $row = $this->receipts->confirm($row, (int) $request->user()->getKey());

        return new CustomerReceiptResource($row);
    }

    public function cancelReceipt(int $id): CustomerReceiptResource
    {
        $row = CustomerReceipt::query()->where('tenant_id', $this->tenant->id())->findOrFail($id);
        $row = $this->receipts->cancel($row);

        return new CustomerReceiptResource($row);
    }
}
