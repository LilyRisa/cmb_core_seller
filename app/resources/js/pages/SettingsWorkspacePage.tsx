import { Alert, App as AntApp, Button, Card, Descriptions, Form, Input } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useTenant, useUpdateTenant } from '@/lib/tenant';

/** /settings/workspace — thông tin gian hàng (tên / slug). owner/admin sửa được. SPEC 0011 §3.2. */
export function SettingsWorkspacePage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const update = useUpdateTenant();
    const canManage = useCan('tenant.settings');
    const [form] = Form.useForm();

    const save = () => form.validateFields().then((v) => {
        const payload: Record<string, string> = {};
        if (v.name?.trim() && v.name.trim() !== tenant?.name) payload.name = v.name.trim();
        if (v.slug?.trim() && v.slug.trim() !== tenant?.slug) payload.slug = v.slug.trim();
        if (Object.keys(payload).length === 0) { message.info('Chưa có thay đổi nào.'); return; }
        update.mutate(payload, { onSuccess: () => message.success('Đã lưu thông tin gian hàng'), onError: (e) => message.error(errorMessage(e)) });
    });

    if (!tenant) return <Card loading title="Thông tin gian hàng" />;
    if (!canManage) {
        return (
            <Card title="Thông tin gian hàng">
                <Descriptions column={1} bordered size="small">
                    <Descriptions.Item label="Tên gian hàng">{tenant.name}</Descriptions.Item>
                    <Descriptions.Item label="Mã định danh (slug)">{tenant.slug}</Descriptions.Item>
                </Descriptions>
                <Alert type="info" showIcon style={{ marginTop: 12 }} message="Chỉ Chủ sở hữu / Quản trị mới sửa được thông tin gian hàng." />
            </Card>
        );
    }
    return (
        <Card title="Thông tin gian hàng">
            <Form form={form} layout="vertical" initialValues={{ name: tenant.name, slug: tenant.slug }} style={{ maxWidth: 460 }}>
                <Form.Item name="name" label="Tên gian hàng" rules={[{ required: true, max: 255 }]}><Input /></Form.Item>
                <Form.Item name="slug" label="Mã định danh (slug)" extra="Chỉ chữ thường, số và dấu gạch ngang. Sẽ dùng để định danh khi nhân viên đăng nhập (vd tên-đăng-nhập@<slug>) — đổi slug ảnh hưởng chuỗi đăng nhập của nhân viên."
                    rules={[{ required: true, pattern: /^[a-z0-9-]+$/, message: 'Chỉ chữ thường, số, dấu gạch ngang' }, { max: 60 }]}>
                    <Input addonBefore="@" />
                </Form.Item>
                <Button type="primary" loading={update.isPending} onClick={save}>Lưu</Button>
            </Form>
        </Card>
    );
}
