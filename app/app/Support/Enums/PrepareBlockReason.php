<?php

namespace CMBcoreSeller\Support\Enums;

/**
 * Lý do một đơn CHƯA thể chuẩn bị hàng (suy từ raw status của sàn). Core-level,
 * KHÔNG gắn tên sàn — connector map raw status sang các case này.
 */
enum PrepareBlockReason: string
{
    case AwaitingPayment = 'awaiting_payment';
    case PlatformHold = 'platform_hold';
    case PlatformFulfilled = 'platform_fulfilled';
    case CancelInProgress = 'cancel_in_progress';
    case PlatformProcessing = 'platform_processing';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Chờ người mua thanh toán',
            self::PlatformHold => 'Sàn đang tạm giữ đơn (thời gian người mua được huỷ / duyệt COD) — chưa cho chuẩn bị',
            self::PlatformFulfilled => 'Đơn do sàn xử lý kho (FBT/FBL) — bạn không cần chuẩn bị',
            self::CancelInProgress => 'Đang xử lý yêu cầu huỷ — chưa thể chuẩn bị',
            self::PlatformProcessing => 'Sàn đang xử lý đơn — chưa thể chuẩn bị',
        };
    }
}
