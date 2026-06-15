import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Alert, App as AntApp, Avatar, Button, Card, DatePicker, Empty, Input, InputNumber, Radio, Result, Space, Spin, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ArrowLeftOutlined, CloudUploadOutlined, DeleteOutlined, PictureOutlined, PlusOutlined, SaveOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { MoneyText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import type { ChannelListing } from '@/lib/inventory';
import { SkuPickerModal } from '@/features/promotions/SkuPickerModal';
import {
    useBusySkuIds, usePromotion, usePromotionCapabilities, usePushPromotion, useSetPromotionSkus, useUpdatePromotion,
} from '@/features/promotions/hooks';
import type { DiscountType, PromotionSku } from '@/features/promotions/api';

interface Row {
    channel_listing_id?: number | null;
    external_product_id: string;
    external_sku_id: string;
    seller_sku: string;
    title?: string;
    image?: string | null;
    base_price: number;
    discount_value: number;
}

function computeSale(base: number, type: DiscountType, value: number): number {
    if (type === 'percent') return Math.round((base * (100 - Math.max(0, Math.min(99, value)))) / 100);
    return Math.max(0, value);
}

export function PromotionEditPage() {
    const { id } = useParams();
    const promotionId = Number(id);
    const navigate = useNavigate();
    const { message } = AntApp.useApp();

    const { data: promo, isLoading, isError, error } = usePromotion(Number.isFinite(promotionId) ? promotionId : null);
    const { data: channelData } = useChannelAccounts();
    const accounts = channelData?.data ?? [];

    const update = useUpdatePromotion();
    const setSkus = useSetPromotionSkus();
    const push = usePushPromotion();

    const provider = promo?.provider ?? null;
    const { data: caps } = usePromotionCapabilities(provider);
    // % chọn được cho MỌI sàn (sàn không hỗ trợ % gốc ⇒ tự quy đổi sang giá sau giảm).
    const nativePercent = caps?.supports_percent ?? false;
    const withTime = caps?.supports_time_of_day ?? true;

    const { data: busySkuIds } = useBusySkuIds(promo?.channel_account_id ?? null, promotionId);

    const [title, setTitle] = useState('');
    const [discountType, setDiscountType] = useState<DiscountType>('fixed');
    const [range, setRange] = useState<[Dayjs, Dayjs] | null>(null);
    const [rows, setRows] = useState<Row[]>([]);
    const [pickerOpen, setPickerOpen] = useState(false);

    useEffect(() => {
        if (!promo) return;
        setTitle(promo.title);
        setDiscountType(promo.discount_type);
        setRange(promo.starts_at && promo.ends_at ? [dayjs(promo.starts_at), dayjs(promo.ends_at)] : null);
        setRows((promo.skus ?? []).map((s) => ({
            channel_listing_id: s.channel_listing_id,
            external_product_id: s.external_product_id ?? '',
            external_sku_id: s.external_sku_id ?? '',
            seller_sku: s.seller_sku ?? '',
            title: s.title ?? undefined,
            image: s.image ?? undefined,
            base_price: s.base_price,
            discount_value: s.discount_value,
        })));
    }, [promo]);

    const shopName = (cid: number) => accounts.find((a) => a.id === cid)?.name ?? `#${cid}`;
    const back = () => navigate('/marketplace/promotions');
    const editable = promo?.status === 'draft' || promo?.status === 'failed';

    const selectedSkuIds = useMemo(() => rows.map((r) => r.external_sku_id).filter(Boolean), [rows]);

    const addRows = (listings: ChannelListing[]) => {
        setRows((prev) => {
            const seen = new Set(prev.map((r) => r.external_sku_id));
            const add = listings
                .filter((l) => (l.external_sku_id ?? '') !== '' && !seen.has(l.external_sku_id!))
                .map((l) => ({
                    channel_listing_id: l.id,
                    external_product_id: l.external_product_id ?? '',
                    external_sku_id: l.external_sku_id ?? '',
                    seller_sku: l.seller_sku ?? '',
                    title: l.title ?? undefined,
                    image: l.image ?? undefined,
                    // Base = GIÁ GỐC (chưa giảm), KHÔNG dùng price hiện tại (có thể đã giảm).
                    base_price: l.original_price ?? l.price ?? 0,
                    discount_value: 0,
                }));
            return [...prev, ...add];
        });
    };

    const setRowValue = (skuId: string, value: number) =>
        setRows((prev) => prev.map((r) => (r.external_sku_id === skuId ? { ...r, discount_value: value } : r)));

    const applyToAll = (value: number) => setRows((prev) => prev.map((r) => ({ ...r, discount_value: value })));

    const buildSkus = (): PromotionSku[] => rows.map((r) => ({
        channel_listing_id: r.channel_listing_id ?? null,
        external_product_id: r.external_product_id,
        external_sku_id: r.external_sku_id,
        seller_sku: r.seller_sku,
        base_price: r.base_price,
        discount_value: r.discount_value,
    }));

    const persist = async () => {
        if (!promo || !range) {
            message.warning('Cần nhập thời gian.');
            return false;
        }
        try {
            await update.mutateAsync({ id: promo.id, payload: { title, discount_type: discountType, starts_at: range[0].toISOString(), ends_at: range[1].toISOString() } });
            await setSkus.mutateAsync({ id: promo.id, skus: buildSkus() });
            return true;
        } catch (e) {
            message.error(errorMessage(e));
            return false;
        }
    };

    const handleSave = async () => {
        if (await persist()) message.success('Đã lưu nháp.');
    };

    const handlePush = async () => {
        if (rows.length === 0) { message.warning('Chưa có SKU nào.'); return; }
        if (!(await persist())) return;
        push.mutate(promo!.id, {
            onSuccess: () => { message.success('Đang đẩy chiến dịch lên sàn…'); back(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns: ColumnsType<Row> = [
        {
            title: 'SKU', key: 'sku',
            render: (_, r) => (
                <Space>
                    <Avatar shape="square" size={40} src={r.image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', flex: 'none' }} />
                    <Space direction="vertical" size={0}>
                        <Typography.Text>{r.title ?? r.external_sku_id}</Typography.Text>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.seller_sku || r.external_sku_id}</Typography.Text>
                    </Space>
                </Space>
            ),
        },
        { title: 'Giá gốc', dataIndex: 'base_price', width: 120, align: 'right', render: (v: number) => <MoneyText value={v} /> },
        {
            title: discountType === 'percent' ? 'Giảm (%)' : 'Giá sau giảm (VND)', key: 'discount', width: 180,
            render: (_, r) => (
                <InputNumber
                    style={{ width: '100%' }} min={0} max={discountType === 'percent' ? 99 : undefined} disabled={!editable}
                    value={r.discount_value} onChange={(v) => setRowValue(r.external_sku_id, Number(v ?? 0))}
                    addonAfter={discountType === 'percent' ? '%' : undefined}
                />
            ),
        },
        { title: 'Giá sau giảm', key: 'sale', width: 130, align: 'right', render: (_, r) => <MoneyText value={computeSale(r.base_price, discountType, r.discount_value)} /> },
        {
            title: '', key: 'rm', width: 50,
            render: (_, r) => editable ? <Button size="small" danger icon={<DeleteOutlined />} onClick={() => setRows((prev) => prev.filter((x) => x.external_sku_id !== r.external_sku_id))} /> : null,
        },
    ];

    const header = (
        <PageHeader
            title={<Space><Button icon={<ArrowLeftOutlined />} onClick={back}>Quay lại</Button><span>Chiến dịch giảm giá</span>{provider && <Tag>{provider}</Tag>}{promo && <Tag>{shopName(promo.channel_account_id)}</Tag>}</Space>}
            subtitle="Chọn SKU và mức giảm rồi đẩy lên sàn. SKU đang thuộc chương trình khác sẽ bị khoá khi chọn."
            extra={editable && (
                <Space>
                    <Button icon={<SaveOutlined />} loading={update.isPending || setSkus.isPending} onClick={handleSave}>Lưu nháp</Button>
                    <Button type="primary" icon={<CloudUploadOutlined />} loading={push.isPending} onClick={handlePush}>Đẩy lên sàn</Button>
                </Space>
            )}
        />
    );

    if (isError) return <div>{header}<Result status="error" title="Không tải được chiến dịch" subTitle={errorMessage(error)} extra={<Button onClick={back}>Quay lại</Button>} /></div>;
    if (isLoading || !promo) return <div>{header}<div style={{ textAlign: 'center', padding: 48 }}><Spin /></div></div>;

    return (
        <div>
            {header}

            {!editable && <Alert type="info" showIcon style={{ marginBottom: 16 }} message="Chiến dịch đã đẩy/đang chạy — chỉ xem. Để sửa, tạo chiến dịch mới hoặc kết thúc rồi tạo lại." />}

            <Card title="Thông tin" style={{ marginBottom: 16 }}>
                <Space direction="vertical" style={{ width: '100%' }} size={12}>
                    <div>
                        <Typography.Text type="secondary">Tên chiến dịch</Typography.Text>
                        <Input style={{ marginTop: 4 }} value={title} onChange={(e) => setTitle(e.target.value)} maxLength={255} disabled={!editable} />
                    </div>
                    <Space wrap size={24}>
                        <div>
                            <Typography.Text type="secondary">Kiểu giảm giá</Typography.Text>
                            <div style={{ marginTop: 4 }}>
                                <Radio.Group value={discountType} disabled={!editable} onChange={(e) => setDiscountType(e.target.value)}>
                                    <Radio value="fixed">Giá cố định</Radio>
                                    <Radio value="percent">Theo %</Radio>
                                </Radio.Group>
                                {discountType === 'percent' && !nativePercent && (
                                    <div><Typography.Text type="secondary" style={{ fontSize: 12 }}>Sàn này dùng giá sau giảm — tự quy đổi từ %.</Typography.Text></div>
                                )}
                            </div>
                        </div>
                        <div>
                            <Typography.Text type="secondary">Thời gian{!withTime && ' (sàn này chỉ theo ngày)'}</Typography.Text>
                            <div style={{ marginTop: 4 }}>
                                <DatePicker.RangePicker
                                    disabled={!editable}
                                    showTime={withTime ? { format: 'HH:mm' } : false}
                                    format={withTime ? 'DD/MM/YYYY HH:mm' : 'DD/MM/YYYY'}
                                    value={range ?? undefined}
                                    onChange={(v) => setRange(v as [Dayjs, Dayjs] | null)}
                                />
                            </div>
                        </div>
                    </Space>
                </Space>
            </Card>

            <Card
                title={`SKU áp dụng (${rows.length})`}
                extra={editable && (
                    <Space>
                        {discountType === 'percent'
                            ? <ApplyAll label="Áp % cho tất cả" suffix="%" onApply={applyToAll} />
                            : <ApplyAll label="Áp giá cho tất cả" onApply={applyToAll} />}
                        <Button icon={<PlusOutlined />} onClick={() => setPickerOpen(true)}>Thêm SKU</Button>
                    </Space>
                )}
            >
                <Table<Row> rowKey="external_sku_id" size="small" dataSource={rows} columns={columns} pagination={false}
                    locale={{ emptyText: <Empty description="Chưa có SKU. Bấm “Thêm SKU”." /> }} />
            </Card>

            <SkuPickerModal
                open={pickerOpen}
                channelAccountId={promo.channel_account_id}
                busySkuIds={busySkuIds ?? []}
                selectedSkuIds={selectedSkuIds}
                onClose={() => setPickerOpen(false)}
                onConfirm={addRows}
            />
        </div>
    );
}

/** Ô nhập + nút áp 1 mức giảm cho toàn bộ SKU (thao tác nhanh). */
function ApplyAll({ label, suffix, onApply }: { label: string; suffix?: string; onApply: (v: number) => void }) {
    const [v, setV] = useState<number | null>(null);
    return (
        <Space.Compact>
            <InputNumber min={0} placeholder={label} style={{ width: 150 }} value={v ?? undefined} addonAfter={suffix} onChange={(x) => setV(x as number | null)} />
            <Button onClick={() => v != null && onApply(v)}>Áp dụng</Button>
        </Space.Compact>
    );
}
