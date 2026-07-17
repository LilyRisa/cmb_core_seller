import { useEffect, useMemo, useRef, useState } from 'react';
import {
    App as AntApp, Alert, Button, Col, Dropdown, Empty, Form, Input, InputNumber, Modal, Radio, Result,
    Row, Segmented, Select, Space, Spin, Switch, Tag, Tooltip, Typography,
} from 'antd';
import type { FormInstance } from 'antd';
import {
    CheckCircleFilled, CloseCircleFilled, EditOutlined, EllipsisOutlined, KeyOutlined,
    LoadingOutlined, PlusOutlined, ReloadOutlined, StarFilled, StarOutlined,
    ThunderboltOutlined, WarningFilled,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { AddressPicker, type PickedAddress } from '@/components/AddressPicker';
import { CarrierLogo, CARRIER_TAGLINE } from '@/components/CarrierLogo';
import { CARRIER_META } from '@/components/CarrierBadge';
import { errorMessage } from '@/lib/api';
import { formatDateShort } from '@/lib/format';
import { useCan, useTenant } from '@/lib/tenant';
import {
    type Carrier, type CarrierAccount, type GhnDistrict, type GhnProvince, type GhnShop, type GhnStation, type GhnWard,
    type VtpProvince, type VtpWard,
    useCarrierAccounts, useCarriers, useCreateCarrierAccount, useDeleteCarrierAccount,
    useGhnMasterData, useGhnShops, useGhnStations, useRevealCarrierCredentials, useUpdateCarrierAccount, useVerifyCarrierAccount, useViettelPostMasterData,
} from '@/lib/fulfillment';

// Trường thông tin xác thực (credentials) theo từng ĐVVC — v1: GHN; carrier khác fallback "token".
// Lưu ý: với GHN form render shop_id qua component riêng (GhnShopSelector) — Select tự load shop list.
// `secret: true` ⇒ render bằng Input.Password (che bằng ••••, có nút con mắt bật/tắt hiển thị).
const CRED_FIELDS: Record<string, Array<{ key: string; label: string; required?: boolean; placeholder?: string; secret?: boolean }>> = {
    ghn: [
        { key: 'token', label: 'API Token', required: true, placeholder: 'Token Production / Test từ GHN Dashboard', secret: true },
    ],
    ghtk: [
        { key: 'token', label: 'API Token', required: true, placeholder: 'Token từ GHTK (Cấu hình → API)', secret: true },
        { key: 'client_source', label: 'Mã đối tác (X-Client-Source)', required: false, placeholder: 'Mã shop/đối tác GHTK (nếu có)' },
    ],
    // VTP: nhập Username+Password HOẶC dán token web — không field nào hard-required (validate ở BE khi verify).
    viettelpost: [
        { key: 'username', label: 'Tài khoản (Username)', required: false, placeholder: 'SĐT/Tài khoản Partner Viettel Post' },
        { key: 'password', label: 'Mật khẩu', required: false, placeholder: 'Mật khẩu tài khoản Partner', secret: true },
        { key: 'token', label: 'Hoặc Token web VTP', required: false, placeholder: 'Token tạo trên viettelpost.vn (nếu không dùng user/mật khẩu)', secret: true },
        { key: 'webhook_secret', label: 'Webhook secret (tuỳ chọn)', required: false, placeholder: 'Secret VTP gửi kèm webhook để xác thực', secret: true },
    ],
    // J&T Express: xác thực merchant per-tenant. apiAccount/privateKey cấp ứng dụng nằm ở config server
    // (integrations.jt.*), KHÔNG nhập ở đây. J&T không công bố cơ chế secret webhook nào — webhook_secret
    // ở đây là quy ước RIÊNG của app: seller tự nhúng giá trị này vào URL webhook (query `?secret=`) lúc
    // gửi cho J&T đăng ký, xem JtExpressConnector::parseWebhook.
    jt: [
        { key: 'customerCode', label: 'Mã khách hàng (customerCode)', required: true, placeholder: 'Do J&T cấp khi ký hợp đồng' },
        { key: 'password', label: 'Mật khẩu', required: true, placeholder: 'Mật khẩu tài khoản J&T', secret: true },
        { key: 'webhook_secret', label: 'Webhook secret (tuỳ chọn)', required: false, placeholder: 'Tự đặt, nhúng vào URL webhook gửi J&T (?secret=...)', secret: true },
    ],
};

// Carrier nào cần "địa chỉ kho hàng" để tạo vận đơn? GHN yêu cầu district_id của kho.
const FROM_ADDRESS_REQUIRED: Record<string, boolean> = { ghn: true, ghtk: true, viettelpost: true, jt: true };

// Các field "tên người gửi / SĐT / địa chỉ" vẫn nhập tay — chỉ mã hành chính do GHN cung cấp được
// load tự động qua cascading Select (xem `GhnFromAddressSection`). Form field names giữ nguyên prefix
// `from_` để `AddCarrierAccountModal.submit()` đóng gói chung vào `meta.from_address`.
const FROM_ADDRESS_BASIC_FIELDS: Array<{ key: string; label: string; required?: boolean; placeholder?: string }> = [
    { key: 'name', label: 'Tên người gửi', required: true, placeholder: 'VD: CMBcore Shop' },
    { key: 'phone', label: 'SĐT', required: true, placeholder: 'VD: 0901234567' },
    { key: 'address', label: 'Địa chỉ kho', required: true, placeholder: 'Số nhà, đường…' },
];

// Danh sách ĐVVC "sắp có" — hiển thị dimmed-card để người dùng biết roadmap.
const COMING_SOON: Array<{ code: string; name: string }> = [
    { code: 'spx', name: 'SPX Express' },
    { code: 'vnpost', name: 'VNPost' },
    { code: 'ahamove', name: 'Ahamove' },
];

interface AddState { open: boolean; carrier?: Carrier | null; edit?: CarrierAccount | null }

// "Cài đặt giao hàng mặc định" lưu theo từng tài khoản ĐVVC (carrier_accounts.meta.defaults). BE đọc ở
// ShipmentService::buildCreatePayload — kích thước gói, loại hàng (→ service), ghi chú xem/thử, gửi tại điểm.
interface ShippingDefaults {
    package?: { length_cm?: number; width_cm?: number; height_cm?: number; weight_grams?: number };
    goods_type?: 'light' | 'heavy';
    required_note?: 'KHONGCHOXEMHANG' | 'CHOXEMHANGKHONGTHU' | 'CHOTHUHANG';
    pickup?: { at_station?: boolean; station_id?: number; station_name?: string };
}

// 3 mức ghi chú ĐVVC (chuẩn GHN required_note). Mặc định an toàn = "Cho xem, không cho thử".
const REQUIRED_NOTE_OPTIONS = [
    { value: 'KHONGCHOXEMHANG', label: 'Không cho xem hàng' },
    { value: 'CHOXEMHANGKHONGTHU', label: 'Cho xem, không cho thử' },
    { value: 'CHOTHUHANG', label: 'Cho xem và thử hàng' },
] as const;

// Giá trị mặc định khi thêm tài khoản mới — khớp fallback cứng của backend.
const DEFAULT_SHIPPING_DEFAULTS = { length_cm: 15, width_cm: 15, height_cm: 10, weight_grams: 500, goods_type: 'light' as const, required_note: 'CHOXEMHANGKHONGTHU' as const };

// Hồ sơ người gửi ("Địa chỉ lấy hàng") lưu ở Cài đặt → In (tenant.settings.print.senders).
// Chỉ có tên/SĐT/địa chỉ tự do (không tách tỉnh/quận/phường, không có mã GHN) — xem SettingsPrintPage.
interface SenderProfile { id: string; name?: string; phone?: string; address?: string; is_default?: boolean }

export function CarrierAccountsPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('fulfillment.carriers');
    const { data: accounts, isFetching } = useCarrierAccounts();
    const { data: carriers, isError, refetch } = useCarriers();
    const update = useUpdateCarrierAccount();
    const del = useDeleteCarrierAccount();

    const [add, setAdd] = useState<AddState>({ open: false });
    const [renaming, setRenaming] = useState<CarrierAccount | null>(null);
    const [aliasDraft, setAliasDraft] = useState('');

    // Nhóm tài khoản theo carrier code (1 ĐVVC có thể có nhiều tài khoản, mỗi tài khoản 1 alias).
    const grouped = useMemo(() => {
        const map = new Map<string, CarrierAccount[]>();
        (accounts ?? []).forEach((a) => {
            const list = map.get(a.carrier) ?? [];
            list.push(a);
            map.set(a.carrier, list);
        });
        return map;
    }, [accounts]);

    if (isError) {
        return <Result status="error" title="Không tải được danh sách ĐVVC" extra={<Button onClick={() => refetch()}>Thử lại</Button>} />;
    }

    const activeCarriers = (carriers ?? []).filter((c) => c.code !== 'manual');
    const manualCarrier = (carriers ?? []).find((c) => c.code === 'manual');
    const installedCarrierCodes = new Set(activeCarriers.map((c) => c.code));
    const totalAccounts = (accounts ?? []).length;
    const activeAccountCount = (accounts ?? []).filter((a) => a.is_active).length;

    const onRenameSubmit = () => {
        if (!renaming) return;
        const trimmed = aliasDraft.trim();
        if (!trimmed) { message.warning('Hãy nhập tên gợi nhớ.'); return; }
        update.mutate({ id: renaming.id, name: trimmed }, {
            onSuccess: () => { message.success('Đã đổi tên tài khoản'); setRenaming(null); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    return (
        <div>
            <PageHeader
                title="Đơn vị vận chuyển"
                subtitle={`${totalAccounts} tài khoản — ${activeAccountCount} đang hoạt động. Một ĐVVC có thể dùng nhiều tài khoản với alias riêng để tách kho / nhãn hàng.`}
                extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>}
            />

            {/* Manual (tự vận chuyển) — luôn có sẵn, không cần thêm tài khoản */}
            {manualCarrier && (
                <ManualCarrierCard accountsCount={(grouped.get('manual') ?? []).length} />
            )}

            <Typography.Title level={5} style={{ fontFamily: 'var(--font-display)', fontWeight: 600, color: 'var(--ink-400)', textTransform: 'uppercase', letterSpacing: '0.14em', fontSize: 11, marginTop: 18, marginBottom: 10 }}>
                ĐVVC tích hợp API
            </Typography.Title>

            <Row gutter={[16, 16]}>
                {activeCarriers.map((carrier) => {
                    const list = grouped.get(carrier.code) ?? [];
                    return (
                        <Col xs={24} md={12} xl={8} key={carrier.code}>
                            <CarrierCard
                                carrier={carrier}
                                accounts={list}
                                canManage={canManage}
                                onAdd={() => setAdd({ open: true, carrier })}
                                onEdit={(a) => setAdd({ open: true, carrier, edit: a })}
                                onRename={(a) => { setRenaming(a); setAliasDraft(a.name); }}
                                onUpdate={update}
                                onDelete={del}
                            />
                        </Col>
                    );
                })}

                {/* Sắp có — chỉ render những carrier KHÔNG nằm trong danh sách registered. */}
                {COMING_SOON.filter((c) => !installedCarrierCodes.has(c.code)).map((c) => (
                    <Col xs={24} md={12} xl={8} key={c.code}>
                        <ComingSoonCard code={c.code} name={c.name} />
                    </Col>
                ))}
            </Row>

            {/* Modal: thêm tài khoản ĐVVC */}
            <AddCarrierAccountModal
                state={add}
                onClose={() => setAdd({ open: false })}
                onAddedMessage={(name) => message.success(`Đã thêm tài khoản ${name}`)}
            />

            {/* Modal: đổi tên alias */}
            <Modal
                open={!!renaming} title="Đặt tên gợi nhớ cho tài khoản" okText="Lưu"
                onCancel={() => setRenaming(null)} onOk={onRenameSubmit} confirmLoading={update.isPending}
            >
                <Typography.Paragraph type="secondary" style={{ marginBottom: 8 }}>
                    ĐVVC: <b>{renaming ? (CARRIER_META[renaming.carrier]?.name ?? renaming.carrier) : ''}</b>.
                    Alias giúp phân biệt khi 1 ĐVVC dùng nhiều tài khoản (VD: theo kho, theo nhãn hàng).
                </Typography.Paragraph>
                <Input
                    value={aliasDraft} onChange={(e) => setAliasDraft(e.target.value)} maxLength={120}
                    placeholder="VD: GHN — Kho Hà Nội · COD" onPressEnter={onRenameSubmit} autoFocus
                />
            </Modal>
        </div>
    );
}

// ---- Carrier card ----------------------------------------------------------

function CarrierCard({
    carrier, accounts, canManage, onAdd, onEdit, onRename, onUpdate, onDelete,
}: {
    carrier: Carrier;
    accounts: CarrierAccount[];
    canManage: boolean;
    onAdd: () => void;
    onEdit: (a: CarrierAccount) => void;
    onRename: (a: CarrierAccount) => void;
    onUpdate: ReturnType<typeof useUpdateCarrierAccount>;
    onDelete: ReturnType<typeof useDeleteCarrierAccount>;
}) {
    const verify = useVerifyCarrierAccount();
    const meta = CARRIER_META[carrier.code] ?? { name: carrier.name, color: 'default' };
    const tagline = CARRIER_TAGLINE[carrier.code] ?? carrier.name;
    const count = accounts.length;
    const hasDefault = accounts.some((a) => a.is_default);
    const [expand, setExpand] = useState(false);
    const visible = expand ? accounts : accounts.slice(0, 3);

    return (
        <div className="carrier-card">
            <div className="carrier-card__ribbon" />

            <div className="carrier-card__head">
                <CarrierLogo code={carrier.code} size={56} />
                <div className="carrier-card__title">
                    <Space size={6} align="center">
                        <Typography.Text style={{ fontFamily: 'var(--font-display)', fontWeight: 500, fontSize: 18, letterSpacing: '-0.01em', color: 'var(--ink-900)' }}>
                            {meta.name}
                        </Typography.Text>
                        {hasDefault && <Tooltip title="Có tài khoản đặt làm mặc định"><StarFilled style={{ color: 'var(--warn-500)', fontSize: 12 }} /></Tooltip>}
                    </Space>
                    <Typography.Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 2 }}>{tagline}</Typography.Text>
                </div>
                <div className="carrier-card__count" title="Số tài khoản đã cấu hình">
                    <span className="num">{count}</span>
                    <span className="lbl">tài khoản</span>
                </div>
            </div>

            {carrier.capabilities.length > 0 && (
                <div className="carrier-card__caps">
                    {carrier.capabilities.map((c) => <span key={c} className="cap-chip">{CAPABILITY_LABEL[c] ?? c}</span>)}
                </div>
            )}

            <div className="carrier-card__divider">
                <span>Tài khoản</span>
                {count > 3 && <a onClick={() => setExpand((e) => !e)}>{expand ? 'Thu gọn' : `Xem cả ${count}`}</a>}
            </div>

            {count === 0 ? (
                <div className="carrier-card__empty">
                    <Empty
                        image={<KeyOutlined style={{ fontSize: 24, color: 'var(--ink-300)' }} />}
                        styles={{ image: { height: 'auto', marginBottom: 4 } }}
                        description={<Typography.Text type="secondary" style={{ fontSize: 12.5 }}>Chưa có tài khoản nào</Typography.Text>}
                    />
                </div>
            ) : (
                <div className="carrier-card__list">
                    {visible.map((a) => (
                        <AccountRow
                            key={a.id} account={a} canManage={canManage}
                            onEdit={() => onEdit(a)}
                            onRename={() => onRename(a)}
                            onToggle={(checked) => onUpdate.mutate({ id: a.id, is_active: checked })}
                            onSetDefault={() => onUpdate.mutate({ id: a.id, is_default: true })}
                            onDelete={() => onDelete.mutateAsync(a.id)}
                            verify={verify}
                        />
                    ))}
                </div>
            )}

            {canManage && (
                <button className="carrier-card__add" onClick={onAdd} type="button">
                    <PlusOutlined /> {count === 0 ? 'Thêm tài khoản đầu tiên' : 'Thêm tài khoản'}
                </button>
            )}

            <style>{CARRIER_CARD_CSS}</style>
        </div>
    );
}

// ---- Account row inside card ----------------------------------------------

function AccountRow({
    account, canManage, onEdit, onRename, onToggle, onSetDefault, onDelete, verify,
}: {
    account: CarrierAccount;
    canManage: boolean;
    onEdit: () => void;
    onRename: () => void;
    onToggle: (checked: boolean) => void;
    onSetDefault: () => void;
    onDelete: () => Promise<void>;
    verify: ReturnType<typeof useVerifyCarrierAccount>;
}) {
    const { message } = AntApp.useApp();
    const meta = (account.meta ?? {}) as Record<string, unknown>;
    const ok = meta.last_verify_ok === true;
    const checked = !!meta.last_verified_at;
    const error = (meta.last_verify_error as string | null) ?? null;
    const expiresAt = (meta.credentials_expires_at as string | null) ?? null;
    const verifiedAt = meta.last_verified_at as string | undefined;
    const expired = expiresAt && dayjs(expiresAt).isBefore(dayjs());

    const onVerify = () => verify.mutate(account.id, {
        onSuccess: (r) => { (r.ok ? message.success : message.error)(`${account.name}: ${r.message}`); },
        onError: (e) => message.error(errorMessage(e)),
    });

    const statusNode = !account.is_active
        ? <span className="dot dot--off" />
        : !checked ? <span className="dot dot--unchecked" />
        : expired ? <span className="dot dot--warn" />
        : ok ? <span className="dot dot--ok" />
        : <span className="dot dot--err" />;

    const statusText = !account.is_active ? 'Đang tạm dừng'
        : !checked ? 'Chưa kiểm tra credentials'
        : expired ? 'Credentials đã hết hạn'
        : ok ? 'Kết nối tốt'
        : (error ?? 'Lỗi xác thực');

    const lastCheckText = verifiedAt ? `Kiểm tra ${formatDateShort(verifiedAt)}` : null;

    return (
        <div className={`acct-row ${account.is_active ? '' : 'is-off'}`}>
            <Tooltip title={statusText}>{statusNode}</Tooltip>
            <div className="acct-row__body">
                <div className="acct-row__title">
                    <Typography.Text strong style={{ color: account.is_active ? 'var(--ink-900)' : 'var(--ink-500)' }}>
                        {account.name}
                    </Typography.Text>
                    {account.is_default && <Tag color="gold" icon={<StarFilled />} style={{ marginInlineEnd: 0, fontSize: 10, padding: '0 6px', lineHeight: '16px' }}>Mặc định</Tag>}
                    {!ok && checked && !expired && account.is_active && (
                        <Tooltip title={error ?? 'Lỗi xác thực'}>
                            <Tag color="red" icon={<CloseCircleFilled />} style={{ marginInlineEnd: 0, fontSize: 10, padding: '0 6px', lineHeight: '16px' }}>Lỗi</Tag>
                        </Tooltip>
                    )}
                    {expired && account.is_active && (
                        <Tag color="orange" icon={<WarningFilled />} style={{ marginInlineEnd: 0, fontSize: 10, padding: '0 6px', lineHeight: '16px' }}>Hết hạn</Tag>
                    )}
                </div>
                <div className="acct-row__meta">
                    {lastCheckText && <span>{lastCheckText}</span>}
                    {account.default_service && <span>·  Dịch vụ <code>{account.default_service}</code></span>}
                </div>
            </div>
            {canManage && (
                <div className="acct-row__actions">
                    <Switch checked={account.is_active} size="small" onChange={onToggle} />
                    <Dropdown
                        trigger={['click']}
                        menu={{ items: [
                            { key: 'edit', icon: <EditOutlined />, label: 'Sửa thông tin / địa chỉ kho', onClick: onEdit },
                            { key: 'rename', icon: <KeyOutlined />, label: 'Đổi tên alias', onClick: onRename },
                            { key: 'verify', icon: <ThunderboltOutlined />, label: 'Kiểm tra kết nối', onClick: onVerify, disabled: verify.isPending },
                            { key: 'default', icon: account.is_default ? <StarFilled /> : <StarOutlined />, label: account.is_default ? 'Đang là mặc định' : 'Đặt làm mặc định', onClick: onSetDefault, disabled: account.is_default },
                            { type: 'divider' },
                            { key: 'delete', icon: <CloseCircleFilled />, danger: true, label: 'Xoá tài khoản', onClick: () => {
                                Modal.confirm({
                                    title: `Xoá tài khoản "${account.name}"?`,
                                    content: 'Sau khi xoá, các đơn đang dùng tài khoản này sẽ không tự tạo được vận đơn.',
                                    okText: 'Xoá', okButtonProps: { danger: true },
                                    onOk: () => onDelete(),
                                });
                            } },
                        ] }}
                    >
                        <Button type="text" size="small" icon={<EllipsisOutlined />} />
                    </Dropdown>
                </div>
            )}
        </div>
    );
}

// ---- Manual / coming-soon cards -------------------------------------------

function ManualCarrierCard({ accountsCount }: { accountsCount: number }) {
    return (
        <div className="carrier-card carrier-card--manual">
            <div className="carrier-card__head">
                <CarrierLogo code="manual" size={56} />
                <div className="carrier-card__title">
                    <Space align="center" size={8}>
                        <Typography.Text style={{ fontFamily: 'var(--font-display)', fontWeight: 500, fontSize: 18 }}>Tự vận chuyển</Typography.Text>
                        <Tag color="green" style={{ marginInlineEnd: 0 }}>Luôn sẵn sàng</Tag>
                    </Space>
                    <Typography.Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 2 }}>
                        Tự nhập mã vận đơn — không cần kết nối API. {accountsCount > 0 ? `(${accountsCount} cấu hình)` : ''}
                    </Typography.Text>
                </div>
            </div>
            <style>{CARRIER_CARD_CSS}</style>
        </div>
    );
}

