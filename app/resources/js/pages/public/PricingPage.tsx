import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Col, Divider, List, Row, Skeleton, Tag, Typography } from 'antd';
import { CheckCircleTwoTone } from '@ant-design/icons';
import { api } from '@/lib/api';
import { PlatformQuota } from '@/components/PlatformQuota';
import { planFeatureList } from '@/lib/planFeatures';

interface PublicPlan {
    code: string;
    name: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    trial_days: number;
    features: Record<string, unknown> | unknown[];
    limits: { max_channel_accounts?: number; max_channel_accounts_per_platform?: number; ai_credits_monthly?: number };
}

const vnd = (n: number) => `${(n || 0).toLocaleString('vi-VN')}₫`;

/** Bảng giá công khai — đọc /api/v1/public/plans. Liệt kê đầy đủ tính năng + số gian hàng/sàn (logo). SPEC 2026-06-26. */
export function PricingPage() {
    const { data, isLoading } = useQuery({
        queryKey: ['public-plans'],
        queryFn: async () => (await api.get<{ data: PublicPlan[] }>('/public/plans')).data.data,
    });

    return (
        <div style={{ background: 'linear-gradient(180deg,#f5f8ff,#fff)', padding: '56px 24px 72px' }}>
            <div style={{ maxWidth: 1200, margin: '0 auto' }}>
                <div style={{ textAlign: 'left', marginBottom: 40 }}>
                    <Typography.Title level={1} style={{ marginBottom: 8 }}>Bảng giá đơn giản, minh bạch</Typography.Title>
                    <Typography.Paragraph style={{ fontSize: 17, color: '#595959', maxWidth: 660 }}>
                        Bắt đầu miễn phí với 1 gian hàng mỗi nền tảng (Shopee, TikTok, Lazada). Nâng cấp khi cần thêm gian hàng, kế toán, quảng cáo và AI.
                    </Typography.Paragraph>
                </div>

                {isLoading ? <Skeleton active paragraph={{ rows: 8 }} /> : (
                    <Row gutter={[24, 24]} align="stretch" justify="start">
                        {(data ?? []).map((p) => {
                            const isRec = p.code.toLowerCase() === 'pro';
                            const isFree = p.price_monthly === 0;
                            const ai = p.limits?.ai_credits_monthly ?? 0;
                            return (
                                <Col xs={24} sm={12} lg={8} key={p.code}>
                                    <Card
                                        style={{ height: '100%', borderColor: isRec ? '#1677ff' : undefined, borderWidth: isRec ? 2 : 1, boxShadow: isRec ? '0 8px 24px rgba(22,119,255,.15)' : undefined }}
                                        styles={{ body: { display: 'flex', flexDirection: 'column', height: '100%' } }}
                                    >
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                            <Typography.Title level={3} style={{ margin: 0 }}>{p.name}</Typography.Title>
                                            {isRec && <Tag color="blue">Phổ biến</Tag>}
                                        </div>
                                        <div style={{ margin: '8px 0' }}>
                                            <Typography.Text style={{ fontSize: 36, fontWeight: 700 }}>{isFree ? 'Miễn phí' : vnd(p.price_monthly)}</Typography.Text>
                                            {!isFree && <Typography.Text type="secondary"> /tháng</Typography.Text>}
                                        </div>
                                        {isFree
                                            ? <Typography.Text type="success" style={{ fontWeight: 500 }}>Miễn phí trọn đời</Typography.Text>
                                            : (p.price_yearly > 0 && <Typography.Text type="secondary">hoặc {vnd(p.price_yearly)}/năm</Typography.Text>)}

                                        <Divider style={{ margin: '14px 0 10px' }} orientation="left" orientationMargin={0}><Typography.Text type="secondary" style={{ fontSize: 12 }}>GIAN HÀNG KẾT NỐI</Typography.Text></Divider>
                                        <PlatformQuota perPlatform={p.limits?.max_channel_accounts_per_platform} facebook={!isFree} />

                                        <Divider style={{ margin: '14px 0 10px' }} orientation="left" orientationMargin={0}><Typography.Text type="secondary" style={{ fontSize: 12 }}>TÍNH NĂNG</Typography.Text></Divider>
                                        <List
                                            size="small" split={false} style={{ flex: 1 }}
                                            dataSource={[...planFeatureList(p.features), ...(ai > 0 ? [`${ai} lượt AI mỗi kỳ`] : [])]}
                                            renderItem={(f) => (
                                                <List.Item style={{ paddingInline: 0, border: 'none', paddingBlock: 2 }}>
                                                    <CheckCircleTwoTone twoToneColor="#52c41a" style={{ marginRight: 8 }} />
                                                    <Typography.Text>{f}</Typography.Text>
                                                </List.Item>
                                            )}
                                        />
                                        <Link to="/register" style={{ marginTop: 16 }}>
                                            <Button type={isRec ? 'primary' : 'default'} block size="large">{isFree ? 'Dùng miễn phí' : 'Bắt đầu'}</Button>
                                        </Link>
                                    </Card>
                                </Col>
                            );
                        })}
                    </Row>
                )}

                <Typography.Paragraph type="secondary" style={{ textAlign: 'left', marginTop: 32 }}>
                    Mọi gói đều đồng bộ đa sàn, xử lý đơn, tồn kho master SKU và giao vận. Có thể nâng/hạ cấp bất cứ lúc nào.
                </Typography.Paragraph>
            </div>
        </div>
    );
}
