import { useState } from 'react';
import { Alert, App as AntApp, Button, Drawer, Empty, List, Select, Space, Spin, Tag, Typography } from 'antd';
import { ApiOutlined, ShareAltOutlined } from '@ant-design/icons';
import { useAdPixels, useSharePixel } from '@/lib/adWizard';
import { useAdAccounts } from '@/lib/marketing';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';

const { Text } = Typography;

interface Props {
    open: boolean;
    accountId: number | null;
    onClose: () => void;
}

/** Quản lý Pixel của tài khoản: xem chi tiết + chia sẻ Pixel sang tài khoản khác (cùng BM). */
export function PixelManagerDrawer({ open, accountId, onClose }: Props) {
    const { message } = AntApp.useApp();
    const { data: pixels, isLoading, isError } = useAdPixels(accountId, open);
    const { data: accounts } = useAdAccounts();
    const sharePixel = useSharePixel();
    // Per-pixel chosen target account id (external act_ id).
    const [target, setTarget] = useState<Record<string, string>>({});

    const current = accounts?.find((a) => a.id === accountId);
    // Candidate targets: other accounts in the same BM (sharing requires same business).
    const targets = (accounts ?? []).filter(
        (a) => a.id !== accountId && a.business_id != null && a.business_id === current?.business_id,
    );

    function doShare(pixelId: string) {
        if (accountId == null) return;
        const t = target[pixelId];
        if (t == null || t === '') { message.warning('Chọn tài khoản đích.'); return; }
        sharePixel.mutate(
            { accountId, pixelId, target_account_id: t },
            {
                onSuccess: () => message.success('Đã chia sẻ Pixel.'),
                onError: (e) => message.error(errorMessage(e, 'Chia sẻ thất bại (cần quyền quản lý BM).')),
            },
        );
    }

    return (
        <Drawer
            open={open}
            onClose={onClose}
            width={560}
            title={<Space><ApiOutlined />Quản lý Pixel{current?.name ? ` — ${current.name}` : ''}</Space>}
            destroyOnClose
        >
            {isError ? (
                <Alert
                    type="warning"
                    showIcon
                    message="Không đọc được Pixel"
                    description="Token hiện tại có thể thiếu quyền ads_management. Hãy bấm 'Kết nối Facebook Ads' để cấp lại quyền, rồi thử lại."
                />
            ) : isLoading ? (
                <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
            ) : pixels == null || pixels.length === 0 ? (
                <Empty description="Tài khoản chưa có Pixel nào (hoặc thiếu quyền ads_management — thử kết nối lại)." />
            ) : (
                <List
                    dataSource={pixels}
                    renderItem={(p) => (
                        <List.Item>
                            <Space direction="vertical" size={6} style={{ width: '100%' }}>
                                <Space wrap>
                                    <Text strong>{p.name}</Text>
                                    <Text type="secondary" copyable style={{ fontSize: 12 }}>{p.id}</Text>
                                    {p.is_unavailable ? <Tag color="red">Không khả dụng</Tag> : <Tag color="green">Hoạt động</Tag>}
                                </Space>
                                <Text type="secondary" style={{ fontSize: 12 }}>
                                    Lần kích hoạt gần nhất: {formatDate(p.last_fired_time)}
                                </Text>
                                <Space wrap>
                                    <Select
                                        size="small"
                                        style={{ minWidth: 240 }}
                                        placeholder={targets.length ? 'Chia sẻ tới tài khoản…' : 'Không có tài khoản cùng BM'}
                                        disabled={targets.length === 0}
                                        value={target[p.id]}
                                        onChange={(v) => setTarget((s) => ({ ...s, [p.id]: v }))}
                                        options={targets.map((a) => ({ label: `${a.name ?? a.external_account_id} (${a.external_account_id})`, value: a.external_account_id }))}
                                    />
                                    <Button
                                        size="small"
                                        icon={<ShareAltOutlined />}
                                        loading={sharePixel.isPending}
                                        disabled={targets.length === 0}
                                        onClick={() => doShare(p.id)}
                                    >
                                        Chia sẻ
                                    </Button>
                                </Space>
                            </Space>
                        </List.Item>
                    )}
                />
            )}
            <Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 12 }}>
                Chỉ chia sẻ được sang tài khoản quảng cáo thuộc cùng Business Manager và bạn có quyền quản lý.
            </Text>
        </Drawer>
    );
}
