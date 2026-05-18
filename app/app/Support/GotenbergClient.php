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
        // Spec 2026-05-17 — super-admin override qua /admin/settings (pdf.gotenberg_url).
        $configured = (string) system_setting('pdf.gotenberg_url', config('fulfillment.gotenberg_url'));

        return rtrim($this->baseUrl ?: $configured, '/').$path;
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
     * Render a shipping label / delivery slip — honors the @page CSS size from the template
     * shell AND forces all 4 margins to 0. Gotenberg defaults to Letter + 0.39in margins
     * which silently override @page CSS (preferCssPageSize must be 'true'), shifting all
     * absolute-positioned fields inward by ~10mm and clipping content near the right edge.
     */
    public function htmlToLabelPdf(string $html): string
    {
        return $this->htmlToPdf($html, [
            'preferCssPageSize' => 'true',
            'marginTop' => '0',
            'marginBottom' => '0',
            'marginLeft' => '0',
            'marginRight' => '0',
            'printBackground' => 'true',
        ]);
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
