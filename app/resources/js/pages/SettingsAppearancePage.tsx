import { useState } from 'react';
import { Card, Radio, Button, Space, Typography, App } from 'antd';
import { useUserPreferences, useUpdatePreferences } from '@/lib/preferences';

export function SettingsAppearancePage() {
    const prefs = useUserPreferences();
    const update = useUpdatePreferences();
    const { message } = App.useApp();
    const [shell, setShell] = useState<'v1' | 'v2'>(prefs.ui_shell);

    const save = () => update.mutate({ ui_shell: shell }, {
        onSuccess: () => { message.success('Đã đổi giao diện, đang tải lại…'); setTimeout(() => window.location.assign('/'), 600); },
        onError: () => message.error('Không lưu được lựa chọn giao diện.'),
    });

    return (
        <Card title="Giao diện" style={{ maxWidth: 560 }}>
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
                <Button type="primary" loading={update.isPending} disabled={shell === prefs.ui_shell} onClick={save}>Lưu</Button>
            </div>
        </Card>
    );
}
