import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Alert, App as AntApp, Button, Card, Col, Divider, Input, InputNumber, Modal, Radio, Row, Segmented, Space, Table, Tag, Tooltip, Typography,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import {
    ArrowLeftOutlined, CheckCircleFilled, CheckOutlined, CrownOutlined, MinusOutlined, ShopOutlined, StarFilled, ThunderboltOutlined,
} from '@ant-design/icons';
import type { PlanFeatures } from '@/lib/billing';
import { errorMessage } from '@/lib/api';
import {
    useBuyAiCredits, useCheckout, usePlans, useSubscription, useValidateVoucher, type Plan, type PlanCode, type VoucherPreview,
} from '@/lib/billing';
import { PlatformQuota } from '@/components/PlatformQuota';

const { Title, Text, Paragraph } = Typography;

// Gói được tô nổi bật là "phổ biến" khi không phải gói đang dùng.
const POPULAR_PLAN: PlanCode = 'pro';

// Toàn bộ tính năng hiển thị trong bảng so sánh (thứ tự cố định).
const FEATURE_ROWS: { key: keyof PlanFeatures; label: string }[] = [
    { key: 'messaging_inbox', label: 'Nhắn tin Facebook Page + sàn' },
    { key: 'messaging_ai', label: 'AI hỗ trợ trả lời tin nhắn' },
    { key: 'marketing_facebook', label: 'Quảng cáo Facebook' },
    { key: 'marketing_tiktok', label: 'Quảng cáo TikTok' },
    { key: 'shop_health_reports', label: 'Báo cáo sàn (sức khỏe/điểm phạt)' },
    { key: 'ai', label: 'Trợ lý & phân tích AI' },
    { key: 'accounting_basic', label: 'Kế toán cơ bản' },
    { key: 'accounting_advanced', label: 'Kế toán nâng cao' },
    { key: 'procurement', label: 'Mua hàng & nhà cung cấp' },
    { key: 'fifo_cogs', label: 'Giá vốn FIFO' },
    { key: 'profit_reports', label: 'Báo cáo lợi nhuận' },
    { key: 'finance_settlements', label: 'Đối soát sàn' },
    { key: 'demand_planning', label: 'Đề xuất nhập hàng' },
    { key: 'mass_listing', label: 'Đăng bán đa sàn' },
    { key: 'automation_rules', label: 'Tự động hoá' },
    { key: 'priority_support', label: 'Hỗ trợ ưu tiên' },
];

const vnd = (n: number) => n.toLocaleString('vi-VN') + 'đ';
const limitText = (n: number | undefined, suffix = '') => (n == null ? '—' : n < 0 ? 'Không giới hạn' : `${n.toLocaleString('vi-VN')}${suffix}`);
const isFreePlan = (p: Plan) => p.price_monthly <= 0 && p.price_yearly <= 0;

// Số tính năng hiển thị tối đa trong thẻ; phần còn lại dồn xuống bảng so sánh.
const CARD_FEATURE_LIMIT = 6;

