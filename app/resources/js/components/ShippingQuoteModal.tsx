import { useEffect } from 'react';
import { Modal, Skeleton, Table, Typography, Empty, Result } from 'antd';
import { CarrierLogo } from '@/components/CarrierLogo';
import { errorMessage } from '@/lib/api';
import { useShippingQuoteAll, type QuoteAllItem } from '@/lib/fulfillment';

const vnd = (n: number) => `${(n || 0).toLocaleString('vi-VN')} đ`;

/**
 * Modal tra cứu cước vận chuyển THAM KHẢO (SPEC 2026-07-13) — liệt kê cước từ mọi tài khoản ĐVVC active
 * của tenant cho địa chỉ nhận hiện tại. Không có hành động "Áp dụng" — chỉ xem, đóng bằng nút Đóng/X.
 */
export function ShippingQuoteModal({ open, onClose, recipient }: {
    open: boolean;
    onClose: () => void;
    recipient: { province?: string; district?: string; ward?: string; address?: string };
}) {
    const quoteAll = useShippingQuoteAll();

    useEffect(() => {
        if (open) quoteAll.mutate({ recipient });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const rows = quoteAll.data ?? [];

    return (
        <Modal open={open} onCancel={onClose} onOk={onClose} okText="Đóng" cancelButtonProps={{ style: { display: 'none' } }}
            title="Tra cứu cước vận chuyển (tham khảo)" width={640}>
            {quoteAll.isPending ? (
                <Skeleton active paragraph={{ rows: 4 }} />
            ) : quoteAll.isError ? (
                <Result status="error" title="Không tra cứu được cước vận chuyển" subTitle={errorMessage(quoteAll.error)} />
            ) : rows.length === 0 ? (
                <Empty description="Chưa có tài khoản ĐVVC nào hỗ trợ tính cước" />
            ) : (
                <Table
                    rowKey={(r) => `${r.carrier_account_id}-${r.service_name ?? 'default'}`}
                    dataSource={rows}
                    pagination={false}
                    size="small"
                    columns={[
                        {
                            title: 'Đơn vị vận chuyển', dataIndex: 'account_name',
                            render: (_, r: QuoteAllItem) => (
                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
                                    <CarrierLogo code={r.carrier} size={20} />
                                    <span>{r.account_name}</span>
                                </span>
                            ),
                        },
                        { title: 'Gói', dataIndex: 'service_name', render: (v: string | null) => v ?? '—' },
                        {
                            title: 'Cước', dataIndex: 'fee', align: 'right',
                            render: (_, r: QuoteAllItem) => r.error
                                ? <Typography.Text type="danger">{r.error}</Typography.Text>
                                : <b>{vnd(r.fee ?? 0)}</b>,
                        },
                        {
                            title: 'Phí khai giá', dataIndex: 'insurance_fee', align: 'right',
                            render: (_, r: QuoteAllItem) => r.error ? '—' : vnd(r.insurance_fee ?? 0),
                        },
                        { title: 'Thời gian', dataIndex: 'eta', render: (v: string | null) => v ?? '—' },
                    ]}
                />
            )}
        </Modal>
    );
}
