import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Result, Spin, Steps, Tag, Timeline } from 'antd';
import {
    CarOutlined,
    EnvironmentOutlined,
    InboxOutlined,
    PhoneOutlined,
    ShoppingOutlined,
    UserOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { usePublicTracking, type PublicTracking, type TrackStepState } from '@/lib/publicTracking';
import { formatDate, formatMoney, ORDER_STATUS_COLOR } from '@/lib/format';
import '../../css/public-tracking.css';

const STEP_STATUS: Record<TrackStepState, 'finish' | 'process' | 'wait' | 'error'> = {
    done: 'finish',
    process: 'process',
    wait: 'wait',
    error: 'error',
};

/**
 * SPEC 0030 — public, un-authenticated order tracking page. Reached at
 * `/tracking?code={order_number}`. Read-only; all PII is masked server-side.
 */
export function PublicTrackingPage() {
    const [params] = useSearchParams();
    const code = params.get('code');
    const { data, isLoading, isError } = usePublicTracking(code);

    return (
        <div className="track-shell">
            <header className="track-header">
                <div className="track-brand">
                    <img src="/images/logocmb.png" alt="CMBcoreSeller" />
                    <span>
                        CMBcoreSeller
                        <small>Tra cứu đơn hàng</small>
                    </span>
                </div>
                <span className="track-header-tag">Theo dõi hành trình giao hàng</span>
            </header>

            {isLoading && (
                <div className="track-loading">
                    <Spin size="large" />
                </div>
            )}

            {!isLoading && (isError || !data) && <NotFound hasCode={!!code} />}

            {!isLoading && data && <TrackingBody data={data} />}

            <footer className="track-footer">
                <span>&copy; {new Date().getFullYear()} CMBcoreSeller</span>
                <span className="dot" />
                <span>Quản lý bán hàng đa sàn</span>
            </footer>
        </div>
    );
}

function TrackingBody({ data }: { data: PublicTracking }) {
    const stepItems = data.steps.map((s) => ({ title: s.label, status: STEP_STATUS[s.state] }));
    const current = useMemo(() => {
        const i = data.steps.findIndex((s) => s.state === 'process' || s.state === 'error');
        return i < 0 ? data.steps.length : i;
    }, [data.steps]);

    const codZero = !data.cod.is_cod || data.cod.amount <= 0;

    return (
        <main className="track-container">
            {/* Hero + progress */}
            <section className="track-card">
                <div className="track-hero">
                    <div className="track-hero-code">
                        Mã đơn: <b>{data.order_number}</b>
                    </div>
                    <div className="track-hero-status">
                        <Tag color={ORDER_STATUS_COLOR[data.status] ?? 'default'} style={{ fontSize: 15, padding: '4px 14px', borderRadius: 999 }}>
                            {data.status_label}
                        </Tag>
                    </div>
                    <div className="track-hero-sub">
                        {data.carrier_name ? (
                            <>
                                <CarOutlined /> Vận chuyển qua <strong>{data.carrier_name}</strong>
                            </>
                        ) : (
                            <>Người bán tự giao hàng</>
                        )}
                        {data.placed_at ? ` · Đặt ngày ${formatDate(data.placed_at, false)}` : null}
                    </div>
                </div>

                <Steps className="track-steps" current={current} items={stepItems} responsive labelPlacement="vertical" size="small" />
            </section>

            {/* COD */}
            <section className={`track-cod${codZero ? ' is-zero' : ''}`}>
                <span className="track-cod-label">
                    <WalletOutlined /> {codZero ? 'Thanh toán' : 'Tiền thu hộ khi nhận (COD)'}
                </span>
                <span className="track-cod-amount">{codZero ? 'Không thu hộ' : formatMoney(data.cod.amount)}</span>
            </section>

            {/* Recipient (masked) */}
            <section className="track-card">
                <div className="track-section-title">
                    <UserOutlined /> Người nhận
                </div>
                {data.recipient.name && (
                    <div className="track-info-row">
                        <UserOutlined />
                        <span>{data.recipient.name}</span>
                    </div>
                )}
                {data.recipient.phone && (
                    <div className="track-info-row">
                        <PhoneOutlined />
                        <span>{data.recipient.phone}</span>
                    </div>
                )}
                {data.recipient.area && (
                    <div className="track-info-row">
                        <EnvironmentOutlined />
                        <span>{data.recipient.area}</span>
                    </div>
                )}
            </section>

            {/* Items (name + qty only) */}
            {data.items.length > 0 && (
                <section className="track-card">
                    <div className="track-section-title">
                        <ShoppingOutlined /> Sản phẩm
                    </div>
                    <div className="track-items">
                        {data.items.map((it, i) => (
                            <div className="track-item" key={i}>
                                {it.image ? (
                                    <img className="track-item-thumb" src={it.image} alt="" loading="lazy" />
                                ) : (
                                    <div className="track-item-thumb track-item-thumb-fallback">
                                        <InboxOutlined />
                                    </div>
                                )}
                                <div>
                                    <div className="track-item-name">{it.name}</div>
                                    {it.variation && <div className="track-item-variation">{it.variation}</div>}
                                </div>
                                <div className="track-item-qty">x{it.qty}</div>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Journey timeline */}
            <section className="track-card">
                <div className="track-section-title">
                    <CarOutlined /> Hành trình đơn hàng
                    {data.carrier_name && <Tag color="blue">{data.carrier_name}</Tag>}
                </div>
                {data.timeline.length > 0 ? (
                    <Timeline
                        className="track-timeline"
                        items={data.timeline.map((e, i) => ({
                            color: i === 0 ? '#2563EB' : 'gray',
                            children: (
                                <div>
                                    <div className="track-tl-label">{e.label}</div>
                                    {e.at && <div className="track-tl-meta">{formatDate(e.at)}</div>}
                                </div>
                            ),
                        }))}
                    />
                ) : (
                    <div className="track-hero-sub">Chưa có cập nhật hành trình.</div>
                )}
            </section>
        </main>
    );
}

function NotFound({ hasCode }: { hasCode: boolean }) {
    return (
        <main className="track-container">
            <section className="track-card">
                <Result
                    status="404"
                    title="Không tìm thấy đơn hàng"
                    subTitle={
                        hasCode
                            ? 'Mã đơn không tồn tại hoặc đường dẫn đã bị sao chép thiếu ký tự. Vui lòng kiểm tra lại link người bán đã gửi.'
                            : 'Thiếu mã đơn trong đường dẫn. Vui lòng mở đúng link tra cứu mà người bán đã gửi.'
                    }
                />
            </section>
        </main>
    );
}
