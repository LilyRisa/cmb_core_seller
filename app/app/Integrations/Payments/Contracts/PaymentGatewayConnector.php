<?php

namespace CMBcoreSeller\Integrations\Payments\Contracts;

use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutSession;
use CMBcoreSeller\Integrations\Payments\DTO\PaymentNotification;
use CMBcoreSeller\Integrations\Payments\Exceptions\GatewayNotConfigured;
use CMBcoreSeller\Integrations\Payments\Exceptions\UnsupportedOperation;
use Illuminate\Http\Request;

/**
 * Hợp đồng mọi cổng thanh toán phải implement (SPEC 0018 §3).
 *
 * QUY TẮC VÀNG (như `ChannelConnector`/`CarrierConnector`):
 *   - Core không biết tên cụ thể của cổng thanh toán.
 *   - Thêm cổng mới = 1 class + 1 dòng register trong `PaymentRegistry`.
 *   - Không `if ($gateway === 'sepay')` trong module Billing.
 *
 * Một connector không hỗ trợ thao tác ⇒ ném {@see UnsupportedOperation}.
 * Caller phải kiểm `supports()` trước khi gọi optional methods.
 */
interface PaymentGatewayConnector
{
    /** Stable code: 'sepay' | 'vnpay' | 'momo' */
    public function code(): string;

    /** Hiển thị: 'SePay (Chuyển khoản tự động)', 'VNPay'... */
    public function displayName(): string;

    /**
     * Capability flags. Quy ước:
     *   - `checkout` (bắt buộc)
     *   - `webhook` (có verify webhook)
     *   - `refund` (hoàn tiền qua API)
     *   - `query` (có thể gọi query status đẩy)
     *
     * @return array<string, bool>
     */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    /** Loại UX: 'bank_transfer' (QR + memo) | 'redirect' (chuyển hướng tới gateway). */
    public function method(): string;

    /**
     * Tạo phiên thanh toán cho invoice. Trả về `CheckoutSession` mô tả cách user trả tiền.
     *
     * SePay: trả `qr_url`, `account_no`, `bank_code`, `memo`, `amount`.
     * VNPay: trả `redirect_url`.
     * MoMo: chưa hỗ trợ ⇒ ném {@see UnsupportedOperation}.
     */
    public function checkout(CheckoutRequest $request): CheckoutSession;

    /**
     * Verify chữ ký của webhook nhận về. Sai chữ ký ⇒ trả false (caller sẽ trả 401).
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse webhook đã verify thành `PaymentNotification` chuẩn (gateway, external_ref,
     * amount, status, reference, occurred_at, raw). Reference = invoice.code để khớp.
     */
    public function parseWebhook(Request $request): PaymentNotification;

    /**
     * Poll status (backup khi webhook không về). Connector không hỗ trợ ⇒ ném
     * {@see UnsupportedOperation}.
     */
    public function queryStatus(string $reference): PaymentNotification;

    /**
     * Ném {@see GatewayNotConfigured} khi credentials cần thiết không có trong env.
     * Caller có thể gọi sớm để fail-fast.
     */
    public function assertConfigured(): void;
}
