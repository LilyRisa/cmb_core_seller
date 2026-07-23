import { useState } from 'react';
import { App, Button, Card, DatePicker, Drawer, Form, Input, Popconfirm, Radio, Space, Table, Tag, Typography, Upload } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { DeleteOutlined, EditOutlined, PlusOutlined, SendOutlined, UploadOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { RichTextEditor } from '@admin/components/RichTextEditor';
import { TenantPicker } from '@admin/components/TenantPicker';
import {
    useAdminGeneralNotificationPages,
    useCreateGeneralNotificationPage,
    useUpdateGeneralNotificationPage,
    useDeleteGeneralNotificationPage,
    useSendGeneralNotificationPage,
    uploadGeneralNotificationPageMedia,
    type AdminGeneralNotificationPage,
} from '@admin/lib/generalNotificationPages';

interface FormShape {
    title: string;
    audience_type: 'all' | 'tenant_ids';
    tenant_ids?: number[];
    cta_label?: string;
    cta_url?: string;
    scheduled_at?: dayjs.Dayjs;
    expires_at?: dayjs.Dayjs;
}

const STATUS_TAG: Record<AdminGeneralNotificationPage['status'], { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    scheduled: { color: 'blue', label: 'Đã lên lịch' },
    sent: { color: 'green', label: 'Đã gửi' },
};

/**
 * Plan C (2026-07-23) — admin soạn + gửi "trang thông báo chung" (ưu đãi/tin chung) tới tenant.
 * Tái dùng RichTextEditor (TipTap, không sửa) cho thân bài; ảnh bìa + nút CTA là field riêng
 * (KHÔNG nhúng trong body_html) — đơn giản hoá, tenant page tự render layout cố định.
 */
export function AdminGeneralNotificationPagesPage() {
    const { message } = App.useApp();
    const { data, isFetching } = useAdminGeneralNotificationPages();
    const create = useCreateGeneralNotificationPage();
    const update = useUpdateGeneralNotificationPage();
    const remove = useDeleteGeneralNotificationPage();
    const send = useSendGeneralNotificationPage();
    const [form] = Form.useForm<FormShape>();
    const [open, setOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [bodyHtml, setBodyHtml] = useState('');
    const [coverImageUrl, setCoverImageUrl] = useState<string | null>(null);
    const [editorKey, setEditorKey] = useState('new');
    const [uploading, setUploading] = useState(false);

    const openCreate = () => {
        form.resetFields();
        setBodyHtml('');
        setCoverImageUrl(null);
        setEditingId(null);
        setEditorKey('new-' + Date.now());
        setOpen(true);
    };

    const openEdit = (p: AdminGeneralNotificationPage) => {
        setEditingId(p.id);
        setBodyHtml(p.body_html);
        setCoverImageUrl(p.cover_image_url);
        setEditorKey('edit-' + p.id);
        form.setFieldsValue({
            title: p.title,
            audience_type: p.audience_type,
            tenant_ids: p.audience_tenant_ids ?? undefined,
            cta_label: p.cta_label ?? undefined,
            cta_url: p.cta_url ?? undefined,
            scheduled_at: p.scheduled_at ? dayjs(p.scheduled_at) : undefined,
            expires_at: p.expires_at ? dayjs(p.expires_at) : undefined,
        });
        setOpen(true);
    };

    const submit = (v: FormShape) => {
        const input = {
            title: v.title,
            body_html: bodyHtml,
            cover_image_url: coverImageUrl,
            cta_label: v.cta_label || null,
            cta_url: v.cta_url || null,
            audience_type: v.audience_type,
            audience_tenant_ids: v.audience_type === 'tenant_ids' ? (v.tenant_ids ?? []) : undefined,
            scheduled_at: v.scheduled_at ? v.scheduled_at.toISOString() : null,
            expires_at: v.expires_at ? v.expires_at.toISOString() : null,
        };
        const opts = {
            onSuccess: () => { message.success(editingId ? 'Đã cập nhật.' : 'Đã lưu nháp.'); setOpen(false); },
            onError: (e: unknown) => message.error(errorMessage(e, 'Lưu lỗi.')),
        };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const uploadCover = (file: File) => {
        setUploading(true);
        uploadGeneralNotificationPageMedia(file)
            .then(setCoverImageUrl)
            .catch(() => message.error('Tải ảnh lên thất bại.'))
            .finally(() => setUploading(false));
        return false;
    };

    const columns: ColumnsType<AdminGeneralNotificationPage> = [
        { title: 'ID', dataIndex: 'id', width: 60 },
        { title: 'Tiêu đề', dataIndex: 'title' },
        {
            title: 'Đối tượng', dataIndex: 'audience_type', width: 160,
            render: (v: AdminGeneralNotificationPage['audience_type'], r) => v === 'all' ? <Tag>Tất cả tenant</Tag> : <Tag>{r.audience_tenant_ids?.length ?? 0} tenant cụ thể</Tag>,
        },
        {
            title: 'Trạng thái', dataIndex: 'status', width: 120,
            render: (v: AdminGeneralNotificationPage['status']) => <Tag color={STATUS_TAG[v].color}>{STATUS_TAG[v].label}</Tag>,
        },
        { title: 'Gửi lúc', dataIndex: 'sent_at', width: 160, render: (v: string | null) => formatDate(v) },
        {
            title: '', key: 'actions', width: 160,
            render: (_, r) => r.status === 'sent' ? null : (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
                    <Popconfirm
                        title="Gửi ngay trang này?"
                        onConfirm={() => send.mutate(r.id, {
                            onSuccess: () => message.success('Đã đưa vào hàng đợi gửi.'),
                            onError: (e) => message.error(errorMessage(e, 'Gửi lỗi.')),
                        })}
                    >
                        <Button size="small" type="primary" icon={<SendOutlined />} />
                    </Popconfirm>
                    <Popconfirm title="Xoá trang này?" onConfirm={() => remove.mutate(r.id, { onSuccess: () => message.success('Đã xoá.') })}>
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <>
            <PageHeader
                title="Thông báo chung"
                subtitle='Soạn trang ưu đãi/tin chung, gửi tới 1 hoặc nhiều tenant cụ thể hoặc tất cả — hiện ở tab "Chung" trong chuông thông báo.'
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Tạo trang mới</Button>}
            />

            <Card title="Danh sách trang">
                <Table rowKey="id" size="small" columns={columns} dataSource={data?.data ?? []} loading={isFetching} pagination={{ pageSize: 20 }} />
            </Card>

            <Drawer open={open} title={editingId ? `Sửa trang #${editingId}` : 'Tạo trang mới'} width={720} onClose={() => setOpen(false)} destroyOnHidden>
                <Form form={form} layout="vertical" initialValues={{ audience_type: 'all' }} onFinish={submit}>
                    <Form.Item name="title" label="Tiêu đề" rules={[{ required: true, max: 255 }]}>
                        <Input placeholder="VD: Ưu đãi tháng 8 cho chủ shop" />
                    </Form.Item>

                    <Form.Item label="Ảnh bìa (tuỳ chọn)">
                        <Upload beforeUpload={uploadCover} showUploadList={false} accept="image/*">
                            <Button icon={<UploadOutlined />} loading={uploading}>Tải ảnh bìa</Button>
                        </Upload>
                        {coverImageUrl && <img src={coverImageUrl} alt="" style={{ maxWidth: '100%', marginTop: 8, borderRadius: 8 }} />}
                    </Form.Item>

                    <Form.Item label="Nội dung" required>
                        <RichTextEditor key={editorKey} value={bodyHtml} onChange={setBodyHtml} />
                    </Form.Item>

                    <Space size="large" wrap align="start">
                        <Form.Item name="cta_label" label="Nhãn nút CTA (tuỳ chọn)">
                            <Input style={{ width: 220 }} placeholder="VD: Xem chi tiết" />
                        </Form.Item>
                        <Form.Item name="cta_url" label="Link CTA (tuỳ chọn)">
                            <Input style={{ width: 320 }} placeholder="https://..." />
                        </Form.Item>
                    </Space>

                    <Form.Item name="audience_type" label="Đối tượng nhận">
                        <Radio.Group>
                            <Radio.Button value="all">Tất cả tenant</Radio.Button>
                            <Radio.Button value="tenant_ids">Tenant cụ thể</Radio.Button>
                        </Radio.Group>
                    </Form.Item>

                    <Form.Item shouldUpdate={(p, c) => p.audience_type !== c.audience_type} noStyle>
                        {({ getFieldValue }) => getFieldValue('audience_type') === 'tenant_ids' && (
                            <Form.Item name="tenant_ids" label="Tenant cụ thể" rules={[{ required: true }]}>
                                <TenantPicker mode="multiple" placeholder="Tìm theo mã / tên / email…" />
                            </Form.Item>
                        )}
                    </Form.Item>

                    <Space size="large" wrap align="start">
                        <Form.Item name="scheduled_at" label="Lên lịch gửi (tuỳ chọn — để trống thì bấm Gửi ngay ở danh sách)">
                            <DatePicker showTime format="DD/MM/YYYY HH:mm" style={{ width: 260 }} />
                        </Form.Item>
                        <Form.Item name="expires_at" label="Hạn hiển thị (tuỳ chọn)">
                            <DatePicker showTime format="DD/MM/YYYY HH:mm" style={{ width: 260 }} />
                        </Form.Item>
                    </Space>

                    <Space>
                        <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending}>
                            {editingId ? 'Cập nhật' : 'Lưu nháp'}
                        </Button>
                        <Button onClick={() => setOpen(false)}>Huỷ</Button>
                    </Space>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12 }}>
                        Lưu ở đây chỉ tạo NHÁP (hoặc lên lịch nếu có chọn thời điểm) — bấm nút gửi ở danh sách để gửi ngay.
                    </Typography.Paragraph>
                </Form>
            </Drawer>
        </>
    );
}
