<?php

namespace CMBcoreSeller\Modules\Notifications\Support;

/**
 * Hằng số loại thông báo in-app (SPEC 0036 §4). Thêm loại mới = thêm hằng số ở đây
 * + 1 dòng trong CATEGORY_MAP + 1 listener trong `Notifications\Listeners` lắng nghe
 * domain event tương ứng — KHÔNG sửa core. FE map type → icon trong
 * `components/NotificationBell.tsx`.
 *
 * `category` (order|system|general) quyết định notification rơi vào tab nào ở panel FE
 * (Plan A, 2026-07-23) — được `NotificationDispatcher` tự gán qua `categoryFor()`, các
 * listener KHÔNG cần truyền `category` trong payload.
 */
final class NotificationType
{
    public const CATEGORY_ORDER = 'order';

    public const CATEGORY_SYSTEM = 'system';

    public const CATEGORY_GENERAL = 'general';

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

    /** Đẩy tồn kho lên sàn thất bại (Plan A, 2026-07-23). */
    public const INVENTORY_STOCK_PUSH_FAILED = 'inventory.stock_push_failed';

    /** @var array<string,string> type => category */
    private const CATEGORY_MAP = [
        self::ORDER_NEGATIVE_TOTAL => self::CATEGORY_ORDER,
        self::ORDER_CANCELLED => self::CATEGORY_ORDER,
        self::ORDER_RETURN_NEW => self::CATEGORY_ORDER,
        self::CHANNEL_RECONNECT_NEEDED => self::CATEGORY_SYSTEM,
        self::ADS_MONITOR_APPROACHING => self::CATEGORY_SYSTEM,
        self::ADS_MONITOR_ACTION => self::CATEGORY_SYSTEM,
        self::INVENTORY_STOCK_PUSH_FAILED => self::CATEGORY_SYSTEM,
    ];

    /** Type không có trong map ⇒ mặc định 'system' (an toàn hơn 'order'/'general'). */
    public static function categoryFor(string $type): string
    {
        return self::CATEGORY_MAP[$type] ?? self::CATEGORY_SYSTEM;
    }

    private function __construct() {}
}
