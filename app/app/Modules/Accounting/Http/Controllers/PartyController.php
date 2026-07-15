<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Tra cứu khách hàng / nhà cung cấp cho các bộ chọn (PartyPicker) trong kế toán:
 * phiếu thu (chọn khách 131), phiếu chi / hoá đơn NCC (chọn NCC 331), bút toán tay (party của dòng).
 *
 * Đặt trong module Accounting (gated `accounting.view`) thay vì gọi /customers, /suppliers để:
 *  - Không phụ thuộc plan-feature `procurement` (NCC) hay quyền `customers.view`/`procurement.view`
 *    — kế toán viên chỉ cần `accounting.view`.
 *  - Trả về cùng một shape gọn cho cả hai loại đối tượng.
 *
 * Accounting đã đọc trực tiếp model Customer/Supplier ở Ar/ApController nên không phá luật module.
 */
class PartyController extends Controller
{
    /**
     * GET /accounting/parties?type=customer|supplier&q=&ids=1,2,3
     *
     *  - `q`   : tìm theo tên / mã / SĐT (>=1 ký tự).
     *  - `ids` : danh sách id (CSV) để resolve nhãn cho giá trị đã chọn sẵn (preset).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:customer,supplier',
            'q' => 'nullable|string|max:100',
            'ids' => 'nullable|string|max:500',
        ]);

        $type = $request->string('type')->toString();
        $term = trim((string) $request->query('q', ''));
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids', ''))));

        $data = $type === 'customer'
            ? $this->customers($term, $ids)
            : $this->suppliers($term, $ids);

        return response()->json(['data' => $data]);
    }

    /** @return array<int, array{id:int, type:string, label:string, secondary:?string}> */
    private function customers(string $term, array $ids): array
    {
        $q = Customer::query();
        if ($ids) {
            $q->whereIn('id', $ids);
        } elseif ($term !== '') {
            // `phone` mã hoá at rest ⇒ không LIKE được; nếu term là SĐT thì tra theo phone_hash
            // (mirror CustomerController::applyFilters), còn lại tìm theo tên.
            $hash = CustomerPhoneNormalizer::normalizeAndHash($term);
            if ($hash !== null) {
                $q->where('phone_hash', $hash);
            } else {
                $q->where('name', 'like', "%{$term}%");
            }
        }
        $rows = $q->orderByDesc('last_seen_at')->limit(20)->get();

        return $rows->map(fn ($c) => [
            'id' => (int) $c->id,
            'type' => 'customer',
            'label' => $c->name ?: ('Khách #'.$c->id),
            'secondary' => $c->phone,
        ])->all();
    }

    /** @return array<int, array{id:int, type:string, label:string, secondary:?string}> */
    private function suppliers(string $term, array $ids): array
    {
        $q = Supplier::query();
        if ($ids) {
            $q->whereIn('id', $ids);
        } elseif ($term !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"));
        }
        $rows = $q->orderBy('name')->limit(20)->get();

        return $rows->map(fn ($s) => [
            'id' => (int) $s->id,
            'type' => 'supplier',
            'label' => $s->name ?: ('NCC #'.$s->id),
            'secondary' => $s->code,
        ])->all();
    }
}
