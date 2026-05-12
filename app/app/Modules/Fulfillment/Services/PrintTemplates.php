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

    /** Khổ giấy `@page` (size + margin) theo tên cài đặt — mỗi phiếu/đơn 1 trang. SPEC 0006 §4.4 / 0013. */
    public static function paperRule(string $name): string
    {
        return match (strtoupper(str_replace(' ', '', trim($name)) ?: 'A4')) {
            'A4' => 'size:A4;margin:12mm',
            'A5' => 'size:A5;margin:10mm',
            'A6' => 'size:A6;margin:5mm',
            '100X150MM', '100X150' => 'size:100mm 150mm;margin:5mm',
            '80MM' => 'size:80mm auto;margin:3mm',
            default => 'size:A4;margin:12mm',
        };
    }

    private static function shell(string $title, string $body, string $pageCss = 'size:A4;margin:12mm'): string
    {
        return '<!doctype html><html><head><meta charset="utf-8"><title>'.self::e($title).'</title><style>'
            .'@page{'.$pageCss.'}*{font-family:DejaVu Sans,Arial,sans-serif}body{font-size:12px;color:#222}'
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

        return self::shell('Picking list', $body);   // picking = danh sách gom theo SKU ⇒ luôn A4
    }

    /** @param Collection<int, Order> $orders */
    public static function packingList(Collection $orders, string $paper = 'A4'): string
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

        return self::shell('Packing list', implode('', $pages), self::paperRule($paper));
    }

    private static function vnd(int $v): string
    {
        return number_format($v, 0, ',', '.').' ₫';
    }

    /**
     * Hoá đơn bán hàng / phiếu đơn — mỗi đơn một trang: tên cửa hàng + mã đơn/ngày + người mua/nhận +
     * bảng hàng (tên · SKU · SL · đơn giá · thành tiền) + tạm tính/ship/giảm giá/thuế/tổng/COD + ghi chú.
     *
     * @param  Collection<int, Order>  $orders  với `items` đã nạp
     */
    public static function invoice(Collection $orders, string $shopName, string $paper = 'A4'): string
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
                $qty = (int) $it->quantity;
                $unit = (int) $it->unit_price;
                $sub = (int) ($it->subtotal ?: ($unit * $qty - (int) $it->discount));
                $rows .= '<tr><td class="r">'.($i + 1).'</td><td>'.self::e($it->name).self::e($it->variation ? ' — '.$it->variation : '')
                    .($it->seller_sku ? '<br><span class="muted">SKU: '.self::e($it->seller_sku).'</span>' : '').'</td>'
                    .'<td class="r">'.$qty.'</td><td class="r">'.self::vnd($unit).'</td><td class="r">'.self::vnd($sub).'</td></tr>';
            }
            $code = $order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey());
            $totalRows = '<div style="display:flex;justify-content:space-between;padding:2px 0"><span class="muted">Tạm tính</span><span>'.self::vnd((int) $order->item_total).'</span></div>'
                .((int) $order->shipping_fee ? '<div style="display:flex;justify-content:space-between;padding:2px 0"><span class="muted">Phí vận chuyển</span><span>'.self::vnd((int) $order->shipping_fee).'</span></div>' : '')
                .((int) $order->seller_discount ? '<div style="display:flex;justify-content:space-between;padding:2px 0"><span class="muted">Giảm giá người bán</span><span>−'.self::vnd((int) $order->seller_discount).'</span></div>' : '')
                .((int) $order->platform_discount ? '<div style="display:flex;justify-content:space-between;padding:2px 0"><span class="muted">Giảm giá sàn</span><span>−'.self::vnd((int) $order->platform_discount).'</span></div>' : '')
                .((int) $order->tax ? '<div style="display:flex;justify-content:space-between;padding:2px 0"><span class="muted">Thuế</span><span>'.self::vnd((int) $order->tax).'</span></div>' : '')
                .'<div style="display:flex;justify-content:space-between;padding:6px 0;font-size:15px;font-weight:700;border-top:1px solid #ccc;margin-top:4px"><span>Tổng cộng</span><span>'.self::vnd((int) $order->grand_total).'</span></div>'
                .($order->is_cod ? '<div style="display:flex;justify-content:space-between;padding:2px 0;color:#cf1322"><span>Thu hộ (COD)</span><span>'.self::vnd((int) ($order->cod_amount ?: $order->grand_total)).'</span></div>' : '');
            $page = '<div class="box"><div style="display:flex;justify-content:space-between;align-items:flex-end"><h1>'.self::e($shopName).'</h1><div class="muted" style="text-align:right">HOÁ ĐƠN BÁN HÀNG<br>'.self::e($code).' · '.now()->format('d/m/Y H:i').'</div></div>'
                .'<p><b>Khách hàng:</b> '.self::e($name).' · '.self::e($phone).'<br><b>Địa chỉ giao:</b> '.self::e($full).'<br><span class="muted">Nguồn đơn: '.self::e($order->source).'</span></p>'
                .'<table><thead><tr><th class="r">#</th><th>Sản phẩm</th><th class="r">SL</th><th class="r">Đơn giá</th><th class="r">Thành tiền</th></tr></thead><tbody>'.$rows.'</tbody></table>'
                .'<div style="max-width:320px;margin-left:auto;margin-top:8px">'.$totalRows.'</div>'
                .($order->note ? '<p class="muted" style="margin-top:8px"><b>Ghi chú:</b> '.self::e($order->note).'</p>' : '')
                .'<p class="muted" style="margin-top:12px;text-align:center">Cảm ơn Quý khách đã mua hàng!</p></div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        return self::shell('Hoá đơn', implode('', $pages), self::paperRule($paper));
    }

    /**
     * "Phiếu giao hàng" tự tạo (SPEC 0013) — mỗi đơn một trang: tên cửa hàng + mã đơn/ngày + người nhận +
     * địa chỉ giao + mã vận đơn / ĐVVC (nếu đã tạo vận đơn) + bảng hàng + COD + ghi chú. Dùng khi chưa kéo
     * được tem/AWB thật của sàn ("luồng A" = follow-up).
     *
     * @param  Collection<int, Order>  $orders  với `items` và `shipments` (mới nhất trước) đã nạp
     */
    public static function deliverySlip(Collection $orders, string $shopName, string $paper = 'A6'): string
    {
        $pages = [];
        $last = $orders->count() - 1;
        foreach ($orders->values() as $idx => $order) {
            /** @var Order $order */
            $addr = (array) ($order->shipping_address ?? []);
            $name = $addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? '—';
            $phone = $addr['phone'] ?? '—';
            $full = trim(implode(', ', array_filter([$addr['line1'] ?? null, $addr['address'] ?? null, $addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null]))) ?: '—';
            $sh = $order->relationLoaded('shipments') ? $order->shipments->first(fn ($x) => $x->status !== 'cancelled') : null;
            $rows = '';
            foreach ($order->items as $i => $it) {
                $rows .= '<tr><td class="r">'.($i + 1).'</td><td>'.self::e($it->name).self::e($it->variation ? ' — '.$it->variation : '')
                    .($it->seller_sku ? '<br><span class="muted">SKU: '.self::e($it->seller_sku).'</span>' : '').'</td><td class="r">'.(int) $it->quantity.'</td></tr>';
            }
            $code = $order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey());
            $shipLine = $sh
                ? '<b>ĐVVC:</b> '.self::e((string) $sh->carrier).' · <b>Mã vận đơn:</b> '.self::e((string) ($sh->tracking_no ?: '(chưa có)'))
                : '<span class="muted">Chưa tạo vận đơn</span>';
            $page = '<div class="box"><div style="display:flex;justify-content:space-between;align-items:flex-end"><h1>'.self::e($shopName).'</h1><div class="muted" style="text-align:right">PHIẾU GIAO HÀNG<br>'.self::e($code).' · '.now()->format('d/m/Y H:i').'</div></div>'
                .'<p><b>Người nhận:</b> '.self::e($name).' · '.self::e($phone).'<br><b>Địa chỉ giao:</b> '.self::e($full).'<br>'.$shipLine.'<br><span class="muted">Nguồn đơn: '.self::e($order->source).'</span></p>'
                .'<table><thead><tr><th class="r">#</th><th>Sản phẩm</th><th class="r">SL</th></tr></thead><tbody>'.$rows.'</tbody></table>'
                .($order->is_cod ? '<p style="margin-top:8px;font-size:15px;font-weight:700;color:#cf1322">Thu hộ (COD): '.self::vnd((int) ($order->cod_amount ?: $order->grand_total)).'</p>' : '')
                .($order->note ? '<p class="muted" style="margin-top:8px"><b>Ghi chú:</b> '.self::e($order->note).'</p>' : '')
                .'<p class="muted" style="margin-top:10px">Lưu ý: kiểm đủ hàng theo phiếu trước khi bàn giao cho ĐVVC.</p></div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        return self::shell('Phiếu giao hàng', implode('', $pages), self::paperRule($paper));
    }
}
