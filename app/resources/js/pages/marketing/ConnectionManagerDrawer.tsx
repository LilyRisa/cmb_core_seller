import { useMemo, useState } from 'react';
import { App as AntApp, Avatar, Button, Checkbox, Drawer, Empty, Popconfirm, Space, Tag, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled } from '@ant-design/icons';
import { useAdAccounts, useBulkDisconnectAccounts, type AdAccount } from '@/lib/marketing';
import { errorMessage } from '@/lib/api';

const { Text } = Typography;

interface BmGroup { id: string; name: string; picture: string | null; accounts: AdAccount[] }

interface Props {
    open: boolean;
    onClose: () => void;
    onChanged?: () => void;
}

/** Ngắt kết nối: tích chọn từng tài khoản hoặc cả BM rồi ngắt hàng loạt. */
export function ConnectionManagerDrawer({ open, onClose, onChanged }: Props) {
    const { message } = AntApp.useApp();
    const { data: accounts } = useAdAccounts();
    const bulkDisconnect = useBulkDisconnectAccounts();
    const [checked, setChecked] = useState<Set<number>>(new Set());

    const groups = useMemo<BmGroup[]>(() => {
        const m = new Map<string, BmGroup>();
        (accounts ?? []).forEach((a) => {
            const id = a.business_id ?? '_';
            if (!m.has(id)) m.set(id, { id, name: a.business_name ?? 'Không thuộc BM', picture: a.business_picture_url ?? null, accounts: [] });
            m.get(id)!.accounts.push(a);
        });
        return [...m.values()];
    }, [accounts]);

    function toggle(id: number, on: boolean) {
        setChecked((s) => { const n = new Set(s); if (on) n.add(id); else n.delete(id); return n; });
    }
    function toggleBm(g: BmGroup, on: boolean) {
        setChecked((s) => { const n = new Set(s); g.accounts.forEach((a) => (on ? n.add(a.id) : n.delete(a.id))); return n; });
    }

    function disconnectSelected() {
        if (checked.size === 0) return;
        bulkDisconnect.mutate({ ids: [...checked] }, {
            onSuccess: (d) => { message.success(`Đã ngắt ${d.deleted} tài khoản.`); setChecked(new Set()); onChanged?.(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    }

    return (
        <Drawer
            open={open}
            onClose={onClose}
            width={520}
            title={<Space><DisconnectOutlined />Quản lý kết nối</Space>}
            footer={
                <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                    <Text type="secondary">Đã chọn {checked.size} tài khoản</Text>
                    <Popconfirm
                        title={`Ngắt kết nối ${checked.size} tài khoản đã chọn?`}
                        okText="Ngắt" okButtonProps={{ danger: true }} cancelText="Huỷ"
                        disabled={checked.size === 0}
                        onConfirm={disconnectSelected}
                    >
                        <Button danger icon={<DisconnectOutlined />} disabled={checked.size === 0} loading={bulkDisconnect.isPending}>
                            Ngắt đã chọn
                        </Button>
                    </Popconfirm>
                </Space>
            }
        >
            {groups.length === 0 ? (
                <Empty description="Chưa có tài khoản quảng cáo nào." />
            ) : (
                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                    {groups.map((g) => {
                        const allChecked = g.accounts.every((a) => checked.has(a.id));
                        const someChecked = g.accounts.some((a) => checked.has(a.id));
                        return (
                            <div key={g.id} style={{ border: '1px solid #f0f0f0', borderRadius: 8, padding: 12 }}>
                                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                                    <Checkbox
                                        checked={allChecked}
                                        indeterminate={someChecked && !allChecked}
                                        onChange={(e) => toggleBm(g, e.target.checked)}
                                    >
                                        <Space>
                                            {g.picture ? <Avatar size={18} src={g.picture} shape="square" /> : <FacebookFilled style={{ color: '#1877f2' }} />}
                                            <Text strong>{g.name}</Text>
                                            {g.id !== '_' && <Text type="secondary" style={{ fontSize: 11 }}>#{g.id}</Text>}
                                        </Space>
                                    </Checkbox>
                                    {g.id !== '_' && (
                                        <Popconfirm
                                            title={`Ngắt cả BM (${g.accounts.length} tài khoản)?`}
                                            okText="Ngắt cả BM" okButtonProps={{ danger: true }} cancelText="Huỷ"
                                            onConfirm={() => bulkDisconnect.mutate({ business_id: g.id }, {
                                                onSuccess: (d) => { message.success(`Đã ngắt ${d.deleted} tài khoản.`); onChanged?.(); },
                                                onError: (e) => message.error(errorMessage(e)),
                                            })}
                                        >
                                            <Button size="small" danger type="text">Ngắt cả BM</Button>
                                        </Popconfirm>
                                    )}
                                </Space>
                                <div style={{ marginTop: 8, paddingLeft: 24 }}>
                                    <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                        {g.accounts.map((a) => (
                                            <Checkbox key={a.id} checked={checked.has(a.id)} onChange={(e) => toggle(a.id, e.target.checked)}>
                                                {a.name ?? a.external_account_id}
                                                <Text type="secondary" style={{ fontSize: 11, marginLeft: 6 }}>{a.external_account_id}</Text>
                                                {a.status !== 'active' && <Tag style={{ marginLeft: 6 }}>{a.status}</Tag>}
                                            </Checkbox>
                                        ))}
                                    </Space>
                                </div>
                            </div>
                        );
                    })}
                </Space>
            )}
        </Drawer>
    );
}