export function PlansPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const plans = usePlans();
    const { data: sub } = useSubscription();
    const checkout = useCheckout();
    const validate = useValidateVoucher();

    const [cycle, setCycle] = useState<'monthly' | 'yearly'>('monthly');
    const [gateway, setGateway] = useState<'sepay' | 'vnpay'>('sepay');
    const [code, setCode] = useState('');
    const [voucher, setVoucher] = useState<VoucherPreview | null>(null);
    const [pay, setPay] = useState<{ url?: string; qr?: string } | null>(null);

    const currentCode = sub?.subscription?.plan_code ?? null;
    // Hiển thị đầy đủ mọi gói đang kích hoạt (kể cả gói miễn phí/dùng thử). Server đã lọc is_active + sort_order.
    const offered = useMemo(() => plans.data ?? [], [plans.data]);

    const applyVoucher = () => {
        const c = code.trim().toUpperCase();
        if (!c) return;
        validate.mutate({ code: c, cycle }, {
            onSuccess: (v) => {
                setVoucher(v);
                message[v.valid ? 'success' : 'warning'](v.valid
                    ? (v.discount_amount ? `Giảm ${vnd(v.discount_amount)}` : (v.message ?? 'Mã hợp lệ.'))
                    : (v.message ?? 'Mã không hợp lệ.'));
            },
            onError: (e) => message.error(errorMessage(e, 'Không kiểm tra được mã.')),
        });
    };

    const buy = (planCode: PlanCode) => {
        checkout.mutate({ plan_code: planCode, cycle, gateway, ...(voucher?.valid ? { voucher_code: code.trim().toUpperCase() } : {}) }, {
            onSuccess: (res) => {
                const url = res.checkout?.redirect_url;
                const qr = res.checkout?.qr_url;
                if (url) { window.location.href = url; return; }
                setPay({ url, qr });
                message.success('Đã tạo hoá đơn — quét QR / chuyển khoản để hoàn tất.');
            },
            onError: (e) => message.error(errorMessage(e, 'Không tạo được thanh toán.')),
        });
    };

    const price = (p: Plan) => (cycle === 'yearly' ? p.price_yearly : p.price_monthly);
    const yearlySavingPct = (p: Plan) => {
        const full = p.price_monthly * 12;
        return full > 0 ? Math.round((1 - p.price_yearly / full) * 100) : 0;
    };

    // ----- Bảng so sánh: cột = từng gói, hàng = hạn mức + từng tính năng -----
    const comparisonColumns: ColumnsType<Record<string, unknown>> = useMemo(() => {
        const highlight = (planCode: string) => (planCode === currentCode
            ? { style: { background: '#e6f4ff' } }
            : {});
        return [
            {
                title: 'Tính năng',
                dataIndex: 'label',
                key: 'label',
                fixed: 'left',
                width: 240,
                render: (label: string, row) => (
                    <Text strong={(row as { kind?: string }).kind === 'limit'}>{label}</Text>
                ),
            },
            ...offered.map((p) => ({
                key: p.code,
                dataIndex: p.code,
                align: 'center' as const,
                width: 150,
                onHeaderCell: () => highlight(p.code),
                onCell: () => highlight(p.code),
                title: (
                    <Space direction="vertical" size={0} style={{ width: '100%' }}>
                        <Text strong>{p.name}</Text>
                        <Text type="secondary" style={{ fontSize: 12 }}>
                            {isFreePlan(p) ? 'Miễn phí' : `${vnd(cycle === 'yearly' ? p.price_yearly : p.price_monthly)}/${cycle === 'yearly' ? 'năm' : 'tháng'}`}
                        </Text>
                    </Space>
                ),
                render: (val: boolean | string, row: Record<string, unknown>) => (
                    (row as { kind?: string }).kind === 'feature'
                        ? (val
                            ? <CheckOutlined style={{ color: '#52c41a', fontSize: 16 }} />
                            : <MinusOutlined style={{ color: '#d9d9d9' }} />)
                        : <Text>{val as string}</Text>
                ),
            })),
        ];
    }, [offered, currentCode, cycle]);

    const comparisonData = useMemo(() => {
        const limitRows = [
            { key: 'l_channels', label: 'Số gian hàng', kind: 'limit', get: (p: Plan) => limitText(p.limits?.max_channel_accounts) },
            { key: 'l_per_platform', label: 'Gian hàng / nền tảng', kind: 'limit', get: (p: Plan) => limitText(p.limits?.max_channel_accounts_per_platform) },
            { key: 'l_ai', label: 'Lượt AI / kỳ', kind: 'limit', get: (p: Plan) => limitText(p.limits?.ai_credits_monthly) },
        ];
        const featureRows = FEATURE_ROWS.map((f) => ({
            key: f.key as string,
            label: f.label,
            kind: 'feature' as const,
            get: (p: Plan) => !!p.features?.[f.key],
        }));
        return [...limitRows, ...featureRows].map((r) => ({
            key: r.key,
            label: r.label,
            kind: r.kind,
            ...Object.fromEntries(offered.map((p) => [p.code, r.get(p)])),
        }));
    }, [offered]);

    return (
        <div style={{ minHeight: '100vh', background: '#f5f6f8', padding: '16px 24px 48px' }}>
            <div style={{ maxWidth: 1120, margin: '0 auto' }}>
                <Space style={{ marginBottom: 12 }}>
                    <Button icon={<ArrowLeftOutlined />} onClick={() => navigate(-1)}>Quay lại</Button>
                </Space>

                <Title level={3} style={{ marginBottom: 4 }}><CrownOutlined /> Gói dịch vụ</Title>
                <Paragraph type="secondary" style={{ marginBottom: 16 }}>
                    Chọn gói phù hợp với quy mô bán hàng của bạn — nâng/hạ cấp bất cứ lúc nào.
                    {currentCode && <> Gói hiện tại: <Tag color="blue">{currentCode}</Tag></>}
                </Paragraph>

                {/* Thanh điều khiển: chu kỳ + cổng thanh toán + mã giảm giá */}
                <Card size="small" style={{ marginBottom: 20 }}>
                    <Row gutter={[16, 12]} align="middle">
                        <Col flex="auto">
                            <Segmented
                                value={cycle} onChange={(v) => setCycle(v as 'monthly' | 'yearly')}
                                options={[
                                    { label: 'Hàng tháng', value: 'monthly' },
                                    { label: 'Hàng năm · tiết kiệm hơn', value: 'yearly' },
                                ]}
                            />
                        </Col>
                        <Col>
                            <Space size={8} wrap>
                                <Text type="secondary">Thanh toán:</Text>
                                <Radio.Group value={gateway} onChange={(e) => setGateway(e.target.value)}
                                    options={[{ label: 'SePay (QR)', value: 'sepay' }, { label: 'VNPay', value: 'vnpay' }]}
                                    optionType="button" size="small" />
                            </Space>
                        </Col>
                        <Col>
                            <Space.Compact>
                                <Input placeholder="Mã giảm giá" value={code} style={{ width: 150, textTransform: 'uppercase' }}
                                    onChange={(e) => { setCode(e.target.value); setVoucher(null); }} onPressEnter={applyVoucher} allowClear />
                                <Button onClick={applyVoucher} loading={validate.isPending}>Áp dụng</Button>
                            </Space.Compact>
                        </Col>
                        {voucher?.valid && (
                            <Col>
                                <Tag color="green">Đã áp mã{voucher.discount_amount ? ` −${vnd(voucher.discount_amount)}` : ''}</Tag>
                            </Col>
                        )}
                    </Row>
                </Card>

                {plans.isError && <Alert type="error" showIcon style={{ marginBottom: 16 }} message={errorMessage(plans.error, 'Không tải được gói.')} />}

                {/* Thẻ gói */}
                <Row gutter={[16, 16]} align="stretch">
                    {offered.map((p) => {
                        const isCurrent = p.code === currentCode;
                        const isPopular = !isCurrent && p.code === POPULAR_PLAN;
                        const free = isFreePlan(p);
                        const included = FEATURE_ROWS.filter((f) => p.features?.[f.key]);
                        const shown = included.slice(0, CARD_FEATURE_LIMIT);
                        const extra = included.length - shown.length;

                        const accent = isCurrent ? '#1677ff' : isPopular ? '#722ed1' : '#f0f0f0';

                        return (
                            <Col xs={24} sm={12} lg={8} key={p.code}>
                                <Card
                                    style={{
                                        height: '100%',
                                        borderColor: accent,
                                        borderWidth: isCurrent || isPopular ? 2 : 1,
                                        boxShadow: isPopular ? '0 6px 20px rgba(114,46,209,0.12)' : undefined,
                                    }}
                                    styles={{ body: { display: 'flex', flexDirection: 'column', height: '100%', padding: 20 } }}
                                >
                                    {/* Nhãn trạng thái/nổi bật */}
                                    <div style={{ minHeight: 24, marginBottom: 4 }}>
                                        {isCurrent && <Tag icon={<CheckCircleFilled />} color="blue">Đang sử dụng</Tag>}
                                        {isPopular && <Tag icon={<StarFilled />} color="purple">Phổ biến nhất</Tag>}
                                    </div>

                                    <Text strong style={{ fontSize: 17 }}>{p.name}</Text>

                                    <div style={{ margin: '8px 0 4px' }}>
                                        <Text style={{ fontSize: 30, fontWeight: 700, lineHeight: 1 }}>
                                            {free ? 'Miễn phí' : vnd(price(p))}
                                        </Text>
                                        {!free && <Text type="secondary"> /{cycle === 'yearly' ? 'năm' : 'tháng'}</Text>}
                                    </div>
                                    {!free && cycle === 'yearly' && yearlySavingPct(p) > 0
                                        ? <Tag color="green" style={{ marginBottom: 8 }}>Tiết kiệm {yearlySavingPct(p)}%</Tag>
                                        : <div style={{ height: 8 }} />}

                                    {p.description && (
                                        <Paragraph type="secondary" style={{ marginBottom: 12, minHeight: 40 }} ellipsis={{ rows: 2 }}>
                                            {p.description}
                                        </Paragraph>
                                    )}

                                    <Button type={isPopular ? 'primary' : 'default'} block size="large"
                                        disabled={isCurrent || free} loading={checkout.isPending}
                                        onClick={() => buy(p.code)}
                                        style={isPopular ? undefined : { borderColor: isCurrent ? '#1677ff' : undefined }}>
                                        {isCurrent ? 'Gói hiện tại' : free ? 'Gói mặc định' : 'Chọn gói này'}
                                    </Button>

                                    <Divider style={{ margin: '16px 0 12px' }} />

                                    {/* Hạn mức chính — số gian hàng mỗi nền tảng (logo) + tổng + AI */}
                                    <Space direction="vertical" size={6} style={{ display: 'flex', marginBottom: 12 }}>
                                        <Text type="secondary" style={{ fontSize: 12 }}><ShopOutlined style={{ color: '#1677ff' }} /> Gian hàng kết nối (tổng: <b>{limitText(p.limits?.max_channel_accounts)}</b>)</Text>
                                        <PlatformQuota perPlatform={p.limits?.max_channel_accounts_per_platform} />
                                        <Text><ThunderboltOutlined style={{ color: '#722ed1' }} /> Lượt AI/kỳ: <b>{limitText(p.limits?.ai_credits_monthly)}</b></Text>
                                    </Space>

                                    {/* Tính năng nổi bật (chỉ liệt kê cái CÓ — chi tiết xem bảng so sánh) */}
                                    <Space direction="vertical" size={6} style={{ display: 'flex' }}>
                                        {shown.map((f) => (
                                            <Text key={f.key}><CheckOutlined style={{ color: '#52c41a' }} /> {f.label}</Text>
                                        ))}
                                        {extra > 0 && (
                                            <Text type="secondary" style={{ fontSize: 13 }}>+ {extra} tính năng khác — xem bảng so sánh bên dưới</Text>
                                        )}
                                        {included.length === 0 && (
                                            <Text type="secondary" style={{ fontSize: 13 }}>Các tính năng nâng cao có ở gói cao hơn.</Text>
                                        )}
                                    </Space>
                                </Card>
                            </Col>
                        );
                    })}
                </Row>

                {/* Bảng so sánh đầy đủ */}
                {offered.length > 0 && (
                    <Card style={{ marginTop: 24 }} styles={{ body: { padding: 0 } }}
                        title={<Space><CrownOutlined />So sánh chi tiết các gói</Space>}>
                        <Table
                            columns={comparisonColumns}
                            dataSource={comparisonData}
                            pagination={false}
                            size="middle"
                            scroll={{ x: 'max-content' }}
                            rowClassName={(r) => ((r as { kind?: string }).kind === 'limit' ? 'plan-cmp-limit' : '')}
                        />
                    </Card>
                )}

                <AiCreditsBlock gateway={gateway} onPaid={(p) => setPay(p)} />
            </div>

            <Modal title="Thanh toán" open={pay !== null} onCancel={() => setPay(null)} footer={null}>
                {pay?.qr
                    ? <div style={{ textAlign: 'center' }}><img src={pay.qr} alt="QR thanh toán" style={{ maxWidth: 260 }} /><Paragraph type="secondary">Quét QR để thanh toán. Gói sẽ kích hoạt sau khi nhận được tiền.</Paragraph></div>
                    : <Paragraph>Hoá đơn đã tạo. Vào <b>Cài đặt → Gói &amp; thanh toán</b> để xem trạng thái.</Paragraph>}
            </Modal>
        </div>
    );
}

