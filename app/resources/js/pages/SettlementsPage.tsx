import { useState } from 'react';
import { Link } from 'react-router-dom';
import { App as AntApp, Avatar, Button, Card, DatePicker, Drawer, Empty, Modal, Radio, Space, Statistic, Switch, Table, Tag, Typography } from 'antd';
import { CheckCircleOutlined, CloudDownloadOutlined, DollarOutlined, ExclamationCircleOutlined, FundOutlined, ReloadOutlined, SyncOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useChannelAccounts } from '@/lib/channels';
import { CHANNEL_META } from '@/lib/format';
import {
    FEE_TYPE_LABEL, type Settlement, type SettlementLine,
    useFetchSettlementsForShop, useReconcileSettlement, useSettlement, useSettlements,
} from '@/lib/finance';

const STATUS_CHIP: Record<Settlement['status'], { color: string; icon?: React.ReactNode }> = {
    pending: { color: 'gold', icon: <ExclamationCircleOutlined /> },
    reconciled: { color: 'green', icon: <CheckCircleOutlined /> },
    error: { color: 'red' },
};

const FEE_TYPE_COLOR: Record<SettlementLine['fee_type'], string> = {
    revenue: 'green', commission: 'orange', payment_fee: 'orange', shipping_fee: 'blue',
    shipping_subsidy: 'cyan', voucher_seller: 'magenta', voucher_platform: 'purple',
    adjustment: 'gold', refund: 'red', other: 'default',
};

/**
 * /finance/settlements — đối soát/Statement của các sàn (Phase 6.2 — SPEC 0016).
 * UI: bảng kỳ đối soát + nút kéo từ sàn + drawer chi tiết với từng dòng phí (chip màu theo loại).
 */
