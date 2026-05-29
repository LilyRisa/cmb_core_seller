<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

/**
 * Mã hoá / giải mã payload postback của nút bấm flow. Builder sinh payload (người
 * dùng KHÔNG bao giờ thấy/nhập tay). Định dạng JSON có marker `t:'flow'` để phân
 * biệt postback flow với postback khác (get_started, persistent menu…). Facebook
 * giới hạn payload 1000 ký tự — JSON gọn này luôn dưới ngưỡng.
 *
 * `n` = id node đã gửi bộ nút (để stale-guard); `h` = handle = id nút (khớp
 * `sourceHandle` của edge ra ⇒ engine resume theo đúng nhánh).
 */
final class FlowPostbackPayload
{
    private const MARKER = 'flow';

    public static function encode(string $nodeId, string $handle): string
    {
        return (string) json_encode(['t' => self::MARKER, 'n' => $nodeId, 'h' => $handle]);
    }

    /** @return array{node_id:string, handle:string}|null */
    public static function decode(string $payload): ?array
    {
        $data = json_decode($payload, true);
        if (! is_array($data) || ($data['t'] ?? null) !== self::MARKER) {
            return null;
        }

        $nodeId = (string) ($data['n'] ?? '');
        $handle = (string) ($data['h'] ?? '');
        if ($nodeId === '' || $handle === '') {
            return null;
        }

        return ['node_id' => $nodeId, 'handle' => $handle];
    }
}
