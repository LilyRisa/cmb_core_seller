import { useEffect, useMemo, useState } from 'react';
import { App as AntApp, Alert, Avatar, Button, Card, Empty, Form, Input, Modal, Popconfirm, Select, Space, Table, Tag, Tooltip, Typography } from 'antd';
import { CloudUploadOutlined, DeleteOutlined, EditOutlined, FacebookFilled, PlusOutlined, ReloadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MessagingNav } from '@/components/MessagingNav';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    type UtilityTemplate,
    type UtilityTemplateStatus,
    useDeleteUtilityTemplate,
    useMessagingChannels,
    useSaveUtilityTemplate,
    useSubmitUtilityTemplate,
    useSyncUtilityTemplate,
    useUtilityTemplates,
} from '@/lib/messagingConfig';

/** Nguồn dữ liệu hỗ trợ điền vào biến {{1}},{{2}}… của tin xác nhận đơn. */
const SUPPORTED_VARIABLES: { name: string; label: string }[] = [
    { name: 'order_number', label: 'Mã đơn hàng' },
    { name: 'tracking_url', label: 'Link tra cứu đơn' },
];

const STATUS_META: Record<UtilityTemplateStatus, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    pending: { color: 'processing', label: 'Chờ Meta duyệt' },
    approved: { color: 'success', label: 'Đã duyệt' },
    rejected: { color: 'error', label: 'Bị từ chối' },
};

/**
 * /messaging/utility-templates — quản lý "tin nhắn tiện ích" (Messenger Utility
 * Messages, SPEC-0032). Sau khi Meta khai tử message tag, đây là cách hợp lệ để gửi
 * tin giao dịch tự động (vd xác nhận đơn) ra ngoài cửa sổ 24h: tạo mẫu → gửi Meta
 * duyệt → khi "Đã duyệt" hệ thống dùng để gửi tự động.
 */
export function MessagingUtilityTemplatesPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.template.manage');

    const { data: channels } = useMessagingChannels();
    const pages = useMemo(() => (channels ?? []).filter((c) => c.provider === 'facebook_page'), [channels]);
    const [accountId, setAccountId] = useState<number | null>(null);
    useEffect(() => {
        if (accountId == null && pages.length > 0) setAccountId(pages[0].id);
    }, [pages, accountId]);

    const { data, isFetching } = useUtilityTemplates(accountId);
    const save = useSaveUtilityTemplate();
    const del = useDeleteUtilityTemplate();
    const submit = useSubmitUtilityTemplate();
    const sync = useSyncUtilityTemplate();

    const [editing, setEditing] = useState<UtilityTemplate | null>(null);
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();

    const openForm = (t?: UtilityTemplate) => {
        setEditing(t ?? null);
        form.setFieldsValue(
            t
                ? { ...t, variables: (t.variables ?? []).join(', ') }
                : { channel_account_id: accountId ?? undefined, code: '', name: '', language: 'vi', body: '', variables: '' },
        );
        setOpen(true);
    };

    /** Option Page hiển thị avatar + tên + ID (dùng cho cả bộ lọc và form). */
    const renderPageOptions = () => pages.map((p) => (
        <Select.Option key={p.id} value={p.id} label={p.name || p.shop_name || p.external_shop_id}>
            <Space>
                <Avatar size={24} src={p.avatar_url || undefined} icon={<FacebookFilled />} style={{ background: p.avatar_url ? undefined : '#1877F2' }} />
                <span>{p.name || p.shop_name || p.external_shop_id}</span>
                <Typography.Text type="secondary" style={{ fontSize: 11 }}>· ID: {p.external_shop_id}</Typography.Text>
            </Space>
        </Select.Option>
    ));

    const onSubmit = () => form.validateFields().then((v) => {
        const variables = String(v.variables ?? '')
            .split(',')
            .map((s: string) => s.trim())
            .filter((s: string) => s !== '');
        save.mutate(
            { ...(editing ? { id: editing.id } : {}), ...v, variables },
            {
                onSuccess: () => { message.success('Đã lưu mẫu tin tiện ích'); setOpen(false); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    });

    const columns: ColumnsType<UtilityTemplate> = [
        { title: 'Mã', dataIndex: 'code', width: 170, render: (v) => <Typography.Text code>{v}</Typography.Text> },
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Ngôn ngữ', dataIndex: 'language', width: 90 },
        {
            title: 'Trạng thái', dataIndex: 'status', width: 160,
            render: (s: UtilityTemplateStatus, r) => {
                const meta = STATUS_META[s] ?? STATUS_META.draft;
                const tag = <Tag color={meta.color}>{meta.label}</Tag>;
                return s === 'rejected' && r.reject_reason ? <Tooltip title={r.reject_reason}>{tag}</Tooltip> : tag;
            },
        },
        { title: 'Biến', dataIndex: 'variables', width: 200, render: (vars: string[]) => (vars ?? []).map((x, i) => <Tag key={x}>{`{{${i + 1}}}=${x}`}</Tag>) },
        ...(canManage ? [{
            title: '', width: 150, render: (_: unknown, r: UtilityTemplate) => (
                <Space size={2}>
                    {(r.status === 'draft' || r.status === 'rejected') && (
                        <Tooltip title="Gửi Meta duyệt">
                            <Button size="small" type="text" icon={<CloudUploadOutlined />}
                                loading={submit.isPending}
                                onClick={() => submit.mutate(r.id, { onSuccess: () => message.success('Đã gửi duyệt'), onError: (e) => message.error(errorMessage(e)) })} />
                        </Tooltip>
                    )}
                    {r.status === 'pending' && (
                        <Tooltip title="Đồng bộ trạng thái duyệt">
                            <Button size="small" type="text" icon={<ReloadOutlined />}
                                loading={sync.isPending}
                                onClick={() => sync.mutate(r.id, { onSuccess: () => message.success('Đã đồng bộ'), onError: (e) => message.error(errorMessage(e)) })} />
                        </Tooltip>
                    )}
                    <Button size="small" type="text" icon={<EditOutlined />} onClick={() => openForm(r)} />
                    <Popconfirm title="Xoá mẫu tin?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                        onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                        <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        }] : []),
    ];

    return (
        <div>
            <PageHeader title="Tin nhắn tiện ích" subtitle="Mẫu tin được Meta duyệt để gửi tự động ngoài cửa sổ 24h (vd xác nhận đơn)."
                extra={canManage && pages.length > 0 && <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm()}>Thêm mẫu</Button>} />
            <MessagingNav />

            <Alert type="info" showIcon style={{ marginBottom: 16 }}
                message="Facebook đã ngừng hỗ trợ thẻ tin nhắn (message tag)."
                description="Để gửi tin xác nhận đơn ra ngoài 24h, hãy tạo mẫu mã 'order_confirmation' với biến {{1}}=order_number, {{2}}=tracking_url rồi gửi Meta duyệt. Khi 'Đã duyệt', hệ thống tự dùng để gửi." />

            {pages.length === 0 ? (
                <Card><Empty description="Chưa kết nối Trang Facebook nào. Vào 'Kết nối kênh' để thêm." /></Card>
            ) : (
                <>
                    <div style={{ marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
                        <Typography.Text type="secondary">Trang Facebook:</Typography.Text>
                        <Select<number>
                            style={{ minWidth: 320 }}
                            value={accountId ?? pages[0].id}
                            onChange={(v) => setAccountId(v)}
                            optionLabelProp="label"
                        >
                            {renderPageOptions()}
                        </Select>
                    </div>
                    <Card>
                        <Table<UtilityTemplate> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
                    </Card>
                </>
            )}

            <Modal open={open} onCancel={() => setOpen(false)} onOk={onSubmit} confirmLoading={save.isPending}
                title={editing ? `Sửa mẫu — ${editing.code}` : 'Thêm mẫu tin tiện ích'} okText="Lưu" cancelText="Huỷ">
                <Form form={form} layout="vertical">
                    <Form.Item name="channel_account_id" label="Trang Facebook" rules={[{ required: true, message: 'Chọn Trang Facebook' }]}>
                        <Select<number> optionLabelProp="label" disabled={!!editing} placeholder="Chọn Trang Facebook">
                            {renderPageOptions()}
                        </Select>
                    </Form.Item>
                    <Form.Item name="code" label="Mã (slug)" rules={[{ required: true, pattern: /^[a-z0-9_-]+$/, message: 'Chỉ chữ thường, số, _ -' }]}
                        extra="Dùng 'order_confirmation' cho tin xác nhận đơn tự động.">
                        <Input placeholder="vd: order_confirmation" disabled={!!editing} />
                    </Form.Item>
                    <Form.Item name="name" label="Tên" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="language" label="Ngôn ngữ" rules={[{ required: true }]} initialValue="vi"><Input style={{ width: 120 }} /></Form.Item>
                    <Form.Item name="body" label="Nội dung" rules={[{ required: true }]}
                        extra="Dùng {{1}}, {{2}}… cho biến. Vd: Đơn {{1}} đã xác nhận. Tra cứu: {{2}}">
                        <Input.TextArea rows={4} placeholder="Đơn {{1}} đã xác nhận. Tra cứu: {{2}}" />
                    </Form.Item>
                    <Form.Item name="variables" label="Biến (theo thứ tự {{1}},{{2}}…)"
                        extra={`Nhập tên nguồn, cách nhau bởi dấu phẩy. Hỗ trợ: ${SUPPORTED_VARIABLES.map((v) => v.name).join(', ')}.`}>
                        <Input placeholder="order_number, tracking_url" />
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
