import { useState } from 'react';
import { App, Button, Card, Radio, Space, Spin, Typography } from 'antd';
import { CheckCircleFilled } from '@ant-design/icons';
import { useUserPreferences, useUpdatePreferences } from '@/lib/preferences';
import { useDesktopBackgrounds } from '@/lib/desktopBackgrounds';

/** Một ô chọn nền (gradient mặc định hoặc preset ảnh). */
function BgTile({ label, image, selected, onClick }: { label: string; image?: string; selected: boolean; onClick: () => void }) {
    return (
        <button type="button" onClick={onClick}
            style={{
                position: 'relative', width: 132, height: 78, borderRadius: 10, cursor: 'pointer', padding: 0,
                border: selected ? '2px solid #2563EB' : '1px solid #E2E8F0', overflow: 'hidden',
                background: image
                    ? `center/cover no-repeat url(${image})`
                    : 'linear-gradient(135deg, #0B1220 0%, #16245C 46%, #3A1D7A 100%)',
            }}>
            {selected && <CheckCircleFilled style={{ position: 'absolute', top: 4, right: 4, color: '#2563EB', fontSize: 18, background: '#fff', borderRadius: '50%' }} />}
            <span style={{ position: 'absolute', left: 0, right: 0, bottom: 0, padding: '3px 6px', fontSize: 11, color: '#fff', background: 'rgba(0,0,0,0.35)', textAlign: 'left' }}>{label}</span>
        </button>
    );
}

export function SettingsAppearancePage() {
    const prefs = useUserPreferences();
    const update = useUpdatePreferences();
    const { message } = App.useApp();
    const { data: backgrounds = [], isLoading: bgLoading } = useDesktopBackgrounds();
    const [shell, setShell] = useState<'v1' | 'v2'>(prefs.ui_shell);

    const saveShell = () => update.mutate({ ui_shell: shell }, {
        onSuccess: () => { message.success('Đã đổi giao diện, đang tải lại…'); setTimeout(() => window.location.assign('/dashboard'), 600); },
        onError: () => message.error('Không lưu được lựa chọn giao diện.'),
    });

    const pickBg = (url: string | null) => update.mutate({ ui_desktop_bg: url }, {
        onSuccess: () => message.success('Đã đổi hình nền.'),
        onError: () => message.error('Không lưu được hình nền.'),
    });

    return (
        <Space direction="vertical" size="large" style={{ width: '100%', maxWidth: 560 }}>
            <Card title="Giao diện">
                <Typography.Paragraph type="secondary">
                    Chọn kiểu giao diện. "Web Desktop" sắp xếp các phần theo ứng dụng dạng tab giống trình duyệt.
                </Typography.Paragraph>
                <Radio.Group value={shell} onChange={(e) => setShell(e.target.value)}>
                    <Space direction="vertical">
                        <Radio value="v1">Cổ điển — thanh điều hướng bên trái (mặc định)</Radio>
                        <Radio value="v2">Web Desktop — ứng dụng theo tab</Radio>
                    </Space>
                </Radio.Group>
                <div style={{ marginTop: 20 }}>
                    <Button type="primary" loading={update.isPending} disabled={shell === prefs.ui_shell} onClick={saveShell}>Lưu</Button>
                </div>
            </Card>

            <Card title="Hình nền Desktop">
                <Typography.Paragraph type="secondary">
                    Áp dụng cho màn hình nền ở giao diện Web Desktop. Chọn một mẫu bên dưới để áp dụng ngay.
                </Typography.Paragraph>
                {bgLoading ? <Spin /> : (
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12 }}>
                        <BgTile label="Mặc định" selected={!prefs.ui_desktop_bg} onClick={() => pickBg(null)} />
                        {backgrounds.map((bg) => (
                            <BgTile key={bg.id} label={bg.name} image={bg.image_url}
                                selected={prefs.ui_desktop_bg === bg.image_url} onClick={() => pickBg(bg.image_url)} />
                        ))}
                    </div>
                )}
            </Card>
        </Space>
    );
}
