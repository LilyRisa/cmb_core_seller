import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Alert, App as AntApp, Badge, Button, Card, Col, Divider, Input, InputNumber, Modal, Radio, Row, Segmented, Space, Tag, Typography,
} from 'antd';
import { ArrowLeftOutlined, CheckOutlined, CloseOutlined, CrownOutlined, ShopOutlined, ThunderboltOutlined } from '@ant-design/icons';
import type { PlanFeatures } from '@/lib/billing';
import { errorMessage } from '@/lib/api';
import {
    useBuyAiCredits, useCheckout, usePlans, useSubscription, useValidateVoucher, type Plan, type PlanCode, type VoucherPreview,
} from '@/lib/billing';

const { Title, Text, Paragraph } = Typography;

// Toàn bộ tính năng hiển thị trong bảng so sánh (thứ tự cố định). Mọi gói render đủ list này:
// có → ✓ xanh, không có → ✗ xám + gạch ngang.
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

    return (
        <div style={{ minHeight: '100vh', background: '#f5f6f8', padding: '16px 24px 48px' }}>
            <Space style={{ marginBottom: 16 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={() => navigate(-1)}>Quay lại</Button>
            </Space>

            <div style={{ maxWidth: 1080, margin: '0 auto' }}>
                <Title level={3}><CrownOutlined /> Gói dịch vụ</Title>
                {currentCode && <Paragraph type="secondary">Gói hiện tại: <Tag color="blue">{currentCode}</Tag></Paragraph>}

                <Card size="small" style={{ marginBottom: 16 }}>
                    <Space wrap size={16}>
                        <Segmented value={cycle} onChange={(v) => setCycle(v as 'monthly' | 'yearly')}
                            options={[{ label: 'Hàng tháng', value: 'monthly' }, { label: 'Hàng năm (tiết kiệm hơn)', value: 'yearly' }]} />
                        <Space>
                            <Text>Cổng thanh toán:</Text>
                            <Radio.Group value={gateway} onChange={(e) => setGateway(e.target.value)}
                                options={[{ label: 'SePay (QR)', value: 'sepay' }, { label: 'VNPay', value: 'vnpay' }]} optionType="button" size="small" />
                        </Space>
                        <Space.Compact>
                            <Input placeholder="Mã giảm giá" value={code} style={{ width: 160, textTransform: 'uppercase' }}
                                onChange={(e) => { setCode(e.target.value); setVoucher(null); }} onPressEnter={applyVoucher} allowClear />
                            <Button onClick={applyVoucher} loading={validate.isPending}>Áp dụng</Button>
                        </Space.Compact>
                        {voucher?.valid && <Tag color="green">Đã áp mã{voucher.discount_amount ? ` −${vnd(voucher.discount_amount)}` : ''}</Tag>}
                    </Space>
                </Card>

                {plans.isError && <Alert type="error" showIcon message={errorMessage(plans.error, 'Không tải được gói.')} />}

                <Row gutter={[16, 16]} align="stretch">
                    {offered.map((p) => {
                        const isCurrent = p.code === currentCode;
                        const isFree = p.price_monthly <= 0 && p.price_yearly <= 0;
                        const card = (
                            <Card
                                title={<b style={{ fontSize: 16 }}>{p.name}</b>}
                                style={{ height: '100%', borderColor: isCurrent ? '#1677ff' : undefined, borderWidth: isCurrent ? 2 : undefined }}
                                styles={{ body: { display: 'flex', flexDirection: 'column', height: 'calc(100% - 57px)' } }}
                            >
                                <Title level={3} style={{ margin: 0 }}>
                                    {isFree ? 'Miễn phí' : vnd(price(p))}
                                    {!isFree && <Text type="secondary" style={{ fontSize: 14 }}>/{cycle === 'yearly' ? 'năm' : 'tháng'}</Text>}
                                </Title>
                                {!isFree && cycle === 'yearly' && yearlySavingPct(p) > 0 && <Tag color="green" style={{ marginTop: 4 }}>Tiết kiệm {yearlySavingPct(p)}%</Tag>}
                                <Paragraph type="secondary" style={{ marginTop: 8, minHeight: 44 }}>{p.description}</Paragraph>

                                <Space direction="vertical" size={2} style={{ display: 'flex', marginBottom: 8 }}>
                                    <Text><ShopOutlined style={{ color: '#1677ff' }} /> Gian hàng: <b>{limitText(p.limits?.max_channel_accounts)}</b></Text>
                                    <Text type="secondary" style={{ fontSize: 13, paddingLeft: 22 }}>
                                        Mỗi nền tảng: {limitText(p.limits?.max_channel_accounts_per_platform)}
                                    </Text>
                                    <Text><ThunderboltOutlined style={{ color: '#722ed1' }} /> Lượt AI/kỳ: <b>{limitText(p.limits?.ai_credits_monthly)}</b></Text>
                                </Space>

                                <Divider style={{ margin: '8px 0' }} />

                                <Space direction="vertical" size={4} style={{ display: 'flex', flex: 1, marginBottom: 12 }}>
                                    {FEATURE_ROWS.map(({ key, label }) => {
                                        const on = !!p.features?.[key];
                                        return on ? (
                                            <Text key={key}><CheckOutlined style={{ color: '#52c41a' }} /> {label}</Text>
                                        ) : (
                                            <Text key={key} delete type="secondary" style={{ opacity: 0.55 }}>
                                                <CloseOutlined style={{ color: '#bfbfbf' }} /> {label}
                                            </Text>
                                        );
                                    })}
                                </Space>

                                <Button type="primary" block disabled={isCurrent || isFree} loading={checkout.isPending}
                                    onClick={() => buy(p.code)}>
                                    {isCurrent ? 'Gói hiện tại' : isFree ? 'Gói mặc định' : 'Chọn gói'}
                                </Button>
                            </Card>
                        );
                        return (
                            <Col xs={24} md={12} lg={8} key={p.code}>
                                {isCurrent
                                    ? <Badge.Ribbon text="Đang sử dụng" color="blue" style={{ height: '100%' }}>{card}</Badge.Ribbon>
                                    : card}
                            </Col>
                        );
                    })}
                </Row>

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
        <Card style={{ marginTop: 16 }} title={<Space><ThunderboltOutlined style={{ color: '#722ed1' }} />Mua thêm lượt gọi AI</Space>}>
            <Paragraph type="secondary" style={{ marginBottom: 12 }}>
                Lượt mua là vĩnh viễn, cộng dồn tối đa 5000 (đang có {ai.purchased_balance}). Giá 100đ/lượt.
                Chỉ dùng được khi gói có AI còn hiệu lực.
            </Paragraph>
            {remainingBuyable === 0 ? (
                <Alert type="info" showIcon message="Bạn đã đạt tối đa 5000 lượt đã mua." />
            ) : (
                <Space wrap size={12}>
                    <InputNumber
                        min={500} max={remainingBuyable} step={100} value={amount}
                        onChange={(v) => setAmount(Math.max(500, Math.min(remainingBuyable, Math.round(Number(v ?? 500) / 100) * 100)))}
                        addonAfter="lượt" style={{ width: 160 }}
                    />
                    <Text strong>= {price.toLocaleString('vi-VN')}đ</Text>
                    <Button type="primary" loading={buy.isPending} onClick={doBuy}>Mua lượt AI</Button>
                </Space>
            )}
        </Card>
    );
}
