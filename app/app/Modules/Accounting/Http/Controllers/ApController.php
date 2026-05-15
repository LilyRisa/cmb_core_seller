<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Http\Resources\VendorBillResource;
use CMBcoreSeller\Modules\Accounting\Http\Resources\VendorPaymentResource;
use CMBcoreSeller\Modules\Accounting\Models\VendorBill;
use CMBcoreSeller\Modules\Accounting\Models\VendorPayment;
use CMBcoreSeller\Modules\Accounting\Services\ApService;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ApController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly ApService $ap,
    ) {}

    public function aging(): JsonResponse
    {
        $tenantId = (int) $this->tenant->id();
        $rows = $this->ap->agingBySupplier($tenantId);
        $supplierIds = array_column($rows, 'supplier_id');
        $suppliers = $supplierIds
            ? Supplier::query()->whereIn('id', $supplierIds)->get()->keyBy('id')
            : collect();
        $data = array_map(function ($r) use ($suppliers) {
            $s = $suppliers->get($r['supplier_id']);
            return array_merge($r, [
                'supplier_name' => $s?->name,
                'supplier_code' => $s?->code,
            ]);
        }, $rows);
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

    public function listBills(Request $request): AnonymousResourceCollection
    {
        $q = VendorBill::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('bill_date', 'desc')->orderBy('id', 'desc');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($sid = $request->query('supplier_id')) {
            $q->where('supplier_id', (int) $sid);
        }
        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        return VendorBillResource::collection($q->paginate($perPage));
    }

    public function createBill(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'nullable|integer|min:1',
            'purchase_order_id' => 'nullable|integer|min:1',
            'goods_receipt_id' => 'nullable|integer|min:1',
            'bill_no' => 'nullable|string|max:64',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|integer|min:0',
            'tax' => 'nullable|integer|min:0',
            'memo' => 'nullable|string|max:500',
        ]);
        $bill = $this->ap->createBill(
            (int) $this->tenant->id(),
            $request->only(['supplier_id', 'purchase_order_id', 'goods_receipt_id', 'bill_no', 'bill_date', 'due_date', 'subtotal', 'tax', 'memo']),
            (int) $request->user()->getKey(),
        );

        return (new VendorBillResource($bill))->response()->setStatusCode(201);
    }

    public function recordBill(Request $request, int $id): VendorBillResource
    {
        $bill = VendorBill::query()->where('tenant_id', $this->tenant->id())->findOrFail($id);
        $bill = $this->ap->recordBill($bill, (int) $request->user()->getKey());

        return new VendorBillResource($bill);
    }

    public function listPayments(Request $request): AnonymousResourceCollection
    {
        $q = VendorPayment::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('paid_at', 'desc')->orderBy('id', 'desc');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($sid = $request->query('supplier_id')) {
            $q->where('supplier_id', (int) $sid);
        }
        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        return VendorPaymentResource::collection($q->paginate($perPage));
    }

    public function createPayment(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'nullable|integer|min:1',
            'paid_at' => 'required|date',
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,bank,ewallet',
            'applied_bills' => 'nullable|array',
            'memo' => 'nullable|string|max:500',
        ]);
        $payment = $this->ap->createPayment(
            (int) $this->tenant->id(),
            $request->only(['supplier_id', 'paid_at', 'amount', 'payment_method', 'applied_bills', 'memo']),
            (int) $request->user()->getKey(),
        );

        return (new VendorPaymentResource($payment))->response()->setStatusCode(201);
    }

    public function confirmPayment(Request $request, int $id): VendorPaymentResource
    {
        $row = VendorPayment::query()->where('tenant_id', $this->tenant->id())->findOrFail($id);
        $row = $this->ap->confirmPayment($row, (int) $request->user()->getKey());

        return new VendorPaymentResource($row);
    }
}
