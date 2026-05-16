import { useMemo, useState } from 'react';
import {
    App as AntApp, Alert, Button, Col, Dropdown, Empty, Form, Input, Modal, Result,
    Row, Space, Switch, Tag, Tooltip, Typography,
} from 'antd';
import {
    CloseCircleFilled, EditOutlined, EllipsisOutlined, KeyOutlined, PlusOutlined,
    ReloadOutlined, StarFilled, StarOutlined, ThunderboltOutlined, WarningFilled,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { CarrierLogo, CARRIER_TAGLINE } from '@/components/CarrierLogo';
import { CARRIER_META } from '@/components/CarrierBadge';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    type Carrier, type CarrierAccount,
    useCarrierAccounts, useCarriers, useCreateCarrierAccount, useDeleteCarrierAccount,
    useUpdateCarrierAccount, useVerifyCarrierAccount,
} from '@/lib/fulfillment';

// Trường thông tin xác thực (credentials) theo từng ĐVVC — v1: GHN; carrier khác fallback "token".
const CRED_FIELDS: Record<string, Array<{ key: string; label: string; required?: boolean; placeholder?: string }>> = {
    ghn: [
        { key: 'token', label: 'API Token', required: true, placeholder: 'Token Production / Test từ GHN Dashboard' },
        { key: 'shop_id', label: 'Shop ID', required: true, placeholder: 'ID gian hàng GHN (số)' },
    ],
};

// Carrier nào cần "địa chỉ kho hàng" để tạo vận đơn? GHN yêu cầu district_id của kho.
const FROM_ADDRESS_REQUIRED: Record<string, boolean> = { ghn: true };

const FROM_ADDRESS_FIELDS: Array<{ key: string; label: string; required?: boolean; placeholder?: string }> = [
    { key: 'name', label: 'Tên người gửi', required: true, placeholder: 'VD: CMBcore Shop' },
    { key: 'phone', label: 'SĐT', required: true, placeholder: 'VD: 0901234567' },
    { key: 'address', label: 'Địa chỉ kho', required: true, placeholder: 'Số nhà, đường…' },
    { key: 'ward_name', label: 'Phường/Xã', placeholder: 'Phường Bến Nghé' },
    { key: 'district_name', label: 'Quận/Huyện', placeholder: 'Quận 1' },
    { key: 'province_name', label: 'Tỉnh/TP', placeholder: 'TP Hồ Chí Minh' },
    { key: 'district_id', label: 'Mã quận GHN', required: true, placeholder: 'VD: 1442 (lấy từ /master-data/district)' },
    { key: 'ward_code', label: 'Mã phường GHN', placeholder: 'VD: 20308' },
];

// Danh sách ĐVVC "sắp có" — hiển thị dimmed-card để người dùng biết roadmap.
const COMING_SOON: Array<{ code: string; name: string }> = [
    { code: 'ghtk', name: 'GHTK' },
    { code: 'jt', name: 'J&T Express' },
    { code: 'viettelpost', name: 'Viettel Post' },
    { code: 'spx', name: 'SPX Express' },
    { code: 'vnpost', name: 'VNPost' },
    { code: 'ahamove', name: 'Ahamove' },
];

interface AddState { open: boolean; carrier?: Carrier | null }

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

            <Typography.Title level={5} style={{ fontFamily: 'var(--font-display)', fontStyle: 'italic', fontWeight: 500, color: 'var(--gold-700)', textTransform: 'uppercase', letterSpacing: '0.18em', fontSize: 12, marginTop: 18, marginBottom: 10 }}>
                — ĐVVC tích hợp API
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
    carrier, accounts, canManage, onAdd, onRename, onUpdate, onDelete,
}: {
    carrier: Carrier;
    accounts: CarrierAccount[];
    canManage: boolean;
    onAdd: () => void;
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
                        {hasDefault && <Tooltip title="Có tài khoản đặt làm mặc định"><StarFilled style={{ color: 'var(--gold-600)', fontSize: 12 }} /></Tooltip>}
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
    account, canManage, onRename, onToggle, onSetDefault, onDelete, verify,
}: {
    account: CarrierAccount;
    canManage: boolean;
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

    const lastCheckText = verifiedAt ? `Kiểm tra ${dayjs(verifiedAt).format('HH:mm DD/MM')}` : null;

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
                            { key: 'rename', icon: <EditOutlined />, label: 'Đổi tên alias', onClick: onRename },
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
                <Tag style={{ fontStyle: 'italic', fontFamily: 'var(--font-display)' }}>Sắp có</Tag>
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
    const [form] = Form.useForm();
    const carrier = state.carrier;
    const code = carrier?.code ?? '';
    const credFields = CRED_FIELDS[code] ?? (code && code !== 'manual' ? [{ key: 'token', label: 'API Token', required: true }] : []);
    const needsFromAddress = !!FROM_ADDRESS_REQUIRED[code];

    const submit = () => form.validateFields().then((v) => {
        const credentials: Record<string, unknown> = {};
        credFields.forEach((f) => { if (v[`cred_${f.key}`] !== undefined && v[`cred_${f.key}`] !== '') credentials[f.key] = v[`cred_${f.key}`]; });
        const meta: Record<string, unknown> = {};
        if (needsFromAddress) {
            const fromAddress: Record<string, unknown> = {};
            FROM_ADDRESS_FIELDS.forEach((f) => {
                const val = v[`from_${f.key}`];
                if (val !== undefined && val !== '') fromAddress[f.key] = f.key === 'district_id' ? Number(val) : val;
            });
            if (Object.keys(fromAddress).length > 0) meta.from_address = fromAddress;
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
            open={state.open} title={carrier ? (<Space size={10} align="center"><CarrierLogo code={carrier.code} size={28} /><span>Thêm tài khoản {CARRIER_META[carrier.code]?.name ?? carrier.name}</span></Space>) : 'Thêm tài khoản'}
            onCancel={onClose} okText="Thêm tài khoản" confirmLoading={create.isPending} onOk={submit}
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
                                <Typography.Title level={5} style={{ marginTop: 4, marginBottom: 8, fontFamily: 'var(--font-display)', fontWeight: 500, fontSize: 14 }}>Thông tin xác thực</Typography.Title>
                                {credFields.map((f) => (
                                    <Form.Item key={f.key} name={`cred_${f.key}`} label={f.label} rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}>
                                        <Input placeholder={f.placeholder} />
                                    </Form.Item>
                                ))}
                            </>
                        )}

                        {needsFromAddress && (
                            <>
                                <Typography.Title level={5} style={{ marginTop: 8, marginBottom: 4, fontFamily: 'var(--font-display)', fontWeight: 500, fontSize: 14 }}>Địa chỉ kho hàng (người gửi)</Typography.Title>
                                <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginBottom: 12 }}>
                                    Bắt buộc với GHN — dùng để tạo vận đơn. Mã quận/phường tra ở GHN API <code>/master-data/district</code>.
                                </Typography.Paragraph>
                                {FROM_ADDRESS_FIELDS.map((f) => (
                                    <Form.Item key={f.key} name={`from_${f.key}`} label={f.label} rules={f.required ? [{ required: true, message: `Nhập ${f.label}` }] : []}>
                                        <Input placeholder={f.placeholder} />
                                    </Form.Item>
                                ))}
                            </>
                        )}

                        <Form.Item name="default_service" label="Mã dịch vụ mặc định (tuỳ chọn)" extra="VD: 2 = GHN Standard service_type_id">
                            <Input placeholder="Để trống nếu chưa rõ" />
                        </Form.Item>
                        <Form.Item name="is_default" valuePropName="checked" label="Đặt làm tài khoản mặc định toàn workspace" style={{ marginBottom: 0 }}>
                            <Switch />
                        </Form.Item>
                    </Form>
                </>
            )}
        </Modal>
    );
}

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

