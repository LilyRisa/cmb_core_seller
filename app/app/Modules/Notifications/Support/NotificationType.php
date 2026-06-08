<?php

namespace CMBcoreSeller\Modules\Notifications\Support;

/**
 * Hằng số loại thông báo in-app (SPEC 0036 §4). Thêm loại mới = thêm hằng số ở đây
 * + 1 listener trong `Notifications\Listeners` lắng nghe domain event tương ứng —
 * KHÔNG sửa core. FE map type → icon trong `components/NotificationBell.tsx`.
 */
final class NotificationType
{
    /** Liên kết sàn/Facebook hết hiệu lực, cần kết nối lại. */
    public const CHANNEL_RECONNECT_NEEDED = 'channel.reconnect_needed';

    /** Đơn có tổng tiền âm. */
    public const ORDER_NEGATIVE_TOTAL = 'order.negative_total';

    /** Đơn chuyển sang trạng thái đã hủy. */
    public const ORDER_CANCELLED = 'order.cancelled';

    /** Có yêu cầu hủy/hoàn mới (after-sales Requested). */
    public const ORDER_RETURN_NEW = 'order.return_new';

    /** Chiến dịch quảng cáo sắp đạt ngưỡng cần tắt. */
    public const ADS_MONITOR_APPROACHING = 'ads.monitor_approaching';

    /** AdMonitor đã tự động tạm dừng / tăng ngân sách chiến dịch. */
    public const ADS_MONITOR_ACTION = 'ads.monitor_action';

    private function __construct() {}
}
