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
            // In HTML phía trình duyệt: để máy in tự co theo khổ giấy đang chọn (nhiệt K80/A6/A5/A4…).
            'AUTO' => 'size:auto;margin:4mm',
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

    /**
     * @param  Collection<int, Order>  $orders  với `items` đã nạp
     * @param  array<int, array{code:?string,name:?string}>  $skuById  map sku_id → {code, name} (để fallback khi `seller_sku`/`name` trống)
     */
    public static function packingList(Collection $orders, string $paper = 'A4', array $skuById = []): string
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
                [$skuCode, $prodName] = self::lineSkuAndName($it, $skuById);
                $rows .= '<tr><td class="r">'.($i + 1).'</td><td>'.self::e($prodName).self::e($it->variation ? ' — '.$it->variation : '').'</td>'
                    .'<td>'.self::e($skuCode).'</td><td class="r">'.(int) $it->quantity.'</td></tr>';
            }
            $code = $order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey());
            $page = '<div class="box"><h1>Phiếu đóng gói — '.self::e($code).'</h1>'
                .'<div class="muted">Nguồn: '.self::e($order->source).' · in lúc '.now()->format('d/m/Y H:i').'</div>'
                .'<p><b>Người nhận:</b> '.self::e($name).' · '.self::e($phone).'<br><b>Địa chỉ:</b> '.self::e($full).'</p>'
                .($order->note ? '<p><b>Ghi chú:</b> '.self::e($order->note).'</p>' : '')
                .'<table><thead><tr><th class="r">#</th><th>Sản phẩm</th><th>SKU</th><th class="r">SL</th></tr></thead><tbody>'.$rows.'</tbody></table></div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        return self::shell('Packing list', implode('', $pages), self::paperRule($paper));
    }

    private static function vnd(int $v): string
    {
        return number_format($v, 0, ',', '.').' ₫';
    }

    /**
     * Mã SKU + tên sản phẩm hiển thị cho 1 dòng đơn: ưu tiên `seller_sku`/`name` của dòng (đúng như sàn gửi),
     * fallback về SKU master (`sku_code`/`name`) khi dòng đã ghép SKU mà thiếu dữ liệu; mã SKU rỗng ⇒ "(chưa ghép)".
     *
     * @param  array<int, array{code:?string,name:?string}>  $skuById
     * @return array{0:string,1:string} [mã SKU, tên sản phẩm]
     */
    private static function lineSkuAndName(object $item, array $skuById): array
    {
        $master = ($item->sku_id ?? null) ? ($skuById[(int) $item->sku_id] ?? null) : null;
        $code = trim((string) ($item->seller_sku ?? '')) ?: trim((string) ($master['code'] ?? '')) ?: '(chưa ghép)';
        $name = trim((string) ($item->name ?? '')) ?: trim((string) ($master['name'] ?? '')) ?: '(không tên)';

        return [$code, $name];
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
     * @param  array<int, array{code:?string,name:?string}>  $skuById  map sku_id → {code, name} (fallback khi `seller_sku`/`name` trống)
     */
    /**
     * SPEC 0021 — Phiếu giao hàng tự tạo cho đơn manual. Bao gồm:
     *  - Header: logo + tên ĐVVC + tên shop + mã đơn + ngày in.
     *  - Khối mã vạch (Code128) + QR code, cả 2 đều encode `tracking_no` (mã vận đơn ĐVVC trả).
     *  - Người nhận: tên + SĐT + địa chỉ đầy đủ.
     *  - Bảng sản phẩm GỘP theo tên (cộng số lượng nếu trùng tên — yêu cầu user).
     *  - COD: tổng tiền cần thu hộ.
     *  - Nội dung in (từ `meta.print_note` của đơn — settings có default).
     *
     * Khi đơn chưa có tracking (race: in trước khi GHN trả mã) → ẩn block barcode + QR, hiện "Chờ mã vận đơn".
     *
     * @param  Collection<int, Order>  $orders
     * @param  array<int, array{code:?string,name:?string}>  $skuById
     */
    public static function deliverySlip(Collection $orders, string $shopName, string $paper = 'A6', array $skuById = [], array $senderByOrderId = []): string
    {
        return self::deliverySlipV2($orders, $shopName, $paper, $skuById, $senderByOrderId);
    }

    /**
     * Mẫu phiếu giao hàng MẶC ĐỊNH (theo order_tem.pdf): header tên shop + COD; mã đơn lớn;
     * "Địa chỉ giao hàng" (người nhận) + "Địa chỉ lấy hàng" (kho/shop); barcode + mã + QR;
     * "Sản phẩm"; "Ghi chú"; khung viền nét đứt. Khổ `AUTO` ⇒ in HTML phía trình duyệt tự co
     * theo khổ máy in (responsive); khổ cố định (A6…) dùng cho PDF Gotenberg.
     *
     * @param  Collection<int, Order>  $orders
     * @param  array<int, array{code:?string,name:?string}>  $skuById
     * @param  array<int, array{name:string,phone:string,address:string}>  $senderByOrderId  địa chỉ lấy hàng (kho) theo order id
     */
    private static function deliverySlipV2(Collection $orders, string $shopName, string $paper, array $skuById, array $senderByOrderId): string
    {
        $br = new BarcodeRenderer;
        $carrierName = [
            'ghn' => 'Giao Hàng Nhanh', 'ghtk' => 'Giao Hàng Tiết Kiệm', 'jt' => 'J&T Express',
            'viettelpost' => 'Viettel Post', 'ninjavan' => 'Ninja Van', 'spx' => 'SPX Express',
            'vnpost' => 'Vietnam Post', 'ahamove' => 'Ahamove', 'manual' => 'Tự vận chuyển',
        ];
        $pages = [];
        $last = $orders->count() - 1;
        foreach ($orders->values() as $idx => $order) {
            /** @var Order $order */
            $addr = (array) ($order->shipping_address ?? []);
            $name = (string) ($addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? '—');
            $phone = (string) ($addr['phone'] ?? '');
            $toFull = trim(implode(', ', array_filter([
                $addr['line1'] ?? $addr['address'] ?? null, $addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null,
            ]))) ?: '—';

            $sh = $order->relationLoaded('shipments') ? $order->shipments->first(fn ($x) => $x->status !== 'cancelled') : null;
            $code = (string) ($order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey()));
            $tracking = (string) ($sh?->tracking_no ?: '');
            $barcodeValue = $tracking !== '' ? $tracking : $code;   // luôn có 1 mã để quét
            $carrierCode = strtolower((string) ($sh?->carrier ?? $order->carrier ?? ''));
            $carrierKey = (string) preg_replace('/^manual_/', '', $carrierCode);
            $carrierLabel = $carrierName[$carrierKey] ?? ($carrierKey !== '' ? strtoupper($carrierKey) : '');

            // Địa chỉ lấy hàng (kho/shop).
            $sender = $senderByOrderId[$order->getKey()] ?? ['name' => $shopName, 'phone' => '', 'address' => ''];
            $senderName = ((string) $sender['name']) !== '' ? (string) $sender['name'] : $shopName;
            $senderLine = trim($senderName.(((string) $sender['phone']) !== '' ? ' - '.$sender['phone'] : ''));

            // COD.
            $codTotal = $order->is_cod ? max(0, (int) ($order->cod_amount ?: ((int) $order->grand_total - (int) ($order->prepaid_amount ?? 0)))) : 0;

            // Sản phẩm — gộp theo tên.
            $grouped = [];
            $totalQty = 0;
            foreach ($order->items as $it) {
                [, $prodName] = self::lineSkuAndName($it, $skuById);
                $displayName = trim($prodName.($it->variation ? ' — '.$it->variation : ''));
                $key = mb_strtolower($displayName);
                $grouped[$key] ??= ['name' => $displayName, 'qty' => 0];
                $grouped[$key]['qty'] += (int) $it->quantity;
                $totalQty += (int) $it->quantity;
            }
            $itemsHtml = '';
            foreach ($grouped as $g) {
                $itemsHtml .= '<li><span class="li-name">'.self::e($g['name']).'</span> <span class="li-qty">× '.$g['qty'].'</span></li>';
            }

            $printNote = (string) (data_get($order->meta, 'print_note') ?: ($order->note ?? ''));

            $page = '<div class="slip">'
                .'<div class="head">'
                    .'<div class="head-brand">'.self::e($shopName).($carrierLabel !== '' ? '<div class="head-carrier">'.self::e($carrierLabel).'</div>' : '').'</div>'
                    .($codTotal > 0 ? '<div class="head-cod">COD <b>'.self::vnd($codTotal).'</b></div>' : '<div class="head-cod head-cod-zero">Đã thanh toán</div>')
                .'</div>'
                .'<div class="code">'.self::e($code).'</div>'
                .'<div class="addr"><span class="addr-lbl">Địa chỉ giao hàng:</span> <b>'.self::e($name).($phone !== '' ? ' - '.self::e($phone) : '').'</b>'
                    .'<div class="addr-detail">'.self::e($toFull).'</div></div>'
                .'<div class="addr"><span class="addr-lbl">Địa chỉ lấy hàng:</span> <b>'.self::e($senderLine).'</b>'
                    .(((string) $sender['address']) !== '' ? '<div class="addr-detail">'.self::e((string) $sender['address']).'</div>' : '').'</div>'
                .'<div class="bc">'
                    .'<div class="bc-left"><img class="bc-bar" src="'.$br->code128SvgDataUrl($barcodeValue, 2, 56).'" alt="barcode"/>'
                        .'<div class="bc-text">'.self::e($barcodeValue).'</div></div>'
                    .'<div class="bc-qr"><img src="'.$br->qrSvgDataUrl($barcodeValue, 96).'" alt="QR"/></div>'
                .'</div>'
                .'<div class="prod"><div class="prod-lbl">Sản phẩm:</div><ul>'.$itemsHtml.'</ul>'
                    .($totalQty > 0 ? '<div class="prod-total">Tổng: '.$totalQty.' món</div>' : '').'</div>'
                .($printNote !== '' ? '<div class="note"><b>Ghi chú:</b> '.nl2br(self::e($printNote)).'</div>' : '')
                .'</div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        $css = <<<'CSS'
            @page{__PAGE__}
            *{font-family:DejaVu Sans,Arial,sans-serif;box-sizing:border-box}
            body{font-size:12px;color:#1a1a1a;margin:0;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .slip{border:1px dashed #555;border-radius:4px;padding:10px 12px;width:100%}
            .head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
            .head-brand{font-size:15px;font-weight:800;line-height:1.15}
            .head-carrier{font-size:10px;font-weight:600;color:#888;margin-top:1px}
            .head-cod{font-size:13px;white-space:nowrap}
            .head-cod b{font-size:15px}
            .head-cod-zero{color:#389e0d;font-weight:600}
            .code{text-align:center;font-size:20px;font-weight:800;letter-spacing:.5px;margin:8px 0 10px}
            .addr{font-size:12px;line-height:1.4;margin-bottom:8px}
            .addr-lbl{font-weight:700}
            .addr-detail{color:#333;margin-top:1px}
            .bc{display:flex;gap:10px;align-items:center;justify-content:space-between;margin:10px 0;padding:6px 0;border-top:1px dashed #ddd;border-bottom:1px dashed #ddd}
            .bc-left{flex:1;min-width:0;text-align:center}
            .bc-bar{height:54px;width:100%;display:block}
            .bc-text{font-family:'DejaVu Sans Mono',monospace;font-size:13px;font-weight:700;letter-spacing:1px;margin-top:2px}
            .bc-qr{flex:0 0 80px}
            .bc-qr img{width:80px;height:80px;display:block}
            .prod{margin-top:6px}
            .prod-lbl{font-weight:700;margin-bottom:2px}
            .prod ul{list-style:none;margin:0;padding:0;font-size:12px;line-height:1.5}
            .prod li{display:flex;padding:1px 0}
            .prod li .li-name{flex:1;min-width:0}
            .prod li .li-qty{font-weight:700;margin-left:6px;flex:0 0 auto}
            .prod-total{font-size:11px;color:#666;margin-top:3px;text-align:right}
            .note{margin-top:8px;font-size:11.5px;line-height:1.4}
            .page-break{page-break-after:always}
        CSS;

        return '<!doctype html><html><head><meta charset="utf-8"><title>Phiếu giao hàng</title><style>'
            .str_replace('__PAGE__', self::paperRule($paper), $css)
            .'</style></head><body>'.implode('', $pages).'</body></html>';
    }
}
