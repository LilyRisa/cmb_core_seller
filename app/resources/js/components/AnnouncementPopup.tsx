import { useState } from 'react';
import { Button, Modal, Typography } from 'antd';
import { useActiveAnnouncements } from '@/lib/announcements';

/**
 * SPEC 0037 — popup thông báo giữa màn hình, z-index cao nhất, chỉ đóng bằng nút xác nhận.
 *
 * Nhớ-đã-xem theo TAB qua sessionStorage: cùng tab chỉ hiện 1 lần/popup; mở tab MỚI thì
 * sessionStorage rỗng ⇒ hiện lại (đúng yêu cầu). KHÔNG dùng localStorage (sẽ nhớ vĩnh viễn).
 * Nhiều popup active ⇒ hiện tuần tự. Mount 1 lần ở AppLayout.
 */
const SEEN_KEY = 'cmb:announce:seen';

function readSeen(): number[] {
    try {
        const raw = sessionStorage.getItem(SEEN_KEY);
        return raw ? (JSON.parse(raw) as number[]) : [];
    } catch {
        return [];
    }
}

function writeSeen(ids: number[]): void {
    try {
        sessionStorage.setItem(SEEN_KEY, JSON.stringify(ids));
    } catch {
        /* private mode / quota — bỏ qua */
    }
}

export function AnnouncementPopup() {
    const { data } = useActiveAnnouncements();
    const [seen, setSeen] = useState<number[]>(() => readSeen());

    const current = (data ?? []).find((a) => !seen.includes(a.id));
    if (!current) return null;

    const dismiss = () => {
        const next = [...seen, current.id];
        setSeen(next);
        writeSeen(next);
    };

    return (
        <Modal
            open
            centered
            zIndex={3000}
            maskClosable={false}
            closable={false}
            keyboard={false}
            title={<Typography.Title level={4} style={{ margin: 0 }}>{current.title}</Typography.Title>}
            footer={[<Button key="ok" type="primary" onClick={dismiss}>{current.dismiss_label || 'Đã hiểu'}</Button>]}
        >
            <style>{'.cmb-announce-body img,.cmb-announce-body video{max-width:100%;height:auto;border-radius:8px}'}</style>
            <div
                className="cmb-announce-body"
                // Nội dung đã được sanitize allowlist phía server (HtmlSanitizer) trước khi lưu.
                dangerouslySetInnerHTML={{ __html: current.body_html }}
            />
        </Modal>
    );
}
