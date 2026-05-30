<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\DocumentTextExtractor;
use Tests\TestCase;

/**
 * Trích text cho AI training (RAG) theo từng định dạng + nhận diện link Google Sheets.
 * Fixture docx/xlsx dựng tại chỗ bằng ZipArchive; pdf dựng tối giản (có xref) cho smalot.
 */
class DocumentTextExtractorTest extends TestCase
{
    private function ext(): DocumentTextExtractor
    {
        return new DocumentTextExtractor;
    }

    public function test_plain_text_returned_as_is(): void
    {
        $this->assertSame('Xin chào RAG', $this->ext()->extract('Xin chào RAG', 'txt'));
    }

    public function test_csv_parsed_into_rows(): void
    {
        $out = $this->ext()->extract("name,price\nÁo,100\nQuần,200", 'csv');

        $this->assertStringContainsString('name | price', (string) $out);
        $this->assertStringContainsString('Áo | 100', (string) $out);
        $this->assertStringContainsString('Quần | 200', (string) $out);
    }

    public function test_tsv_parsed_with_tab_separator(): void
    {
        $out = $this->ext()->extract("sku\tqty\nAT01\t3", 'tsv');

        $this->assertStringContainsString('sku | qty', (string) $out);
        $this->assertStringContainsString('AT01 | 3', (string) $out);
    }

    public function test_binary_content_returns_null(): void
    {
        // PNG header + byte cao ⇒ không phải UTF-8 ⇒ không trích được.
        $binary = "\x89PNG\r\n\x1a\n\xFF\xFE\x00\x01rubbish";

        $this->assertNull($this->ext()->extract($binary, 'png'));
    }

    public function test_docx_text_extracted(): void
    {
        $docXml = '<?xml version="1.0"?><w:document xmlns:w="x"><w:body>'
            .'<w:p><w:r><w:t>Chính sách đổi trả 7 ngày</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t>Hỗ trợ 24/7</w:t></w:r></w:p>'
            .'</w:body></w:document>';
        $bytes = $this->zipBytes(['word/document.xml' => $docXml]);

        $out = (string) $this->ext()->extract($bytes, 'docx');
        $this->assertStringContainsString('Chính sách đổi trả 7 ngày', $out);
        $this->assertStringContainsString('Hỗ trợ 24/7', $out);
    }

    public function test_xlsx_text_extracted_from_shared_strings(): void
    {
        $shared = '<?xml version="1.0"?><sst xmlns="x">'
            .'<si><t>SKU</t></si><si><t>Áo thun</t></si><si><t>Bảo hành</t></si></sst>';
        $sheet = '<?xml version="1.0"?><worksheet xmlns="x"><sheetData>'
            .'<row><c t="s"><v>0</v></c><c t="s"><v>1</v></c></row></sheetData></worksheet>';
        $bytes = $this->zipBytes([
            'xl/sharedStrings.xml' => $shared,
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);

        $out = (string) $this->ext()->extract($bytes, 'xlsx');
        $this->assertStringContainsString('SKU', $out);
        $this->assertStringContainsString('Áo thun', $out);
        $this->assertStringContainsString('Bảo hành', $out);
    }

    public function test_pdf_text_extracted(): void
    {
        $out = (string) $this->ext()->extract($this->buildPdf('Hello RAG PDF training'), 'pdf');

        $this->assertStringContainsString('Hello RAG PDF training', $out);
    }

    public function test_google_sheet_url_rewritten_to_csv_export(): void
    {
        $this->assertSame(
            'https://docs.google.com/spreadsheets/d/ABC123_-/export?format=csv&gid=42',
            DocumentTextExtractor::googleSheetCsvUrl('https://docs.google.com/spreadsheets/d/ABC123_-/edit#gid=42'),
        );
        // Không có gid ⇒ export sheet đầu.
        $this->assertSame(
            'https://docs.google.com/spreadsheets/d/XYZ/export?format=csv',
            DocumentTextExtractor::googleSheetCsvUrl('https://docs.google.com/spreadsheets/d/XYZ/edit'),
        );
    }

    public function test_non_google_sheet_url_returns_null(): void
    {
        $this->assertNull(DocumentTextExtractor::googleSheetCsvUrl('https://example.com/faq'));
        $this->assertNull(DocumentTextExtractor::googleSheetCsvUrl('https://docs.google.com/document/d/ABC/edit'));
    }

    public function test_google_sheet_id_and_gviz_url(): void
    {
        $this->assertSame('ABC123_-', DocumentTextExtractor::googleSheetId('https://docs.google.com/spreadsheets/d/ABC123_-/edit#gid=42'));
        $this->assertNull(DocumentTextExtractor::googleSheetId('https://example.com/x'));

        $this->assertSame(
            'https://docs.google.com/spreadsheets/d/ABC/gviz/tq?tqx=out:json&gid=7',
            DocumentTextExtractor::googleSheetGvizUrl('https://docs.google.com/spreadsheets/d/ABC/edit#gid=7'),
        );
        $this->assertSame(
            'https://docs.google.com/spreadsheets/d/XYZ/gviz/tq?tqx=out:json',
            DocumentTextExtractor::googleSheetGvizUrl('https://docs.google.com/spreadsheets/d/XYZ/edit'),
        );
    }

    public function test_from_gviz_json_parses_rows_utf8(): void
    {
        $body = "/*O_o*/\n".'google.visualization.Query.setResponse({"version":"0.6","status":"ok","table":{"cols":[],"rows":['
            .'{"c":[{"v":"Bộ chia AV"},{"v":"220k"},{"v":"Chia tín hiệu\nKhông cần điện"},{"v":"https://x.vn/a"}]},'
            .'{"c":[{"v":"Nguồn 12V10A"},{"v":"200k"},{"v":"Điện áp ra 12V"},{"v":null}]},'
            .'{"c":[{"v":null},{"v":null},{"v":null},{"v":null}]}'  // dòng rỗng → bỏ
            .']}});';

        $out = (new DocumentTextExtractor)->fromGvizJson($body);

        $this->assertNotNull($out);
        $lines = explode("\n", $out);
        $this->assertCount(2, $lines); // dòng rỗng bị loại
        $this->assertSame('Bộ chia AV | 220k | Chia tín hiệu Không cần điện | https://x.vn/a', $lines[0]);
        $this->assertSame('Nguồn 12V10A | 200k | Điện áp ra 12V | ', $lines[1]); // ô null cuối → rỗng (join ' | ')
        $this->assertTrue(mb_check_encoding($out, 'UTF-8'));
    }

    public function test_from_gviz_json_invalid_returns_null(): void
    {
        $this->assertNull((new DocumentTextExtractor)->fromGvizJson('not gviz at all'));
        $this->assertNull((new DocumentTextExtractor)->fromGvizJson('setResponse({"table":{"rows":[]}});'));
    }

    /** Dựng file zip (docx/xlsx) trong bộ nhớ từ các entry name→content. */
    private function zipBytes(array $entries): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ktest');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    /** PDF tối giản có xref + startxref để smalot/pdfparser đọc được. */
    private function buildPdf(string $text): string
    {
        $objs = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        ];
        $stream = "BT /F1 24 Tf 72 700 Td ({$text}) Tj ET";
        $objs[4] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
        $objs[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $count = count($objs) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
