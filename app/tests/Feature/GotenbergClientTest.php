<?php

namespace Tests\Feature;

use CMBcoreSeller\Support\GotenbergClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Two regressions guarded here:
 *
 * 1) "A 'contents' key is required" — PendingRequest::attach() pipes each part through
 *    array_filter() (no callback), which strips any value PHP considers falsy. The string
 *    '0' is falsy, so marginTop='0' (etc.) used to crash inside Guzzle's MultipartStream
 *    before the request was sent. htmlToPdf now builds the multipart array by hand.
 *
 * 2) "415 Invalid Content-Type header value: want multipart/form-data" — building the
 *    multipart by hand only works if the PendingRequest bodyFormat is also flipped to
 *    'multipart' (via asMultipart()). Without it, bodyFormat stays at the default 'json',
 *    parseHttpOptions injects json=null, and Guzzle then overwrites the multipart body
 *    with the literal JSON "null" and sets Content-Type: application/json. Gotenberg
 *    rejects with 415. Http::fake() short-circuits before Guzzle's option resolution so
 *    the bug is invisible to fake-only tests — we therefore assert on the Request shape
 *    that Laravel hands to Guzzle.
 */
class GotenbergClientTest extends TestCase
{
    public function test_html_to_label_pdf_sends_multipart_request_with_zero_margins_preserved(): void
    {
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('PDF-OK', 200),
        ]);

        $bytes = app(GotenbergClient::class)->htmlToLabelPdf('<html><body>hi</body></html>');
        $this->assertSame('PDF-OK', $bytes);

        // $request->data() returns the body for whichever bodyFormat is set on the
        // PendingRequest. If asMultipart() is missing, bodyFormat stays 'json' and
        // data() returns the (null) JSON body instead of the multipart parts —
        // exactly the broken state that triggers the 415 against Gotenberg.
        Http::assertSent(function (Request $request) {
            $parts = $request->data();
            if (! is_array($parts) || $parts === []) {
                return false;
            }
            $byName = collect($parts)->keyBy(fn ($p) => $p['name'] ?? null);
            $files = $byName->get('files');
            $marginTop = $byName->get('marginTop');

            return is_array($files)
                && ($files['contents'] ?? null) === '<html><body>hi</body></html>'
                && ($files['filename'] ?? null) === 'index.html'
                && is_array($marginTop)
                && ($marginTop['contents'] ?? null) === '0' // string '0' must survive — was dropped by attach()/array_filter
                && ($byName->get('preferCssPageSize')['contents'] ?? null) === 'true';
        });
    }

    public function test_html_to_pdf_propagates_gotenberg_error(): void
    {
        Http::fake(['*' => Http::response('boom', 502)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Gotenberg render HTML lỗi: 502/');
        app(GotenbergClient::class)->htmlToPdf('<html></html>');
    }
}
