import { useMemo, useState } from 'react';
import { App as AntApp, Button, Drawer, Empty, List, Space, Spin, Tag, Tooltip, Typography } from 'antd';
import { FileDoneOutlined, PlusOutlined, ShoppingOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import type { Conversation } from '@/lib/messaging';
import { useLinkConversationOrder } from '@/lib/messaging';
import { useCustomerLookup, useCustomerOrders } from '@/lib/customers';
import { CreateOrderForm, type OrderDraft } from '@/pages/CreateOrderPage';
import { formatMoney } from '@/lib/format';

const { Text } = Typography;

interface OrderRow { id: number; number: string; status: string; total: number; date: string | null }

/** Drawer rộng vừa màn — form 2 cột vừa khít, không tràn trên màn nhỏ. */
function drawerWidth(): number {
    if (typeof window === 'undefined') return 960;
    return Math.min(980, window.innerWidth - 16);
}

/** map provider hội thoại → `sub_source` của đơn (nếu khớp option trong form). */
function providerSubSource(provider: string): string | undefined {
    if (provider === 'facebook_page') return 'facebook';
    if (provider.includes('zalo')) return 'zalo';
    return undefined;
}

/**
 * Cột phải khung chat — danh sách đơn của khách + nút "Tạo đơn".
 *
 * "Tạo đơn" mở Drawer chứa NGUYÊN form tạo đơn thủ công (cùng validate, chọn SP,
 * địa chỉ…), bơm sẵn SĐT + tên (+ địa chỉ đã lưu nếu tra được). Lưu xong: gắn đơn
 * vào hội thoại (icon đơn hiện ở danh sách) + refresh list.
 */
export function ConversationOrderPanel({ conversation }: { conversation: Conversation }) {
    const { notification } = AntApp.useApp();
    const qc = useQueryClient();
    const [open, setOpen] = useState(false);
    const link = useLinkConversationOrder();

    const phone = conversation.detected_phone ?? '';
    const hasCustomer = conversation.customer_id != null;

    // Danh sách đơn: khách đã định danh ⇒ lấy theo customer; chưa ⇒ tra theo SĐT.
    // Lookup luôn chạy (nếu có SĐT) để lấy địa chỉ đã lưu bơm vào form (react-query dedupe).
    const byCustomer = useCustomerOrders(conversation.customer_id ?? undefined, { per_page: 20 });
    const lookup = useCustomerLookup(phone);

    const rows: OrderRow[] = useMemo(() => {
        if (hasCustomer) {
            return (byCustomer.data?.data ?? []).map((o) => ({
                id: o.id, number: o.order_number ?? `#${o.id}`, status: o.status_label, total: o.grand_total, date: o.placed_at,
            }));
        }
        const lk = lookup.data;
        if (!lk) return [];
        return [...lk.open_orders, ...lk.returning_orders].map((o) => ({
            id: o.id, number: o.order_number ?? `#${o.id}`, status: o.status, total: o.grand_total, date: o.placed_at,
        }));
    }, [hasCustomer, byCustomer.data, lookup.data]);

    const loading = hasCustomer ? byCustomer.isLoading : (phone.replace(/[^\d+]/g, '').length >= 9 && lookup.isLoading);

    // Nháp khởi tạo cho form trong Drawer — bơm SĐT + tên (+ địa chỉ đã lưu nếu có).
    const initialDraft: OrderDraft = useMemo(() => {
        const addr = lookup.data?.addresses?.[0];
        const name = conversation.buyer_name ?? lookup.data?.customer?.name ?? addr?.name ?? '';
        const isOld = !!addr?.district;
        return {
            items: [],
            phone,
            shipAddress: addr
                ? {
                    format: isOld ? 'old' : 'new',
                    province: addr.province ?? undefined,
                    district: addr.district ?? undefined,
                    ward: addr.ward ?? undefined,
                    address: addr.address ?? addr.detail ?? undefined,
                }
                : {},
            tags: [],
            attachments: [],
            form: {
                channel_mode: 'online',
                sub_source: providerSubSource(conversation.provider),
                buyer_name: name || undefined,
                recipient_name: name || undefined,
                recipient_phone: phone || undefined,
                recipient_address: addr?.address ?? addr?.detail ?? undefined,
            },
        };
    }, [lookup.data, conversation.buyer_name, conversation.provider, phone]);

    const handleSaved = (orderId: number) => {
        setOpen(false);
        notification.success({
            message: 'Đã tạo đơn',
            description: <a href={`/orders/${orderId}`} target="_blank" rel="noreferrer">Xem đơn #{orderId} →</a>,
            placement: 'topRight',
        });
        // Gắn đơn vào hội thoại ⇒ icon đơn hiện ở danh sách tin nhắn.
        link.mutate({ conversationId: conversation.id, orderId }, {
            onSuccess: () => {
                qc.invalidateQueries({ queryKey: ['customer-orders'] });
                qc.invalidateQueries({ queryKey: ['customer-lookup'] });
            },
            onError: () => notification.warning({
                message: 'Đơn đã tạo nhưng chưa gắn được vào hội thoại',
                description: 'Bạn có thể mở đơn để kiểm tra.',
            }),
        });
    };

    return (
        <div style={{ marginTop: 16, display: 'flex', flexDirection: 'column', minHeight: 0 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text strong><ShoppingOutlined /> Đơn hàng</Text>
                <Button type="primary" size="small" icon={<PlusOutlined />} onClick={() => setOpen(true)}>Tạo đơn</Button>
            </div>

            {loading ? (
                <div style={{ textAlign: 'center', padding: 16 }}><Spin size="small" /></div>
            ) : rows.length === 0 ? (
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description={<Text type="secondary" style={{ fontSize: 12 }}>Chưa có đơn cho khách này</Text>}
                />
            ) : (
                <div style={{ overflowY: 'auto', minHeight: 0 }}>
                    <List
                        size="small"
                        dataSource={rows}
                        rowKey={(o) => o.id}
                        renderItem={(o) => (
                            <List.Item style={{ padding: '8px 0' }}>
                                <Link to={`/orders/${o.id}`} style={{ width: '100%', color: 'inherit' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: 8 }}>
                                        <Space size={4}>
                                            {conversation.order_id === o.id && (
                                                <Tooltip title="Đơn gắn với hội thoại này">
                                                    <FileDoneOutlined style={{ color: '#2563EB' }} />
                                                </Tooltip>
                                            )}
                                            <Text strong style={{ fontSize: 13 }}>{o.number}</Text>
                                        </Space>
                                        <Text style={{ fontSize: 13 }}>{formatMoney(o.total)}</Text>
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: 8, marginTop: 2 }}>
                                        <Tag style={{ fontSize: 11, marginInlineEnd: 0 }}>{o.status}</Tag>
                                        {o.date && <Text type="secondary" style={{ fontSize: 11 }}>{o.date.slice(0, 10)}</Text>}
                                    </div>
                                </Link>
                            </List.Item>
                        )}
                    />
                </div>
            )}

            <Drawer
                title={`Tạo đơn — ${conversation.buyer_name ?? (phone || 'khách')}`}
                placement="right"
                width={drawerWidth()}
                open={open}
                onClose={() => setOpen(false)}
                destroyOnHidden
                styles={{ body: { padding: 16 } }}
            >
                {open && (
                    <CreateOrderForm
                        embedded
                        compact
                        active={open}
                        initialDraft={initialDraft}
                        onSaved={handleSaved}
                    />
                )}
            </Drawer>
        </div>
    );
}
