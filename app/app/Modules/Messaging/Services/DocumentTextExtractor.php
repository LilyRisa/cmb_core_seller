<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Trích text thuần từ tài liệu cho AI training (RAG). Route theo phần mở rộng:
 *   - txt/md/log : text UTF-8 nguyên bản
 *   - csv/tsv    : parse bảng → "ô | ô | ô" mỗi dòng (giữ ngữ cảnh hàng/cột)
 *   - docx       : đọc word/document.xml trong zip → strip tag (mỗi <w:p> = 1 đoạn)
 *   - xlsx       : đọc sharedStrings + sheet trong zip → strip tag
 *   - pdf        : smalot/pdfparser; thiếu lib ⇒ ném `pdf_parser_unavailable`
 *
 * Trả `null` khi không trích được (binary/không hỗ trợ) ⇒ caller (IndexKnowledgeDoc)
 * đánh document `failed`. Mọi định dạng zip-based dùng `ext-zip` (đã bật).
 */
class DocumentTextExtractor
{
    /** Map nội dung file → text theo `$extension`. Null = không trích được. */
    public function extract(string $contents, string $extension): ?string
    {
        return match (strtolower(trim($extension))) {
            'csv' => $this->fromDelimited($contents, ','),
            'tsv' => $this->fromDelimited($contents, "\t"),
            'docx' => $this->fromDocx($contents),
            'xlsx' => $this->fromXlsx($contents),
            'pdf' => $this->fromPdf($contents),
            // txt/md/log + mặc định: thử coi như text UTF-8; binary ⇒ null.
            default => $this->fromPlainText($contents),
        };
    }

    /** Trích spreadsheet ID từ link Google Sheets bất kỳ. Null nếu không phải Sheets. */
    public static function googleSheetId(string $url): ?string
    {
        return preg_match('#^https?://docs\.google\.com/spreadsheets/d/([A-Za-z0-9_-]+)#', $url, $m)
            ? $m[1]
            : null;
    }

    /** gid (tab) từ link nếu có. */
    private static function googleSheetGid(string $url): ?string
    {
        return preg_match('/[#?&]gid=([0-9]+)/', $url, $g) ? $g[1] : null;
    }

    /**
     * Link Google Sheets công khai → URL gviz JSON (đầu ra UTF-8 chuẩn, structured —
     * không lo CSV escaping / xuống dòng trong ô). Null nếu không phải Sheets.
     * `…/spreadsheets/d/<ID>/gviz/tq?tqx=out:json[&gid=<GID>]`.
     */
    public static function googleSheetGvizUrl(string $url): ?string
    {
        $id = self::googleSheetId($url);
        if ($id === null) {
            return null;
        }
        $u = "https://docs.google.com/spreadsheets/d/{$id}/gviz/tq?tqx=out:json";

        return ($gid = self::googleSheetGid($url)) !== null ? $u."&gid={$gid}" : $u;
    }

    /**
     * Link Google Sheets công khai → URL export CSV (fallback khi gviz lỗi).
     * `…/spreadsheets/d/<ID>/export?format=csv[&gid=<GID>]`.
     */
    public static function googleSheetCsvUrl(string $url): ?string
    {
        $id = self::googleSheetId($url);
        if ($id === null) {
            return null;
        }
        $export = "https://docs.google.com/spreadsheets/d/{$id}/export?format=csv";

        return ($gid = self::googleSheetGid($url)) !== null ? $export."&gid={$gid}" : $export;
    }

