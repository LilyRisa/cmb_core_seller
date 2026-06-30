import { useState } from 'react';
import { App as AntApp, Alert, Button, Card, Empty, Form, Input, Modal, Radio, Result, Skeleton, Space, Tag, Typography } from 'antd';
import { CheckCircleFilled, CloseCircleFilled, KeyOutlined, PlusOutlined, ReloadOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    useEInvoiceAccounts, useCreateEInvoiceAccount,
    useDeleteEInvoiceAccount, useVerifyEInvoiceAccount, type EInvoiceAccount,
} from '@/lib/einvoice';

const CRED_FIELDS = [
    { key: 'appid', label: 'AppID (MISA cấp)', required: true },
    { key: 'taxcode', label: 'Mã số thuế', required: true },
    { key: 'username', label: 'Tài khoản meInvoice', required: true },
    { key: 'password', label: 'Mật khẩu', required: true },
];

export function EInvoiceSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('einvoice.config');
    const { data: accounts, isFetching, isError, refetch } = useEInvoiceAccounts();
    const create = useCreateEInvoiceAccount();
    const verify = useVerifyEInvoiceAccount();
    const del = useDeleteEInvoiceAccount();
    const [open, setOpen] = useState(false);
    const [verifyingId, setVerifyingId] = useState<number | null>(null);
    const [form] = Form.useForm();

    if (isError) {
        return (
            <Result
                status="error"
                title="Không tải được cấu hình HĐĐT"
                extra={<Button onClick={() => refetch()}>Thử lại</Button>}
            />
        );
    }

    const submit = () =>
        form.validateFields().then((v) => {
            const credentials: Record<string, string> = {};
            CRED_FIELDS.forEach((f) => {
                if (v[`cred_${f.key}`]) credentials[f.key] = v[`cred_${f.key}`] as string;
            });
            create.mutate(
                { provider: 'misa', name: v.name as string, default_mode: (v.default_mode as 'hsm' | 'mtt') ?? 'hsm', credentials, is_default: true },
                {
                    onSuccess: () => {
                        message.success('Đã thêm tài khoản MISA. Đang kiểm tra kết nối...');
                        form.resetFields();
                        setOpen(false);
                    },
                    onError: (e) => message.error(errorMessage(e)),
                },
            );
        });

    const onVerify = (acc: EInvoiceAccount) => {
        setVerifyingId(acc.id);
        verify.mutate(acc.id, {
            onSettled: () => setVerifyingId(null),
            onSuccess: (r) => (r.ok ? message.success : message.error)(`${acc.name}: ${r.message}`),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    return (
        <div>
            <PageHeader
                title="Hóa đơn điện tử (MISA meInvoice)"
                subtitle="Khai báo tài khoản MISA để phát hành hóa đơn cho đơn hàng."
                extra={
                    <Space>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>
                            Làm mới
                        </Button>
                        {canConfig && (
                            <Button type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>
                                Thêm tài khoản
                            </Button>
                        )}
                    </Space>
                }
            />

            {accounts === undefined ? (
                <Skeleton active />
            ) : accounts.length === 0 ? (
                <Empty description="Chưa có tài khoản HĐĐT" />
            ) : (
                <Space direction="vertical" style={{ width: '100%' }}>
                    {accounts.map((acc) => {
                        const ok = acc.meta?.last_verify_ok;
                        return (
                            <Card
                                key={acc.id}
                                size="small"
                                title={
                                    <Space>
                                        <KeyOutlined />
                                        {acc.name}
                                        {acc.is_default && <Tag color="blue">Mặc định</Tag>}
                                    </Space>
                                }
                                extra={
                                    canConfig ? (
                                        <Space>
                                            <Button
                                                size="small"
                                                icon={<ThunderboltOutlined />}
                                                loading={verify.isPending && verifyingId === acc.id}
                                                onClick={() => onVerify(acc)}
                                            >
                                                Kiểm tra kết nối
                                            </Button>
                                            <Button
                                                size="small"
                                                danger
                                                onClick={() => Modal.confirm({
                                                    title: 'Xóa tài khoản MISA?',
                                                    content: 'Thao tác này không thể hoàn tác — credentials đã lưu sẽ bị xóa.',
                                                    okText: 'Xóa', okButtonProps: { danger: true }, cancelText: 'Hủy',
                                                    onOk: () => del.mutate(acc.id),
                                                })}
                                            >
                                                Xóa
                                            </Button>
                                        </Space>
                                    ) : undefined
                                }
                            >
                                <Space direction="vertical" size={4}>
                                    <Typography.Text type="secondary">
                                        Kiểu mặc định (đơn tự tạo):{' '}
                                        {acc.default_mode === 'hsm' ? 'HSM' : 'Máy tính tiền (MTT)'}
                                    </Typography.Text>
                                    <span>
                                        {ok === true && (
                                            <Tag icon={<CheckCircleFilled />} color="success">
                                                Kết nối OK
                                            </Tag>
                                        )}
                                        {ok === false && (
                                            <Tag icon={<CloseCircleFilled />} color="error">
                                                Lỗi kết nối
                                            </Tag>
                                        )}
                                        {acc.meta?.last_verify_error && (
                                            <Typography.Text type="danger">{acc.meta.last_verify_error}</Typography.Text>
                                        )}
                                    </span>
                                </Space>
                            </Card>
                        );
                    })}
                </Space>
            )}

            <Modal
                open={open}
                title="Thêm tài khoản MISA meInvoice"
                okText="Thêm"
                onCancel={() => setOpen(false)}
                onOk={submit}
                confirmLoading={create.isPending}
                destroyOnClose
                width={560}
            >
                <Alert
                    type="info"
                    showIcon
                    style={{ marginBottom: 16 }}
                    message="Đơn sàn xuất hóa đơn máy tính tiền (MTT); đơn tự tạo theo kiểu mặc định bên dưới."
                />
                <Form form={form} layout="vertical" preserve={false}>
                    <Form.Item
                        name="name"
                        label="Tên gợi nhớ"
                        rules={[{ required: true, message: 'Nhập tên gợi nhớ' }, { max: 120 }]}
                    >
                        <Input placeholder="MISA - cửa hàng chính" />
                    </Form.Item>
                    <Form.Item
                        name="default_mode"
                        label="Kiểu phát hành mặc định cho đơn tự tạo"
                        initialValue="hsm"
                    >
                        <Radio.Group optionType="button" buttonStyle="solid">
                            <Radio value="hsm">HSM (HĐ GTGT đầy đủ)</Radio>
                            <Radio value="mtt">Máy tính tiền (MTT)</Radio>
                        </Radio.Group>
                    </Form.Item>
                    {CRED_FIELDS.map((f) => (
                        <Form.Item
                            key={f.key}
                            name={`cred_${f.key}`}
                            label={f.label}
                            rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}
                        >
                            {f.key === 'password' ? (
                                <Input.Password placeholder={f.label} />
                            ) : (
                                <Input placeholder={f.label} autoComplete="off" />
                            )}
                        </Form.Item>
                    ))}
                </Form>
            </Modal>
        </div>
    );
}
