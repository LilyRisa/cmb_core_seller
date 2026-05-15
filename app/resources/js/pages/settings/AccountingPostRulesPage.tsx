import { useState } from 'react';
import { App, Alert, Button, Card, Drawer, Form, Input, Space, Switch, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { EditOutlined, InfoCircleOutlined } from '@ant-design/icons';
import { EVENT_KEY_LABEL, PostRule, usePostRules, useUpdatePostRule } from '@/lib/accounting';
import { AccountTreeSelect } from '@/components/accounting/AccountTreeSelect';
import { AccountingSetupBanner } from '@/pages/accounting/AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

export function AccountingPostRulesPage() {
    const { data: rules = [], isFetching } = usePostRules();
    const [editTarget, setEditTarget] = useState<PostRule | null>(null);
    const canConfig = useCan('accounting.config');

    const columns: ColumnsType<PostRule> = [
        {
            title: 'Sự kiện',
            dataIndex: 'event_key',
            render: (k: string) => (
                <Space size={4} direction="vertical" style={{ display: 'flex' }}>
                    <Typography.Text strong>{EVENT_KEY_LABEL[k] ?? k}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12, fontFamily: 'ui-monospace, monospace' }}>{k}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Nợ TK',
            dataIndex: 'debit_account_code',
            width: 140,
            align: 'center',
            render: (c: string) => <Tag color="blue" style={{ marginInlineEnd: 0, fontFamily: 'ui-monospace, monospace' }}>{c}</Tag>,
        },
        {
            title: 'Có TK',
            dataIndex: 'credit_account_code',
            width: 140,
            align: 'center',
            render: (c: string) => <Tag color="orange" style={{ marginInlineEnd: 0, fontFamily: 'ui-monospace, monospace' }}>{c}</Tag>,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'is_enabled',
            width: 110,
            align: 'center',
            render: (b: boolean) => <Tag color={b ? 'green' : 'default'} style={{ marginInlineEnd: 0 }}>{b ? 'Bật' : 'Tắt'}</Tag>,
        },
        {
            title: 'Thao tác',
            width: 100,
            align: 'right',
            render: (_, r) => canConfig ? (
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => setEditTarget(r)}>Sửa</Button>
            ) : null,
        },
    ];

    return (
        <div>
            <AccountingSetupBanner />

            <Alert
                type="info"
                showIcon
                icon={<InfoCircleOutlined />}
                style={{ marginBottom: 16 }}
                message="Quy tắc hạch toán tự động"
                description={
                    <>
                        Mỗi khi có sự kiện nghiệp vụ (xác nhận phiếu nhập kho, đơn shipped, v.v.), hệ thống tự ghi bút toán theo cặp TK Nợ/Có ở dưới.
                        Tenant có thể đổi TK đối ứng cho phù hợp gian hàng. <b>Thay đổi chỉ áp cho bút toán mới</b>; bút toán đã ghi sổ không đổi (bất biến).
                    </>
                }
            />

            <Card
                title={<Typography.Title level={5} style={{ margin: 0 }}>Mapping tài khoản theo sự kiện</Typography.Title>}
                styles={{ body: { padding: 0 } }}
            >
                <Table<PostRule>
                    rowKey="id"
                    dataSource={rules}
                    columns={columns}
                    loading={isFetching}
                    pagination={false}
                    size="middle"
                    scroll={{ x: 800 }}
                />
            </Card>

            <EditRuleDrawer target={editTarget} onClose={() => setEditTarget(null)} />
        </div>
    );
}

function EditRuleDrawer({ target, onClose }: { target: PostRule | null; onClose: () => void }) {
    const [form] = Form.useForm();
    const update = useUpdatePostRule();
    const { message } = App.useApp();

    return (
        <Drawer
            open={target != null}
            onClose={onClose}
            title={target ? `Sửa: ${EVENT_KEY_LABEL[target.event_key] ?? target.event_key}` : ''}
            destroyOnClose
            width={520}
            footer={
                <Space>
                    <Button onClick={onClose}>Đóng</Button>
                    <Button type="primary" loading={update.isPending} onClick={async () => {
                        try {
                            const v = await form.validateFields();
                            await update.mutateAsync({
                                event_key: target!.event_key,
                                debit_account_code: v.debit_account_code,
                                credit_account_code: v.credit_account_code,
                                is_enabled: v.is_enabled,
                                notes: v.notes,
                            });
                            message.success('Đã lưu quy tắc.');
                            onClose();
                        } catch (e) {
                            if ((e as { errorFields?: unknown }).errorFields) return;
                            message.error(errorMessage(e));
                        }
                    }}>Lưu</Button>
                </Space>
            }
        >
            {target && (
                <Form form={form} layout="vertical" initialValues={{
                    debit_account_code: target.debit_account_code,
                    credit_account_code: target.credit_account_code,
                    is_enabled: target.is_enabled,
                    notes: target.notes ?? '',
                }} preserve={false}>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 0 }}>
                        Mã sự kiện: <code>{target.event_key}</code>
                    </Typography.Paragraph>

                    <Form.Item label="Tài khoản ghi Nợ" name="debit_account_code" rules={[{ required: true }]}>
                        <AccountTreeSelect onlyPostable />
                    </Form.Item>
                    <Form.Item label="Tài khoản ghi Có" name="credit_account_code" rules={[{ required: true }]}>
                        <AccountTreeSelect onlyPostable />
                    </Form.Item>
                    <Form.Item label="Đang bật" name="is_enabled" valuePropName="checked"
                        tooltip="Tắt nếu shop không muốn ghi sổ tự động cho sự kiện này.">
                        <Switch />
                    </Form.Item>
                    <Form.Item label="Ghi chú nội bộ" name="notes">
                        <Input.TextArea rows={3} maxLength={500} placeholder="vd: Dùng TK 6422 thay 6421 vì cửa hàng F&B." />
                    </Form.Item>
                </Form>
            )}
        </Drawer>
    );
}
