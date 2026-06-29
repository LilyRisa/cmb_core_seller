import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Skeleton } from 'antd';
import { CheckCircleOutlined, StarFilled } from '@ant-design/icons';
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
        <section style={{ background: 'var(--bg-soft)', borderTop: '1px solid var(--border-soft)' }}>
            <div className="container">
                {/* Section header — left-aligned */}
                <div style={{ marginBottom: 48 }}>
                    <span className="section-tag">Bảng giá</span>
                    <h2 style={{ marginTop: 16, marginBottom: 12 }}>Bảng giá đơn giản, minh bạch</h2>
                    <p className="section-sub" style={{ maxWidth: 660, margin: 0 }}>
                        Bắt đầu miễn phí với 1 gian hàng mỗi nền tảng (Shopee, TikTok, Lazada). Nâng cấp khi cần thêm gian hàng, kế toán, quảng cáo và AI.
                    </p>
                </div>

                {isLoading ? (
                    <Skeleton active paragraph={{ rows: 8 }} />
                ) : (
                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
                        gap: 24,
                    }}>
                        {(data ?? []).map((p) => {
                            const isRec = p.code.toLowerCase() === 'pro';
                            const isFree = p.price_monthly === 0;
                            const ai = p.limits?.ai_credits_monthly ?? 0;
                            const features = [
                                ...planFeatureList(p.features),
                                ...(ai > 0 ? [`${ai} lượt AI mỗi kỳ`] : []),
                            ];

                            return (
                                <div
                                    key={p.code}
                                    style={{
                                        background: '#fff',
                                        border: `${isRec ? 2 : 1}px solid ${isRec ? 'var(--primary)' : 'var(--border-soft)'}`,
                                        borderRadius: 'var(--radius-lg)',
                                        padding: '28px 24px',
                                        boxShadow: isRec ? 'var(--shadow-lg)' : 'var(--shadow-sm)',
                                        display: 'flex',
                                        flexDirection: 'column',
                                        position: 'relative',
                                        overflow: 'hidden',
                                    }}
                                >
                                    {/* Top accent gradient bar for recommended plan */}
                                    {isRec && (
                                        <div style={{
                                            position: 'absolute',
                                            top: 0, left: 0, right: 0,
                                            height: 3,
                                            background: 'linear-gradient(90deg, var(--primary), var(--accent))',
                                        }} />
                                    )}

                                    {/* Plan name + popular badge */}
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 }}>
                                        <h3 style={{ margin: 0, fontSize: 20 }}>{p.name}</h3>
                                        {isRec && (
                                            <span style={{
                                                display: 'inline-flex', alignItems: 'center', gap: 5,
                                                padding: '3px 10px',
                                                background: 'var(--primary-soft)', color: 'var(--primary)',
                                                borderRadius: 999, fontSize: 12, fontWeight: 700,
                                                flexShrink: 0,
                                            }}>
                                                <StarFilled style={{ fontSize: 10 }} />
                                                Phổ biến
                                            </span>
                                        )}
                                    </div>

                                    {/* Price */}
                                    <div style={{ display: 'flex', alignItems: 'baseline', gap: 4, marginBottom: 2 }}>
                                        <span style={{
                                            fontSize: 38, fontWeight: 800, letterSpacing: '-0.03em', lineHeight: 1,
                                            color: isFree ? 'var(--accent)' : 'var(--text)',
                                        }}>
                                            {isFree ? 'Miễn phí' : vnd(p.price_monthly)}
                                        </span>
                                        {!isFree && (
                                            <span style={{ fontSize: 14, color: 'var(--text-muted)' }}>/tháng</span>
                                        )}
                                    </div>

                                    {/* Yearly price / free-forever note */}
                                    <div style={{ marginBottom: 20, minHeight: 22 }}>
                                        {isFree
                                            ? <span style={{ fontSize: 13.5, color: 'var(--accent)', fontWeight: 600 }}>Miễn phí trọn đời</span>
                                            : (p.price_yearly > 0 && (
                                                <span style={{ fontSize: 13.5, color: 'var(--text-muted)' }}>
                                                    hoặc {vnd(p.price_yearly)}/năm
                                                </span>
                                            ))
                                        }
                                    </div>

                                    {/* Divider: Platform quota */}
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 10 }}>
                                        <span style={{
                                            fontSize: 11, fontWeight: 700, letterSpacing: '0.06em',
                                            color: 'var(--text-soft)', textTransform: 'uppercase', whiteSpace: 'nowrap',
                                        }}>
                                            Gian hàng kết nối
                                        </span>
                                        <div style={{ flex: 1, height: 1, background: 'var(--border-soft)' }} />
                                    </div>
                                    <div style={{ marginBottom: 4 }}>
                                        <PlatformQuota
                                            perPlatform={p.limits?.max_channel_accounts_per_platform}
                                            facebook={!isFree}
                                        />
                                    </div>

                                    {/* Divider: Features */}
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, margin: '16px 0 10px' }}>
                                        <span style={{
                                            fontSize: 11, fontWeight: 700, letterSpacing: '0.06em',
                                            color: 'var(--text-soft)', textTransform: 'uppercase', whiteSpace: 'nowrap',
                                        }}>
                                            Tính năng
                                        </span>
                                        <div style={{ flex: 1, height: 1, background: 'var(--border-soft)' }} />
                                    </div>

                                    {/* Feature list */}
                                    <ul style={{ listStyle: 'none', display: 'flex', flexDirection: 'column', gap: 8, flex: 1, marginBottom: 24 }}>
                                        {features.map((f) => (
                                            <li key={f} style={{ display: 'flex', alignItems: 'flex-start', gap: 9 }}>
                                                <CheckCircleOutlined style={{ color: 'var(--accent)', fontSize: 15, marginTop: 2, flexShrink: 0 }} />
                                                <span style={{ fontSize: 14, color: 'var(--text)', lineHeight: 1.45 }}>{f}</span>
                                            </li>
                                        ))}
                                    </ul>

                                    {/* CTA */}
                                    <Link to="/register" style={{ display: 'block' }}>
                                        {isRec ? (
                                            <button className="btn btn-blue btn-lg" style={{ width: '100%' }}>
                                                {isFree ? 'Dùng miễn phí' : 'Bắt đầu'}
                                            </button>
                                        ) : (
                                            <button
                                                className="btn btn-lg"
                                                style={{
                                                    width: '100%',
                                                    background: 'transparent',
                                                    color: 'var(--text)',
                                                    border: '1.5px solid var(--border)',
                                                }}
                                            >
                                                {isFree ? 'Dùng miễn phí' : 'Bắt đầu'}
                                            </button>
                                        )}
                                    </Link>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Footer note */}
                <p className="section-sub" style={{ marginTop: 32, fontSize: 15 }}>
                    Mọi gói đều đồng bộ đa sàn, xử lý đơn, tồn kho master SKU và giao vận. Có thể nâng/hạ cấp bất cứ lúc nào.
                </p>
            </div>
        </section>
    );
}
