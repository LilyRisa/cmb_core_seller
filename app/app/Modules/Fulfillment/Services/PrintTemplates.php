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
        // SPEC 2026-05-17 — redesign theo chuẩn shipping-label (giống tem của các sàn TMĐT). KHÔNG dùng
        // format invoice (tên cửa hàng + bảng giá). Layout:
        //   ┌─────────────────────────────────────┐
        //   │ ĐVVC + service                       │
        //   │ ███ BARCODE ████   [QR]              │
        //   │ TRACKING#                            │
        //   ├─────────────────────────────────────┤
        //   │ FROM: shop · phone · địa chỉ kho     │
        //   ├─────────────────────────────────────┤
        //   │ TO: TÊN · SĐT (lớn)                  │
        //   │     Địa chỉ chi tiết đầy đủ          │
        //   ├─────────────────────────────────────┤
        //   │ [COD]    Weight    Items qty         │
        //   ├─────────────────────────────────────┤
        //   │ • SP1 × SL                          │
        //   │ • SP2 × SL                          │
        //   ├─────────────────────────────────────┤
        //   │ Ghi chú · mã đơn shop + ngày in     │
        //   └─────────────────────────────────────┘
        $carrierMeta = [
            'ghn' => ['name' => 'GHN', 'full' => 'GIAO HÀNG NHANH', 'color' => '#1f9e3a'],
            'ghtk' => ['name' => 'GHTK', 'full' => 'GIAO HÀNG TIẾT KIỆM', 'color' => '#fa8c16'],
            'jt' => ['name' => 'J&T', 'full' => 'J&T EXPRESS', 'color' => '#cf1322'],
            'viettelpost' => ['name' => 'VTP', 'full' => 'VIETTEL POST', 'color' => '#d4380d'],
            'ninjavan' => ['name' => 'NJV', 'full' => 'NINJA VAN', 'color' => '#c41d7f'],
            'spx' => ['name' => 'SPX', 'full' => 'SPX EXPRESS', 'color' => '#2f54eb'],
            'vnpost' => ['name' => 'VNPost', 'full' => 'VIETNAM POST', 'color' => '#d48806'],
            'ahamove' => ['name' => 'AHA', 'full' => 'AHAMOVE', 'color' => '#13c2c2'],
            'manual' => ['name' => 'TỰ VC', 'full' => 'TỰ VẬN CHUYỂN', 'color' => '#595959'],
        ];
        $pages = [];
        $last = $orders->count() - 1;
        foreach ($orders->values() as $idx => $order) {
            /** @var Order $order */
            $addr = (array) ($order->shipping_address ?? []);
            $name = $addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? '—';
            $phone = $addr['phone'] ?? '—';
            $detail = trim((string) ($addr['line1'] ?? $addr['address'] ?? ''));
            $admin = trim(implode(', ', array_filter([$addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null])));
            $sh = $order->relationLoaded('shipments') ? $order->shipments->first(fn ($x) => $x->status !== 'cancelled') : null;

            // Item list — compact "• Tên × SL" thay vì bảng giá đầy đủ. Gộp theo tên (case-insensitive).
            $grouped = [];
            $totalQty = 0;
            foreach ($order->items as $it) {
                [$skuCode, $prodName] = self::lineSkuAndName($it, $skuById);
                $variationSuffix = $it->variation ? ' — '.$it->variation : '';
                $displayName = trim($prodName.$variationSuffix);
                $key = mb_strtolower($displayName);
                $grouped[$key] ??= ['name' => $displayName, 'sku' => $skuCode, 'qty' => 0];
                $grouped[$key]['qty'] += (int) $it->quantity;
                $totalQty += (int) $it->quantity;
            }
            $itemsHtml = '';
            foreach ($grouped as $g) {
                $itemsHtml .= '<li><span class="li-name">'.self::e($g['name']).'</span>'
                    .($g['sku'] ? ' <span class="li-sku">['.self::e($g['sku']).']</span>' : '')
                    .' <span class="li-qty">× '.$g['qty'].'</span></li>';
            }

            $code = $order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey());
            $tracking = $sh?->tracking_no ?: '';
            $service = (string) ($sh?->service ?: '');
            $carrierCode = strtolower((string) ($sh?->carrier ?? $order->carrier ?? 'manual'));
            // Đơn manual đã đẩy ĐVVC ⇒ shipment.carrier = 'manual_ghn' / 'manual_ghtk'... — strip prefix.
            $carrierKey = preg_replace('/^manual_/', '', $carrierCode);
            $cMeta = $carrierMeta[$carrierKey] ?? ['name' => strtoupper($carrierKey) ?: 'ĐVVC', 'full' => strtoupper($carrierKey) ?: 'ĐVVC', 'color' => '#595959'];

            // Header band — ĐVVC + service (lớn, banner màu carrier).
            $headerBand = '<div class="band" style="background:'.$cMeta['color'].'">'
                .'<div class="band-l"><div class="band-carrier">'.self::e($cMeta['full']).'</div>'
                .($service !== '' ? '<div class="band-svc">Dịch vụ: '.self::e($service).'</div>' : '<div class="band-svc">Phiếu giao hàng</div>')
                .'</div>'
                .'<div class="band-r"><div class="band-code">'.self::e($code).'</div><div class="band-date">'.now()->format('d/m/Y H:i').'</div></div>'
                .'</div>';

            // Barcode block — to + QR. Khi thiếu tracking, hiện banner cảnh báo (đơn chưa "Chuẩn bị hàng").
            $hasTracking = $tracking !== '';
            $barcodeBlock = $hasTracking
                ? '<div class="bc">'
                    .'<div class="bc-bar"><img src="'.$br->code128SvgDataUrl($tracking, 2, 56).'" alt="barcode" style="height:54px;width:100%;display:block"/>'
                    .'<div class="bc-text">'.self::e($tracking).'</div></div>'
                    .'<div class="bc-qr"><img src="'.$br->qrSvgDataUrl($tracking, 96).'" alt="QR" style="width:80px;height:80px;display:block"/></div>'
                .'</div>'
                : '<div class="bc-empty">⚠ Chưa có mã vận đơn — bấm "Chuẩn bị hàng" để lấy mã từ '.self::e($cMeta['name']).'.</div>';

            // COD + weight + qty info row.
            $codTotal = $order->is_cod ? max(0, (int) ($order->cod_amount ?: ((int) $order->grand_total - (int) ($order->prepaid_amount ?? 0)))) : 0;
            $weightG = $sh && $sh->weight_grams ? (int) $sh->weight_grams : null;
            $weightStr = $weightG ? ($weightG >= 1000 ? number_format($weightG / 1000, 1).' kg' : $weightG.' g') : '—';
            $infoRow = '<div class="info">'
                .'<div class="info-cell '.($order->is_cod ? 'is-cod' : 'is-cod-zero').'">'
                    .'<div class="info-lbl">'.($order->is_cod ? 'THU HỘ (COD)' : 'KHÔNG COD').'</div>'
                    .'<div class="info-val">'.self::vnd($codTotal).' đ</div>'
                .'</div>'
                .'<div class="info-cell"><div class="info-lbl">KHỐI LƯỢNG</div><div class="info-val">'.self::e($weightStr).'</div></div>'
                .'<div class="info-cell"><div class="info-lbl">SỐ MÓN</div><div class="info-val">'.$totalQty.'</div></div>'
                .'</div>';

            // Print note.
            $printNote = (string) (data_get($order->meta, 'print_note') ?: '');
            $noteBlock = $printNote !== ''
                ? '<div class="note"><b>Ghi chú:</b> '.nl2br(self::e($printNote)).'</div>'
                : '';

            $page = '<div class="slip">'
                .$headerBand
                .$barcodeBlock
                .'<div class="party">'
                    .'<div class="party-lbl">Từ</div>'
                    .'<div class="party-body"><b class="party-name">'.self::e($shopName).'</b></div>'
                .'</div>'
                .'<div class="party party-to">'
                    .'<div class="party-lbl">Đến</div>'
                    .'<div class="party-body">'
                        .'<div class="party-name">'.self::e($name).' &nbsp;·&nbsp; '.self::e($phone).'</div>'
                        .($detail !== '' ? '<div class="party-addr">'.self::e($detail).'</div>' : '')
                        .($admin !== '' ? '<div class="party-admin">'.self::e($admin).'</div>' : '')
                    .'</div>'
                .'</div>'
                .$infoRow
                .'<div class="items">'
                    .'<div class="items-lbl">Hàng hoá ('.count($grouped).' loại / '.$totalQty.' món)</div>'
                    .'<ul>'.$itemsHtml.'</ul>'
                .'</div>'
                .$noteBlock
                .'</div>';
            $pages[] = $idx < $last ? $page.'<div class="page-break"></div>' : $page;
        }

        $css = <<<'CSS'
            @page{__PAGE__}
            *{font-family:DejaVu Sans,Arial,sans-serif;box-sizing:border-box}
            body{font-size:11px;color:#1a1a1a;margin:0;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .slip{padding:0}

            /* Top band — carrier + service (full-bleed coloured banner) */
            .band{display:flex;justify-content:space-between;align-items:center;color:#fff;padding:6px 10px}
            .band-l .band-carrier{font-size:16px;font-weight:800;letter-spacing:0.5px;line-height:1.1}
            .band-l .band-svc{font-size:10px;opacity:.92;margin-top:1px;letter-spacing:.3px}
            .band-r{text-align:right}
            .band-r .band-code{font-size:13px;font-weight:700;letter-spacing:.4px}
            .band-r .band-date{font-size:10px;opacity:.85;margin-top:1px}

            /* Barcode block — barcode chiếm rộng, QR bên phải */
            .bc{display:flex;gap:8px;align-items:center;padding:8px 10px;border-bottom:1px dashed #d9d9d9}
            .bc-bar{flex:1;min-width:0}
            .bc-bar .bc-text{font-family:'DejaVu Sans Mono',monospace;font-size:13px;font-weight:700;letter-spacing:1px;text-align:center;margin-top:2px}
            .bc-qr{flex:0 0 80px}
            .bc-empty{padding:10px;background:#fffbe6;border-bottom:1px dashed #ffc53d;color:#874d00;font-size:11px;text-align:center}

            /* Party block (From / To) */
            .party{display:flex;align-items:flex-start;padding:6px 10px;border-bottom:1px dashed #e8e8e8}
            .party-lbl{flex:0 0 32px;font-size:9px;font-weight:700;color:#888;letter-spacing:1px;text-transform:uppercase;padding-top:2px}
            .party-body{flex:1;line-height:1.4;min-width:0}
            .party-name{font-size:11.5px;font-weight:600;color:#1a1a1a;display:block;word-wrap:break-word}
            .party-to{padding:8px 10px;background:#fafafa}
            .party-to .party-lbl{color:#cf1322}
            .party-to .party-name{font-size:13.5px;font-weight:800}
            .party-addr{font-size:12px;margin-top:2px;color:#222;line-height:1.4;word-wrap:break-word}
            .party-admin{font-size:11.5px;color:#444;margin-top:1px;font-weight:600;word-wrap:break-word}

            /* Info row — COD, weight, qty */
            .info{display:flex;border-bottom:1px dashed #e8e8e8}
            .info-cell{flex:1;padding:6px 8px;text-align:center;border-right:1px solid #f0f0f0}
            .info-cell:last-child{border-right:0}
            .info-cell .info-lbl{font-size:8.5px;color:#888;letter-spacing:1px;font-weight:600;text-transform:uppercase}
            .info-cell .info-val{font-size:13px;font-weight:700;margin-top:1px;letter-spacing:.2px}
            .info-cell.is-cod{background:#fff1f0}
            .info-cell.is-cod .info-lbl{color:#cf1322}
            .info-cell.is-cod .info-val{color:#cf1322;font-size:15px;font-weight:800}
            .info-cell.is-cod-zero .info-lbl{color:#888}
            .info-cell.is-cod-zero .info-val{color:#999;font-size:11px;font-weight:600}

            /* Items list — compact bullet list, not invoice table */
            .items{padding:6px 10px;border-bottom:1px dashed #e8e8e8}
            .items-lbl{font-size:9px;color:#888;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:3px}
            .items ul{list-style:none;margin:0;padding:0;font-size:11px;line-height:1.5}
            .items li{display:flex;padding:1px 0}
            .items li .li-name{flex:1;min-width:0}
            .items li .li-sku{color:#888;font-size:10px;font-family:'DejaVu Sans Mono',monospace}
            .items li .li-qty{font-weight:700;margin-left:6px;color:#1a1a1a;flex:0 0 auto}

            /* Note (optional) */
            .note{padding:6px 10px;background:#fffbe6;font-size:10.5px;line-height:1.4;border-top:1px dashed #ffe58f}

            .page-break{page-break-after:always}
        CSS;
        $rule = self::paperRule($paper);
        $cssFinal = str_replace('__PAGE__', $rule, $css);

        return '<!doctype html><html><head><meta charset="utf-8"><title>Phiếu giao hàng</title><style>'.$cssFinal.'</style></head><body>'.implode('', $pages).'</body></html>';
    }
}
