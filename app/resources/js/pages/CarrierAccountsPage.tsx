import { useState } from 'react';
import { App as AntApp, Button, Card, Empty, Form, Input, Modal, Select, Space, Switch, Table, Tag, Typography } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type CarrierAccount, useCarrierAccounts, useCarriers, useCreateCarrierAccount, useDeleteCarrierAccount, useUpdateCarrierAccount } from '@/lib/fulfillment';

// Known credential fields per carrier (v1: GHN; others = a generic "token").
const CRED_FIELDS: Record<string, Array<{ key: string; label: string; required?: boolean }>> = {
    ghn: [{ key: 'token', label: 'API Token', required: true }, { key: 'shop_id', label: 'Shop ID', required: true }],
};

// SPEC 0021 — carrier nào cần "địa chỉ kho hàng" (from_address) để tạo vận đơn? GHN yêu cầu
// district_id của kho. Carrier khác sẽ thêm khi connector lên.
const FROM_ADDRESS_REQUIRED: Record<string, boolean> = { ghn: true };

const FROM_ADDRESS_FIELDS: Array<{ key: string; label: string; required?: boolean; placeholder?: string }> = [
    { key: 'name', label: 'Tên người gửi', required: true, placeholder: 'VD: CMBcore Shop' },
    { key: 'phone', label: 'SĐT', required: true, placeholder: 'VD: 0901234567' },
    { key: 'address', label: 'Địa chỉ kho', required: true, placeholder: 'Số nhà, đường…' },
    { key: 'ward_name', label: 'Phường/Xã', placeholder: 'Phường Bến Nghé' },
    { key: 'district_name', label: 'Quận/Huyện', placeholder: 'Quận 1' },
    { key: 'province_name', label: 'Tỉnh/TP', placeholder: 'TP Hồ Chí Minh' },
    { key: 'district_id', label: 'Mã quận GHN', required: true, placeholder: 'VD: 1442 (lấy từ /master-data/district)' },
    { key: 'ward_code', label: 'Mã phường GHN', placeholder: 'VD: 20308' },
];

