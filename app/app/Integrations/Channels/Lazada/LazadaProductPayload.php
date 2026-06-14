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
            if (in_array($k, ['description', 'video_url'], true) || is_array($v)) {
                continue;
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

            // saleProp holds variant (sale) properties e.g. color_family, size
            $sp = $dom->createElement('saleProp');
            foreach (($s['sale_props'] ?? []) as $k => $v) {
                self::child($dom, $sp, $k, (string) $v);
            }
            $sku->appendChild($sp);

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
