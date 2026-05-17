// Spec 2026-05-17 — input cho secret setting (`is_secret=true`).
//
// Mặc định mask `••••••••`. Click "Hiện" gọi `/reveal` (audit log ghi) và hiển
// thị plain trong 10s rồi tự ẩn (giảm cửa sổ leak qua shoulder-surfing). Đặt
// giá trị mới mở Input.Password riêng — KHÔNG hiển thị giá trị cũ song song.

import { useEffect, useState } from 'react';
import { Button, Input, Space, App } from 'antd';
import { EyeOutlined, EyeInvisibleOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { revealSetting } from '../lib/systemSettings';

const REVEAL_TTL_MS = 10_000;

export function SecretInput({
    settingKey,
    hasValue,
    onSave,
}: {
    settingKey: string;
    hasValue: boolean;
    onSave: (newValue: string) => void;
}) {
    const [revealed, setRevealed] = useState<string | null>(null);
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState('');
    const { message } = App.useApp();

    useEffect(() => {
        if (revealed === null) return;
        const t = setTimeout(() => setRevealed(null), REVEAL_TTL_MS);
        return () => clearTimeout(t);
    }, [revealed]);

    async function doReveal() {
        try {
            const v = await revealSetting(settingKey);
            setRevealed(v ?? '(rỗng)');
        } catch (e) {
            message.error(errorMessage(e, 'Reveal lỗi.'));
        }
    }

    if (editing) {
        return (
            <Space.Compact style={{ width: '100%' }}>
                <Input.Password
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
            <Input value={revealed ?? (hasValue ? '••••••••' : '(chưa đặt)')} readOnly />
            {hasValue && (
                <Button
                    icon={revealed ? <EyeInvisibleOutlined /> : <EyeOutlined />}
                    onClick={revealed ? () => setRevealed(null) : doReveal}
                />
            )}
            <Button onClick={() => setEditing(true)}>Đặt giá trị</Button>
        </Space.Compact>
    );
}
