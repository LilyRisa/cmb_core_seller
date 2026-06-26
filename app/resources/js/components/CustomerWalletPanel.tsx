import { useState } from 'react';
import { App as AntApp, Button, Card, InputNumber, Input, List, Modal, Radio, Space, Tag, Typography } from 'antd';
import { WalletOutlined, PlusOutlined } from '@ant-design/icons';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useCustomerWalletTopup, useCustomerWalletTransactions, type WalletTransaction } from '@/lib/customers';

const TYPE_LABEL: Record<WalletTransaction['type'], string> = {
    topup: 'Nạp tiền',
    order_payment: 'Trừ cho đơn',
    refund: 'Hoàn ví',
    adjustment: 'Điều chỉnh',
};

/** Ví trả trước của khách: số dư + nạp tiền (yêu cầu số tiền + hóa đơn) + lịch sử. SPEC 2026-06-26. */
export function CustomerWalletPanel({ customerId, balance }: { customerId: number; balance: number }) {
    const { message } = AntApp.useApp();
    const canTopup = useCan('accounting.post');
    const topup = useCustomerWalletTopup(customerId);
    const { data: txPage } = useCustomerWalletTransactions(customerId);
    const [open, setOpen] = useState(false);
    const [amount, setAmount] = useState<number | null>(null);
    const [method, setMethod] = useState<'cash' | 'bank' | 'ewallet'>('cash');
    const [invoice, setInvoice] = useState('');
    const [note, setNote] = useState('');

    const submit = () => {
        if (!amount || amount <= 0) { message.error('Nhập số tiền nạp.'); return; }
        if (!invoice.trim()) { message.error('Nhập số/mã hóa đơn.'); return; }
        topup.mutate(
            { amount, payment_method: method, invoice_ref: invoice.trim(), note: note.trim() || undefined },
            {
                onSuccess: (r) => { message.success(`Đã nạp ${r.balance.toLocaleString('vi-VN')}₫ vào ví.`); setOpen(false); setAmount(null); setInvoice(''); setNote(''); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    return (
        <Card
            size="small"
            style={{ marginBottom: 16 }}
            title={<Space><WalletOutlined /> Ví trả trước</Space>}
            extra={canTopup && <Button size="small" type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>Nạp tiền</Button>}
        >
            <Typography.Title level={3} style={{ margin: '0 0 12px' }}><MoneyText value={balance} /></Typography.Title>
            <List
                size="small"
                locale={{ emptyText: 'Chưa có giao dịch ví' }}
                dataSource={txPage?.data ?? []}
                renderItem={(t) => (
                    <List.Item style={{ paddingInline: 0 }}>
                        <Space direction="vertical" size={0} style={{ width: '100%' }}>
                            <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                                <Tag color={t.amount >= 0 ? 'green' : 'red'}>{TYPE_LABEL[t.type]}</Tag>
                                <Typography.Text strong style={{ color: t.amount >= 0 ? '#389e0d' : '#cf1322' }}>
                                    {t.amount >= 0 ? '+' : ''}<MoneyText value={t.amount} />
                                </Typography.Text>
                            </Space>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                                Số dư: {t.balance_after.toLocaleString('vi-VN')}₫{t.invoice_ref ? ` · HĐ ${t.invoice_ref}` : ''}{t.order_id ? ` · Đơn #${t.order_id}` : ''} · <DateText value={t.created_at} />
                            </Typography.Text>
                        </Space>
                    </List.Item>
                )}
            />
            <Modal title="Nạp tiền vào ví khách" open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={topup.isPending} okText="Nạp tiền" cancelText="Huỷ">
                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                    <div>
                        <Typography.Text>Số tiền *</Typography.Text>
                        <InputNumber<number>
                            style={{ width: '100%' }} min={1} value={amount} onChange={setAmount}
                            formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')} parser={(v) => Number((v ?? '').replace(/[^\d]/g, ''))}
                            addonAfter="₫"
                        />
                    </div>
                    <div>
                        <Typography.Text>Số/mã hóa đơn *</Typography.Text>
                        <Input value={invoice} onChange={(e) => setInvoice(e.target.value)} placeholder="VD: HD-2026-001" maxLength={120} />
                    </div>
                    <div>
                        <Typography.Text style={{ display: 'block', marginBottom: 4 }}>Phương thức</Typography.Text>
                        <Radio.Group value={method} onChange={(e) => setMethod(e.target.value)} optionType="button" buttonStyle="solid"
                            options={[{ label: 'Tiền mặt', value: 'cash' }, { label: 'Chuyển khoản', value: 'bank' }, { label: 'Ví điện tử', value: 'ewallet' }]} />
                    </div>
                    <div>
                        <Typography.Text>Ghi chú</Typography.Text>
                        <Input.TextArea value={note} onChange={(e) => setNote(e.target.value)} rows={2} maxLength={255} />
                    </div>
                </Space>
            </Modal>
        </Card>
    );
}