function ComingSoonCard({ code, name }: { code: string; name: string }) {
    return (
        <div className="carrier-card carrier-card--soon">
            <div className="carrier-card__ribbon carrier-card__ribbon--soon" />
            <div className="carrier-card__head">
                <div style={{ filter: 'grayscale(1)', opacity: 0.55 }}><CarrierLogo code={code} size={56} /></div>
                <div className="carrier-card__title">
                    <Typography.Text style={{ fontFamily: 'var(--font-display)', fontWeight: 500, fontSize: 18, color: 'var(--ink-500)' }}>{name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 2 }}>{CARRIER_TAGLINE[code] ?? name}</Typography.Text>
                </div>
                <Tag style={{ fontFamily: 'var(--font-display)', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.08em', fontSize: 10.5 }}>Sắp có</Tag>
            </div>
            <div className="carrier-card__soon-msg">
                Roadmap — sẽ mở khi tích hợp connector. Bạn vẫn có thể dùng "Tự vận chuyển" và nhập mã vận đơn thủ công cho ĐVVC này.
            </div>
            <style>{CARRIER_CARD_CSS}</style>
        </div>
    );
}

// ---- Add modal -------------------------------------------------------------

function AddCarrierAccountModal({
    state, onClose, onAddedMessage,
}: { state: AddState; onClose: () => void; onAddedMessage: (name: string) => void }) {
    const { message } = AntApp.useApp();
    const create = useCreateCarrierAccount();
    const update = useUpdateCarrierAccount();
    const [form] = Form.useForm();
    const carrier = state.carrier;
    const code = carrier?.code ?? '';
    const isEdit = !!state.edit;
    const editFa = ((state.edit?.meta as Record<string, unknown> | undefined)?.from_address ?? {}) as Record<string, unknown>;
    const editDefaults = ((state.edit?.meta as Record<string, unknown> | undefined)?.defaults ?? {}) as ShippingDefaults;
    const credFields = CRED_FIELDS[code] ?? (code && code !== 'manual' ? [{ key: 'token', label: 'API Token', required: true, secret: true }] : []);
    const needsFromAddress = !!FROM_ADDRESS_REQUIRED[code];

    // "Địa chỉ kho hàng (người gửi)" của MỌI ĐVVC lấy sẵn từ hồ sơ người gửi trong Cài đặt → In
    // (tenant.settings.print.senders) — khỏi nhập lại. Sender chỉ có tên/SĐT/địa chỉ (free-text);
    // tỉnh/quận/phường + mã GHN vẫn phải chọn riêng ở dưới (sender không lưu mã).
    const { data: tenant } = useTenant();
    const senders = useMemo<SenderProfile[]>(() => {
        const s = (tenant?.settings as { print?: { senders?: unknown } } | null)?.print?.senders;
        return Array.isArray(s) ? (s as SenderProfile[]).filter((x) => x && (x.name || x.address)) : [];
    }, [tenant]);
    const defaultSender = useMemo(() => senders.find((s) => s.is_default) ?? senders[0], [senders]);
    const [senderId, setSenderId] = useState<string | undefined>(undefined);

    // Điền tên/SĐT/địa chỉ từ 1 hồ sơ người gửi. overwrite=false ⇒ chỉ điền ô đang trống (auto),
    // overwrite=true ⇒ ghi đè (khi user chủ động chọn hồ sơ khác).
    const applySender = (s: SenderProfile | undefined, overwrite: boolean) => {
        if (!s) return;
        const cur = form.getFieldsValue(['from_name', 'from_phone', 'from_address']) as Record<string, string | undefined>;
        const patch: Record<string, unknown> = {};
        if (overwrite || !cur.from_name) patch.from_name = s.name ?? '';
        if (overwrite || !cur.from_phone) patch.from_phone = s.phone ?? '';
        if (overwrite || !cur.from_address) patch.from_address = s.address ?? '';
        if (Object.keys(patch).length) form.setFieldsValue(patch);
    };

    // Tạo mới (không phải Sửa): auto điền từ hồ sơ người gửi mặc định vào các ô đang trống.
    useEffect(() => {
        if (!state.open || isEdit || !needsFromAddress || !defaultSender) return;
        setSenderId(defaultSender.id);
        applySender(defaultSender, false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.open, isEdit, needsFromAddress, defaultSender]);

    // Tạo mới J&T: mặc định "Trả trước tiền mặt" (an toàn cho seller mới, không cần hợp đồng đối soát riêng).
    useEffect(() => {
        if (!state.open || isEdit || code !== 'jt') return;
        form.setFieldsValue({ jt_pay_type: 'PP_CASH' });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.open, isEdit, code]);

    // Edit mode: nạp sẵn giá trị hiện có. Credentials (token/shop_id…) KHÔNG có trong list (tránh rò rỉ)
    // ⇒ gọi endpoint reveal riêng rồi đổ vào field form. Token nhạy cảm render bằng Input.Password.
    const reveal = useRevealCarrierCredentials();
    useEffect(() => {
        if (!state.open || !state.edit) return;
        const fa = editFa;
        form.setFieldsValue({
            name: state.edit.name,
            default_service: state.edit.default_service ?? undefined,
            is_default: state.edit.is_default,
            from_name: fa.name, from_phone: fa.phone, from_address: fa.address,
            from_province_name: fa.province_name, from_district_name: fa.district_name, from_ward_name: fa.ward_name,
            from_district_id: fa.district_id, from_ward_code: fa.ward_code,
            from_province_id: fa.province_id, from_ward_id: fa.ward_id,
            jt_pay_type: (state.edit.meta as Record<string, unknown> | undefined)?.pay_type === 'PP_PM' ? 'PP_PM' : 'PP_CASH',
        });
        // Lấy credential đã lưu để hiển thị lại (đổ SAU nên GHN Shop/địa chỉ tự nạp theo token).
        reveal.mutate(state.edit.id, {
            onSuccess: (creds) => {
                const credPatch: Record<string, unknown> = {};
                Object.entries(creds ?? {}).forEach(([k, v]) => {
                    if (v === null || v === undefined) return;
                    credPatch[`cred_${k}`] = k === 'shop_id' ? String(v) : v;
                });
                if (Object.keys(credPatch).length) form.setFieldsValue(credPatch);
            },
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.open, state.edit]);

    // Nạp "Cài đặt giao hàng mặc định": edit → từ meta.defaults; tạo mới → giá trị mặc định an toàn.
    useEffect(() => {
        if (!state.open) return;
        const d: ShippingDefaults = isEdit ? editDefaults : {};
        const p = d.package ?? {};
        form.setFieldsValue({
            def_length_cm: p.length_cm ?? DEFAULT_SHIPPING_DEFAULTS.length_cm,
            def_width_cm: p.width_cm ?? DEFAULT_SHIPPING_DEFAULTS.width_cm,
            def_height_cm: p.height_cm ?? DEFAULT_SHIPPING_DEFAULTS.height_cm,
            def_weight_grams: p.weight_grams ?? DEFAULT_SHIPPING_DEFAULTS.weight_grams,
            def_goods_type: d.goods_type ?? DEFAULT_SHIPPING_DEFAULTS.goods_type,
            def_required_note: d.required_note ?? DEFAULT_SHIPPING_DEFAULTS.required_note,
            def_at_station: d.pickup?.at_station ?? false,
            def_station_id: d.pickup?.station_id ? String(d.pickup.station_id) : undefined,
            def_station_name: d.pickup?.station_name,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.open, isEdit]);

    const submit = () => form.validateFields().then((v) => {
        const credentials: Record<string, unknown> = {};
        credFields.forEach((f) => { if (v[`cred_${f.key}`] !== undefined && v[`cred_${f.key}`] !== '') credentials[f.key] = v[`cred_${f.key}`]; });
        // GHN: shop_id do GhnShopSelector ghi vào field `cred_shop_id` (KHÔNG nằm trong credFields) —
        // phải gói vào credentials, nếu không backend sẽ thiếu ShopId ⇒ tạo vận đơn lỗi "không lấy được
        // thông tin kho". Đây là lỗi gốc khiến đẩy đơn GHN thất bại.
        if (code === 'ghn' && v.cred_shop_id !== undefined && v.cred_shop_id !== '') {
            credentials.shop_id = Number(v.cred_shop_id);
        }
        const meta: Record<string, unknown> = {};
        if (needsFromAddress) {
            // Tập hợp tất cả field địa chỉ — text basic + mã GHN do cascading Select tự fill (hidden).
            const fromKeys: Array<{ key: string; numeric?: boolean }> = [
                { key: 'name' }, { key: 'phone' }, { key: 'address' },
                { key: 'ward_name' }, { key: 'district_name' }, { key: 'province_name' },
                { key: 'district_id', numeric: true }, { key: 'ward_code' },
                // VTP: ID đơn vị HC mới (v3) — chỉ có khi carrier = viettelpost.
                { key: 'province_id', numeric: true }, { key: 'ward_id', numeric: true },
            ];
            const fromAddress: Record<string, unknown> = {};
            fromKeys.forEach(({ key, numeric }) => {
                const val = v[`from_${key}`];
                if (val !== undefined && val !== '' && val !== null) {
                    fromAddress[key] = numeric ? Number(val) : val;
                }
            });
            if (Object.keys(fromAddress).length > 0) meta.from_address = fromAddress;
        }
        if (code === 'jt') {
            meta.pay_type = v.jt_pay_type === 'PP_PM' ? 'PP_PM' : 'PP_CASH';
        }
        // "Cài đặt giao hàng mặc định" (theo từng tài khoản ĐVVC) — gói vào meta.defaults. Áp cho mọi ĐVVC;
        // "gửi tại điểm" chỉ GHN có chọn điểm cụ thể (GHTK/VTP chỉ lưu cờ). BE đọc ở buildCreatePayload.
        const atStation = !!v.def_at_station;
        meta.defaults = {
            package: {
                length_cm: Math.max(1, Number(v.def_length_cm) || 15),
                width_cm: Math.max(1, Number(v.def_width_cm) || 15),
                height_cm: Math.max(1, Number(v.def_height_cm) || 10),
                weight_grams: Math.max(1, Number(v.def_weight_grams) || 500),
            },
            goods_type: v.def_goods_type === 'heavy' ? 'heavy' : 'light',
            required_note: ['KHONGCHOXEMHANG', 'CHOXEMHANGKHONGTHU', 'CHOTHUHANG'].includes(v.def_required_note) ? v.def_required_note : 'CHOXEMHANGKHONGTHU',
            pickup: atStation && code === 'ghn' && v.def_station_id
                ? { at_station: true, station_id: Number(v.def_station_id), station_name: v.def_station_name || undefined }
                : { at_station: atStation },
        };
        if (isEdit && state.edit) {
            // Chỉ gửi credentials nếu user nhập mới (BE merge — để trống = giữ nguyên token cũ).
            update.mutate({
                id: state.edit.id,
                name: v.name.trim(),
                is_default: !!v.is_default,
                default_service: v.default_service?.trim() || null,
                ...(Object.keys(credentials).length > 0 ? { credentials } : {}),
                ...(Object.keys(meta).length > 0 ? { meta } : {}),
            }, {
                onSuccess: () => { message.success('Đã cập nhật tài khoản'); form.resetFields(); onClose(); },
                onError: (e) => message.error(errorMessage(e)),
            });
            return;
        }
        create.mutate({
            carrier: code,
            name: v.name.trim(),
            credentials,
            is_default: !!v.is_default,
            default_service: v.default_service?.trim() || null,
            meta: Object.keys(meta).length > 0 ? meta : undefined,
        }, {
            onSuccess: (acc) => { onAddedMessage(acc.name); form.resetFields(); onClose(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <Modal
            open={state.open} title={carrier ? (<Space size={10} align="center"><CarrierLogo code={carrier.code} size={28} /><span>{isEdit ? 'Sửa' : 'Thêm'} tài khoản {CARRIER_META[carrier.code]?.name ?? carrier.name}</span></Space>) : 'Thêm tài khoản'}
            onCancel={onClose} okText={isEdit ? 'Lưu thay đổi' : 'Thêm tài khoản'} confirmLoading={create.isPending || update.isPending} onOk={submit}
            destroyOnClose width={560}
        >
            {carrier && (
                <>
                    <Alert
                        type="info" showIcon style={{ marginBottom: 16 }}
                        message={<><b>Alias</b> là tên gợi nhớ — bạn có thể tạo nhiều tài khoản cho cùng 1 ĐVVC để tách kho, tách nhãn hàng, hoặc tách môi trường (test/production).</>}
                    />
                    <Form form={form} layout="vertical" preserve={false}>
                        <Form.Item name="name" label="Tên gợi nhớ (alias)" rules={[{ required: true, message: 'Nhập tên gợi nhớ' }, { max: 120 }]}>
                            <Input placeholder={`VD: ${CARRIER_META[carrier.code]?.name ?? carrier.name} — Kho Hà Nội`} />
                        </Form.Item>

                        {credFields.length > 0 && (
                            <>
                                <Typography.Title level={5} style={{ marginTop: 4, marginBottom: 8, fontFamily: 'var(--font-display)', fontWeight: 600, fontSize: 14 }}>Thông tin xác thực</Typography.Title>
                                {code === 'viettelpost' && (
                                    <Alert
                                        type="info" showIcon style={{ marginBottom: 12 }}
                                        message={<>Nhập <b>Tài khoản + Mật khẩu</b> Partner Viettel Post, <b>hoặc</b> dán <b>Token</b> tạo trên viettelpost.vn. Chỉ cần 1 trong 2 cách.</>}
                                    />
                                )}
                                {credFields.map((f) => (
                                    <Form.Item
                                        key={f.key} name={`cred_${f.key}`} label={f.label}
                                        rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}
                                        extra={isEdit && f.secret ? 'Đã lưu — bấm biểu tượng con mắt để xem, sửa nếu muốn đổi.' : undefined}
                                    >
                                        {f.secret
                                            ? <Input.Password placeholder={f.placeholder} visibilityToggle autoComplete="new-password" />
                                            : <Input placeholder={f.placeholder} />}
                                    </Form.Item>
                                ))}
                                {/* GHN: 1 token có thể có nhiều shop — Select tự load danh sách sau khi nhập token. */}
                                {code === 'ghn' && <GhnShopSelector form={form} />}
                            </>
                        )}

                        {needsFromAddress && (
                            <>
                                <Typography.Title level={5} style={{ marginTop: 8, marginBottom: 4, fontFamily: 'var(--font-display)', fontWeight: 600, fontSize: 14 }}>Địa chỉ kho hàng (người gửi)</Typography.Title>
                                <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginBottom: 12 }}>
                                    {code === 'ghtk'
                                        ? <>Bắt buộc với GHTK — dùng làm địa chỉ lấy hàng khi tạo vận đơn. GHTK nhận địa chỉ <b>theo tên</b> Tỉnh/Quận/Phường.</>
                                        : code === 'viettelpost'
                                            ? <>Bắt buộc với Viettel Post — dùng làm địa chỉ lấy hàng. Chọn Tỉnh/Phường (đơn vị hành chính mới) để lấy <b>mã VTP</b> tạo vận đơn.</>
                                            : code === 'jt'
                                                ? <>Bắt buộc với J&amp;T Express — dùng làm địa chỉ lấy hàng. J&amp;T chỉ nhận địa chỉ <b>2 cấp</b> Tỉnh/Phường theo danh mục hành chính mới (không có Quận), nhập theo tên.</>
                                                : <>Bắt buộc với GHN — dùng để tạo vận đơn. Mã quận/phường <b>tự động tải</b> từ GHN sau khi bạn nhập API Token.</>}
                                </Typography.Paragraph>

                                {/* Lấy sẵn tên/SĐT/địa chỉ từ hồ sơ người gửi ("Địa chỉ lấy hàng") ở Cài đặt → In. */}
                                {senders.length > 0 ? (
                                    <Form.Item label="Lấy từ Địa chỉ lấy hàng (Cài đặt in)" extra="Điền sẵn tên/SĐT/địa chỉ người gửi. Bạn vẫn có thể sửa lại bên dưới.">
                                        <Radio.Group
                                            value={senderId}
                                            onChange={(e) => { const id = e.target.value as string; setSenderId(id); applySender(senders.find((s) => s.id === id), true); }}
                                        >
                                            <Space direction="vertical" size={4}>
                                                {senders.map((s) => (
                                                    <Radio key={s.id} value={s.id}>
                                                        <b>{s.name || 'Người gửi'}</b>{s.phone ? ` · ${s.phone}` : ''}{s.is_default ? ' · mặc định' : ''}
                                                        {s.address ? <span style={{ color: 'var(--ink-500)', fontSize: 12, display: 'block' }}>{s.address}</span> : null}
                                                    </Radio>
                                                ))}
                                            </Space>
                                        </Radio.Group>
                                    </Form.Item>
                                ) : (
                                    <Alert
                                        type="info" showIcon style={{ marginBottom: 12 }}
                                        message={<>Chưa có hồ sơ người gửi. Thêm ở <b>Cài đặt → In → Địa chỉ lấy hàng</b> để lần sau điền tự động.</>}
                                    />
                                )}

                                {FROM_ADDRESS_BASIC_FIELDS.map((f) => (
                                    <Form.Item key={f.key} name={`from_${f.key}`} label={f.label} rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}>
                                        <Input placeholder={f.placeholder} />
                                    </Form.Item>
                                ))}
                                {code === 'ghn' && <GhnFromAddressSection form={form} initial={isEdit ? {
                                    province_name: editFa.province_name as string | undefined,
                                    district_name: editFa.district_name as string | undefined,
                                    ward_name: editFa.ward_name as string | undefined,
                                    district_id: editFa.district_id as number | undefined,
                                    ward_code: editFa.ward_code as string | undefined,
                                } : undefined} />}
                                {code === 'ghtk' && <GhtkFromAddressSection form={form} initial={isEdit ? { province: editFa.province_name as string | undefined, district: editFa.district_name as string | undefined, ward: editFa.ward_name as string | undefined } : undefined} />}
                                {code === 'viettelpost' && <ViettelPostFromAddressSection form={form} initial={isEdit ? { province_id: editFa.province_id as number | undefined, province_name: editFa.province_name as string | undefined, ward_id: editFa.ward_id as number | undefined, ward_name: editFa.ward_name as string | undefined } : undefined} />}
                                {code === 'jt' && <JtFromAddressSection form={form} initial={isEdit ? { province_name: editFa.province_name as string | undefined, ward_name: editFa.ward_name as string | undefined } : undefined} />}
                            </>
                        )}

                        <Form.Item name="default_service" label="Mã dịch vụ mặc định (tuỳ chọn)" extra="VD: 2 = GHN Standard service_type_id">
                            <Input placeholder="Để trống nếu chưa rõ" />
                        </Form.Item>

                        {code === 'jt' && (
                            <Form.Item name="jt_pay_type" label="Cách trả cước vận chuyển" extra="Trả trước tiền mặt phù hợp với hầu hết seller mới. Đối soát tháng chỉ dùng được nếu bạn đã ký hợp đồng riêng với J&T.">
                                <Radio.Group options={[
                                    { value: 'PP_CASH', label: 'Trả trước tiền mặt' },
                                    { value: 'PP_PM', label: 'Đối soát theo tháng' },
                                ]} />
                            </Form.Item>
                        )}

                        <Typography.Title level={5} style={{ marginTop: 12, marginBottom: 2, fontFamily: 'var(--font-display)', fontWeight: 600, fontSize: 14 }}>Cài đặt giao hàng mặc định</Typography.Title>
                        <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginBottom: 12 }}>
                            Áp dụng khi tạo vận đơn cho tài khoản này. Đơn thủ công dùng cài đặt của tài khoản <b>mặc định</b>; từng đơn vẫn có thể chỉnh lại.
                        </Typography.Paragraph>

                        <Form.Item label="Kích thước gói mặc định (cm) & cân nặng (g)" style={{ marginBottom: 10 }}>
                            <Space wrap size={8}>
                                <Form.Item name="def_length_cm" noStyle><InputNumber min={1} max={200} addonBefore="Dài" style={{ width: 120 }} /></Form.Item>
                                <Form.Item name="def_width_cm" noStyle><InputNumber min={1} max={200} addonBefore="Rộng" style={{ width: 124 }} /></Form.Item>
                                <Form.Item name="def_height_cm" noStyle><InputNumber min={1} max={200} addonBefore="Cao" style={{ width: 120 }} /></Form.Item>
                                <Form.Item name="def_weight_grams" noStyle><InputNumber min={1} max={50000} addonBefore="Nặng" addonAfter="g" style={{ width: 168 }} /></Form.Item>
                            </Space>
                        </Form.Item>

                        <Form.Item name="def_goods_type" label="Loại hàng" extra="Hàng nhẹ → chuyển phát nhanh (GHN service 2); hàng nặng → dịch vụ hàng nặng (GHN service 5).">
                            <Segmented options={[{ value: 'light', label: 'Hàng nhẹ' }, { value: 'heavy', label: 'Hàng nặng' }]} />
                        </Form.Item>

                        <Form.Item name="def_required_note" label="Ghi chú cho ĐVVC (cho khách xem/thử hàng)">
                            <Radio.Group>
                                <Space direction="vertical" size={2}>
                                    {REQUIRED_NOTE_OPTIONS.map((o) => <Radio key={o.value} value={o.value}>{o.label}</Radio>)}
                                </Space>
                            </Radio.Group>
                        </Form.Item>

                        <Form.Item name="def_at_station" valuePropName="checked" label="Gửi hàng tại điểm / bưu cục" extra={code === 'ghn' ? 'Mang đơn tới bưu cục GHN thay vì shipper qua lấy tại kho.' : 'GHTK / Viettel Post: mới lưu ghi chú, chưa hỗ trợ chọn điểm cụ thể.'}>
                            <Switch />
                        </Form.Item>
                        {code === 'ghn' && <GhnStationSelector form={form} />}

                        <Form.Item name="is_default" valuePropName="checked" label="Đặt làm tài khoản mặc định toàn workspace" style={{ marginBottom: 0 }}>
                            <Switch />
                        </Form.Item>
                    </Form>
                </>
            )}
        </Modal>
    );
}

// ---- GHN shop selector (1 token = N shops, must pick one) -----------------

/**
 * Khi user nhập API Token GHN ⇒ debounced fetch danh sách shop của token đó. Hiển thị Select
 * cho user chọn shop. Selected shop → ghi `cred_shop_id` + auto-gợi ý from_address (phone, address,
 * district_id, ward_code) lấy từ shop default warehouse trên GHN.
 */
function GhnShopSelector({ form }: { form: FormInstance }) {
    const { message } = AntApp.useApp();
    const token = (Form.useWatch('cred_token', form) ?? '') as string;
    const shopsMutation = useGhnShops();
    const [shops, setShops] = useState<GhnShop[]>([]);
    const [loading, setLoading] = useState(false);
    const fetchedTokenRef = useRef<string>('');

    useEffect(() => {
        const t = token.trim();
        if (t.length < 8) {
            setShops([]); fetchedTokenRef.current = '';
            form.setFieldsValue({ cred_shop_id: undefined });
            return;
        }
        if (t === fetchedTokenRef.current) return;
        const timer = setTimeout(() => {
            setLoading(true);
            shopsMutation.mutate({ token: t }, {
                onSuccess: (data) => {
                    setShops(data);
                    fetchedTokenRef.current = t;
                    // Auto-select khi chỉ có 1 shop (tránh user phải click).
                    if (data.length === 1) {
                        form.setFieldsValue({ cred_shop_id: String(data[0].id) });
                        applyShopToAddress(form, data[0]);
                    } else if (data.length === 0) {
                        message.warning('Token hợp lệ nhưng chưa có shop nào — tạo shop trên dashboard GHN trước.');
                    }
                },
                onError: (e) => {
                    setShops([]);
                    message.error(errorMessage(e, 'Không tải được danh sách shop từ GHN.'));
                },
                onSettled: () => setLoading(false),
            });
        }, 800);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [token]);

    const shopId = (Form.useWatch('cred_shop_id', form) ?? '') as string;
    const onPickShop = (val: string) => {
        form.setFieldsValue({ cred_shop_id: val });
        const shop = shops.find((s) => String(s.id) === val);
        if (shop) {
            applyShopToAddress(form, shop);
        }
    };

    const tokenReady = token.trim().length >= 8;

    return (
        <>
            <Form.Item
                label="Gian hàng (Shop)"
                name="cred_shop_id"
                rules={[{ required: true, message: 'Chọn 1 gian hàng GHN' }]}
                extra={shops.length > 1 ? <span style={{ color: 'var(--ink-500)' }}>1 token GHN có thể có nhiều shop — chọn shop muốn dùng để tạo vận đơn.</span> : undefined}
            >
                <Select
                    showSearch
                    placeholder={tokenReady ? (loading ? 'Đang tải shop từ GHN…' : 'Chọn gian hàng') : 'Nhập API Token để mở danh sách shop'}
                    disabled={!tokenReady || (shops.length === 0 && !loading)}
                    loading={loading}
                    value={shopId || undefined}
                    onChange={onPickShop}
                    optionFilterProp="label"
                    notFoundContent={loading ? <Spin size="small" /> : 'Chưa có shop nào'}
                    options={shops.map((s) => ({
                        value: String(s.id),
                        label: `${s.name} · #${s.id}${s.phone ? ' · ' + s.phone : ''}`,
                    }))}
                />
            </Form.Item>
            {shops.length > 0 && shopId && (() => {
                const sel = shops.find((s) => String(s.id) === shopId);
                if (!sel) return null;
                return (
                    <div className="ghn-shop-preview">
                        <Space size={6}>
                            <CheckCircleFilled style={{ color: 'var(--success-500)' }} />
                            <span><b>{sel.name}</b> · ShopId <code>{sel.id}</code></span>
                        </Space>
                        {sel.address && <div style={{ marginTop: 4, color: 'var(--ink-600)' }}>{sel.address}</div>}
                        {sel.phone && <div style={{ color: 'var(--ink-500)', fontSize: 12 }}>SĐT: {sel.phone}</div>}
                        <div style={{ marginTop: 6, fontSize: 12, color: 'var(--ink-500)' }}>
                            Đã tự điền tên/SĐT/địa chỉ vào "Địa chỉ kho hàng" bên dưới — bạn có thể chỉnh lại nếu cần.
                        </div>
                    </div>
                );
            })()}
            <style>{GHN_SHOP_CSS}</style>
        </>
    );
}

/**
 * Áp shop GHN vào các form field địa chỉ kho hàng (from_*). Chỉ điền khi field hiện đang RỖNG
 * — không ghi đè giá trị user đã chỉnh tay.
 */
function applyShopToAddress(form: FormInstance, shop: GhnShop): void {
    const current = form.getFieldsValue([
        'from_name', 'from_phone', 'from_address',
        'from_district_id', 'from_ward_code',
    ]);
    const patch: Record<string, unknown> = {};
    if (!current.from_name && shop.name) patch.from_name = shop.name;
    if (!current.from_phone && shop.phone) patch.from_phone = shop.phone;
    if (!current.from_address && shop.address) patch.from_address = shop.address;
    if (!current.from_district_id && shop.district_id) patch.from_district_id = shop.district_id;
    if (!current.from_ward_code && shop.ward_code) patch.from_ward_code = shop.ward_code;
    if (Object.keys(patch).length > 0) {
        form.setFieldsValue(patch);
    }
}

const GHN_SHOP_CSS = `
.ghn-shop-preview{
    margin: -6px 0 14px;
    padding: 10px 14px;
    border-radius: var(--radius);
    border: 1px solid #A7F3D0;
    background: var(--success-50, #ECFDF5);
    color: #047857;
    font-size: 13px;
    line-height: 1.5;
}
.ghn-shop-preview b{ color: var(--ink-900); font-weight: 600; }
.ghn-shop-preview code{
    font-family: var(--font-mono);
    font-size: 12px;
    background: rgba(15,23,42,.06);
    padding: 1px 6px;
    border-radius: 4px;
}
`;

// ---- GHN station selector (gửi hàng tại điểm) -----------------------------

/**
 * Khi bật "gửi hàng tại điểm" cho GHN ⇒ tải danh sách bưu cục quanh khu vực kho gửi (from_district_id)
 * qua proxy /carrier-accounts/ghn/stations (cần token + shop_id). User chọn 1 điểm ⇒ ghi def_station_id
 * + def_station_name để submit() gói vào meta.defaults.pickup. Ẩn khi chưa bật switch.
 */
function GhnStationSelector({ form }: { form: FormInstance }) {
    const { message } = AntApp.useApp();
    const atStation = Form.useWatch('def_at_station', form) as boolean | undefined;
    const token = (Form.useWatch('cred_token', form) ?? '') as string;
    const shopId = (Form.useWatch('cred_shop_id', form) ?? '') as string;
    const districtId = Form.useWatch('from_district_id', form) as number | string | undefined;
    const stationId = (Form.useWatch('def_station_id', form) ?? '') as string;
    const stationsMutation = useGhnStations();
    const [stations, setStations] = useState<GhnStation[]>([]);
    const [loading, setLoading] = useState(false);
    const fetchedKeyRef = useRef<string>('');

    useEffect(() => {
        if (!atStation) return;
        const t = token.trim();
        const sid = Number(shopId);
        const did = Number(districtId);
        if (t.length < 8 || !sid || !did) { setStations([]); fetchedKeyRef.current = ''; return; }
        const key = `${t}|${sid}|${did}`;
        if (key === fetchedKeyRef.current) return;
        const timer = setTimeout(() => {
            setLoading(true);
            stationsMutation.mutate({ token: t, shop_id: sid, district_id: did }, {
                onSuccess: (data) => {
                    setStations(data);
                    fetchedKeyRef.current = key;
                    if (data.length === 0) message.info('GHN chưa có điểm gửi cho khu vực kho này — dùng lấy hàng tại kho.');
                },
                onError: (e) => { setStations([]); message.error(errorMessage(e, 'Không tải được danh sách điểm gửi GHN.')); },
                onSettled: () => setLoading(false),
            });
        }, 600);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [atStation, token, shopId, districtId]);

    if (!atStation) return null;

    return (
        <Form.Item label="Điểm gửi hàng (bưu cục GHN)" extra="Danh sách theo khu vực kho gửi ở trên. Chọn kho gửi + nhập token để tải điểm.">
            <Select
                showSearch
                placeholder={loading ? 'Đang tải điểm gửi…' : (stations.length ? 'Chọn điểm gửi' : 'Chọn kho gửi + nhập token để tải điểm')}
                loading={loading}
                value={stationId || undefined}
                optionFilterProp="label"
                notFoundContent={loading ? <Spin size="small" /> : 'Chưa có điểm gửi'}
                onChange={(val) => {
                    const st = stations.find((s) => String(s.station_id) === val);
                    form.setFieldsValue({ def_station_id: val, def_station_name: st?.name });
                }}
                options={stations.map((s) => ({ value: String(s.station_id), label: `${s.name}${s.address ? ' · ' + s.address : ''}` }))}
            />
            <Form.Item name="def_station_id" hidden><Input /></Form.Item>
            <Form.Item name="def_station_name" hidden><Input /></Form.Item>
        </Form.Item>
    );
}

// ---- GHN address picker (cascading Select, auto-load from API token) ------

/**
 * GHTK nhận địa chỉ theo TÊN (không cần ID/mã như GHN) ⇒ dùng AddressPicker hành chính VN (hỗ trợ cả
 * địa chỉ 2 cấp Tỉnh+Phường lẫn 3 cấp). Tên Tỉnh/Quận/Phường ghi vào hidden field `from_*_name` để
 * `AddCarrierAccountModal.submit()` đóng gói `meta.from_address` (map sang pick_* khi tạo vận đơn).
 */
function GhtkFromAddressSection({ form, initial }: { form: FormInstance; initial?: PickedAddress }) {
    const [addr, setAddr] = useState<PickedAddress>(initial ?? {});

    return (
        <>
            <Form.Item label="Khu vực kho gửi (Tỉnh / Quận / Phường)" required>
                <AddressPicker value={addr} onPick={(p) => {
                    setAddr(p);
                    form.setFieldsValue({
                        from_province_name: p.province ?? '',
                        from_district_name: p.district ?? '',
                        from_ward_name: p.ward ?? '',
                    });
                    form.validateFields(['from_province_name', 'from_ward_name']).catch(() => {});
                }} />
            </Form.Item>
            {/* Hidden — set bởi AddressPicker; submit() gói vào meta.from_address. */}
            <Form.Item name="from_province_name" hidden rules={[{ required: true, message: 'Chọn Tỉnh/Thành của kho gửi' }]}><Input /></Form.Item>
            <Form.Item name="from_ward_name" hidden rules={[{ required: true, message: 'Chọn Phường/Xã của kho gửi' }]}><Input /></Form.Item>
            <Form.Item name="from_district_name" hidden><Input /></Form.Item>
        </>
    );
}

/**
 * J&T Express chỉ hỗ trợ addressing "selfAddress=1" — địa chỉ 2 CẤP (Tỉnh + Phường/Xã) theo danh mục
 * hành chính MỚI, KHÔNG có cấp Quận; BE không đọc/gửi district cho J&T (xem
 * JtExpressConnector::createShipment — hard-require account.meta.from_address.province_name/ward_name).
 * J&T không có API tra cứu tỉnh/quận/phường như GHN ⇒ dùng lại AddressPicker (như GHTK) nhưng chỉ ghi
 * from_province_name/from_ward_name (bỏ qua district — J&T không dùng tới).
 */
function JtFromAddressSection({ form, initial }: { form: FormInstance; initial?: { province_name?: string; ward_name?: string } }) {
    const [addr, setAddr] = useState<PickedAddress>({ format: 'new', province: initial?.province_name, ward: initial?.ward_name });

    return (
        <>
            <Form.Item label="Khu vực kho gửi (Tỉnh / Phường, đơn vị hành chính mới)" required>
                <AddressPicker value={addr} onPick={(p) => {
                    setAddr(p);
                    form.setFieldsValue({
                        from_province_name: p.province ?? '',
                        from_ward_name: p.ward ?? '',
                    });
                    form.validateFields(['from_province_name', 'from_ward_name']).catch(() => {});
                }} />
            </Form.Item>
            {/* Hidden — set bởi AddressPicker; submit() gói vào meta.from_address. J&T không dùng district. */}
            <Form.Item name="from_province_name" hidden rules={[{ required: true, message: 'Chọn Tỉnh/Thành của kho gửi' }]}><Input /></Form.Item>
            <Form.Item name="from_ward_name" hidden rules={[{ required: true, message: 'Chọn Phường/Xã của kho gửi' }]}><Input /></Form.Item>
        </>
    );
}

/**
 * Viettel Post nhận địa chỉ bằng ID. Cascading Tỉnh → Phường theo đơn vị hành chính MỚI (v3) qua proxy
 * `/carrier-accounts/viettelpost/master-data` (danh mục công khai, không cần token). Ghi mã VTP + tên vào
 * hidden field `from_province_id/from_ward_id/from_district_id` (suy từ phường) + `from_*_name` để
 * `AddCarrierAccountModal.submit()` đóng gói `meta.from_address`.
 */
function ViettelPostFromAddressSection({ form, initial }: {
    form: FormInstance;
    initial?: { province_id?: number; province_name?: string; ward_id?: number; ward_name?: string };
}) {
    const { message } = AntApp.useApp();
    const masterData = useViettelPostMasterData();
    const [provinces, setProvinces] = useState<VtpProvince[]>([]);
    const [wards, setWards] = useState<VtpWard[]>([]);
    const [loading, setLoading] = useState<null | 'provinces' | 'wards'>(null);
    const [provinceId, setProvinceId] = useState<number | undefined>(initial?.province_id);
    const [wardId, setWardId] = useState<number | undefined>(initial?.ward_id);
    const loadedRef = useRef(false);

    const provName = (p: VtpProvince) => p.PROVINCE_NAME ?? p.WPROVINCE_NAME ?? `#${p.PROVINCE_ID}`;

    // Load tỉnh 1 lần khi mở; nếu edit có sẵn province_id thì load luôn phường.
    useEffect(() => {
        if (loadedRef.current) return;
        loadedRef.current = true;
        setLoading('provinces');
        masterData.mutate({ level: 'provinces' }, {
            onSuccess: (data) => {
                setProvinces(data as VtpProvince[]);
                if (initial?.province_id) loadWards(initial.province_id);
            },
            onError: (e) => message.error(errorMessage(e, 'Không tải được danh sách tỉnh từ Viettel Post.')),
            onSettled: () => setLoading(null),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const loadWards = (pid: number) => {
        setLoading('wards');
        masterData.mutate({ level: 'wards', province_id: pid }, {
            onSuccess: (data) => setWards(data as VtpWard[]),
            onError: (e) => message.error(errorMessage(e, 'Không tải được danh sách phường từ Viettel Post.')),
            onSettled: () => setLoading(null),
        });
    };

    const onPickProvince = (id: number) => {
        const p = provinces.find((x) => x.PROVINCE_ID === id);
        setProvinceId(id);
        setWardId(undefined);
        setWards([]);
        form.setFieldsValue({
            from_province_id: id, from_province_name: p ? provName(p) : '',
            from_ward_id: undefined, from_ward_name: undefined, from_district_id: undefined,
        });
        loadWards(id);
    };

    const onPickWard = (id: number) => {
        const w = wards.find((x) => x.WARDS_ID === id);
        setWardId(id);
        form.setFieldsValue({
            from_ward_id: id, from_ward_name: w?.WARDS_NAME ?? '',
            from_district_id: w?.DISTRICT_ID ?? undefined,
        });
    };

    const provincesReady = provinces.length > 0;

    return (
        <>
            <Form.Item label="Tỉnh / Thành phố (đơn vị hành chính mới)" required>
                <Select
                    showSearch
                    placeholder={loading === 'provinces' ? 'Đang tải tỉnh từ Viettel Post…' : 'Chọn tỉnh / thành phố'}
                    disabled={!provincesReady}
                    loading={loading === 'provinces'}
                    value={provinceId}
                    onChange={onPickProvince}
                    optionFilterProp="label"
                    options={provinces.map((p) => ({ value: p.PROVINCE_ID, label: provName(p) }))}
                    notFoundContent={loading === 'provinces' ? <Spin size="small" /> : 'Chưa có dữ liệu'}
                />
            </Form.Item>
            <Form.Item label="Phường / Xã" required>
                <Select
                    showSearch
                    placeholder={provinceId ? 'Chọn phường / xã' : 'Chọn tỉnh trước'}
                    disabled={!provinceId || loading === 'wards'}
                    loading={loading === 'wards'}
                    value={wardId}
                    onChange={onPickWard}
                    optionFilterProp="label"
                    options={wards.map((w) => ({ value: w.WARDS_ID, label: w.WARDS_NAME }))}
                    notFoundContent={loading === 'wards' ? <Spin size="small" /> : 'Chưa có dữ liệu'}
                />
            </Form.Item>
            {/* Hidden — set bởi cascading Select; submit() gói vào meta.from_address. */}
            <Form.Item name="from_province_name" hidden><Input /></Form.Item>
            <Form.Item name="from_ward_name" hidden><Input /></Form.Item>
            <Form.Item name="from_district_id" hidden><Input /></Form.Item>
            <Form.Item name="from_province_id" hidden rules={[{ required: true, message: 'Chọn Tỉnh/Thành của kho gửi.' }]}><Input /></Form.Item>
            <Form.Item name="from_ward_id" hidden rules={[{ required: true, message: 'Chọn Phường/Xã của kho gửi.' }]}><Input /></Form.Item>
        </>
    );
}

/**
 * Khi user nhập API Token GHN, tự gọi BE proxy `/carrier-accounts/ghn/master-data` để cascading
 * tỉnh → quận → phường thay vì gõ tay mã. Field name vẫn ghi vào form prefix `from_` để hợp với
 * `AddCarrierAccountModal.submit()` đóng gói `meta.from_address`.
 */
function GhnFromAddressSection({ form, initial }: {
    form: FormInstance;
    initial?: { province_name?: string; district_name?: string; ward_name?: string; district_id?: number; ward_code?: string };
}) {
    const { message } = AntApp.useApp();
    const token = (Form.useWatch('cred_token', form) ?? '') as string;
    const masterData = useGhnMasterData();

    const [provinces, setProvinces] = useState<GhnProvince[]>([]);
    const [districts, setDistricts] = useState<GhnDistrict[]>([]);
    const [wards, setWards] = useState<GhnWard[]>([]);
    const [loading, setLoading] = useState<null | 'provinces' | 'districts' | 'wards'>(null);
    const [provinceId, setProvinceId] = useState<number | undefined>(undefined);
    const [districtId, setDistrictId] = useState<number | undefined>(undefined);
    const [wardCode, setWardCode] = useState<string | undefined>(undefined);
    // Sửa tài khoản: giữ nguyên địa chỉ kho đã lưu ở lần token-load ĐẦU TIÊN (đừng để effect xoá).
    // Người dùng vẫn có thể chọn lại cascade để đổi.
    const preserveInitialRef = useRef<boolean>(!!initial?.district_id);
    const [changingAddr, setChangingAddr] = useState<boolean>(!initial?.district_id);

    // Debounce token → fetch provinces. Reset cascade khi token đổi.
    const fetchedTokenRef = useRef<string>('');
    useEffect(() => {
        const t = token.trim();
        if (t.length < 8) {
            // Token bị xoá hẳn ⇒ reset. Nhưng KHÔNG xoá địa chỉ đã lưu khi đang giữ (chưa từng load).
            setProvinces([]); setDistricts([]); setWards([]);
            setProvinceId(undefined); setDistrictId(undefined); setWardCode(undefined);
            if (!preserveInitialRef.current) {
                form.setFieldsValue({ from_province_name: undefined, from_district_name: undefined, from_district_id: undefined, from_ward_code: undefined, from_ward_name: undefined });
            }
            fetchedTokenRef.current = '';
            return;
        }
        if (t === fetchedTokenRef.current) return;
        const timer = setTimeout(() => {
            setLoading('provinces');
            masterData.mutate({ level: 'provinces', token: t }, {
                onSuccess: (data) => {
                    setProvinces(data as GhnProvince[]);
                    fetchedTokenRef.current = t;
                    // Lần load đầu ở chế độ SỬA ⇒ giữ nguyên from_* đã prefill (không reset cascade).
                    if (preserveInitialRef.current) {
                        preserveInitialRef.current = false;
                        return;
                    }
                    // Token đổi ⇒ reset selection cascade.
                    setDistricts([]); setWards([]);
                    setProvinceId(undefined); setDistrictId(undefined); setWardCode(undefined);
                    form.setFieldsValue({ from_province_name: undefined, from_district_name: undefined, from_district_id: undefined, from_ward_code: undefined, from_ward_name: undefined });
                },
                onError: (e) => {
                    setProvinces([]);
                    message.error(errorMessage(e, 'Không tải được danh sách tỉnh từ GHN. Kiểm tra API Token.'));
                },
                onSettled: () => setLoading(null),
            });
        }, 800);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [token]);

    const onPickProvince = (id: number) => {
        const p = provinces.find((x) => x.ProvinceID === id);
        setProvinceId(id);
        setDistrictId(undefined); setWardCode(undefined);
        setDistricts([]); setWards([]);
        form.setFieldsValue({
            from_province_name: p?.ProvinceName ?? '',
            from_district_id: undefined,
            from_district_name: undefined,
            from_ward_code: undefined,
            from_ward_name: undefined,
        });
        setLoading('districts');
        masterData.mutate({ level: 'districts', token: token.trim(), province_id: id }, {
            onSuccess: (data) => setDistricts(data as GhnDistrict[]),
            onError: (e) => message.error(errorMessage(e, 'Không tải được danh sách quận từ GHN.')),
            onSettled: () => setLoading(null),
        });
    };

    const onPickDistrict = (id: number) => {
        const d = districts.find((x) => x.DistrictID === id);
        setDistrictId(id);
        setWardCode(undefined);
        setWards([]);
        form.setFieldsValue({
            from_district_id: id,
            from_district_name: d?.DistrictName ?? '',
            from_ward_code: undefined,
            from_ward_name: undefined,
        });
        setLoading('wards');
        masterData.mutate({ level: 'wards', token: token.trim(), district_id: id }, {
            onSuccess: (data) => setWards(data as GhnWard[]),
            onError: (e) => message.error(errorMessage(e, 'Không tải được danh sách phường từ GHN.')),
            onSettled: () => setLoading(null),
        });
    };

    const onPickWard = (code: string) => {
        const w = wards.find((x) => x.WardCode === code);
        setWardCode(code);
        form.setFieldsValue({
            from_ward_code: code,
            from_ward_name: w?.WardName ?? '',
        });
    };

    const tokenReady = token.trim().length >= 8;
    const provincesReady = provinces.length > 0;

    return (
        <div className="ghn-addr">
            <div className={`ghn-addr__banner ${tokenReady ? (provincesReady ? 'is-ok' : 'is-loading') : 'is-idle'}`}>
                {!tokenReady && (
                    <Space size={8}>
                        <KeyOutlined />
                        <span>Nhập <b>API Token</b> ở trên — danh sách tỉnh/quận/phường GHN sẽ tự tải về.</span>
                    </Space>
                )}
                {tokenReady && loading === 'provinces' && (
                    <Space size={8}>
                        <Spin size="small" indicator={<LoadingOutlined spin />} />
                        <span>Đang gọi GHN để tải danh sách tỉnh…</span>
                    </Space>
                )}
                {tokenReady && loading !== 'provinces' && provincesReady && (
                    <Space size={8}>
                        <CheckCircleFilled style={{ color: 'var(--success-500)' }} />
                        <span>Đã tải <b>{provinces.length}</b> tỉnh/TP từ GHN. Chọn tỉnh → quận → phường để tự điền mã.</span>
                        <Tooltip title="Tải lại từ GHN">
                            <Button size="small" type="text" icon={<ReloadOutlined />} onClick={() => { fetchedTokenRef.current = ''; setProvinces([]); /* trigger effect by clearing then re-running */ form.setFieldsValue({ cred_token: token }); }} />
                        </Tooltip>
                    </Space>
                )}
                {tokenReady && loading !== 'provinces' && !provincesReady && (
                    <Space size={8}>
                        <CloseCircleFilled style={{ color: 'var(--danger-500)' }} />
                        <span>Chưa tải được — kiểm tra lại API Token.</span>
                        <Button size="small" type="link" onClick={() => { fetchedTokenRef.current = ''; form.setFieldsValue({ cred_token: token + ' ' }); setTimeout(() => form.setFieldsValue({ cred_token: token }), 50); }}>Thử lại</Button>
                    </Space>
                )}
            </div>

            {/* Sửa tài khoản: hiện địa chỉ kho ĐÃ LƯU (read-only) + nút đổi. Không đụng cascade thì giữ nguyên. */}
            {!changingAddr && initial?.district_id ? (
                <div className="ghn-shop-preview" style={{ marginBottom: 14 }}>
                    <Space size={6}>
                        <CheckCircleFilled style={{ color: 'var(--success-500)' }} />
                        <span>Khu vực kho đã lưu: <b>{[initial.ward_name, initial.district_name, initial.province_name].filter(Boolean).join(' › ') || '—'}</b></span>
                    </Space>
                    <div style={{ marginTop: 4, color: 'var(--ink-500)', fontSize: 12 }}>Mã quận GHN: <code>{initial.district_id}</code>{initial.ward_code ? <> · Mã phường: <code>{initial.ward_code}</code></> : null}</div>
                    <div style={{ marginTop: 6 }}>
                        <Button size="small" type="link" style={{ padding: 0 }} onClick={() => { preserveInitialRef.current = false; setChangingAddr(true); }}>Đổi khu vực kho hàng</Button>
                    </div>
                </div>
            ) : (
                <>
                    <Form.Item label="Tỉnh / Thành phố" required>
                        <Select
                            showSearch
                            placeholder={tokenReady ? 'Chọn tỉnh / thành phố' : 'Nhập API Token để mở danh sách'}
                            disabled={!provincesReady}
                            loading={loading === 'provinces'}
                            value={provinceId}
                            onChange={onPickProvince}
                            optionFilterProp="label"
                            options={provinces.map((p) => ({ value: p.ProvinceID, label: p.ProvinceName }))}
                            notFoundContent={loading === 'provinces' ? <Spin size="small" /> : 'Chưa có dữ liệu'}
                        />
                    </Form.Item>

                    <Form.Item label="Quận / Huyện" required>
                        <Select
                            showSearch
                            placeholder={provinceId ? 'Chọn quận / huyện' : 'Chọn tỉnh trước'}
                            disabled={!provinceId || loading === 'districts'}
                            loading={loading === 'districts'}
                            value={districtId}
                            onChange={onPickDistrict}
                            optionFilterProp="label"
                            options={districts.map((d) => ({ value: d.DistrictID, label: `${d.DistrictName}  ·  #${d.DistrictID}` }))}
                            notFoundContent={loading === 'districts' ? <Spin size="small" /> : 'Chưa có dữ liệu'}
                        />
                    </Form.Item>

                    <Form.Item label="Phường / Xã">
                        <Select
                            showSearch
                            placeholder={districtId ? 'Chọn phường / xã (tuỳ chọn)' : 'Chọn quận trước'}
                            disabled={!districtId || loading === 'wards'}
                            loading={loading === 'wards'}
                            value={wardCode}
                            onChange={onPickWard}
                            optionFilterProp="label"
                            options={wards.map((w) => ({ value: w.WardCode, label: `${w.WardName}  ·  ${w.WardCode}` }))}
                            notFoundContent={loading === 'wards' ? <Spin size="small" /> : 'Chưa có dữ liệu'}
                            allowClear
                        />
                    </Form.Item>
                </>
            )}

            {/* Hidden fields — populated bởi cascading Select để submit() gói vào meta.from_address. */}
            <Form.Item name="from_province_name" hidden><Input /></Form.Item>
            <Form.Item name="from_district_name" hidden><Input /></Form.Item>
            <Form.Item name="from_ward_name" hidden><Input /></Form.Item>
            <Form.Item name="from_district_id" hidden rules={[{ required: true, message: 'Hãy chọn quận / huyện để lấy mã GHN.' }]}>
                <Input />
            </Form.Item>
            <Form.Item name="from_ward_code" hidden><Input /></Form.Item>

            <style>{GHN_ADDR_CSS}</style>
        </div>
    );
}

const GHN_ADDR_CSS = `
.ghn-addr__banner{
    margin: 4px 0 14px;
    padding: 10px 14px;
    border-radius: var(--radius);
    font-size: 13px;
    line-height: 1.5;
    border: 1px solid var(--ink-100);
    background: var(--ink-50);
    color: var(--ink-700);
}
.ghn-addr__banner.is-idle{ background: var(--ink-50); border-color: var(--ink-100); color: var(--ink-600); }
.ghn-addr__banner.is-loading{ background: var(--blue-50); border-color: var(--blue-100); color: var(--blue-700); }
.ghn-addr__banner.is-ok{ background: var(--success-50, #ECFDF5); border-color: #A7F3D0; color: #047857; }
.ghn-addr__banner b{ font-weight: 600; }
`;

// ---- Capability labels & inline CSS ---------------------------------------

const CAPABILITY_LABEL: Record<string, string> = {
    create_shipment: 'Tạo vận đơn',
    cancel_shipment: 'Huỷ vận đơn',
    track: 'Tra cứu',
    webhook: 'Webhook',
    print_label: 'In tem',
    cod: 'COD',
    refund: 'Hoàn hàng',
};

// CSS scoped trong page — đồng bộ với Minimal Ecommerce theme (white + blue).
const CARRIER_CARD_CSS = `
.carrier-card{
    position:relative;
    background: #FFFFFF;
    border: 1px solid var(--ink-100);
    border-radius: var(--radius-lg);
    padding: 18px 18px 12px;
    box-shadow: var(--shadow-xs);
    transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
    height: 100%;
    display:flex; flex-direction:column;
    overflow:hidden;
}
.carrier-card:hover{ box-shadow: var(--shadow-sm); transform: translateY(-1px); border-color: var(--ink-200); }
.carrier-card__ribbon{
    position:absolute; left:0; right:0; top:0; height:3px;
    background: linear-gradient(90deg, var(--blue-600) 0%, var(--blue-300) 60%, transparent 100%);
    opacity: .85;
}
.carrier-card__ribbon--soon{ background: linear-gradient(90deg, var(--ink-200) 0%, transparent 80%); }
.carrier-card--manual{
    background: linear-gradient(135deg, #FFFFFF 0%, var(--ink-50) 100%);
    border-color: var(--blue-100);
    margin-bottom: 0;
}
.carrier-card--manual .carrier-card__ribbon{
    background: linear-gradient(90deg, var(--success-500) 0%, rgba(16,185,129,0.15) 70%, transparent 100%);
}
.carrier-card--soon{ background: var(--ink-50); }
.carrier-card__head{
    display: grid;
    grid-template-columns: 56px 1fr auto;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 10px;
}
.carrier-card__title{ min-width:0; padding-top: 2px; }
.carrier-card__count{
    text-align:right; line-height:1; padding-top:4px;
    display:flex; flex-direction:column; align-items:flex-end; gap:2px;
}
.carrier-card__count .num{
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 28px;
    color: var(--ink-900);
    letter-spacing: -0.025em;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.carrier-card__count .lbl{
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.10em;
    color: var(--ink-400);
    font-family: var(--font-sans);
    font-weight: 500;
}
.carrier-card__caps{
    display:flex; flex-wrap:wrap; gap:4px;
    margin: 4px 0 12px;
}
.cap-chip{
    font-size: 10.5px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--blue-50);
    color: var(--blue-700);
    border: 1px solid var(--blue-100);
    letter-spacing: .02em;
}
.carrier-card__divider{
    display:flex; align-items:center; gap:8px;
    margin: 4px 0 8px;
    font-size: 10.5px;
    font-family: var(--font-display);
    font-weight: 600;
    color: var(--ink-400);
    text-transform: uppercase;
    letter-spacing: .14em;
}
.carrier-card__divider::before, .carrier-card__divider::after{
    content:""; flex:1; height:1px;
    background: linear-gradient(to right, transparent, var(--ink-200), transparent);
}
.carrier-card__divider a{
    flex:none;
    font-family: var(--font-sans);
    font-weight: 500;
    font-size: 12px;
    color: var(--blue-600);
    letter-spacing: 0;
    text-transform: none;
}
.carrier-card__list{
    display:flex; flex-direction:column; gap: 4px; flex: 1;
}
.carrier-card__empty{
    padding: 18px 0 14px;
}

.acct-row{
    display: grid;
    grid-template-columns: 14px 1fr auto;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: var(--radius);
    background: var(--ink-50);
    border: 1px solid transparent;
    transition: background .15s ease, border-color .15s ease;
}
.acct-row:hover{ background: var(--blue-50); border-color: var(--blue-100); }
.acct-row.is-off{ opacity: .68; background: var(--ink-100); }
.acct-row .dot{
    width: 10px; height: 10px; border-radius: 50%;
    display:inline-block; flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255,255,255,.9);
}
.dot--ok{ background: var(--success-500); }
.dot--warn{ background: var(--warn-500); }
.dot--err{ background: var(--danger-500); }
.dot--unchecked{ background: var(--ink-300); }
.dot--off{ background: var(--ink-300); opacity: .7; }
.acct-row__body{ min-width:0; line-height: 1.3; }
.acct-row__title{
    display:flex; align-items:center; gap:6px; flex-wrap:wrap;
}
.acct-row__meta{
    font-size: 11.5px;
    color: var(--ink-500);
    margin-top: 2px;
    display:flex; gap:4px; flex-wrap: wrap;
}
.acct-row__meta code{
    font-family: var(--font-mono);
    font-size: 10.5px;
    background: var(--ink-100);
    padding: 0 4px;
    border-radius: 3px;
    color: var(--ink-700);
}
.acct-row__actions{
    display:flex; align-items:center; gap:4px;
}

.carrier-card__add{
    all: unset;
    cursor: pointer;
    margin-top: 10px;
    display:flex; align-items:center; justify-content:center; gap:6px;
    padding: 9px 12px;
    border: 1px dashed var(--ink-200);
    border-radius: var(--radius);
    color: var(--blue-600);
    font-weight: 500;
    font-size: 13px;
    transition: all .15s ease;
    background: transparent;
}
.carrier-card__add:hover{
    border-color: var(--blue-600);
    background: var(--blue-50);
    color: var(--blue-700);
}
.carrier-card__soon-msg{
    margin-top: 6px;
    padding: 10px 12px;
    background: var(--ink-50);
    border-left: 3px solid var(--ink-200);
    font-size: 12px;
    color: var(--ink-500);
    line-height: 1.5;
    border-radius: 0 var(--radius) var(--radius) 0;
}
`;
