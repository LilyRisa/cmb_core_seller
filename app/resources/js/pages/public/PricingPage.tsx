import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Col, List, Row, Skeleton, Typography } from 'antd';
import { CheckCircleTwoTone } from '@ant-design/icons';
import { api } from '@/lib/api';

interface PublicPlan {
    code: string;
    name: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    trial_days: number;
    features: Record<string, unknown> | unknown[];
    limits: Record<string, unknown>;
}

const vnd = (n: number) => `${(n || 0).toLocaleString('vi-VN')}₫`;

/** Bảng giá công khai — đọc /api/v1/public/plans (không cần auth). SPEC 2026-06-26. */
export function PricingPage() {
    const { data, isLoading } = useQuery({
        queryKey: ['public-plans'],
        queryFn: async () => (await api.get<{ data: PublicPlan[] }>('/public/plans')).data.data,
    });

    const featureList = (p: PublicPlan): string[] => {
        const f = p.features as Record<string, unknown> | unknown[];
        if (Array.isArray(f)) return f.map((x) => String(x));
        return Object.entries(f ?? {}).filter(([, v]) => v).map(([k]) => k);
    };

    return (
        <div style={{ maxWidth: 1080, margin: '0 auto', padding: '48px 24px' }}>
            <Typography.Title level={2} style={{ textAlign: 'center' }}>Bảng giá</Typography.Title>
            <Typography.Paragraph type="secondary" style={{ textAlign: 'center', marginBottom: 32 }}>
                Chọn gói phù hợp quy mô gian hàng. Dùng thử miễn phí trước khi nâng cấp.
            </Typography.Paragraph>
            {isLoading ? <Skeleton active /> : (
                <Row gutter={[24, 24]} align="stretch">
                    {(data ?? []).map((p) => (
                        <Col xs={24} sm={12} lg={6} key={p.code}>
                            <Card title={p.name} style={{ height: '100%' }} variant="outlined">
                                <Typography.Title level={3} style={{ marginTop: 0 }}>{vnd(p.price_monthly)}<Typography.Text type="secondary" style={{ fontSize: 14 }}> /tháng</Typography.Text></Typography.Title>
                                {p.price_yearly > 0 && <Typography.Paragraph type="secondary">hoặc {vnd(p.price_yearly)}/năm</Typography.Paragraph>}
                                {p.description && <Typography.Paragraph type="secondary">{p.description}</Typography.Paragraph>}
                                <List size="small" dataSource={featureList(p)} split={false}
                                    renderItem={(f) => <List.Item style={{ paddingInline: 0 }}><CheckCircleTwoTone twoToneColor="#52c41a" style={{ marginRight: 8 }} />{f}</List.Item>} />
                                <Link to="/register"><Button type="primary" block style={{ marginTop: 12 }}>Bắt đầu</Button></Link>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}
        </div>
    );
}
