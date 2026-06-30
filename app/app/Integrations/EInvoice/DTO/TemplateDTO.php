<?php

namespace CMBcoreSeller\Integrations\EInvoice\DTO;

/** Một mẫu hóa đơn (InvoiceTemplate). */
final class TemplateDTO
{
    public function __construct(
        public readonly string $templateId,
        public readonly string $templateName,
        public readonly string $invSeries,
        public readonly int $invoiceType,
        public readonly bool $isPublished,
        public readonly bool $inactive,
    ) {}

    public static function fromMisa(array $raw): self
    {
        return new self(
            templateId: (string) ($raw['IPTemplateID'] ?? ''),
            templateName: (string) ($raw['TemplateName'] ?? ''),
            invSeries: (string) ($raw['InvSeries'] ?? ''),
            invoiceType: (int) ($raw['InvoiceType'] ?? 0),
            isPublished: (bool) ($raw['IsPublished'] ?? false),
            inactive: (bool) ($raw['Inactive'] ?? false),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'template_id' => $this->templateId,
            'template_name' => $this->templateName,
            'inv_series' => $this->invSeries,
            'invoice_type' => $this->invoiceType,
            'is_published' => $this->isPublished,
            'inactive' => $this->inactive,
        ];
    }
}
