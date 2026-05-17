// Spec 2026-05-17 — `/admin/settings` quản lý 38 key cấu hình động.
//
// Tabs (Segmented theo memory `ui-avoid-select-prefer-radio`) theo group:
// Branding · Marketplace · Fulfillment · Sync. Mỗi tab list `SettingRow`
// theo catalog order từ server.

import { useState } from 'react';
import { Card, Segmented, Spin, Typography, Button, App, Space } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useSystemSettings, useSyncFromEnv, type SettingGroup } from '../../lib/systemSettings';
import { SettingRow } from '../../components/SettingRow';

const GROUPS: { value: SettingGroup; label: string }[] = [
    { value: 'branding', label: 'Thương hiệu' },
    { value: 'marketplace', label: 'Marketplace' },
    { value: 'fulfillment', label: 'Vận hành' },
    { value: 'sync', label: 'Đồng bộ' },
];

export function SystemSettingsPage() {
    const [group, setGroup] = useState<SettingGroup>('branding');
    const { data, isLoading, refetch } = useSystemSettings(group);
    const sync = useSyncFromEnv();
    const { message } = App.useApp();

    return (
        <Card
            title="Cấu hình hệ thống"
            extra={
                <Space>
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()}>Tải lại</Button>
                    <Button
                        onClick={() =>
                            sync.mutate(undefined, {
                                onSuccess: (d) => message.success(`Đã nạp ${d.created} setting từ env.`),
                                onError: (e) => message.error(errorMessage(e)),
                            })
                        }
                        loading={sync.isPending}
                    >
                        Nạp từ env (lần đầu)
                    </Button>
                </Space>
            }
        >
            <Segmented
                options={GROUPS}
                value={group}
                onChange={(v) => setGroup(v as SettingGroup)}
                block
                style={{ marginBottom: 16 }}
            />

            <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>
                Các cấu hình dưới đây ưu tiên giá trị trong DB. Nếu chưa đặt, hệ thống dùng giá trị từ
                tệp <Typography.Text code>.env</Typography.Text>. Mọi thay đổi ghi nhật ký (audit log).
            </Typography.Paragraph>

            {isLoading ? <Spin /> : data?.map((r) => <SettingRow key={r.key} row={r} />)}
        </Card>
    );
}
