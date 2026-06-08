<?php

namespace CMBcoreSeller\Support;

/**
 * Sanitize HTML do super-admin soạn (TipTap) trước khi lưu/hiển thị (SPEC 0037).
 * Allowlist thẻ + thuộc tính; loại bỏ script/iframe/style/event-handler/scheme nguy hiểm
 * (javascript:, data:…). Thẻ lạ NHƯNG vô hại ⇒ "unwrap" (giữ nội dung con đã sanitize),
 * thẻ nguy hiểm ⇒ xoá cả nội dung. Phòng thủ nhiều lớp dù tác giả là admin tin cậy.
 */
class HtmlSanitizer
{
    /** Thẻ cho phép → danh sách thuộc tính cho phép. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'span' => [], 'div' => [], 'hr' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'video' => ['src', 'controls', 'width', 'height', 'poster'],
        'source' => ['src', 'type'],
    ];

    /** Thẻ xoá CẢ nội dung (không unwrap). */
    private const DANGEROUS = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta'];

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // Bọc trong #root + ép UTF-8; NOIMPLIED/NODEFDTD để không tự thêm <html><body>.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="cmb-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('cmb-root');
        if (! $root instanceof \DOMElement) {
            return '';
        }

        $this->sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private function sanitizeChildren(\DOMElement $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof \DOMComment) {
                $node->parentNode?->removeChild($node);

                continue;
            }
            if (! $node instanceof \DOMElement) {
                continue; // text node — giữ nguyên
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DANGEROUS, true)) {
                $node->parentNode?->removeChild($node); // xoá cả nội dung

                continue;
            }

            // Luôn sanitize nội dung con TRƯỚC (để nội dung được unwrap cũng đã sạch).
            $this->sanitizeChildren($node);

            if (! isset(self::ALLOWED[$tag])) {
                $this->unwrap($node); // thẻ lạ vô hại ⇒ giữ con, bỏ thẻ

                continue;
            }

            $this->stripAttributes($node, self::ALLOWED[$tag]);
        }
    }

    /** @param list<string> $allowed */
    private function stripAttributes(\DOMElement $el, array $allowed): void
    {
        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $name = strtolower($attr->name);
            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);

                continue;
            }
            if (in_array($name, ['href', 'src', 'poster'], true) && ! $this->safeUrl($attr->value)) {
                $el->removeAttribute($attr->name);
            }
        }
    }

    /** Chỉ cho phép http(s), mailto, hoặc đường dẫn gốc-tương đối; chặn javascript:/data:… */
    private function safeUrl(string $url): bool
    {
        $u = trim($url);

        return $u !== '' && (bool) preg_match('#^(https?:|mailto:|/)#i', $u);
    }

    /** Thay element bằng các node con của nó (giữ nội dung, bỏ thẻ bao). */
    private function unwrap(\DOMElement $el): void
    {
        $parent = $el->parentNode;
        if ($parent === null) {
            return;
        }
        while ($el->firstChild !== null) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
