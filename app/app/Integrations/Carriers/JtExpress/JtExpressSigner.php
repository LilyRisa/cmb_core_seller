<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

/**
 * Ký request J&T Express Open API. Theo tài liệu (open.jtexpress.vn/apiDoc): "digest=base64(md5(business
 * params Json+privateKey))" — MD5 trước ra mảng byte, base64-encode mảng byte đó (KHÔNG phải base64 của
 * chuỗi hex). Cách nối `privateKey` (cuối chuỗi JSON hay field riêng) và cách encode `password` bên trong
 * `bizContent` KHÔNG được tài liệu J&T xác nhận rõ — implement literal theo mô tả, đánh dấu CHƯA VERIFY với
 * tài khoản UAT thật (SPEC 0042 §11). Đây là NƠI DUY NHẤT cần sửa nếu có tài khoản thật để xác nhận công
 * thức khác.
 */
final class JtExpressSigner
{
    public static function sign(string $bizContentJson, string $privateKey): string
    {
        return base64_encode(md5($bizContentJson.$privateKey, true));
    }
}
