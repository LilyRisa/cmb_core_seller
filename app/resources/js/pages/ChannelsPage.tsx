import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Alert, Avatar, Button, Card, Col, Empty, Input, Modal, Result, Row, Space, Tag, Tooltip, Typography } from 'antd';
import { App as AntApp } from 'antd';
import { CheckCircleOutlined, ClockCircleOutlined, DeleteOutlined, EditOutlined, PlusOutlined, ReloadOutlined, ShopOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { DateText } from '@/components/MoneyText';
import { CHANNEL_META, CHANNEL_STATUS_COLOR, CHANNEL_STATUS_LABEL } from '@/lib/format';
import { errorMessage } from '@/lib/api';
import { ChannelAccount, useChannelAccounts, useConnectChannel, useDeleteChannelAccount, useOutboundIp, useRenameChannel, useResyncChannel } from '@/lib/channels';
import { useCan } from '@/lib/tenant';

const CALLBACK_ERRORS: Record<string, string> = {
    oauth_state: 'Phiên kết nối đã hết hạn hoặc không hợp lệ. Vui lòng thử kết nối lại.',
    shop_already_connected: 'Gian hàng này đã được kết nối ở một workspace khác.',
    oauth_failed: 'Kết nối thất bại. Vui lòng thử lại.',
    oauth_missing_params: 'Thiếu tham số từ sàn. Vui lòng thử lại.',
    tiktok_scope_denied:
        'TikTok từ chối: app chưa được cấp scope cần thiết (cần "Authorization/Shop" để đọc danh sách gian hàng). ' +
        'Vào TikTok Shop Partner Center → app của bạn → "Scopes" → bật các scope: Authorization, Shop, Order, Webhook → ' +
        'lưu, rồi ngắt kết nối và ủy quyền lại từ tài khoản người bán.',
    tiktok_auth_failed:
        'TikTok không nhận access_token (có thể hết hạn hoặc đã bị thu hồi). Vui lòng ủy quyền lại.',
    tiktok_api_error: 'TikTok trả lỗi khi lấy thông tin gian hàng. Xem chi tiết trong log server.',
    lazada_api_error:
        'Lazada trả lỗi khi cấp quyền / lấy thông tin gian hàng. ' +
        'Kiểm tra: (1) app đã được "Published" trong Open Platform; (2) tài khoản người bán đã ' +
        '"Subscribe" / "Add" app trên Lazada Service Marketplace (với app loại ERP); (3) URL callback ' +
        'đăng ký trong app console khớp đúng với địa chỉ này (kể cả http/https & dấu /).',
};

/** Thông điệp cho từng mã lỗi Lazada (lz_code) — đè lên CALLBACK_ERRORS['lazada_api_error'] khi khớp. */
const LAZADA_CODE_GUIDE: Record<string, string> = {
    AppWhiteIpLimit:
        'Lazada chặn vì IP của server KHÔNG nằm trong "IP Whitelist" của app. Cách xử lý: ' +
        '(1) Đăng nhập https://open.lazada.com → App Management → chọn app của bạn → Security / IP Whitelist; ' +
        '(2) Thêm IP outbound của server (xem ở thẻ "Kết nối gian hàng mới" bên dưới — nút "IP máy chủ"), ' +
        'HOẶC xoá toàn bộ IP whitelist để cho phép mọi IP (bảo mật vẫn còn app_secret + sign + OAuth); ' +
        '(3) Lưu, đợi 1–2 phút Lazada cache, thử kết nối lại.',
    IncompleteSignature: 'Sai chữ ký request. Thường do `app_secret` sai / lệch giờ máy server. Đồng bộ NTP & kiểm tra LAZADA_APP_SECRET.',
    InvalidApi: 'Đường dẫn API không hợp lệ — app có thể chưa được cấp quyền dùng endpoint này.',
    MissingPartner: 'Thiếu / sai `partner_id` trong request. Đặt `LAZADA_PARTNER_ID=lazop-sdk-php-20180422` rồi thử lại.',
    IllegalAccessToken: 'Access token Lazada không hợp lệ / đã thu hồi. Cần ủy quyền lại từ tài khoản người bán.',
    AppCallLimit: 'App đã vượt giới hạn gọi API trong phút này. Đợi 1 phút rồi thử lại.',
    InvalidAppKey: 'Sai `LAZADA_APP_KEY`. Kiểm tra lại trong Lazada Open Platform → App Management.',
    AppNotSubscribed: 'Tài khoản người bán chưa Subscribe / Add app trên Lazada Service Marketplace. Người bán cần vào https://service.lazada.vn (hoặc service.lazada.com) → tìm app này → Add/Subscribe trước, rồi mới ủy quyền được.',
};

/** Lazada redirect `?error=<x>` khi seller huỷ / chưa subscribe / app chưa published — surface mã của sàn. */
const PROVIDER_ERROR_PREFIXES: Record<string, string> = {
    lazada_access_denied: 'Người bán đã từ chối cấp quyền cho ứng dụng.',
    lazada_invalid_request: 'Lazada nhận tham số ủy quyền không hợp lệ (thường do redirect_uri / client_id không khớp app console).',
    lazada_unauthorized_client: 'App của bạn chưa được duyệt / chưa Published trên Lazada Open Platform.',
    lazada_unsupported_response_type: 'Tham số response_type sai (cần `code`).',
    lazada_server_error: 'Lazada lỗi tạm thời ở phía server, thử lại sau.',
    lazada_temporarily_unavailable: 'Lazada bảo trì / quá tải, thử lại sau.',
};

function ShopCard({ account, canManage, onResync, onDelete, onRename }: { account: ChannelAccount; canManage: boolean; onResync: () => void; onDelete: () => void; onRename: () => void }) {
    const meta = CHANNEL_META[account.provider] ?? { name: account.provider, color: '#8c8c8c' };
    return (
        <Card styles={{ body: { padding: 16 } }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12 }}>
                <Space align="start">
                    <Avatar shape="square" size={40} style={{ background: meta.color, color: '#fff', fontWeight: 700 }}>{meta.name.slice(0, 2)}</Avatar>
                    <Space direction="vertical" size={2}>
                        <Space size={6}>
                            <Typography.Text strong>{account.name}</Typography.Text>
                            {canManage && <Button type="text" size="small" icon={<EditOutlined />} onClick={onRename} title="Đặt tên hiển thị (alias)" />}
                        </Space>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>{meta.name} · {account.shop_name ?? account.external_shop_id} · ID: {account.external_shop_id}</Typography.Text>
                        <Tag color={CHANNEL_STATUS_COLOR[account.status] ?? 'default'}>{CHANNEL_STATUS_LABEL[account.status] ?? account.status}</Tag>
                    </Space>
                </Space>
                {canManage && (
                    <Space>
                        <Tooltip title="Đồng bộ lại đơn ngay"><Button size="small" icon={<ReloadOutlined />} onClick={onResync} disabled={account.status !== 'active'}>Đồng bộ</Button></Tooltip>
                        <Tooltip title="Xóa kết nối — xóa cả đơn hàng & liên kết SKU của gian hàng này"><Button size="small" danger icon={<DeleteOutlined />} onClick={onDelete}>Xóa kết nối</Button></Tooltip>
                    </Space>
                )}
            </div>
            <div style={{ marginTop: 12, display: 'flex', gap: 24, color: '#8c8c8c', fontSize: 12 }}>
                <span><ClockCircleOutlined /> Đồng bộ gần nhất: <DateText value={account.last_synced_at} /></span>
                <span>Webhook gần nhất: <DateText value={account.last_webhook_at} /></span>
                {account.token_expires_at && <span>Token hết hạn: <DateText value={account.token_expires_at} withTime={false} /></span>}
            </div>
            {account.status === 'expired' && <Alert type="warning" showIcon style={{ marginTop: 12 }} message="Token đã hết hạn — cần kết nối lại để tiếp tục đồng bộ đơn." />}
        </Card>
    );
}