    /**
     * Parse phản hồi gviz JSON của Google Sheets → bảng "ô | ô | ô" mỗi dòng (như CSV).
     *
     * Body gviz bọc trong `/*O_o*​/\ngoogle.visualization.Query.setResponse({...});` —
     * bóc lớp vỏ rồi json_decode. Mỗi `row.c[].v` là giá trị ô (null = ô trống). JSON
     * luôn UTF-8 nên tiếng Việt an toàn (không lo encoding/escaping như CSV). Null nếu
     * không phải JSON gviz hợp lệ / không có dòng nào.
     */
    public function fromGvizJson(string $contents): ?string
    {
        $contents = $this->stripBom($contents);
        // Bóc vỏ: lấy phần trong setResponse( ... ). Regex non-greedy tới dấu ) ; cuối.
        if (! preg_match('/setResponse\((.*)\)\s*;?\s*$/s', trim($contents), $m)) {
            return null;
        }
        $data = json_decode($m[1], true);
        if (! is_array($data) || ! isset($data['table']['rows']) || ! is_array($data['table']['rows'])) {
            return null;
        }

        $lines = [];
        foreach ($data['table']['rows'] as $row) {
            $cells = [];
            foreach ((array) ($row['c'] ?? []) as $cell) {
                // `v` có thể là string/number/bool/null. Lấy formatted `f` nếu có (số/ngày
                // đã format theo locale), không thì `v`. Xuống dòng trong ô → khoảng trắng.
                $val = $cell['f'] ?? $cell['v'] ?? '';
                $cells[] = trim(str_replace(["\r\n", "\n", "\r"], ' ', (string) $val));
            }
            if (implode('', $cells) === '') {
                continue; // bỏ dòng rỗng
            }
            $lines[] = implode(' | ', $cells);
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function fromPlainText(string $contents): ?string
    {
        $contents = $this->stripBom($contents);

        return mb_check_encoding($contents, 'UTF-8') && trim($contents) !== '' ? $contents : null;
    }

    private function fromDelimited(string $contents, string $separator): ?string
    {
        $contents = $this->stripBom($contents);
        if (! mb_check_encoding($contents, 'UTF-8')) {
            return null;
        }

        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return null;
        }
        fwrite($fh, $contents);
        rewind($fh);

        $lines = [];
        while (($cells = fgetcsv($fh, 0, $separator, '"', '\\')) !== false) {
            $cells = array_map(fn ($c) => trim((string) $c), $cells);
            if (implode('', $cells) === '') {
                continue; // bỏ dòng rỗng
            }
            $lines[] = implode(' | ', $cells);
        }
        fclose($fh);

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function fromDocx(string $contents): ?string
    {
        $xml = $this->readZipEntry($contents, fn (\ZipArchive $z) => $z->getFromName('word/document.xml'));
        if (! is_string($xml) || $xml === '') {
            return null;
        }
        // <w:tab/> → tab; mỗi đoạn <w:p>…</w:p> → newline; còn lại strip.
        $xml = preg_replace(['#<w:tab\b[^>]*/>#', '#</w:p>#'], ["\t", "\n"], $xml) ?? $xml;

        return $this->cleanXmlText($xml);
    }

    private function fromXlsx(string $contents): ?string
    {
        $text = $this->readZipEntry($contents, function (\ZipArchive $z): ?string {
            $parts = [];
            // Phần lớn text nằm ở sharedStrings; cộng thêm inline string trong từng sheet.
            $shared = $z->getFromName('xl/sharedStrings.xml');
            if (is_string($shared)) {
                $parts[] = $shared;
            }
            for ($i = 0; $i < $z->numFiles; $i++) {
                $name = (string) $z->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheet = $z->getFromIndex($i);
                    if (is_string($sheet)) {
                        $parts[] = $sheet;
                    }
                }
            }
            if ($parts === []) {
                return null;
            }
            // </t> → space để các ô không dính nhau khi strip tag.
            $joined = str_replace('</t>', '</t> ', implode(' ', $parts));

            return $this->cleanXmlText($joined);
        });

        return is_string($text) ? $text : null;
    }

    private function fromPdf(string $contents): ?string
    {
        if (! class_exists(PdfParser::class)) {
            throw new \RuntimeException('pdf_parser_unavailable');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'kpdf');
        if ($tmp === false) {
            return null;
        }
        try {
            file_put_contents($tmp, $contents);
            $text = trim((new PdfParser)->parseFile($tmp)->getText());

            return $text === '' ? null : $text;
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Ghi nội dung ra file tạm, mở zip, chạy `$fn($zip)`, đóng + xoá. Null nếu mở zip lỗi.
     */
    private function readZipEntry(string $contents, callable $fn): mixed
    {
        $tmp = tempnam(sys_get_temp_dir(), 'kzip');
        if ($tmp === false) {
            return null;
        }
        try {
            file_put_contents($tmp, $contents);
            $zip = new \ZipArchive;
            if ($zip->open($tmp) !== true) {
                return null;
            }
            try {
                return $fn($zip);
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($tmp);
        }
    }

    /** Strip tag XML/HTML → text, decode entity, gộp khoảng trắng. Null nếu rỗng. */
    private function cleanXmlText(string $xml): ?string
    {
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);

        return $text === '' ? null : $text;
    }

    private function stripBom(string $s): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $s) ?? $s;
    }
}
