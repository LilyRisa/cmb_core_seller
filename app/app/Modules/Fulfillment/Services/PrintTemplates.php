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
    public static function deliverySlip(Collection $orders, string $shopName, string $paper = 'A6', array $skuById = []): string
    {
        $br = new \CMBcoreSeller\Modules\Fulfillment\Services\BarcodeRenderer();
        $carrierMeta = [
            'ghn' => ['name' => 'Giao Hàng Nhanh', 'color' => '#1f9e3a'],
            'ghtk' => ['name' => 'Giao Hàng Tiết Kiệm', 'color' => '#fa8c16'],
            'jt' => ['name' => 'J&T Express', 'color' => '#cf1322'],
            'viettelpost' => ['name' => 'Viettel Post', 'color' => '#d4380d'],
            'ninjavan' => ['name' => 'Ninja Van', 'color' => '#c41d7f'],
            'spx' => ['name' => 'SPX Express', 'color' => '#2f54eb'],
            'vnpost' => ['name' => 'VNPost', 'color' => '#d48806'],
            'ahamove' => ['name' => 'Ahamove', 'color' => '#13c2c2'],
            'manual' => ['name' => 'Tự vận chuyển', 'color' => '#8c8c8c'],
        ];
        $pages = [];
        $last = $orders->count() - 1;
        foreach ($orders->values() as $idx => $order) {
            /** @var Order $order */
            $addr = (array) ($order->shipping_address ?? []);
            $name = $addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? '—';
            $phone = $addr['phone'] ?? '—';
            $full = trim(implode(', ', array_filter([$addr['line1'] ?? null, $addr['address'] ?? null, $addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null]))) ?: '—';
            $sh = $order->relationLoaded('shipments') ? $order->shipments->first(fn ($x) => $x->status !== 'cancelled') : null;

            // Gộp sản phẩm theo TÊN (case-insensitive, trim) — yêu cầu user: "nhiều SP nhưng trùng tên ⇒ gộp 1 SP + tổng SL".
            $grouped = [];
            foreach ($order->items as $it) {
                [$skuCode, $prodName] = self::lineSkuAndName($it, $skuById);
                $variationSuffix = $it->variation ? ' — '.$it->variation : '';
                $displayName = trim($prodName.$variationSuffix);
                $key = mb_strtolower($displayName);
                $grouped[$key] ??= ['name' => $displayName, 'sku' => $skuCode, 'qty' => 0, 'amount' => 0];
                $grouped[$key]['qty'] += (int) $it->quantity;
                $grouped[$key]['amount'] += (int) ($it->unit_price * $it->quantity - $it->discount);
            }
            $rows = '';
            $totalQty = 0;
            $idxRow = 1;
            foreach ($grouped as $g) {
                $totalQty += $g['qty'];
                $rows .= '<tr><td class="r">'.($idxRow++).'</td>'
                    .'<td>'.self::e($g['name']).($g['sku'] ? '<br><span class="muted">'.self::e($g['sku']).'</span>' : '').'</td>'
                    .'<td class="r">'.$g['qty'].'</td>'
                    .'<td class="r">'.self::vnd((int) $g['amount']).'</td></tr>';
            }
            $rows .= '<tr><td colspan="2" class="r"><b>Tổng số lượng</b></td><td class="r"><b>'.$totalQty.'</b></td><td class="r"><b>'.self::vnd((int) $order->grand_total).'</b></td></tr>';

            $code = $order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey());
            $tracking = $sh?->tracking_no ?: '';
            $carrierCode = strtolower((string) ($sh?->carrier ?? $order->carrier ?? 'manual'));
            $cMeta = $carrierMeta[$carrierCode] ?? ['name' => $carrierCode ?: 'ĐVVC', 'color' => '#8c8c8c'];
            $carrierLogo = '<span style="display:inline-block;padding:3px 10px;background:'.$cMeta['color'].';color:#fff;font-weight:700;font-size:12px;border-radius:4px;letter-spacing:0.3px">'.self::e($cMeta['name']).'</span>';

            // Barcode + QR — encode tracking_no (chuẩn industry-wide). Khi thiếu tracking, ẩn block barcode.
            $hasTracking = $tracking !== '';
            $qrSrc = $hasTracking ? $br->qrSvgDataUrl($tracking, 110) : '';
            $barcodeSrc = $hasTracking ? $br->code128SvgDataUrl($tracking, 2, 45) : '';
            $barcodeBlock = $hasTracking
                ? '<div class="bc-block">'
                    .'<div class="bc-meta">'.$carrierLogo.'<div class="muted" style="margin-top:4px">Mã vận đơn</div><div class="tracking">'.self::e($tracking).'</div></div>'
                    .'<div class="bc-img"><img src="'.$barcodeSrc.'" alt="barcode" style="height:42px;width:auto"/></div>'
                    .'<div class="qr"><img src="'.$qrSrc.'" alt="QR" style="width:82px;height:82px"/></div>'
                    .'</div>'
                : '<div class="bc-block" style="background:#fffbe6;border:1px dashed #ffc53d"><div class="bc-meta">'.$carrierLogo.'<div class="muted" style="margin-top:4px">Chưa có mã vận đơn — bấm "Chuẩn bị hàng" để lấy mã từ ĐVVC.</div></div></div>';

            // COD highlight box (chỉ khi is_cod). Lấy cod_amount nếu set, fallback grand_total - prepaid.
            $codTotal = $order->is_cod ? max(0, (int) ($order->cod_amount ?: ((int) $order->grand_total - (int) ($order->prepaid_amount ?? 0)))) : 0;
            $codBlock = $order->is_cod
                ? '<div class="cod-box"><span class="cod-label">THU HỘ (COD)</span><span class="cod-amt">'.self::vnd($codTotal).'</span></div>'
                : '';

            // Print note — ưu tiên meta.print_note (cài đặt khi tạo đơn). Nếu trống, fallback note nội bộ.
            $printNote = (string) (data_get($order->meta, 'print_note') ?: '');
            $noteBlock = $printNote !== ''
                ? '<div class="print-note"><b>Nội dung:</b><br>'.nl2br(self::e($printNote)).'</div>'
                : '';

            $page = '<div class="slip">'
                .'<div class="head">'
                    .'<div><div class="shop">'.self::e($shopName).'</div><div class="muted">Phiếu giao hàng</div></div>'
                    .'<div style="text-align:right"><div class="order-code">'.self::e($code).'</div><div class="muted">'.now()->format('d/m/Y H:i').'</div></div>'
                .'</div>'
                .$barcodeBlock
                .'<div class="recipient">'
                    .'<div><b>Người nhận:</b> '.self::e($name).' · '.self::e($phone).'</div>'
                    .'<div><b>Địa chỉ:</b> '.self::e($full).'</div>'
                .'</div>'
                .'<table class="items"><thead><tr><th class="r">#</th><th>Sản phẩm</th><th class="r">SL</th><th class="r">Thành tiền</th></tr></thead><tbody>'.$rows.'</tbody></table>'
                .$codBlock
                .$noteBlock
                .'<div class="footer muted">Người gửi giữ phiếu để đối chiếu khi nhận hoàn / khiếu nại.</div>'
                .'</div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        $css = <<<'CSS'
            @page{__PAGE__}
            *{font-family:DejaVu Sans,Arial,sans-serif;box-sizing:border-box}
            body{font-size:11px;color:#222;margin:0}
            .slip{padding:2mm}
            .head{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:6px;border-bottom:2px solid #222;margin-bottom:8px}
            .shop{font-size:14px;font-weight:700;letter-spacing:-0.2px}
            .order-code{font-size:13px;font-weight:700}
            .muted{color:#888;font-size:10px}
            .bc-block{display:flex;align-items:center;gap:10px;padding:6px 10px;border:1px solid #eaeaea;border-radius:6px;margin-bottom:8px;background:#fafafa}
            .bc-meta{flex:0 0 auto;min-width:120px}
            .bc-img{flex:1 1 auto;display:flex;justify-content:center;align-items:center}
            .qr{flex:0 0 auto}
            .tracking{font-family:'DejaVu Sans Mono',monospace;font-size:13px;font-weight:700;letter-spacing:0.4px;margin-top:2px;word-break:break-all}
            .recipient{padding:6px 0;border-bottom:1px dashed #eaeaea;margin-bottom:6px;line-height:1.5}
            table.items{width:100%;border-collapse:collapse;margin-top:4px}
            table.items th,table.items td{border:1px solid #e8e8e8;padding:4px 6px;text-align:left;vertical-align:top}
            table.items th{background:#f5f5f5;font-weight:600}
            table.items .r{text-align:right}
            .cod-box{display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#fff1f0;border:1.5px solid #cf1322;border-radius:6px;margin-top:8px}
            .cod-label{font-size:11px;font-weight:700;color:#cf1322;letter-spacing:0.5px}
            .cod-amt{font-size:16px;font-weight:800;color:#cf1322}
            .print-note{background:#fffbe6;border:1px solid #ffe58f;border-radius:6px;padding:6px 10px;margin-top:8px;line-height:1.5}
            .footer{margin-top:10px;padding-top:6px;border-top:1px dashed #eaeaea;text-align:center}
            .page-break{page-break-after:always}
        CSS;
        $rule = self::paperRule($paper);
        $cssFinal = str_replace('__PAGE__', $rule, $css);

        return '<!doctype html><html><head><meta charset="utf-8"><title>Phiếu giao hàng</title><style>'.$cssFinal.'</style></head><body>'.implode('', $pages).'</body></html>';
    }
}