// CSS scoped trong page — đặt cùng file để dễ tracking (theme editorial postal).
const CARRIER_CARD_CSS = `
.carrier-card{
    position:relative;
    background: var(--paper);
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
    background: linear-gradient(90deg, var(--gold-500) 0%, var(--gold-100) 60%, transparent 100%);
    opacity: .85;
}
.carrier-card__ribbon--soon{ background: linear-gradient(90deg, var(--ink-200) 0%, transparent 80%); }
.carrier-card--manual{
    background: linear-gradient(135deg, var(--paper) 0%, var(--paper-2) 100%);
    border-color: var(--gold-100);
    margin-bottom: 0;
}
.carrier-card--manual .carrier-card__ribbon{
    background: linear-gradient(90deg, var(--success-500) 0%, rgba(16,185,129,0.15) 70%, transparent 100%);
}
.carrier-card--soon{ background: var(--bg-tinted); }
.carrier-card--soon .carrier-card__title{ }
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
    font-style: italic;
    font-weight: 500;
    font-size: 28px;
    color: var(--navy-900);
    letter-spacing: -0.02em;
    line-height: 1;
}
.carrier-card__count .lbl{
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--ink-500);
    font-family: var(--font-display);
    font-style: italic;
}
.carrier-card__caps{
    display:flex; flex-wrap:wrap; gap:4px;
    margin: 4px 0 12px;
}
.cap-chip{
    font-size: 10.5px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--brand-50);
    color: var(--brand-700);
    border: 1px solid var(--brand-100);
    letter-spacing: .02em;
}
.carrier-card__divider{
    display:flex; align-items:center; gap:8px;
    margin: 4px 0 8px;
    font-size: 11px;
    font-family: var(--font-display);
    font-style: italic;
    color: var(--gold-700);
    text-transform: uppercase;
    letter-spacing: .14em;
}
.carrier-card__divider::before, .carrier-card__divider::after{
    content:""; flex:1; height:1px;
    background: linear-gradient(to right, transparent, rgba(200,146,61,.30), transparent);
}
.carrier-card__divider a{
    flex:none;
    font-family: var(--font-sans);
    font-style: normal;
    font-size: 12px;
    color: var(--brand-700);
    letter-spacing: 0;
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
    background: var(--bg-tinted);
    border: 1px dashed transparent;
    transition: background .15s ease, border-color .15s ease;
}
.acct-row:hover{ background: var(--gold-50); border-color: var(--gold-100); }
.acct-row.is-off{ opacity: .68; background: var(--paper-3); }
.acct-row .dot{
    width: 10px; height: 10px; border-radius: 50%;
    display:inline-block; flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255,255,255,.6);
}
.dot--ok{ background: var(--success-500); }
.dot--warn{ background: var(--warn-500); }
.dot--err{ background: var(--danger-500); }
.dot--unchecked{ background: var(--ink-300); }
.dot--off{ background: var(--ink-300); box-shadow: 0 0 0 2px rgba(255,255,255,.6); opacity: .7; }
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
    color: var(--brand-700);
    font-weight: 500;
    font-size: 13px;
    transition: all .15s ease;
    background: transparent;
}
.carrier-card__add:hover{
    border-color: var(--brand-700);
    background: var(--brand-50);
    color: var(--brand-700);
}
.carrier-card__soon-msg{
    margin-top: 6px;
    padding: 10px 12px;
    background: rgba(11,20,55,0.03);
    border-left: 3px solid var(--ink-200);
    font-size: 12px;
    color: var(--ink-500);
    line-height: 1.5;
    border-radius: 0 var(--radius) var(--radius) 0;
}
`;
