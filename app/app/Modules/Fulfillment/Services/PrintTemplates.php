<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Support\Collection;

/**
 * Built-in HTML templates for the picking & packing lists (rendered to PDF by Gotenberg).
 * v1 only — user-customisable templates (`print_templates`) are a follow-up. See SPEC 0006 §3.3.
 */
final class PrintTemplates
{
    private static function e(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    private static function shell(string $title, string $body): string
    {
        return '<!doctype html><html><head><meta charset="utf-8"><title>'.self::e($title).'</title><style>'
            .'@page{size:A4;margin:14mm}*{font-family:DejaVu Sans,Arial,sans-serif}body{font-size:12px;color:#222}'
            .'h1{font-size:18px;margin:0 0 4px}.muted{color:#888;font-size:11px}table{width:100%;border-collapse:collapse;margin-top:8px}'
            .'th,td{border:1px solid #ccc;padding:6px 8px;text-align:left;vertical-align:top}th{background:#f5f5f5}'
            .'.r{text-align:right}.page-break{page-break-after:always}.box{border:1px solid #ddd;border-radius:6px;padding:10px;margin-bottom:10px}'
            .'</style></head><body>'.$body.'</body></html>';
    }

    /**
     * @param  list<array{code:string,name:string,qty:int,orders:list<string>}>  $rows
     */
    public static function pickingList(array $rows, int $orderCount): string
    {
        $tr = '';
        foreach ($rows as $i => $r) {
            $tr .= '<tr><td class="r">'.($i + 1).'</td><td><b>'.self::e($r['code']).'</b></td><td>'.self::e($r['name']).'</td>'
                .'<td class="r"><b>'.(int) $r['qty'].'</b></td><td class="muted">'.self::e(implode(', ', $r['orders'])).'</td></tr>';
        }

        $body = '<h1>Phiếu soạn hàng (Picking list)</h1>'
            .'<div class="muted">'.$orderCount.' đơn · '.count($rows).' SKU · in lúc '.now()->format('d/m/Y H:i').'</div>'
            .'<table><thead><tr><th class="r">#</th><th>Mã SKU</th><th>Tên</th><th class="r">Tổng SL</th><th>Từ các đơn</th></tr></thead><tbody>'.$tr.'</tbody></table>';

        return self::shell('Picking list', $body);
    }

    /** @param Collection<int, Order> $orders */
    public static function packingList(Collection $orders): string
    {
        $pages = [];
        $last = $orders->count() - 1;
        foreach ($orders->values() as $idx => $order) {
            /** @var Order $order */
            $addr = (array) ($order->shipping_address ?? []);
            $name = $addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? '—';
            $phone = $addr['phone'] ?? '—';
            $full = trim(implode(', ', array_filter([$addr['line1'] ?? null, $addr['address'] ?? null, $addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null]))) ?: '—';
            $rows = '';
            foreach ($order->items as $i => $it) {
                $rows .= '<tr><td class="r">'.($i + 1).'</td><td>'.self::e($it->name).self::e($it->variation ? ' — '.$it->variation : '').'</td>'
                    .'<td>'.self::e($it->seller_sku ?: '').'</td><td class="r">'.(int) $it->quantity.'</td></tr>';
            }
            $code = $order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey());
            $page = '<div class="box"><h1>Phiếu đóng gói — '.self::e($code).'</h1>'
                .'<div class="muted">Nguồn: '.self::e($order->source).' · in lúc '.now()->format('d/m/Y H:i').'</div>'
                .'<p><b>Người nhận:</b> '.self::e($name).' · '.self::e($phone).'<br><b>Địa chỉ:</b> '.self::e($full).'</p>'
                .($order->note ? '<p><b>Ghi chú:</b> '.self::e($order->note).'</p>' : '')
                .'<table><thead><tr><th class="r">#</th><th>Sản phẩm</th><th>SKU sàn</th><th class="r">SL</th></tr></thead><tbody>'.$rows.'</tbody></table></div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        return self::shell('Packing list', implode('', $pages));
    }
}
