<?php

namespace CMBcoreSeller\Integrations\EInvoice\DTO;

/** Thông tin công ty trả về từ nhà cung cấp HĐĐT (GetCompanyInfo). */
final class CompanyInfoDTO
{
    public function __construct(
        public readonly string $companyName,
        public readonly string $taxCode,
        public readonly ?string $address,
        public readonly bool $isInvoiceWithCode,
        public readonly ?string $email = null,
        public readonly ?string $bankAccount = null,
        public readonly ?string $bankName = null,
    ) {}

    public static function fromMisa(array $raw): self
    {
        return new self(
            companyName: (string) ($raw['CompanyName'] ?? ''),
            taxCode: (string) ($raw['CompanyTaxCode'] ?? ''),
            address: $raw['CompanyAddress'] ?? null,
            isInvoiceWithCode: (bool) ($raw['IsInvoiceWithCode'] ?? false),
            email: $raw['CompanyEmail'] ?? null,
            bankAccount: $raw['BankAccount'] ?? null,
            bankName: $raw['BankName'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'tax_code' => $this->taxCode,
            'address' => $this->address,
            'is_invoice_with_code' => $this->isInvoiceWithCode,
            'email' => $this->email,
            'bank_account' => $this->bankAccount,
            'bank_name' => $this->bankName,
        ];
    }
}
