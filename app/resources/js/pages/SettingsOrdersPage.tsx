import { useEffect, useState } from 'react';
import { Alert, App as AntApp, Button, Card, Divider, InputNumber, Space, Switch, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { CHANNEL_META } from '@/lib/format';
import { ChannelLogo } from '@/components/ChannelLogo';
import { useCan, useTenant, useUpdateTenant } from '@/lib/tenant';

/**
 * /settings/orders — "Cài đặt đơn hàng": biểu phí sàn dùng để ƯỚC TÍNH lợi nhuận mỗi đơn
 * (hoa hồng + phí giao dịch + phí cố định + phí chương trình tùy chọn như Voucher Xtra,
 * Freeship Xtra, PiShip…). Lưu vào tenant.settings.fee_rates[source]. owner/admin sửa.
 * Giá vốn đặt theo từng SKU ở màn hình SKU — không cấu hình ở đây.
 */
interface ProgramCfg { key: string; label: string; kind: 'pct' | 'fixed'; rate: number; cap_per_item?: number; enabled: boolean }
interface SourceCfg { commission_pct: number; transaction_pct: number; fixed_fee: number; programs: ProgramCfg[] }

const SOURCES = ['tiktok', 'shopee', 'lazada'] as const;
type Source = (typeof SOURCES)[number];

// Mặc định mirror config/orders.php (biểu phí chính chủ VN ~06/2026). Phí chương trình mặc định TẮT.
const DEFAULTS: Record<Source, SourceCfg> = {
    tiktok: {
        commission_pct: 14, transaction_pct: 6, fixed_fee: 3000,
        programs: [
            { key: 'affiliate', label: 'Hoa hồng Affiliate', kind: 'pct', rate: 0, enabled: false },
            { key: 'sfp_service', label: 'Phí dịch vụ SFP', kind: 'pct', rate: 0, enabled: false },
        ],
    },
    shopee: {
        commission_pct: 12.5, transaction_pct: 6, fixed_fee: 3000,
        programs: [
            { key: 'voucher_xtra', label: 'Gói Voucher Xtra', kind: 'pct', rate: 5.5, cap_per_item: 50000, enabled: false },
            { key: 'freeship_xtra', label: 'Gói Freeship Xtra', kind: 'pct', rate: 7, enabled: false },
            { key: 'piship', label: 'Gói PiShip', kind: 'fixed', rate: 2700, enabled: false },
        ],
    },
    lazada: {
        commission_pct: 4, transaction_pct: 6, fixed_fee: 3000,
        programs: [
            { key: 'freeship_program', label: 'Gói Freeship/Voucher', kind: 'pct', rate: 0, enabled: false },
        ],
    },
};

function mergeSource(src: Source, saved: Record<string, unknown> | undefined): SourceCfg {
    const def = DEFAULTS[src];
    const s = (saved ?? {}) as Partial<SourceCfg>;
    const savedProgs = new Map((s.programs ?? []).map((p) => [p.key, p]));
    return {
        commission_pct: s.commission_pct ?? def.commission_pct,
        transaction_pct: s.transaction_pct ?? def.transaction_pct,
        fixed_fee: s.fixed_fee ?? def.fixed_fee,
        programs: def.programs.map((p) => {
            const o = savedProgs.get(p.key);
            return { ...p, rate: o?.rate ?? p.rate, enabled: o?.enabled ?? p.enabled };
        }),
    };
}

export function SettingsOrdersPage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const update = useUpdateTenant();
    const canManage = useCan('tenant.settings');
    const [cfg, setCfg] = useState<Record<Source, SourceCfg> | null>(null);

    useEffect(() => {
        if (!tenant) return;
        const saved = (tenant.settings?.fee_rates ?? {}) as Record<string, Record<string, unknown>>;
        setCfg(Object.fromEntries(SOURCES.map((s) => [s, mergeSource(s, saved[s])])) as Record<Source, SourceCfg>);
    }, [tenant]);

    const patchSource = (src: Source, patch: Partial<SourceCfg>) =>
        setCfg((c) => (c ? { ...c, [src]: { ...c[src], ...patch } } : c));
    const patchProgram = (src: Source, key: string, patch: Partial<ProgramCfg>) =>
        setCfg((c) => (c ? { ...c, [src]: { ...c[src], programs: c[src].programs.map((p) => (p.key === key ? { ...p, ...patch } : p)) } } : c));

    const save = () => {
        if (!cfg) return;
        const fee_rates: Record<string, unknown> = {};
        for (const s of SOURCES) {
            fee_rates[s] = {
                commission_pct: cfg[s].commission_pct,
                transaction_pct: cfg[s].transaction_pct,
                fixed_fee: cfg[s].fixed_fee,
                programs: cfg[s].programs.map((p) => ({ key: p.key, enabled: p.enabled, rate: p.rate })),
            };
        }
        // Dùng biểu phí chi tiết ⇒ xoá % phẳng cũ (platform_fee_pct) để tránh đè breakdown.
        update.mutate({ settings: { fee_rates, platform_fee_pct: {} } }, {
            onSuccess: () => message.success('Đã lưu biểu phí sàn'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    if (!tenant || !cfg) return <Card loading title="Cài đặt đơn hàng" />;

    return (
        <Card title="Biểu phí sàn — ước tính lợi nhuận đơn hàng">
            <Typography.Paragraph type="secondary" style={{ marginBottom: 16 }}>
                Khai báo các khoản phí của từng sàn để hệ thống tính <b>lợi nhuận ước tính</b> mỗi đơn:
                <i> tổng tiền − (hoa hồng + phí giao dịch + phí cố định + phí chương trình) − phí vận chuyển − giá vốn hàng</i>.
                Hoa hồng tính trên giá hàng sau giảm giá người bán (không gồm ship); phí giao dịch tính trên tổng khách trả.
                Số mặc định là tham khảo theo công bố của sàn — chỉnh cho khớp ngành hàng & chương trình shop tham gia.
                Giá vốn lấy theo cài đặt từng SKU.
            </Typography.Paragraph>

            <Space direction="vertical" size="large" style={{ width: '100%', maxWidth: 560 }}>
                {SOURCES.map((src) => {
                    const meta = CHANNEL_META[src] ?? { name: src, color: '#8c8c8c' };
                    const c = cfg[src];
                    return (
                        <div key={src}>
                            <Typography.Text strong style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: 15 }}>
                                <ChannelLogo provider={src} size={20} /> {meta.name}
                            </Typography.Text>
                            <div style={{ marginTop: 10, display: 'flex', flexDirection: 'column', gap: 8 }}>
                                <FeeNum label="Hoa hồng / phí cố định" suffix="%" disabled={!canManage} value={c.commission_pct}
                                    onChange={(v) => patchSource(src, { commission_pct: v })} />
                                <FeeNum label="Phí giao dịch" suffix="%" disabled={!canManage} value={c.transaction_pct}
                                    onChange={(v) => patchSource(src, { transaction_pct: v })} />
                                <FeeNum label="Phí cố định / đơn" suffix="đ" max={100000} step={500} disabled={!canManage} value={c.fixed_fee}
                                    onChange={(v) => patchSource(src, { fixed_fee: v })} />
                            </div>
                            <Divider style={{ margin: '12px 0 8px' }} orientation="left" orientationMargin={0}>
                                <Typography.Text type="secondary" style={{ fontSize: 12 }}>Phí chương trình (tùy chọn — bật nếu shop tham gia)</Typography.Text>
                            </Divider>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                                {c.programs.map((p) => (
                                    <div key={p.key} style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                        <Switch size="small" disabled={!canManage} checked={p.enabled}
                                            onChange={(v) => patchProgram(src, p.key, { enabled: v })} />
                                        <span style={{ flex: 1 }}>{p.label}
                                            {p.cap_per_item ? <Typography.Text type="secondary" style={{ fontSize: 11 }}> (tối đa {p.cap_per_item.toLocaleString('vi-VN')}đ/SP)</Typography.Text> : null}
                                        </span>
                                        <InputNumber size="small" disabled={!canManage || !p.enabled} min={0}
                                            max={p.kind === 'fixed' ? 100000 : 100} step={p.kind === 'fixed' ? 500 : 0.5}
                                            value={p.rate} onChange={(v) => patchProgram(src, p.key, { rate: v ?? 0 })}
                                            addonAfter={p.kind === 'fixed' ? 'đ' : '%'} style={{ width: 130 }} />
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                })}

                {canManage
                    ? <Button type="primary" loading={update.isPending} onClick={save}>Lưu biểu phí</Button>
                    : <Alert type="info" showIcon message="Chỉ Chủ sở hữu / Quản trị mới sửa được cài đặt này." />}
            </Space>
        </Card>
    );
}

function FeeNum({ label, suffix, value, onChange, disabled, max = 100, step = 0.5 }: {
    label: string; suffix: string; value: number; onChange: (v: number) => void; disabled?: boolean; max?: number; step?: number;
}) {
    return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
            <span>{label}</span>
            <InputNumber disabled={disabled} min={0} max={max} step={step} value={value}
                onChange={(v) => onChange(v ?? 0)} addonAfter={suffix} style={{ width: 150 }} />
        </div>
    );
}
