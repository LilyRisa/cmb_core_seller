import { Button, Card, Col, Row, Space, Statistic, Tag, Typography } from 'antd';
import { ArrowRightOutlined, BankOutlined, BookOutlined, DollarOutlined, FileTextOutlined, ShopOutlined, TeamOutlined, WarningFilled } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import { formatAmount, useAccountingDashboardSummary } from '@/lib/accounting';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';

const PERIOD_COLOR: Record<string, string> = { open: 'green', closed: 'orange', locked: 'red' };

export function AccountingDashboardPage() {
    const { data, isLoading, error } = useAccountingDashboardSummary();
    const canView = useCan('accounting.view');
    const canPost = useCan('accounting.post');

    const status = (error as { response?: { status?: number } })?.response?.status;
    if (status === 402) {
        return (
            <Card style={{ marginTop: 8 }}>
                <Typography.Paragraph type="warning" style={{ margin: 0 }}>
                    Module Kế toán thuộc gói Pro/Business. Vui lòng nâng gói để sử dụng.
                </Typography.Paragraph>
            </Card>
        );
    }
    if (!canView) {
        return <Card><Typography.Paragraph type="warning">Vai trò hiện tại không có quyền xem kế toán.</Typography.Paragraph></Card>;
    }

    if (data && !data.initialized) {
        return (
            <div style={{ padding: '8px 0' }}>
                <AccountingSetupBanner />
                <Card><Typography.Paragraph>Hãy khởi tạo hệ thống tài khoản (TT133) ở banner phía trên để bắt đầu sử dụng kế toán.</Typography.Paragraph></Card>
            </div>
        );
    }

    const cash = data?.cash;
    const ar = data?.ar;
    const ap = data?.ap;
    const pl = data?.pl_period;
    const period = data?.current_period;

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />

            <Space style={{ marginBottom: 16, justifyContent: 'space-between', width: '100%' }} wrap>
                <Space size={8}>
                    <Typography.Title level={4} style={{ margin: 0 }}>Tổng quan kế toán</Typography.Title>
                    {period && <Tag color={PERIOD_COLOR[period.status]}>Kỳ {period.code} · {period.status_label}</Tag>}
                </Space>
                {canPost && (
                    <Space wrap>
                        <Link to="/accounting/journals"><Button icon={<BookOutlined />}>Bút toán tay</Button></Link>
                        <Link to="/accounting/ar"><Button icon={<DollarOutlined />}>Phiếu thu</Button></Link>
                        <Link to="/accounting/ap"><Button icon={<FileTextOutlined />}>Phiếu chi / HĐ NCC</Button></Link>
                    </Space>
                )}
            </Space>

            <Row gutter={[16, 16]}>
                <Col xs={24} sm={12} lg={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Tiền mặt & ngân hàng" value={cash?.total ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} prefix={<BankOutlined />} valueStyle={{ color: '#2563EB' }} />
                        <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                            <Typography.Text type="secondary">{cash?.accounts ?? 0} quỹ/tài khoản</Typography.Text>
                            <Link to="/accounting/cash">Chi tiết <ArrowRightOutlined /></Link>
                        </Space>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Phải thu khách hàng" value={ar?.total ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} prefix={<TeamOutlined />} valueStyle={{ color: '#2563EB' }} />
                        <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                            <Typography.Text type={ar && ar.overdue > 0 ? 'danger' : 'secondary'}>
                                {ar && ar.overdue > 0 ? <><WarningFilled /> Quá hạn {formatAmount(ar.overdue)} ₫</> : 'Không có nợ quá hạn'}
                            </Typography.Text>
                            <Link to="/accounting/ar">Chi tiết <ArrowRightOutlined /></Link>
                        </Space>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Phải trả nhà cung cấp" value={ap?.total ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} prefix={<ShopOutlined />} valueStyle={{ color: '#fa541c' }} />
                        <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                            <Typography.Text type={ap && ap.overdue > 0 ? 'danger' : 'secondary'}>
                                {ap && ap.overdue > 0 ? <><WarningFilled /> Quá hạn {formatAmount(ap.overdue)} ₫</> : 'Không có nợ quá hạn'}
                            </Typography.Text>
                            <Link to="/accounting/ap">Chi tiết <ArrowRightOutlined /></Link>
                        </Space>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Lợi nhuận ròng (kỳ này)" value={pl?.net_income ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: (pl?.net_income ?? 0) >= 0 ? '#3f8600' : '#cf1322' }} />
                        <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                            <Typography.Text type="secondary">Sau thuế</Typography.Text>
                            <Link to="/accounting/reports">Báo cáo <ArrowRightOutlined /></Link>
                        </Space>
                    </Card>
                </Col>
            </Row>

            <Card title={<Typography.Text strong>Kết quả kinh doanh — kỳ {period?.code ?? 'hiện tại'}</Typography.Text>} style={{ marginTop: 16 }} loading={isLoading}>
                {pl ? (
                    <Row gutter={[16, 16]}>
                        <Col xs={12} md={8} lg={4}><Statistic title="Doanh thu thuần" value={pl.revenue} formatter={(v) => formatAmount(Number(v))} /></Col>
                        <Col xs={12} md={8} lg={4}><Statistic title="Giá vốn" value={pl.cogs} formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa541c' }} /></Col>
                        <Col xs={12} md={8} lg={4}><Statistic title="Lợi nhuận gộp" value={pl.gross_profit} formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#3f8600' }} /></Col>
                        <Col xs={12} md={8} lg={4}><Statistic title="Chi phí QLKD" value={pl.opex} formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa541c' }} /></Col>
                        <Col xs={12} md={8} lg={4}><Statistic title="Lợi nhuận sau thuế" value={pl.net_income} formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: pl.net_income >= 0 ? '#3f8600' : '#cf1322' }} /></Col>
                    </Row>
                ) : (
                    <Typography.Text type="secondary">Chưa có dữ liệu kết quả kinh doanh cho kỳ hiện tại.</Typography.Text>
                )}
            </Card>
        </div>
    );
}
