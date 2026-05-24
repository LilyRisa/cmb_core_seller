<?php

namespace CMBcoreSeller\Modules\Fulfillment\Support;

use Throwable;

/**
 * Map exception khi xử lý đơn → kết quả per-đơn cho bulk action: phân loại
 * `skipped` (đơn không hợp lệ, bỏ qua êm) vs `error` (lỗi vận hành, nên thử lại),
 * kèm câu tiếng Việt thân thiện (`reason`) và chi tiết kỹ thuật (`technical`).
 *
 * `reason` LUÔN trả; `technical` do controller quyết định có lộ ra response hay không
 * (theo `system_setting('fulfillment.expose_technical_errors')`).
 */
class FulfillmentErrorMapper
{
    /** Các thông điệp "đơn không hợp lệ" ⇒ skipped (không phải lỗi vận hành). */
    private const SKIP_NEEDLES = [
        'đã huỷ' => 'Đơn đã huỷ — bỏ qua.',
        'đã bàn giao' => 'Đơn đã bàn giao trước đó — bỏ qua.',
        'đã được đóng gói' => 'Đơn đã đóng gói trước đó — bỏ qua.',
        'âm tồn' => 'Đơn có SKU âm tồn — bỏ qua, cần nhập thêm hàng.',
        'không có vận đơn' => 'Đơn chưa được chuẩn bị hàng — bỏ qua.',
    ];

    /** @return array{status:string,reason:string,technical:string} */
    public static function classify(Throwable $e): array
    {
        $msg = $e->getMessage();
        $lower = mb_strtolower($msg);
        foreach (self::SKIP_NEEDLES as $needle => $friendly) {
            if (str_contains($lower, $needle)) {
                return ['status' => 'skipped', 'reason' => $friendly, 'technical' => self::technical($e)];
            }
        }

        return ['status' => 'error', 'reason' => self::friendlyError($msg), 'technical' => self::technical($e)];
    }

    private static function friendlyError(string $msg): string
    {
        $lower = mb_strtolower($msg);

        return match (true) {
            str_contains($lower, 'timeout') || str_contains($lower, 'curl') => 'Kết nối tới sàn/ĐVVC bị gián đoạn — vui lòng thử lại sau ít phút.',
            str_contains($lower, 'rate') || str_contains($lower, 'limit') => 'Sàn đang giới hạn truy cập — thử lại sau ít phút.',
            str_contains($lower, '50008') => 'Người mua đã yêu cầu huỷ một sản phẩm trong đơn — kiểm tra lại đơn trên sàn.',
            // Câu nghiệp vụ tiếng Việt đã rõ (do service ném ra) thì giữ nguyên.
            preg_match('/[\x{00C0}-\x{1EF9}]/u', $msg) === 1 => $msg,
            default => 'Xử lý đơn thất bại — vui lòng thử lại hoặc liên hệ hỗ trợ.',
        };
    }

    private static function technical(Throwable $e): string
    {
        return sprintf('%s: %s', class_basename($e), $e->getMessage());
    }
}