export function ChannelsPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const canManage = useCan('channels.manage');
    const { data, isLoading, isError, error, refetch } = useChannelAccounts();
    const connect = useConnectChannel();
    const deleteAccount = useDeleteChannelAccount();
    const resync = useResyncChannel();
    const rename = useRenameChannel();
    const [renaming, setRenaming] = useState<ChannelAccount | null>(null);
    const [aliasDraft, setAliasDraft] = useState('');
    const [deleteTarget, setDeleteTarget] = useState<ChannelAccount | null>(null);
    const [confirmDraft, setConfirmDraft] = useState('');
    const confirmOk = !!deleteTarget && confirmDraft.trim().toLowerCase() === deleteTarget.name.trim().toLowerCase();
    // Modal "AppWhiteIpLimit" — hiện riêng để chèn IP outbound thật của server (lấy bằng useOutboundIp).
    const [ipModal, setIpModal] = useState<{ lzCode: string; guide: string; detail: string } | null>(null);
    const { data: outboundIp } = useOutboundIp(ipModal !== null);

    useEffect(() => {
        const connected = params.get('connected');
        const err = params.get('error');
        const ttCode = params.get('tt_code');
        const lzCode = params.get('lz_code');
        const lzMsg = params.get('lz_msg');
        const errDesc = params.get('error_description');
        if (connected) {
            message.success(`Đã kết nối gian hàng ${CHANNEL_META[connected]?.name ?? connected}! Đơn 90 ngày gần đây đang được tải về.`);
            params.delete('connected'); setParams(params, { replace: true });
        } else if (err) {
            // Ưu tiên thông điệp cho prefix `lazada_*` (Lazada redirect ?error=<x>); với lazada_api_error ưu
            // tiên LAZADA_CODE_GUIDE[lz_code] (vd "AppWhiteIpLimit" có hướng dẫn cụ thể); fallback bảng chung.
            const base = (err === 'lazada_api_error' && lzCode && LAZADA_CODE_GUIDE[lzCode])
                || PROVIDER_ERROR_PREFIXES[err]
                || CALLBACK_ERRORS[err]
                || 'Có lỗi khi kết nối gian hàng.';
            const detail = [
                ttCode ? `TikTok code ${ttCode}` : null,
                // Khi đã có hướng dẫn riêng cho lz_code thì không nhắc lại tên mã trong ngoặc cho gọn.
                lzCode && !LAZADA_CODE_GUIDE[lzCode] ? `Lazada code ${lzCode}` : null,
                lzMsg ? `chi tiết: ${lzMsg}` : null,
                errDesc ? `chi tiết: ${errDesc}` : null,
            ].filter(Boolean).join(' · ');
            // Lỗi cần hành động cụ thể (AppWhiteIpLimit, AppNotSubscribed, ...) → mở Modal có IP server để
            // copy-paste; còn lại dùng toast nhanh.
            if (err === 'lazada_api_error' && lzCode === 'AppWhiteIpLimit') {
                setIpModal({ lzCode, guide: LAZADA_CODE_GUIDE[lzCode], detail });
            } else if (err === 'lazada_api_error' && lzCode && LAZADA_CODE_GUIDE[lzCode]) {
                Modal.error({ title: `Lazada báo lỗi: ${lzCode}`, content: <div style={{ whiteSpace: 'pre-line' }}>{base}{detail ? `\n\n${detail}` : ''}</div>, width: 640 });
            } else {
                message.error({ content: detail ? `${base} (${detail})` : base, duration: 15 });
            }
            ['error', 'tt_code', 'lz_code', 'lz_msg', 'error_description'].forEach((k) => params.delete(k));
            setParams(params, { replace: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    if (isError) return <Result status="error" title="Không tải được danh sách gian hàng" subTitle={errorMessage(error)} extra={<Button onClick={() => refetch()}>Thử lại</Button>} />;

    const accounts = data?.data ?? [];
    const connectable = data?.meta.connectable_providers ?? [];

    return (
        <div>
            <PageHeader title="Gian hàng" subtitle="Kết nối các gian hàng sàn TMĐT để đồng bộ đơn hàng tự động" extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isLoading}>Làm mới</Button>} />

            {canManage && (
                <Card title={<><PlusOutlined /> Kết nối gian hàng mới</>} style={{ marginBottom: 16 }}>
                    {connect.isError && <Alert type="error" showIcon style={{ marginBottom: 12 }} message={errorMessage(connect.error, 'Không bắt đầu được luồng kết nối.')} />}
                    <Space wrap>
                        {connectable.length === 0 && <Typography.Text type="secondary">Chưa có sàn nào sẵn sàng. (TikTok cần cấu hình app key/secret sandbox trong <code>.env</code>.)</Typography.Text>}
                        {connectable.map((p) => {
                            const meta = CHANNEL_META[p.code] ?? { name: p.name, color: '#8c8c8c' };
                            return (
                                <Button key={p.code} type="primary" icon={<ShopOutlined />} loading={connect.isPending && connect.variables === p.code} onClick={() => connect.mutate(p.code)} style={{ background: meta.color, borderColor: meta.color }}>
                                    Kết nối {meta.name}
                                </Button>
                            );
                        })}
                        {/* providers awaiting API approval */}
                        {!connectable.some((p) => p.code === 'shopee') && <Button disabled icon={<ShopOutlined />}>Shopee <Tag style={{ marginLeft: 6 }}>Phase 4</Tag></Button>}
                        {!connectable.some((p) => p.code === 'lazada') && <Button disabled icon={<ShopOutlined />}>Lazada <Tag style={{ marginLeft: 6 }}>Phase 4</Tag></Button>}
                    </Space>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12, marginBottom: 0, fontSize: 12 }}>
                        Bấm "Kết nối" sẽ chuyển bạn tới trang ủy quyền của sàn; sau khi đồng ý, bạn quay lại đây. Yêu cầu: <code>APP_URL</code> phải là địa chỉ HTTPS công khai (dùng ngrok cho dev) để sàn redirect callback về được.
                    </Typography.Paragraph>
                </Card>
            )}

            <Card title={<><CheckCircleOutlined /> Gian hàng đã kết nối ({accounts.length})</>} loading={isLoading} styles={{ body: { padding: accounts.length ? 16 : undefined } }}>
                {accounts.length === 0 ? (
                    <Empty description="Chưa có gian hàng nào. Kết nối TikTok Shop để bắt đầu." />
                ) : (
                    <Row gutter={[16, 16]}>
                        {accounts.map((a) => (
                            <Col xs={24} xl={12} key={a.id}>
                                <ShopCard
                                    account={a} canManage={canManage}
                                    onResync={() => resync.mutate(a.id, { onSuccess: () => message.success('Đã xếp lịch đồng bộ lại đơn của gian hàng này.') })}
                                    onDelete={() => { setDeleteTarget(a); setConfirmDraft(''); }}
                                    onRename={() => { setRenaming(a); setAliasDraft(a.display_name ?? ''); }}
                                />
                            </Col>
                        ))}
                    </Row>
                )}
            </Card>

            <Modal
                title="Đặt tên hiển thị cho gian hàng" open={!!renaming} onCancel={() => setRenaming(null)} okText="Lưu" confirmLoading={rename.isPending}
                onOk={() => rename.mutate({ id: renaming!.id, display_name: aliasDraft.trim() || null }, {
                    onSuccess: () => { message.success('Đã cập nhật tên gian hàng.'); setRenaming(null); },
                    onError: (e) => message.error(errorMessage(e)),
                })}
            >
                <Typography.Paragraph type="secondary">Tên sàn: <b>{renaming?.shop_name ?? renaming?.external_shop_id}</b>. Đặt một alias riêng để phân biệt khi có nhiều shop trùng tên. Để trống = dùng tên sàn.</Typography.Paragraph>
                <Input value={aliasDraft} onChange={(e) => setAliasDraft(e.target.value)} placeholder="VD: TikTok – kho HN" maxLength={120} onPressEnter={() => rename.mutate({ id: renaming!.id, display_name: aliasDraft.trim() || null }, { onSuccess: () => { message.success('Đã cập nhật.'); setRenaming(null); } })} />
            </Modal>

            <Modal
                title={<span><DeleteOutlined style={{ color: '#cf1322', marginRight: 6 }} />Xóa kết nối gian hàng?</span>}
                open={!!deleteTarget} onCancel={() => setDeleteTarget(null)} destroyOnClose
                okText="Xóa vĩnh viễn" okButtonProps={{ danger: true, disabled: !confirmOk, loading: deleteAccount.isPending }}
                onOk={() => deleteAccount.mutate({ id: deleteTarget!.id, confirm: confirmDraft.trim() }, {
                    onSuccess: (r) => { message.success(`Đã xóa kết nối — xóa ${r.deleted_orders} đơn hàng, hủy ${r.unlinked_skus} liên kết SKU.`); setDeleteTarget(null); },
                    onError: (e) => message.error(errorMessage(e)),
                })}
            >
                <Alert type="error" showIcon style={{ marginBottom: 12 }}
                    message="Hành động không thể hoàn tác từ giao diện"
                    description={<>Xóa kết nối gian hàng <b>«{deleteTarget?.name}»</b> sẽ:<ul style={{ margin: '6px 0 0', paddingInlineStart: 18 }}>
                        <li><b>Xóa toàn bộ đơn hàng</b> đã đồng bộ từ gian hàng này.</li>
                        <li><b>Hủy mọi liên kết SKU</b> (sku_mappings) của gian hàng này.</li>
                        <li>Ngắt token & gỡ gian hàng khỏi danh sách kết nối.</li>
                    </ul></>}
                />
                <Typography.Paragraph style={{ marginBottom: 4 }}>Để xác nhận, gõ chính xác tên gian hàng: <b>{deleteTarget?.name}</b></Typography.Paragraph>
                <Input autoFocus value={confirmDraft} onChange={(e) => setConfirmDraft(e.target.value)} placeholder={deleteTarget?.name}
                    status={confirmDraft && !confirmOk ? 'error' : undefined}
                    onPressEnter={() => { if (confirmOk) { deleteAccount.mutate({ id: deleteTarget!.id, confirm: confirmDraft.trim() }, { onSuccess: (r) => { message.success(`Đã xóa kết nối — xóa ${r.deleted_orders} đơn, hủy ${r.unlinked_skus} liên kết SKU.`); setDeleteTarget(null); }, onError: (e) => message.error(errorMessage(e)) }); } }} />
            </Modal>

            {/* Lazada `AppWhiteIpLimit` — Lazada gateway chặn vì IP server không trong whitelist. */}
            <Modal open={ipModal !== null} title="Lazada chặn vì IP server chưa được whitelist" width={680}
                onCancel={() => setIpModal(null)}
                footer={[<Button key="close" onClick={() => setIpModal(null)}>Đóng</Button>,
                    <Button key="open" type="primary" onClick={() => window.open('https://open.lazada.com', '_blank', 'noopener')}>Mở Lazada Open Platform</Button>]}>
                <Alert type="warning" showIcon style={{ marginBottom: 12 }}
                    message="Lazada chỉ cho phép gọi API từ những IP nằm trong 'IP Whitelist' đã cấu hình ở app console." />
                <Typography.Paragraph style={{ marginBottom: 8 }}>
                    <b>IP outbound của server</b> (copy vào "IP Whitelist" của app):
                </Typography.Paragraph>
                <Typography.Paragraph style={{ marginBottom: 16 }}>
                    {outboundIp?.detected
                        ? <Typography.Text code copyable style={{ fontSize: 16 }}>{outboundIp.ip}</Typography.Text>
                        : <Typography.Text type="secondary">Đang dò IP… (nếu vẫn không thấy, dùng dịch vụ ngoài như ipify.org / ifconfig.me trên chính server)</Typography.Text>}
                </Typography.Paragraph>
                <Typography.Paragraph><b>Các bước fix:</b></Typography.Paragraph>
                <ol style={{ paddingInlineStart: 20, margin: 0 }}>
                    <li>Đăng nhập <a href="https://open.lazada.com" target="_blank" rel="noreferrer">open.lazada.com</a> → <b>App Management</b> → chọn app của bạn.</li>
                    <li>Vào tab <b>Security</b> / <b>IP Whitelist</b>.</li>
                    <li><b>Thêm IP ở trên</b> vào danh sách (mỗi dòng 1 IP) <b>HOẶC xoá hết IP</b> trong whitelist để cho phép mọi IP (bảo mật còn nguyên app_secret + sign + OAuth).</li>
                    <li>Bấm <b>Save</b>, đợi 1–2 phút Lazada cache, rồi thử kết nối lại.</li>
                </ol>
                {ipModal?.detail && <Alert type="info" showIcon style={{ marginTop: 12, fontSize: 12 }} message={ipModal.detail} />}
            </Modal>
        </div>
    );
}
