import { App as AntApp, Button, Card, Divider, Form, Input, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useAuth, useUpdateProfile } from '@/lib/auth';

/** /settings/profile — sửa hồ sơ cá nhân (tên / email / mật khẩu). SPEC 0011 §3.1. */
export function SettingsProfilePage() {
    const { message } = AntApp.useApp();
    const { data: user } = useAuth();
    const update = useUpdateProfile();
    const [infoForm] = Form.useForm();
    const [pwForm] = Form.useForm();

    const saveInfo = () => infoForm.validateFields().then((v) => {
        const payload: Record<string, string> = {};
        if (v.name?.trim() && v.name.trim() !== user?.name) payload.name = v.name.trim();
        if (v.email?.trim() && v.email.trim() !== user?.email) { payload.email = v.email.trim(); payload.current_password = v.current_password ?? ''; }
        if (Object.keys(payload).length === 0) { message.info('Chưa có thay đổi nào.'); return; }
        update.mutate(payload, { onSuccess: () => { message.success('Đã lưu hồ sơ'); infoForm.setFieldsValue({ current_password: '' }); }, onError: (e) => message.error(errorMessage(e)) });
    });
    const savePassword = () => pwForm.validateFields().then((v) => {
        update.mutate({ current_password: v.current_password, password: v.password, password_confirmation: v.password_confirmation }, {
            onSuccess: () => { message.success('Đã đổi mật khẩu'); pwForm.resetFields(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <Card title="Hồ sơ cá nhân">
            <Form form={infoForm} layout="vertical" initialValues={{ name: user?.name, email: user?.email }} style={{ maxWidth: 460 }}>
                <Form.Item name="name" label="Họ tên" rules={[{ required: true, max: 255 }]}><Input /></Form.Item>
                <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email' }]}><Input /></Form.Item>
                <Form.Item noStyle shouldUpdate={(p, c) => p.email !== c.email}>
                    {() => (infoForm.getFieldValue('email')?.trim() !== user?.email
                        ? <Form.Item name="current_password" label="Mật khẩu hiện tại (để xác nhận đổi email)" rules={[{ required: true, message: 'Nhập mật khẩu hiện tại' }]}><Input.Password autoComplete="current-password" /></Form.Item>
                        : null)}
                </Form.Item>
                <Button type="primary" loading={update.isPending} onClick={saveInfo}>Lưu hồ sơ</Button>
            </Form>

            <Divider />
            <Typography.Title level={5}>Đổi mật khẩu</Typography.Title>
            <Form form={pwForm} layout="vertical" style={{ maxWidth: 460 }}>
                <Form.Item name="current_password" label="Mật khẩu hiện tại" rules={[{ required: true }]}><Input.Password autoComplete="current-password" /></Form.Item>
                <Form.Item name="password" label="Mật khẩu mới" rules={[{ required: true, min: 8, message: 'Tối thiểu 8 ký tự' }]}><Input.Password autoComplete="new-password" /></Form.Item>
                <Form.Item name="password_confirmation" label="Nhập lại mật khẩu mới" dependencies={['password']} rules={[{ required: true }, ({ getFieldValue }) => ({ validator: (_, v) => (!v || v === getFieldValue('password') ? Promise.resolve() : Promise.reject('Mật khẩu nhập lại không khớp')) })]}><Input.Password autoComplete="new-password" /></Form.Item>
                <Button loading={update.isPending} onClick={savePassword}>Đổi mật khẩu</Button>
            </Form>
        </Card>
    );
}
