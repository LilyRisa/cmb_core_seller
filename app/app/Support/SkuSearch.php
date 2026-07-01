<?php

namespace CMBcoreSeller\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Two-tier SKU search used by every SKU/product picker & filter in the app. Lives in the shared
 * `Support` namespace (like StandardOrderStatus) so any module may use it without breaking the
 * "no cross-module internals" rule.
 *
 * Yêu cầu nghiệp vụ: người dùng gõ mã SKU thì phải khớp KHÔNG phân biệt hoa/thường trước; nếu
 * không có mã nào khớp thì mới fallback sang tìm theo TIÊU ĐỀ (khớp một phần, không phân biệt
 * hoa/thường). Trước đây nhiều nơi dùng `LIKE '%term%'` trần → PostgreSQL (prod) phân biệt
 * hoa/thường nên gõ chữ thường không ra kết quả (mã lưu chuẩn hoá HOA — xem SkuCodeNormalizer).
 *
 *  - Tier 1: mã (code columns) khớp CHÍNH XÁC, không phân biệt hoa/thường. Ưu tiên tuyệt đối —
 *    có mã khớp thì chỉ trả về đúng các bản ghi đó.
 *  - Tier 2 (fallback khi Tier 1 rỗng): tiêu đề (title columns) HOẶC mã khớp MỘT PHẦN
 *    (`LIKE %term%`), không phân biệt hoa/thường.
 *
 * Column names come from the caller (không phải input người dùng) nên an toàn để nội suy vào SQL;
 * giá trị so khớp luôn được bind. Dùng `LOWER(col)` để chạy được trên cả SQLite (dev/test),
 * PostgreSQL (prod) lẫn MySQL — tránh `ILIKE` (chỉ PG).
 */
final class SkuSearch
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $q
     * @param  array<int,string>  $codeColumns  cột mã (sku_code, barcode, seller_sku…) — khớp chính xác trước
     * @param  array<int,string>  $titleColumns  cột tiêu đề (name, title…) — fallback khớp một phần
     * @return Builder<TModel>
     */
    public static function apply(Builder $q, string $term, array $codeColumns, array $titleColumns): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $q;
        }
        $lower = mb_strtolower($term);

        // Tier 1 — có mã nào khớp chính xác (không phân biệt hoa/thường) trong tập đã lọc hiện tại?
        if ($codeColumns !== [] && (clone $q)->where(fn (Builder $w) => self::exactCode($w, $codeColumns, $lower))->exists()) {
            return $q->where(fn (Builder $w) => self::exactCode($w, $codeColumns, $lower));
        }

        // Tier 2 — fallback: tiêu đề khớp một phần, kèm mã khớp một phần (đều không phân biệt hoa/thường).
        return $q->where(function (Builder $w) use ($titleColumns, $codeColumns, $lower) {
            foreach ($titleColumns as $c) {
                $w->orWhereRaw('LOWER('.$c.') LIKE ?', ['%'.$lower.'%']);
            }
            foreach ($codeColumns as $c) {
                $w->orWhereRaw('LOWER('.$c.') LIKE ?', ['%'.$lower.'%']);
            }
        });
    }

    /** @param  array<int,string>  $codeColumns */
    private static function exactCode(Builder $w, array $codeColumns, string $lower): void
    {
        foreach ($codeColumns as $c) {
            $w->orWhereRaw('LOWER('.$c.') = ?', [$lower]);
        }
    }
}
