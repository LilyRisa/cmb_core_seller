<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

/**
 * Mã hoá / giải mã payload postback của nút bấm flow. Builder sinh payload (người
 * dùng KHÔNG bao giờ thấy/nhập tay). Định dạng JSON có marker `t:'flow'` để phân
 * biệt postback flow với postback khác (get_started, persistent menu…). Facebook
 * giới hạn payload 1000 ký tự — JSON gọn này luôn dưới ngưỡng.
 *
 * `f` = id flow (để tự định tuyến khi không còn run "waiting" nào khớp — xem
 * AdvanceFlowOnPostback::handle, nhánh revive); `n` = id node đã gửi bộ nút (để
 * stale-guard); `h` = handle = id nút (khớp `sourceHandle` của edge ra ⇒ engine
 * resume theo đúng nhánh). `f` optional khi decode — payload cũ (gửi trước khi
 * thêm field này) đã nằm sẵn trong tin nhắn cũ của khách, vẫn phải decode được.
 */
final class FlowPostbackPayload
{
    private const MARKER = 'flow';

    public static function encode(string $flowId, string $nodeId, string $handle): string
    {
        return (string) json_encode(['t' => self::MARKER, 'f' => $flowId, 'n' => $nodeId, 'h' => $handle]);
    }

    /** @return array{flow_id:?string, node_id:string, handle:string}|null */
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

        $flowId = isset($data['f']) && $data['f'] !== '' ? (string) $data['f'] : null;

        return ['flow_id' => $flowId, 'node_id' => $nodeId, 'handle' => $handle];
    }
}