function AiCreditsBlock({ gateway, onPaid }: { gateway: 'sepay' | 'vnpay'; onPaid: (p: { url?: string; qr?: string }) => void }) {
    const { message } = AntApp.useApp();
    const { data } = useSubscription();
    const buy = useBuyAiCredits();
    const ai = data?.usage?.ai_credits;
    const [amount, setAmount] = useState(500);

    if (!ai?.enabled || ai.unlimited) return null;

    const remainingBuyable = Math.max(0, 5000 - ai.purchased_balance);
    const price = amount * 100;

    const doBuy = () => {
        buy.mutate({ amount, gateway }, {
            onSuccess: (res) => {
                const url = res.checkout?.redirect_url;
                if (url) { window.location.href = url; return; }
                onPaid({ url, qr: res.checkout?.qr_url });
                message.success('Đã tạo hoá đơn mua lượt AI — thanh toán để cộng lượt.');
            },
            onError: (e) => message.error(errorMessage(e, 'Không mua được lượt AI.')),
        });
    };

    return (
        <Card style={{ marginTop: 24 }} title={<Space><ThunderboltOutlined style={{ color: '#722ed1' }} />Mua thêm lượt gọi AI</Space>}>
            <Paragraph type="secondary" style={{ marginBottom: 12 }}>
                Lượt mua là vĩnh viễn, cộng dồn tối đa 5000 (đang có {ai.purchased_balance}). Giá 100đ/lượt.
                Chỉ dùng được khi gói có AI còn hiệu lực.
            </Paragraph>
            {remainingBuyable === 0 ? (
                <Alert type="info" showIcon message="Bạn đã đạt tối đa 5000 lượt đã mua." />
            ) : (
                <Space wrap size={12}>
                    <Tooltip title="Tối thiểu 500, bước 100 lượt">
                        <InputNumber
                            min={500} max={remainingBuyable} step={100} value={amount}
                            onChange={(v) => setAmount(Math.max(500, Math.min(remainingBuyable, Math.round(Number(v ?? 500) / 100) * 100)))}
                            addonAfter="lượt" style={{ width: 160 }}
                        />
                    </Tooltip>
                    <Text strong>= {price.toLocaleString('vi-VN')}đ</Text>
                    <Button type="primary" loading={buy.isPending} onClick={doBuy}>Mua lượt AI</Button>
                </Space>
            )}
        </Card>
    );
}
