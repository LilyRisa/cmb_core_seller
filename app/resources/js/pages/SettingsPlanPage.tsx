import { useMemo, useState } from 'react';
import {
    Alert, App as AntApp, Badge, Button, Card, Col, Empty, List, Modal, Progress,
    Radio, Row, Segmented, Space, Statistic, Tag, Tooltip, Typography,
} from 'antd';
import {
    BankOutlined, CheckCircleOutlined, CloseCircleOutlined, CreditCardOutlined,
    CrownOutlined, FileTextOutlined, QrcodeOutlined, WarningOutlined,
} from '@ant-design/icons';
import { MoneyText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import {
    useCancelSubscription, useCheckout, useInvoices, usePlans, useSubscription,
    type Plan,
} from '@/lib/billing';
import { useCan } from '@/lib/tenant';

/**
 * /settings/plan — Phase 6.4 / SPEC 0018.
 *
 * Trang gồm:
 *   - Card "Gói hiện tại" + usage (gian hàng đã dùng / hạn mức) + nút huỷ (owner only).
 *   - Bảng so sánh 4 gói + nút "Chọn gói này" → modal upgrade (Radio.Group cycle, Segmented gateway).
 *   - List hoá đơn gần đây (link sang chi tiết — chi tiết để PR2/PR3 hoàn thiện).
 *
 * Tuân thủ memory rule:
 *   - Icon: @ant-design/icons (không emoji).
 *   - Lựa chọn ít option: Radio.Group / Segmented (không Select).
 */

const CYCLE_OPTIONS = [
    { label: 'Tháng', value: 'monthly' as const },
    { label: 'Năm (giảm 2 tháng)', value: 'yearly' as const },
];

const GATEWAY_OPTIONS = [
    { label: <span><BankOutlined /> SePay (chuyển khoản)</span>, value: 'sepay' as const, disabled: false },
    { label: <span><CreditCardOutlined /> VNPay (thẻ/QR)</span>, value: 'vnpay' as const, disabled: false },
    { label: <span><QrcodeOutlined /> MoMo (sắp có)</span>, value: 'momo' as const, disabled: true },
];

const FEATURE_LABELS: Record<string, string> = {
    procurement: 'Mua hàng & NCC',
    fifo_cogs: 'Giá vốn FIFO (chuẩn kế toán)',
    profit_reports: 'Báo cáo lợi nhuận thật',
    finance_settlements: 'Đối soát settlement sàn',
    demand_planning: 'Đề xuất nhập hàng',
    mass_listing: 'Đăng bán đa sàn',
    automation_rules: 'Tự động hoá (rules engine)',
    priority_support: 'Hỗ trợ ưu tiên (SLA)',
};

export function SettingsPlanPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('billing.manage');

    const plansQ = usePlans();
    const subQ = useSubscription();
    const invoicesQ = useInvoices();
    const checkout = useCheckout();
    const cancel = useCancelSubscription();

    const subscription = subQ.data?.subscription ?? null;
    const usage = subQ.data?.usage ?? null;
    const currentPlan: Plan | null = subscription?.plan ?? null;

    const channelLimit = usage?.channel_accounts.limit ?? 0;
    const channelUsed = usage?.channel_accounts.used ?? 0;
    const channelPercent = channelLimit > 0 ? Math.min(100, Math.round((channelUsed / channelLimit) * 100)) : 0;

    const orderedPlans = useMemo(() => (plansQ.data ?? []).slice().sort((a, b) => a.price_monthly - b.price_monthly), [plansQ.data]);

    // Upgrade modal state.
    const [upgradeOpen, setUpgradeOpen] = useState(false);
    const [upgradePlan, setUpgradePlan] = useState<Plan | null>(null);
    const [cycle, setCycle] = useState<'monthly' | 'yearly'>('monthly');
    const [gateway, setGateway] = useState<'sepay' | 'vnpay' | 'momo'>('sepay');

    const openUpgrade = (plan: Plan) => {
        setUpgradePlan(plan);
        setCycle('monthly');
        setGateway('sepay');
        setUpgradeOpen(true);
    };

    const submitCheckout = () => {
        if (!upgradePlan) return;
        checkout.mutate(
            { plan_code: upgradePlan.code, cycle, gateway },
            {
                onSuccess: (res) => {
                    message.success(`Đã tạo hoá đơn ${res.invoice.code}. Hoàn tất thanh toán để kích hoạt.`);
                    setUpgradeOpen(false);
                    // PR2/PR3 sẽ điều hướng tới CheckoutPage; PR1 vẫn ở lại trang & reload invoices.
                    invoicesQ.refetch();
                    subQ.refetch();
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const onCancel = () => {
        Modal.confirm({
            title: 'Huỷ gói hiện tại?',
            content: 'Gói sẽ chạy đến hết kỳ hiện tại rồi tự rớt về gói dùng thử. Bạn có thể nâng cấp lại bất cứ lúc nào.',
            okText: 'Huỷ gói', cancelText: 'Đóng', okButtonProps: { danger: true },
            onOk: () => new Promise<void>((resolve, reject) => {
                cancel.mutate(undefined, {
                    onSuccess: () => { message.success('Đã đặt lịch huỷ — gói sẽ ngừng vào cuối kỳ.'); resolve(); },
                    onError: (e) => { message.error(errorMessage(e)); reject(); },
                });
            }),
        });
    };

    const isTrialBanner = subscription?.is_trialing || subscription?.plan_code === 'trial';
    const isPastDue = subscription?.is_past_due;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {isTrialBanner && (
                <Alert
                    type="info" showIcon
                    icon={<CreditCardOutlined />}
                    message={<>Bạn đang dùng gói <strong>{currentPlan?.name ?? 'thử'}</strong> — còn {subscription?.days_left ?? 0} ngày. Nâng cấp để mở khoá tính năng nâng cao.</>}
                />
            )}
            {isPastDue && (
                <Alert
                    type="warning" showIcon icon={<WarningOutlined />}
                    message={<>Gói đã quá hạn — còn {subscription?.days_left ?? 0} ngày trước khi tự rớt về gói dùng thử. Vui lòng thanh toán để giữ tính năng.</>}
                />
            )}

            <Card title="Gói hiện tại">
                {!subscription ? (
                    <Empty description="Hệ thống chưa cấu hình gói — vui lòng liên hệ quản trị viên." />
                ) : (
                    <Row gutter={[16, 16]} align="middle">
                        <Col xs={24} md={8}>
                            <Space direction="vertical" size={2}>
                                <Typography.Text type="secondary">Gói</Typography.Text>
                                <Space>
                                    <Typography.Title level={3} style={{ margin: 0 }}>{currentPlan?.name ?? '—'}</Typography.Title>
                                    {subscription.is_trialing && <Tag color="blue">Dùng thử</Tag>}
                                    {subscription.status === 'active' && <Tag color="green" icon={<CheckCircleOutlined />}>Đang dùng</Tag>}
                                    {subscription.status === 'past_due' && <Tag color="orange" icon={<WarningOutlined />}>Quá hạn</Tag>}
                                    {subscription.status === 'cancelled' && <Tag color="default">Đã huỷ — chạy tiếp đến hết kỳ</Tag>}
                                </Space>
                                <Typography.Text type="secondary">{subscription.billing_cycle === 'yearly' ? 'Thanh toán theo năm' : subscription.billing_cycle === 'monthly' ? 'Thanh toán theo tháng' : 'Gói thử'}</Typography.Text>
                            </Space>
                        </Col>
                        <Col xs={24} md={8}>
                            <Statistic title="Còn lại trong kỳ" value={subscription.days_left} suffix="ngày" />
                        </Col>
                        <Col xs={24} md={8} style={{ textAlign: 'right' }}>
                            {canManage && subscription.status === 'active' && !subscription.cancel_at && (
                                <Button danger icon={<CloseCircleOutlined />} loading={cancel.isPending} onClick={onCancel}>Huỷ gói</Button>
                            )}
                        </Col>
                    </Row>
                )}

                {usage && (
                    <div style={{ marginTop: 16 }}>
                        <Typography.Text strong>Gian hàng đã kết nối</Typography.Text>
                        <Space style={{ width: '100%', marginTop: 8 }} direction="vertical">
                            <Progress
                                percent={channelPercent}
                                status={channelPercent >= 100 ? 'exception' : 'active'}
                                format={() => `${channelUsed} / ${channelLimit > 0 ? channelLimit : '∞'}`}
                            />
                            {channelLimit > 0 && channelUsed >= channelLimit && (
                                <Typography.Text type="warning"><WarningOutlined /> Đã đạt hạn mức — nâng cấp để kết nối thêm.</Typography.Text>
                            )}
                        </Space>
                    </div>
                )}
            </Card>

            <Card title="Các gói có sẵn">
                <Row gutter={[16, 16]}>
                    {orderedPlans.map((plan) => {
                        const isCurrent = plan.code === currentPlan?.code;
                        const isTrial = plan.code === 'trial';
                        const features = plan.features ?? {};
                        return (
                            <Col key={plan.code} xs={24} sm={12} lg={6}>
                                <Card
                                    type={isCurrent ? 'inner' : undefined}
                                    style={{ borderColor: isCurrent ? '#1668dc' : undefined, height: '100%' }}
                                    title={
                                        <Space>
                                            {plan.code === 'business' && <CrownOutlined style={{ color: '#faad14' }} />}
                                            <span>{plan.name}</span>
                                            {isCurrent && <Badge status="processing" text="Đang dùng" />}
                                        </Space>
                                    }
                                >
                                    <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                        <div>
                                            <Typography.Title level={3} style={{ margin: 0 }}>
                                                {plan.price_monthly === 0 ? 'Miễn phí' : <><MoneyText value={plan.price_monthly} /> <Typography.Text type="secondary" style={{ fontSize: 14 }}> / tháng</Typography.Text></>}
                                            </Typography.Title>
                                            {plan.price_yearly > 0 && (
                                                <Typography.Text type="secondary">hoặc <MoneyText value={plan.price_yearly} /> / năm</Typography.Text>
                                            )}
                                        </div>
                                        <div>
                                            <Tag color="blue">{plan.limits.max_channel_accounts < 0 ? 'Không giới hạn' : `${plan.limits.max_channel_accounts} gian hàng`}</Tag>
                                        </div>
                                        <Typography.Text type="secondary">{plan.description}</Typography.Text>
                                        <div style={{ marginTop: 8 }}>
                                            {Object.entries(FEATURE_LABELS).map(([k, label]) => {
                                                const on = (features as Record<string, boolean>)[k] === true;
                                                return (
                                                    <div key={k} style={{ color: on ? undefined : '#a3a3a3' }}>
                                                        {on ? <CheckCircleOutlined style={{ color: '#52c41a' }} /> : <CloseCircleOutlined />} <span>{label}</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        {isCurrent ? (
                                            <Button block disabled>Gói hiện tại</Button>
                                        ) : isTrial ? (
                                            <Tooltip title="Trial tự khởi động khi đăng ký — không chọn được từ đây">
                                                <Button block disabled>Dùng thử</Button>
                                            </Tooltip>
                                        ) : !canManage ? (
                                            <Tooltip title="Chỉ chủ shop được nâng cấp gói">
                                                <Button block disabled>Chọn gói này</Button>
                                            </Tooltip>
                                        ) : (
                                            <Button block type="primary" onClick={() => openUpgrade(plan)}>Chọn gói này</Button>
                                        )}
                                    </Space>
                                </Card>
                            </Col>
                        );
                    })}
                </Row>
            </Card>

            <Card title="Hoá đơn gần đây" extra={<FileTextOutlined />}>
                <List
                    dataSource={invoicesQ.data ?? []}
                    locale={{ emptyText: <Empty description="Chưa có hoá đơn" /> }}
                    renderItem={(inv) => (
                        <List.Item
                            actions={[
                                inv.status === 'pending' ? <Tag color="orange">Chờ thanh toán</Tag> :
                                inv.status === 'paid' ? <Tag color="green">Đã thanh toán</Tag> :
                                inv.status === 'void' ? <Tag>Huỷ</Tag> :
                                <Tag color="blue">{inv.status}</Tag>,
                            ]}
                        >
                            <List.Item.Meta
                                title={<Space><FileTextOutlined /> {inv.code}</Space>}
                                description={
                                    <Space split="·">
                                        <span><MoneyText value={inv.total} /></span>
                                        <span>Kỳ: {inv.period_start ?? '—'} → {inv.period_end ?? '—'}</span>
                                        {inv.paid_at ? <span>Thanh toán: {new Date(inv.paid_at).toLocaleDateString('vi-VN')}</span> : null}
                                    </Space>
                                }
                            />
                        </List.Item>
                    )}
                />
            </Card>

            <Modal
                open={upgradeOpen}
                title={upgradePlan ? `Nâng cấp lên gói ${upgradePlan.name}` : 'Nâng cấp gói'}
                onCancel={() => setUpgradeOpen(false)}
                okText="Tạo hoá đơn & thanh toán"
                cancelText="Đóng"
                confirmLoading={checkout.isPending}
                onOk={submitCheckout}
                width={520}
            >
                {upgradePlan && (
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        <div>
                            <Typography.Text type="secondary">Chu kỳ thanh toán</Typography.Text>
                            <div style={{ marginTop: 6 }}>
                                <Radio.Group
                                    optionType="button"
                                    buttonStyle="solid"
                                    options={CYCLE_OPTIONS}
                                    value={cycle}
                                    onChange={(e) => setCycle(e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <Typography.Text type="secondary">Tổng cần thanh toán</Typography.Text>
                            <Typography.Title level={3} style={{ margin: '4px 0' }}>
                                <MoneyText value={cycle === 'yearly' ? upgradePlan.price_yearly : upgradePlan.price_monthly} />
                                <Typography.Text type="secondary" style={{ fontSize: 14, marginLeft: 8 }}>
                                    {cycle === 'yearly' ? '/ 1 năm' : '/ 1 tháng'}
                                </Typography.Text>
                            </Typography.Title>
                        </div>
                        <div>
                            <Typography.Text type="secondary">Phương thức</Typography.Text>
                            <div style={{ marginTop: 6 }}>
                                <Segmented
                                    block
                                    options={GATEWAY_OPTIONS}
                                    value={gateway}
                                    onChange={(v) => setGateway(v as 'sepay' | 'vnpay' | 'momo')}
                                />
                            </div>
                            <Typography.Paragraph type="secondary" style={{ marginTop: 8 }}>
                                {gateway === 'sepay' && 'Bạn sẽ nhận mã QR + số tài khoản để chuyển khoản; hệ thống tự nhận diện khi giao dịch về (vài giây – vài phút).'}
                                {gateway === 'vnpay' && 'Bạn sẽ được chuyển tới VNPay để thanh toán bằng thẻ/QR/ATM.'}
                                {gateway === 'momo' && 'MoMo đang được hoàn thiện — chọn SePay hoặc VNPay.'}
                            </Typography.Paragraph>
                        </div>
                        <Alert
                            type="info" showIcon
                            message="Cổng thanh toán thật đang được hoàn thiện — phiên bản hiện tại tạo hoá đơn và lưu lại để chủ shop hoàn tất khi cổng sẵn sàng (PR2 SePay / PR3 VNPay)."
                        />
                    </Space>
                )}
            </Modal>
        </div>
    );
}
