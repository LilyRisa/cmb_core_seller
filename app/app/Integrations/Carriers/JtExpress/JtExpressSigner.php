<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

/**
 * Ký request J&T Express Open API. Theo tài liệu (open.jtexpress.vn/apiDoc): "digest=base64(md5(business
 * params Json+privateKey))" — MD5 trước ra mảng byte, base64-encode mảng byte đó (KHÔNG phải base64 của
 * chuỗi hex). Cách nối `privateKey` (cuối chuỗi JSON hay field riêng) KHÔNG được tài liệu J&T xác nhận rõ —
 * implement literal theo mô tả, đánh dấu CHƯA VERIFY với tài khoản UAT thật (SPEC 0042 §11). Đây là NƠI DUY
 * NHẤT cần sửa nếu có tài khoản thật để xác nhận công thức `digest` khác.
 */
final class JtExpressSigner
{
    /** Salt cố định J&T dùng để hash password merchant — xem {@see hashPassword()}. */
    private const PASSWORD_SALT = 'jadada369t3';

    public static function sign(string $bizContentJson, string $privateKey): string
    {
        return base64_encode(md5($bizContentJson.$privateKey, true));
    }

    /**
     * Hash password merchant (`customerCode`/`password`) TRƯỚC khi gửi trong `bizContent` — J&T không
     * nhận plaintext. Công thức đã XÁC NHẬN THẬT 2026-07-20 qua open.jtexpress.vn/helpCenter →
     * Authentication Tools: trang tự cho ví dụ customerCode=`084LC02438`, password hồ sơ (raw, Sales/
     * Network cấp)=`KGC6jju1` ⇒ `MD5_UPPERCASE(KGC6jju1 + 'jadada369t3')` = `4AE2DBF6527EA7C49C59EFF24F6FEA71`,
     * khớp đúng giá trị trang liệt kê là "test parameters required: password". Xem
     * `docs/superpowers/research/2026-07-17-jt-express-api-reference.md` §1.4.
     */
    public static function hashPassword(string $rawPassword): string
    {
        return strtoupper(md5($rawPassword.self::PASSWORD_SALT));
    }
}
