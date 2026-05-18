<?php

namespace Tests\Feature;

use CMBcoreSeller\Support\GotenbergClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression for "A 'contents' key is required" — Laravel's PendingRequest::attach
 * pipes the multipart element through array_filter() (no callback), which strips any
 * value PHP considers falsy. The string '0' is falsy, so passing marginTop='0' (etc.)
 * to Gotenberg crashed inside Guzzle's MultipartStream before the request was sent.
 *
 * htmlToPdf now builds the multipart array by hand and hands it to Http::withOptions().
 */
class GotenbergClientTest extends TestCase
{
    public function test_html_to_label_pdf_sends_zero_margins_without_array_filter_dropping_contents(): void
    {
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('PDF-OK', 200),
        ]);

        // Would throw "A 'contents' key is required" before the fix because the '0' values
        // pass through Laravel attach() → array_filter() → drop the 'contents' key, then
        // Guzzle's MultipartStream balks while serialising the request body. With the fix
        // (multipart built by hand and passed via Http::withOptions), the request goes out
        // cleanly and returns the faked body.
        $bytes = app(GotenbergClient::class)->htmlToLabelPdf('<html><body>hi</body></html>');
        $this->assertSame('PDF-OK', $bytes);
        Http::assertSentCount(1);
    }

    public function test_html_to_pdf_propagates_gotenberg_error(): void
    {
        Http::fake(['*' => Http::response('boom', 502)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Gotenberg render HTML lỗi: 502/');
        app(GotenbergClient::class)->htmlToPdf('<html></html>');
    }
}
