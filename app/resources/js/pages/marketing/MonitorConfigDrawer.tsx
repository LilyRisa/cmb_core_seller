import { useEffect, useState } from 'react';
import { App as AntApp, Alert, Button, Drawer, Divider, Form, InputNumber, Popconfirm, Space, Switch, Typography } from 'antd';
import { AlertOutlined } from '@ant-design/icons';
import { useAdMonitors, useDeleteMonitor, useUpsertMonitor, type AdMonitor } from '@/lib/marketing';
import { errorMessage } from '@/lib/api';

const { Text } = Typography;

export interface MonitorTarget {
    level: 'campaign' | 'adset';
    externalId: string;
    name: string | null;
    /** Whether the target has its own daily budget to raise (campaign-on-adset-budget can't). */
    canIncrease: boolean;
}

interface Props {
    open: boolean;
    accountId: number | null;
    target: MonitorTarget | null;
    onClose: () => void;
}

const vnd = (v: number | null | undefined) => (v ?? undefined);

/** Cài đặt giám sát tự động cho một chiến dịch/nhóm: tăng ngân sách khi rẻ, tạm dừng khi đắt. */
export function MonitorConfigDrawer({ open, accountId, target, onClose }: Props) {
    const { message } = AntApp.useApp();
    const { data: monitors } = useAdMonitors(accountId);
    const upsert = useUpsertMonitor();
    const del = useDeleteMonitor();

    const existing: AdMonitor | undefined = monitors?.find(
        (m) => m.target_level === target?.level && m.target_external_id === target?.externalId,
    );

    const [enabled, setEnabled] = useState(true);
    const [increaseEnabled, setIncreaseEnabled] = useState(false);
    const [increaseBelow, setIncreaseBelow] = useState<number | undefined>(undefined);
    const [stepPct, setStepPct] = useState<number>(20);
    const [maxBudget, setMaxBudget] = useState<number | undefined>(undefined);
    const [pauseEnabled, setPauseEnabled] = useState(false);
    const [pauseAbove, setPauseAbove] = useState<number | undefined>(undefined);
    const [minResults, setMinResults] = useState<number>(1);

    // Load existing config when the drawer opens for a target.
    useEffect(() => {
        if (!open) return;
        setEnabled(existing?.enabled ?? true);
        setIncreaseEnabled((existing?.increase_enabled ?? false) && (target?.canIncrease ?? false));
        setIncreaseBelow(vnd(existing?.increase_below));
        setStepPct(existing?.increase_step_pct ?? 20);
        setMaxBudget(vnd(existing?.max_daily_budget));
        setPauseEnabled(existing?.pause_enabled ?? false);
        setPauseAbove(vnd(existing?.pause_above));
        setMinResults(existing?.min_results ?? 1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, target?.externalId]);

    function save() {
        if (accountId == null || target == null) return;
        upsert.mutate(
            {
                accountId,
                target_level: target.level,
                target_external_id: target.externalId,
                enabled,
                increase_enabled: target.canIncrease ? increaseEnabled : false,
                increase_below: increaseEnabled ? (increaseBelow ?? null) : null,
                increase_step_pct: stepPct,
                max_daily_budget: maxBudget ?? null,
                pause_enabled: pauseEnabled,
                pause_above: pauseEnabled ? (pauseAbove ?? null) : null,
                min_results: minResults,
            },
            {
                onSuccess: () => { message.success('Đã lưu giám sát.'); onClose(); },
                onError: (e) => message.error(errorMessage(e, 'Lưu giám sát thất bại.')),
            },
        );
    }

    return (
        <Drawer
            open={open}
            onClose={onClose}
            width={460}
            title={<Space><AlertOutlined />Giám sát: {target?.name ?? target?.externalId}</Space>}
            destroyOnClose
            footer={
                <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                    {existing != null ? (
                        <Popconfirm title="Xoá cài đặt giám sát?" okText="Xoá" cancelText="Huỷ"
                            onConfirm={() => del.mutate(existing.id, { onSuccess: () => { message.success('Đã xoá giám sát.'); onClose(); } })}>
                            <Button danger>Xoá giám sát</Button>
                        </Popconfirm>
                    ) : <span />}
                    <Button type="primary" loading={upsert.isPending} onClick={save}>Lưu</Button>
                </Space>
            }
        >
            <Form layout="vertical">
                <Form.Item>
                    <Space><Switch checked={enabled} onChange={setEnabled} /><Text>Bật giám sát</Text></Space>
                </Form.Item>

                {!target?.canIncrease && (
                    <Alert
                        type="info"
                        showIcon
                        style={{ marginBottom: 12 }}
                        message="Chiến dịch dùng ngân sách theo nhóm — chỉ cài được giám sát tạm dừng. Để tăng ngân sách, cài giám sát ở từng nhóm quảng cáo."
                    />
                )}

                {target?.canIncrease && (
                    <>
                        <Form.Item>
                            <Space><Switch checked={increaseEnabled} onChange={setIncreaseEnabled} /><Text strong>Tăng ngân sách khi rẻ</Text></Space>
                        </Form.Item>
                        {increaseEnabled && (
                            <div style={{ paddingLeft: 8 }}>
                                <Form.Item label="Tăng nếu chi phí/kết quả DƯỚI (VND)">
                                    <InputNumber min={1} step={1000} style={{ width: 200 }} value={increaseBelow} onChange={(v) => setIncreaseBelow(v ?? undefined)}
                                        formatter={(v) => (v != null ? Number(v).toLocaleString('vi-VN') : '')} parser={(v) => (v != null ? Number(v.replace(/\./g, '')) : 0)} />
                                </Form.Item>
                                <Form.Item label="Mỗi lần tăng (%)">
                                    <InputNumber min={1} max={500} style={{ width: 120 }} value={stepPct} onChange={(v) => setStepPct(Number(v ?? 20))} addonAfter="%" />
                                </Form.Item>
                                <Form.Item label="Trần ngân sách/ngày (VND, tuỳ chọn)">
                                    <InputNumber min={1000} step={10000} style={{ width: 200 }} value={maxBudget} onChange={(v) => setMaxBudget(v ?? undefined)}
                                        formatter={(v) => (v != null ? Number(v).toLocaleString('vi-VN') : '')} parser={(v) => (v != null ? Number(v.replace(/\./g, '')) : 0)} />
                                </Form.Item>
                            </div>
                        )}
                        <Divider style={{ margin: '8px 0' }} />
                    </>
                )}

                <Form.Item>
                    <Space><Switch checked={pauseEnabled} onChange={setPauseEnabled} /><Text strong>Tạm dừng khi đắt</Text></Space>
                </Form.Item>
                {pauseEnabled && (
                    <div style={{ paddingLeft: 8 }}>
                        <Form.Item label="Tạm dừng nếu chi phí/kết quả VƯỢT (VND)">
                            <InputNumber min={1} step={1000} style={{ width: 200 }} value={pauseAbove} onChange={(v) => setPauseAbove(v ?? undefined)}
                                formatter={(v) => (v != null ? Number(v).toLocaleString('vi-VN') : '')} parser={(v) => (v != null ? Number(v.replace(/\./g, '')) : 0)} />
                        </Form.Item>
                    </div>
                )}

                <Divider style={{ margin: '8px 0' }} />
                <Form.Item label="Số kết quả tối thiểu trước khi hành động">
                    <InputNumber min={1} max={10000} style={{ width: 120 }} value={minResults} onChange={(v) => setMinResults(Number(v ?? 1))} />
                </Form.Item>
                <Text type="secondary" style={{ fontSize: 12 }}>
                    Hệ thống tự đánh giá & xử lý 30 phút/lần (dữ liệu hôm nay). Khi có hành động sẽ gửi email cho Quản trị.
                </Text>
                {existing?.last_action && (
                    <Alert
                        type="warning"
                        style={{ marginTop: 12 }}
                        message={`Hành động gần nhất: ${existing.last_action === 'pause' ? 'tạm dừng' : 'tăng ngân sách'}${existing.last_action_at ? ' lúc ' + new Date(existing.last_action_at).toLocaleString('vi-VN') : ''}`}
                    />
                )}
            </Form>
        </Drawer>
    );
}
