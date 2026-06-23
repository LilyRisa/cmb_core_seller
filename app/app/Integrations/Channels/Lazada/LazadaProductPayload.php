<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Builds the Lazada CreateProduct XML payload string.
 *
 * Root structure: <Request><Product>…</Product></Request>
 *
 * Key correctness points from Lazada docs:
 *   - Images.Image and Skus.Sku must be array-style collections to avoid error 1001.
 *   - Values are XML-escaped safely via DOMDocument text nodes (no raw htmlspecialchars).
 *   - package_dims unit: cm; package_weight unit: kg.
 *   - saleProp holds per-SKU variant attributes.
 */
final class LazadaProductPayload
{
    public static function toXml(ListingDraftDTO $d, ?string $videoId = null): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $req = $dom->createElement('Request');
        $dom->appendChild($req);

        $p = $dom->createElement('Product');
        $req->appendChild($p);

        // Category
        self::child($dom, $p, 'PrimaryCategory', $d->categoryId);

        // Images — always array-style (avoids Lazada error 1001)
        $imgs = $dom->createElement('Images');
        foreach ($d->media as $m) {
            self::child($dom, $imgs, 'Image', $m->ref);
        }
        $p->appendChild($imgs);

        // Attributes
        $attr = $dom->createElement('Attributes');
        self::child($dom, $attr, 'name', $d->title);
        self::child($dom, $attr, 'description', $d->description);
        if ($d->shortDescription !== null && $d->shortDescription !== '') {
            self::child($dom, $attr, 'short_description', $d->shortDescription);
        }
        self::child($dom, $attr, 'brand_id', (string) $d->brandId);
        if ($videoId !== null && $videoId !== '') {
            self::child($dom, $attr, 'video', $videoId);
        }
        foreach ($d->attributes as $k => $v) {
            // Bỏ khóa nghiệp vụ nội bộ (đã xử lý riêng / không phải attribute của sàn).
            if (in_array($k, ['description', 'video_url'], true)) {
                continue;
            }
            if (is_array($v)) {
                // Thuộc tính đa chọn (multiSelect/multiEnumInput): gộp các giá trị thành
                // chuỗi phân tách bằng dấu phẩy (định dạng phẳng như ví dụ CreateProduct
                // chính chủ) — KHÔNG bỏ âm thầm như trước (gây thiếu thuộc tính bắt buộc).
                $vals = array_map(
                    fn ($x) => is_array($x) ? (string) ($x['name'] ?? $x['value'] ?? $x['id'] ?? '') : (string) $x,
                    $v,
                );
                $v = implode(',', array_filter($vals, fn ($s) => $s !== ''));
                if ($v === '') {
                    continue;
                }
            }
            self::child($dom, $attr, $k, (string) $v);
        }
        $p->appendChild($attr);

        // Skus — always array-style (avoids Lazada error 1001)
        $skus = $dom->createElement('Skus');
        foreach ($d->skus as $s) {
            $sku = $dom->createElement('Sku');
            self::child($dom, $sku, 'SellerSku', (string) $s['seller_sku']);
            self::child($dom, $sku, 'price', (string) $s['price']);
            self::child($dom, $sku, 'quantity', (string) $s['stock']);

            // saleProp giữ thuộc tính biến thể (vd color_family, size). Sản phẩm KHÔNG biến thể
            // (1 SKU) ⇒ sale_props rỗng ⇒ BỎ HẲN thẻ saleProp (Lazada chỉ bắt buộc khi có biến thể;
            // saleProp rỗng/khóa lạ gây BIZ_CHECK_SALEPROP_ATTRIBUTE_INVALID).
            if (! empty($s['sale_props'])) {
                $sp = $dom->createElement('saleProp');
                foreach ($s['sale_props'] as $k => $v) {
                    self::child($dom, $sp, $k, (string) $v);
                }
                $sku->appendChild($sp);
            }

            // Ảnh riêng của SKU — Lazada gắn ảnh ở CẤP SKU (<Sku><Images><Image>). URL phải đã
            // upload lên Lazada (cấm link ngoài — BIZ_CHECK_EXIST_OUTER_IMAGE). Chỉ thêm khi có ảnh.
            $img = trim((string) ($s['image'] ?? ''));
            if ($img !== '') {
                $imagesEl = $dom->createElement('Images');
                self::child($dom, $imagesEl, 'Image', $img);
                $sku->appendChild($imagesEl);
            }

            // Package dimensions (unit: cm)
            self::child($dom, $sku, 'package_height', (string) $s['package_dims']['height']);
            self::child($dom, $sku, 'package_length', (string) $s['package_dims']['length']);
            self::child($dom, $sku, 'package_width', (string) $s['package_dims']['width']);

            // Package weight (unit: kg)
            self::child($dom, $sku, 'package_weight', (string) $s['package_weight']);

            $skus->appendChild($sku);
        }
        $p->appendChild($skus);

        $result = $dom->saveXML($dom->documentElement);

        // saveXML() returns false only on catastrophic DOM failure — treat as string
        return $result === false ? '' : $result;
    }

    /**
     * Append a child element with a text node to $parent.
     * Using createTextNode ensures special chars (&, <, >, etc.) are properly escaped.
     */
    private static function child(\DOMDocument $dom, \DOMElement $parent, string $tag, string $val): void
    {
        $el = $dom->createElement($tag);
        $el->appendChild($dom->createTextNode($val));
        $parent->appendChild($el);
    }
}