export function SettlementsPage() {
    const { message } = AntApp.useApp();
    const canReconcile = useCan('finance.reconcile');
    const [shopId, setShopId] = useState<number | undefined>();
    const [status, setStatus] = useState<string>('');
    const [page, setPage] = useState(1);
    const { data, isFetching, refetch } = useSettlements({ channel_account_id: shopId, status: status || undefined, page, per_page: 20 });
    const { data: shopsData } = useChannelAccounts();
    const fetchShop = useFetchSettlementsForShop();
    const [detailId, setDetailId] = useState<number | null>(null);
    const [fetchModalOpen, setFetchModalOpen] = useState(false);

    const columns: ColumnsType<Settlement> = [
        { title: 'Gian hàng', key: 'shop', render: (_, r) => (
            <Space>
                {r.channel_account?.provider && <Tag color={CHANNEL_META[r.channel_account.provider]?.color}>{CHANNEL_META[r.channel_account.provider]?.name ?? r.channel_account.provider}</Tag>}
                <Typography.Text strong>{r.channel_account?.name ?? `#${r.channel_account_id}`}</Typography.Text>
            </Space>
        ) },
        { title: 'Kỳ đối soát', key: 'period', render: (_, r) => <Space direction="vertical" size={0}><DateText value={r.period_start} />→ <DateText value={r.period_end} /></Space> },
        { title: 'Mã statement', dataIndex: 'external_id', key: 'ext', width: 180, render: (v) => v ?? <Typography.Text type="secondary">—</Typography.Text> },
        { title: 'Doanh thu', dataIndex: 'total_revenue', key: 'rev', width: 140, align: 'right', render: (v) => <MoneyText value={v} /> },
        { title: 'Phí sàn', dataIndex: 'total_fee', key: 'fee', width: 140, align: 'right', render: (v) => <Typography.Text style={{ color: v < 0 ? '#cf1322' : undefined }}><MoneyText value={Math.abs(v)} /></Typography.Text> },
        { title: 'Phí ship', dataIndex: 'total_shipping_fee', key: 'ship', width: 130, align: 'right', render: (v) => <MoneyText value={v} /> },
        { title: 'Sàn trả seller', dataIndex: 'total_payout', key: 'payout', width: 160, align: 'right', render: (v) => <MoneyText value={v} strong /> },
        { title: 'Trạng thái', dataIndex: 'status', key: 's', width: 150, render: (s, r) => <Tag color={STATUS_CHIP[s as Settlement['status']]?.color} icon={STATUS_CHIP[s as Settlement['status']]?.icon}>{r.status_label}</Tag> },
        { title: 'Kéo về', dataIndex: 'fetched_at', key: 'f', width: 130, render: (v) => <DateText value={v} /> },
    ];

    return (
        <div>
            <PageHeader title="Đối soát sàn" subtitle="Kéo dữ liệu phí thực (commission, payment, ship, voucher, adjustment) từ TikTok / Lazada → tự khớp với đơn → báo cáo lợi nhuận chính xác."
                extra={(
                    <Space>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                        {canReconcile && <Button type="primary" icon={<CloudDownloadOutlined />} onClick={() => setFetchModalOpen(true)}>Kéo đối soát từ sàn</Button>}
                    </Space>
                )}
            />
            <Card>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Radio.Group value={shopId ?? 0} onChange={(e) => { setShopId(e.target.value || undefined); setPage(1); }} optionType="button"
                        options={[{ value: 0, label: 'Tất cả gian hàng' }, ...(shopsData?.data ?? []).map((s) => ({ value: s.id, label: s.name }))]} />
                    <Radio.Group value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} optionType="button" buttonStyle="solid"
                        options={[{ value: '', label: 'Tất cả' }, { value: 'pending', label: 'Chờ đối chiếu' }, { value: 'reconciled', label: 'Đã đối chiếu' }, { value: 'error', label: 'Lỗi' }]} />
                </Space>
                <Table<Settlement> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                    locale={{ emptyText: <Empty image={<FundOutlined style={{ fontSize: 32, color: '#bfbfbf' }} />}
                        description="Chưa có kỳ đối soát nào — bấm 'Kéo đối soát từ sàn' để bắt đầu." /> }}
                    onRow={(r) => ({ onClick: () => setDetailId(r.id), style: { cursor: 'pointer' } })}
                    pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} kỳ` }} />
            </Card>

            <SettlementDetailDrawer id={detailId} canReconcile={canReconcile} onClose={() => setDetailId(null)} />
            <FetchModal open={fetchModalOpen} onClose={() => setFetchModalOpen(false)} shops={shopsData?.data ?? []} onSubmit={(v) => fetchShop.mutate(v, {
                onSuccess: (r) => {
                    message.success(r.queued ? 'Đã yêu cầu kéo đối soát — kết quả sẽ hiện trong vài phút.' : `Đã kéo ${r.fetched ?? 0} kỳ (${r.lines ?? 0} dòng)`);
                    setFetchModalOpen(false); refetch();
                },
                onError: (e) => message.error(errorMessage(e)),
            })} loading={fetchShop.isPending} />
        </div>
    );
}

function FetchModal({ open, onClose, shops, onSubmit, loading }: { open: boolean; onClose: () => void; shops: Array<{ id: number; name: string; provider: string }>; onSubmit: (v: { channelAccountId: number; from?: string; to?: string; sync?: boolean }) => void; loading: boolean }) {
    const [shopId, setShopId] = useState<number | undefined>();
    const [range, setRange] = useState<[Dayjs, Dayjs]>([dayjs().subtract(30, 'day'), dayjs()]);
    const [sync, setSync] = useState(false);
    return (
        <Modal title="Kéo đối soát từ sàn" open={open} onCancel={onClose} okText="Bắt đầu" confirmLoading={loading} width={520}
            onOk={() => { if (!shopId) return; onSubmit({ channelAccountId: shopId, from: range[0].format('YYYY-MM-DD'), to: range[1].format('YYYY-MM-DD'), sync }); }}
            okButtonProps={{ disabled: !shopId }}>
            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                <div>
                    <Typography.Text type="secondary" style={{ fontSize: 13, display: 'block', marginBottom: 4 }}>Gian hàng</Typography.Text>
                    <Radio.Group value={shopId} onChange={(e) => setShopId(e.target.value)} optionType="button"
                        options={shops.filter((s) => s.provider !== 'manual').map((s) => ({ value: s.id, label: <Space><Tag color={CHANNEL_META[s.provider]?.color}>{CHANNEL_META[s.provider]?.name ?? s.provider}</Tag>{s.name}</Space> }))} />
                </div>
                <div>
                    <Typography.Text type="secondary" style={{ fontSize: 13, display: 'block', marginBottom: 4 }}>Khoảng thời gian</Typography.Text>
                    <DatePicker.RangePicker value={range as [Dayjs, Dayjs]} onChange={(v) => { if (v?.[0] && v?.[1]) setRange([v[0], v[1]]); }} format="DD/MM/YYYY" allowClear={false} />
                </div>
                <Space>
                    <Switch checked={sync} onChange={setSync} size="small" />
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Kéo đồng bộ (chờ trong request — chỉ dùng cho sandbox/test, dữ liệu lớn nên để chạy nền)</Typography.Text>
                </Space>
                <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginBottom: 0 }}>
                    Lưu ý: tính năng đối soát theo sàn cần được BẬT trong cấu hình app (env <code>INTEGRATIONS_TIKTOK_FINANCE</code> hoặc <code>INTEGRATIONS_LAZADA_FINANCE</code>) sau khi đối chiếu shape với sandbox của sàn — nếu chưa bật, hệ thống sẽ báo "không hỗ trợ".
                </Typography.Paragraph>
            </Space>
        </Modal>
    );
}

function SettlementDetailDrawer({ id, canReconcile, onClose }: { id: number | null; canReconcile: boolean; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const open = id != null;
    const { data: settlement, isFetching, refetch } = useSettlement(id);
    const reconcile = useReconcileSettlement();
    if (!open) return null;

    const lineColumns: ColumnsType<SettlementLine> = [
        { title: 'Loại phí', dataIndex: 'fee_type', key: 'fee', width: 160, render: (v) => <Tag color={FEE_TYPE_COLOR[v as SettlementLine['fee_type']]}>{FEE_TYPE_LABEL[v as SettlementLine['fee_type']] ?? v}</Tag> },
        { title: 'Đơn hàng', key: 'order', render: (_, l) => l.order ? <Link to={`/orders/${l.order.id}`}><Typography.Text strong>{l.order.order_number ?? l.order.external_order_id ?? `#${l.order.id}`}</Typography.Text></Link> : (l.external_order_id ?? <Typography.Text type="secondary">—</Typography.Text>) },
        { title: 'Số tiền', dataIndex: 'amount', key: 'amount', align: 'right', width: 140, render: (v) => <Typography.Text strong style={{ color: v >= 0 ? '#389e0d' : '#cf1322' }}><MoneyText value={v} strong /></Typography.Text> },
        { title: 'Ngày', dataIndex: 'occurred_at', key: 'oc', width: 130, render: (v) => <DateText value={v} /> },
        { title: 'Mô tả', dataIndex: 'description', key: 'd', render: (v) => v ?? '' },
    ];

    return (
        <Drawer open={open} onClose={onClose} width={900} loading={isFetching}
            title={settlement ? `Đối soát ${settlement.external_id ?? '#' + settlement.id} — ${settlement.channel_account?.name ?? ''}` : ''}
            extra={(
                <Space>
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()} />
                    {canReconcile && settlement?.status === 'pending' && <Button type="primary" icon={<SyncOutlined />} loading={reconcile.isPending}
                        onClick={() => reconcile.mutate(settlement.id, { onSuccess: (r) => message.success(`Đã khớp ${r.matched} dòng với đơn`), onError: (e) => message.error(errorMessage(e)) })}>Đối chiếu lại</Button>}
                </Space>
            )}>
            {settlement && (
                <>
                    <Space wrap size={28} style={{ marginBottom: 16 }}>
                        <Statistic title="Sàn trả seller" value={settlement.total_payout} suffix="₫" prefix={<DollarOutlined />} formatter={(v) => Number(v).toLocaleString('vi-VN')} valueStyle={{ color: '#389e0d' }} />
                        <Statistic title="Doanh thu" value={settlement.total_revenue} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} />
                        <Statistic title="Phí sàn" value={Math.abs(settlement.total_fee)} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} valueStyle={{ color: '#cf1322' }} />
                        <Statistic title="Phí ship" value={settlement.total_shipping_fee} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} />
                    </Space>
                    <Typography.Title level={5}>Chi tiết dòng phí ({settlement.lines_count ?? settlement.lines?.length ?? 0})</Typography.Title>
                    <Table<SettlementLine> rowKey="id" size="small" pagination={{ pageSize: 50 }} dataSource={settlement.lines ?? []} columns={lineColumns}
                        locale={{ emptyText: <Empty description="Chưa có dòng nào" /> }} />
                </>
            )}
        </Drawer>
    );
}
