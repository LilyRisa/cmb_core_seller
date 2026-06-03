// Spec 2026-05-17 (cập nhật) — input cho secret setting (`is_secret=true`).
//
// Theo yêu cầu chủ dự án: KHÔNG che giá trị nữa — hiển thị thẳng giá trị rõ
// (backend index đã trả clear) để tiện đối chiếu/sửa. "Đặt giá trị" mở ô nhập
// để ghi giá trị mới. (Trang admin chỉ super-admin truy cập.)

import { useState } from 'react';
import { Button, Input, Space, App } from 'antd';

export function SecretInput({
    value,
    onSave,
}: {
    value: string | null;
    onSave: (newValue: string) => void;
}) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState('');
    const { message } = App.useApp();

    if (editing) {
        return (
            <Space.Compact style={{ width: '100%' }}>
                <Input
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    autoFocus
                    placeholder="Giá trị mới"
                />
                <Button
                    type="primary"
                    onClick={() => {
                        if (!draft) {
                            message.error('Giá trị trống.');
                            return;
                        }
                        onSave(draft);
                        setEditing(false);
                        setDraft('');
                    }}
                >
                    Lưu
                </Button>
                <Button onClick={() => { setEditing(false); setDraft(''); }}>Huỷ</Button>
            </Space.Compact>
        );
    }

    return (
        <Space.Compact style={{ width: '100%' }}>
            <Input value={value && value !== '' ? value : '(chưa đặt)'} readOnly />
            <Button onClick={() => setEditing(true)}>Đặt giá trị</Button>
        </Space.Compact>
    );
}
