import { useState } from 'react';
import { App as AntApp, Alert, Button, Card, DatePicker, Input, Modal, Popconfirm, Result, Segmented, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ApiOutlined, DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useApiKeys, useCreateApiKey, useDeleteApiKey, type ApiKey, type CreatedApiKey } from '@/lib/apiKeys';

type Preset = '30' | '90' | '365' | 'never' | 'custom';

/** Quản lý API key bên thứ 3 — CHỈ chủ gian hàng (owner). Token hiện 1 lần khi tạo. SPEC 2026-06-26. */
export function SettingsApiKeysPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('api_keys.manage');
    const { data: keys, isLoading } = useApiKeys();
    const create = useCreateApiKey();
    const del = useDeleteApiKey();

    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [preset, setPreset] = useState<Preset>('90');
    const [customDate, setCustomDate] = useState<Dayjs | null>(null);
    const [created, setCreated] = useState<CreatedApiKey | null>(null);

    if (!canManage) {
        return <Result status="403" title="Chỉ chủ gian hàng" subTitle="Chỉ chủ gian hàng (owner) được quản lý API key." />;
    }

    const expiresAt = (): string | null => {
        if (preset === 'never') return null;
        if (preset === 'custom') return customDate ? customDate.endOf('day').toISOString() : null;
        return dayjs().add(Number(preset), 'day').toISOString();
    };

    const submit = () => {
        if (!name.trim()) { message.error('Nhập tên API key.'); return; }
        if (preset === 'custom' && !customDate) { message.error('Chọn ngày hết hạn.'); return; }
        create.mutate(
            { name: name.trim(), expires_at: expiresAt() },
            {
                onSuccess: (r) => { setCreated(r); setOpen(false); setName(''); setPreset('90'); setCustomDate(null); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const columns: ColumnsType<ApiKey> = [
        { title: 'Tên', dataIndex: 'name', render: (v: string) => <Space><ApiOutlined />{v}</Space> },
        { title: 'Key', dataIndex: 'last_four', render: (v: string | null) => <Typography.Text code>••••{v ?? '••••'}</Typography.Text> },
        { title: 'Hết hạn', dataIndex: 'expires_at', render: (v: string | null) => (v ? <DateText value={v} /> : <Tag>Không hết hạn</Tag>) },
        { title: 'Dùng lần cuối', dataIndex: 'last_used_at', render: (v: string | null) => (v ? <DateText value={v} /> : <Typography.Text type="secondary">Chưa dùng</Typography.Text>) },
        { title: 'Tạo lúc', dataIndex: 'created_at', render: (v: string | null) => <DateText value={v} /> },
        {
            title: '', key: 'actions', align: 'right', render: (_: unknown, r: ApiKey) => (
                <Popconfirm title="Xóa API key?" description="Ứng dụng đang dùng key này sẽ ngừng hoạt động ngay." okText="Xóa" okButtonProps={{ danger: true }} cancelText="Huỷ"
                    onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xóa API key.'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" danger type="text" icon={<DeleteOutlined />} loading={del.isPending}>Xóa</Button>
                </Popconfirm>
            ),
        },
    ];

    return (
        <div>
            <PageHeader title="API & Tích hợp" subtitle="API key cho bên thứ 3 — thao tác như trên web. Chỉ chủ gian hàng quản lý." />
            <Card
                size="small"
                title="Danh sách API key"
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>Tạo API key</Button>}
            >
                <Alert type="warning" showIcon style={{ marginBottom: 12 }}
                    message="Giữ API key bí mật như mật khẩu — key có toàn quyền thao tác gian hàng. Token chỉ hiện 1 lần khi tạo." />
                <Table<ApiKey> rowKey="id" size="small" loading={isLoading} columns={columns} dataSource={keys ?? []} pagination={false}
                    locale={{ emptyText: 'Chưa có API key' }} />
                <Typography.Paragraph type="secondary" style={{ marginTop: 12, fontSize: 13 }}>
                    Cách dùng: gửi header <Typography.Text code>Authorization: Bearer &lt;API key&gt;</Typography.Text> tới <Typography.Text code>/api/v1/...</Typography.Text>.
                    Key đã gắn gian hàng nên KHÔNG cần gửi <Typography.Text code>X-Tenant-Id</Typography.Text>. Xem <a href="/api-docs" target="_blank" rel="noreferrer">tài liệu API</a>.
                </Typography.Paragraph>
            </Card>

            <Modal title="Tạo API key" open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={create.isPending} okText="Tạo" cancelText="Huỷ">
                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                    <div>
                        <Typography.Text>Tên (gợi nhớ nơi dùng) *</Typography.Text>
                        <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="VD: Zapier, ERP nội bộ" maxLength={80} />
                    </div>
                    <div>
                        <Typography.Text style={{ display: 'block', marginBottom: 4 }}>Thời hạn</Typography.Text>
                        <Segmented value={preset} onChange={(v) => setPreset(v as Preset)}
                            options={[{ label: '30 ngày', value: '30' }, { label: '90 ngày', value: '90' }, { label: '1 năm', value: '365' }, { label: 'Không hết hạn', value: 'never' }, { label: 'Tùy chọn', value: 'custom' }]} />
                        {preset === 'custom' && (
                            <DatePicker style={{ display: 'block', marginTop: 8 }} value={customDate} onChange={setCustomDate}
                                disabledDate={(d) => d.isBefore(dayjs().startOf('day'))} placeholder="Ngày hết hạn" />
                        )}
                    </div>
                </Space>
            </Modal>

            <Modal title="API key đã tạo" open={created !== null} onCancel={() => setCreated(null)} footer={[<Button key="ok" type="primary" onClick={() => setCreated(null)}>Đã lưu, đóng</Button>]}>
                <Alert type="success" showIcon style={{ marginBottom: 12 }} message="Sao chép key ngay — key chỉ hiện 1 lần duy nhất, không xem lại được." />
                <Typography.Paragraph copyable={{ text: created?.token }} style={{ wordBreak: 'break-all' }}>
                    <Typography.Text code>{created?.token}</Typography.Text>
                </Typography.Paragraph>
            </Modal>
        </div>
    );
}