export function CarrierAccountsPage() {
    const { message } = AntApp.useApp();
    const { data: accounts, isFetching } = useCarrierAccounts();
    const { data: carriers } = useCarriers();
    const create = useCreateCarrierAccount();
    const update = useUpdateCarrierAccount();
    const del = useDeleteCarrierAccount();
    const canManage = useCan('fulfillment.carriers');
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();
    const selectedCarrier: string | undefined = Form.useWatch('carrier', form);
    const credFields = CRED_FIELDS[selectedCarrier ?? ''] ?? (selectedCarrier && selectedCarrier !== 'manual' ? [{ key: 'token', label: 'API Token' }] : []);

    const needsFromAddress = !!FROM_ADDRESS_REQUIRED[selectedCarrier ?? ''];

    const submit = () => form.validateFields().then((v) => {
        const credentials: Record<string, unknown> = {};
        credFields.forEach((f) => { if (v[`cred_${f.key}`] !== undefined && v[`cred_${f.key}`] !== '') credentials[f.key] = v[`cred_${f.key}`]; });
        // SPEC 0021 — Lưu from_address vào meta để ShipmentService dùng khi gọi GHN createOrder.
        const meta: Record<string, unknown> = {};
        if (needsFromAddress) {
            const fromAddress: Record<string, unknown> = {};
            FROM_ADDRESS_FIELDS.forEach((f) => {
                const val = v[`from_${f.key}`];
                if (val !== undefined && val !== '') fromAddress[f.key] = f.key === 'district_id' ? Number(val) : val;
            });
            if (Object.keys(fromAddress).length > 0) meta.from_address = fromAddress;
        }
        create.mutate({ carrier: v.carrier, name: v.name.trim(), credentials, is_default: !!v.is_default, default_service: v.default_service || null, meta: Object.keys(meta).length > 0 ? meta : undefined }, {
            onSuccess: () => { message.success('Đã thêm ĐVVC'); setOpen(false); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const columns: ColumnsType<CarrierAccount> = [
        { title: 'Tên', dataIndex: 'name', key: 'n', render: (v, a) => <Space direction="vertical" size={0}><Typography.Text strong>{v}</Typography.Text><Tag>{a.carrier}</Tag>{a.is_default && <Tag color="blue">Mặc định</Tag>}</Space> },
        { title: 'Dịch vụ mặc định', dataIndex: 'default_service', key: 's', render: (v) => v ?? '—' },
        { title: 'Thông tin xác thực', dataIndex: 'credential_keys', key: 'c', render: (v: string[]) => (v.length ? v.map((k) => <Tag key={k}>{k}</Tag>) : <Typography.Text type="secondary">Không cần</Typography.Text>) },
        { title: 'Trạng thái', dataIndex: 'is_active', key: 'a', width: 110, render: (v, a) => canManage
            ? <Switch checked={v} size="small" onChange={(checked) => update.mutate({ id: a.id, is_active: checked })} />
            : (v ? <Tag color="green">Bật</Tag> : <Tag>Tắt</Tag>) },
        ...(canManage ? [{ title: '', key: 'x', width: 140, render: (_: unknown, a: CarrierAccount) => (
            <Space>
                {!a.is_default && <a onClick={() => update.mutate({ id: a.id, is_default: true })}>Đặt mặc định</a>}
                <a style={{ color: '#cf1322' }} onClick={() => Modal.confirm({ title: `Xoá "${a.name}"?`, onOk: () => del.mutateAsync(a.id) })}>Xoá</a>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Đơn vị vận chuyển (ĐVVC)" subtitle="Cấu hình tài khoản ĐVVC để tạo vận đơn & in tem. 'Tự vận chuyển' luôn có sẵn — bạn tự nhập mã vận đơn."
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => { form.resetFields(); setOpen(true); }}>Thêm ĐVVC</Button>} />
            <Card>
                <Table<CarrierAccount> rowKey="id" loading={isFetching} dataSource={accounts ?? []} columns={columns} pagination={false}
                    locale={{ emptyText: <Empty description="Chưa cấu hình ĐVVC. Đơn vẫn tạo vận đơn được dạng 'Tự vận chuyển'." /> }} />
            </Card>

            <Modal title="Thêm ĐVVC" open={open} onCancel={() => setOpen(false)} okText="Thêm" confirmLoading={create.isPending} onOk={submit}>
                <Form form={form} layout="vertical">
                    <Form.Item name="carrier" label="Đơn vị vận chuyển" rules={[{ required: true, message: 'Chọn ĐVVC' }]}>
                        <Select placeholder="— Chọn —" options={(carriers ?? []).map((c) => ({ value: c.code, label: c.name + (c.needs_credentials ? '' : ' (không cần thông tin xác thực)') }))} />
                    </Form.Item>
                    <Form.Item name="name" label="Tên gợi nhớ" rules={[{ required: true, max: 120 }]}><Input placeholder="VD: GHN - kho Hà Nội" /></Form.Item>
                    {credFields.map((f) => (
                        <Form.Item key={f.key} name={`cred_${f.key}`} label={f.label} rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}>
                            <Input />
                        </Form.Item>
                    ))}
                    {needsFromAddress && (
                        <>
                            <Typography.Title level={5} style={{ marginTop: 8 }}>Địa chỉ kho hàng (người gửi)</Typography.Title>
                            <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginBottom: 12 }}>
                                Bắt buộc với GHN — dùng để tạo vận đơn. Mã quận/phường tra ở GHN API <code>/master-data/district</code>.
                            </Typography.Paragraph>
                            {FROM_ADDRESS_FIELDS.map((f) => (
                                <Form.Item key={f.key} name={`from_${f.key}`} label={f.label}
                                    rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}>
                                    <Input placeholder={f.placeholder} />
                                </Form.Item>
                            ))}
                        </>
                    )}
                    <Form.Item name="default_service" label="Mã dịch vụ mặc định (tuỳ chọn)"><Input placeholder="VD: 2 (GHN service_type_id)" /></Form.Item>
                    <Form.Item name="is_default" valuePropName="checked"><Switch /> <span style={{ marginLeft: 8 }}>Đặt làm mặc định</span></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
