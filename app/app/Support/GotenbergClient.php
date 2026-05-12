<?php

namespace CMBcoreSeller\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal client for Gotenberg (https://gotenberg.dev) — used to render picking/packing
 * list HTML to PDF and to merge shipping-label PDFs into one file. Base URL from
 * config('fulfillment.gotenberg_url') (service `gotenberg` in the Docker stack).
 */
class GotenbergClient
{
    public function __construct(private readonly ?string $baseUrl = null) {}

    private function url(string $path): string
    {
        return rtrim($this->baseUrl ?: (string) config('fulfillment.gotenberg_url'), '/').$path;
    }

    /** Render an HTML document to a PDF (Chromium engine). Returns the PDF bytes. */
    public function htmlToPdf(string $html, array $options = []): string
    {
        $req = Http::timeout(60)->attach('files', $html, 'index.html', ['Content-Type' => 'text/html']);
        foreach ($options as $k => $v) {
            $req = $req->attach($k, (string) $v);
        }
        $res = $req->post($this->url('/forms/chromium/convert/html'));
        if (! $res->successful()) {
            throw new RuntimeException('Gotenberg render HTML lỗi: '.$res->status().' '.$res->body());
        }

        return $res->body();
    }

    /**
     * Merge several PDF byte-strings into one PDF (order preserved).
     *
     * @param  list<string>  $pdfContents
     */
    public function mergePdfs(array $pdfContents): string
    {
        if ($pdfContents === []) {
            throw new RuntimeException('Không có tệp PDF nào để ghép.');
        }
        if (count($pdfContents) === 1) {
            return $pdfContents[0];
        }
        $req = Http::timeout(60);
        foreach ($pdfContents as $i => $bytes) {
            // numeric prefix keeps Gotenberg's alphabetical merge order = our order
            $req = $req->attach('files', $bytes, sprintf('%04d.pdf', $i + 1), ['Content-Type' => 'application/pdf']);
        }
        $res = $req->post($this->url('/forms/pdfengines/merge'));
        if (! $res->successful()) {
            throw new RuntimeException('Gotenberg ghép PDF lỗi: '.$res->status().' '.$res->body());
        }

        return $res->body();
    }
}
