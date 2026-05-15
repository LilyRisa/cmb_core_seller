import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
    Alert, App as AntApp, Avatar, Button, Card, Checkbox, Col, DatePicker, Form, Input, InputNumber,
    Popover, Radio, Row, Segmented, Space, Tag, Tooltip, Typography, Upload,
} from 'antd';
import {
    ArrowLeftOutlined, BarcodeOutlined, CalendarOutlined, CarOutlined, EnvironmentOutlined,
    InfoCircleOutlined, PaperClipOutlined, PrinterOutlined, SaveOutlined, ShopOutlined, UpOutlined, UserOutlined,
} from '@ant-design/icons';
import type { RcFile } from 'antd/es/upload';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { OrderItemsEditor, type OrderLineInput } from '@/components/OrderItemsEditor';
import { AddressPicker, type PickedAddress } from '@/components/AddressPicker';
import { CarrierBadge, CARRIER_META } from '@/components/CarrierBadge';
import { errorMessage } from '@/lib/api';
import { useCreateManualOrder, useUploadImage } from '@/lib/inventory';
import { useChannelAccounts } from '@/lib/channels';
import { useTenantMembers } from '@/lib/tenant';
import { useAuth } from '@/lib/auth';
import { useCustomerLookup, type CustomerLookupResult } from '@/lib/customers';
import { useCarrierAccounts } from '@/lib/fulfillment';

const vnd = (n: number) => `${(n || 0).toLocaleString('vi-VN')}₫`;

const SUB_SOURCES = [
    { value: 'website', label: 'Website' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'zalo', label: 'Zalo' },
    { value: 'hotline', label: 'Hotline' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'shopee', label: 'Shopee' },
    { value: 'instagram', label: 'Instagram' },
];

/**
 * Tạo đơn thủ công — UI taodon.png / taodon2.png (BigSeller-style).
 *
 * Layout 2 cột: TRÁI = Sản phẩm + Thanh toán + Ghi chú (đính kèm); PHẢI = Thông tin + Khách hàng + Nhận hàng
 * + Vận chuyển. Đáy: Tiền cần thu + COD + In (F4) + Lưu (F2).
 *
 * Khác bản cũ:
 *  - Khách hàng tách khỏi shipping_address — `recipient` ưu tiên hơn `buyer` (ManualOrderService::buildShippingAddress).
 *  - Lookup theo SĐT: có đơn đang xử lý / đơn đang hoàn ⇒ hiện cảnh báo + danh sách order_number.
 *  - AddressPicker hỗ trợ 2 tab "Địa chỉ mới" (GHN master-data) / "Địa chỉ cũ" (customer.addresses_meta) ⇒
 *    cung cấp đủ district_id / ward_code cho GHN createShipment.
 *  - Thêm các trường tài chính chuẩn để báo cáo: phí vận chuyển, giảm giá đơn hàng, tiền chuyển khoản (prepaid),
 *    phụ thu, miễn phí giao hàng, chỉ thu phí nếu hoàn (collect_fee_on_return_only).
 */
export function CreateOrderPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const create = useCreateManualOrder();
    const { data: channelsData } = useChannelAccounts();
    const channels = channelsData?.data ?? [];
    const { data: members = [] } = useTenantMembers();
    const { data: carrierAccounts = [] } = useCarrierAccounts();
    const upload = useUploadImage();
    const { data: me } = useAuth();
    const meId = me?.id ?? null;

    // ---- state ngoài form (controlled cho UX riêng) ----
    const [items, setItems] = useState<OrderLineInput[]>([]);
    const [phone, setPhone] = useState('');
    const [shipAddress, setShipAddress] = useState<PickedAddress>({});
    const [addrPickerOpen, setAddrPickerOpen] = useState(false);
    const [carrierAccountId, setCarrierAccountId] = useState<number | null>(null);
    const [tags, setTags] = useState<string[]>([]);
    const [tagInput, setTagInput] = useState('');
    const [collapsed, setCollapsed] = useState(false);
    const [showTagInput, setShowTagInput] = useState(false);
    const [attachments, setAttachments] = useState<Array<{ url: string; name: string }>>([]);
    const lookup = useCustomerLookup(phone);
    const customerData: CustomerLookupResult | undefined = lookup.data;
    const oldAddresses = customerData?.addresses ?? [];

    // Khi lookup được khách & user chưa điền tên ⇒ auto-fill tên / email từ customer (memo). Không ghi đè nếu user gõ rồi.
    useEffect(() => {
        if (!customerData?.customer) return;
        const cur = form.getFieldsValue(['buyer_name']);
        if (!cur.buyer_name && customerData.customer.name) form.setFieldsValue({ buyer_name: customerData.customer.name });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [customerData?.customer?.id, form]);

    // mặc định "NV xử lý" = user hiện tại
    useEffect(() => { if (meId != null) form.setFieldsValue({ assignee_user_id: meId }); }, [meId, form]);

    // ---- payment summary (live) ----
    const summary = Form.useWatch([], form) as Record<string, unknown> | undefined;
    const totals = useMemo(() => {
        const itemTotal = items.reduce((s, r) => s + Math.max(0, r.unit_price * r.quantity - r.discount), 0);
        const itemDiscount = items.reduce((s, r) => s + (r.discount || 0), 0);
        const orderDiscount = Math.max(0, Number(summary?.order_discount) || 0);
        const freeShipping = !!summary?.free_shipping;
        const shippingFee = freeShipping ? 0 : Math.max(0, Number(summary?.shipping_fee) || 0);
        const surcharge = Math.max(0, Number(summary?.surcharge) || 0);
        const prepaid = Math.max(0, Number(summary?.prepaid_amount) || 0);
        const totalBefore = itemTotal + shippingFee + surcharge;
        const afterDiscount = Math.max(0, totalBefore - orderDiscount);
        const needCollect = Math.max(0, afterDiscount - prepaid);
        return { itemTotal, itemDiscount, orderDiscount, totalDiscount: itemDiscount + orderDiscount, shippingFee, surcharge, prepaid, afterDiscount, needCollect, freeShipping };
    }, [items, summary]);

    // ---- submit ----
    const submit = (andPrint?: boolean) => form.validateFields().then((v) => {
        if (items.length === 0) { message.error('Cần ít nhất một dòng hàng.'); return; }
        if (items.some((l) => l.uploading)) { message.error('Đang tải ảnh — vui lòng đợi.'); return; }
        if (items.some((l) => !l.sku_id && l.name.trim() === '')) { message.error('Dòng "sản phẩm nhanh" phải có tên.'); return; }
        const lines = items.map((l) => ({
            sku_id: l.sku_id,
            name: l.sku_id ? undefined : l.name.trim(),
            image: l.sku_id ? undefined : (l.image || undefined),
            quantity: l.quantity, unit_price: l.unit_price, discount: l.discount,
        }));
        const isCod = !!v.is_cod;
        const codAmount = isCod ? Math.max(0, totals.needCollect) : undefined;
        create.mutate({
            sub_source: v.sub_source || undefined,
            status: 'processing',
            buyer: { name: v.buyer_name || undefined, phone: phone || undefined },
            recipient: {
                name: v.recipient_name || v.buyer_name || undefined,
                phone: v.recipient_phone || phone || undefined,
                address: v.recipient_address || undefined,
                ward: shipAddress.ward || undefined,
                ward_code: shipAddress.ward_code || undefined,
                district: shipAddress.district || undefined,
                district_id: shipAddress.district_id || undefined,
                province: shipAddress.province || undefined,
                province_id: shipAddress.province_id || undefined,
                expected_at: v.expected_delivery_date ? dayjs(v.expected_delivery_date).toISOString() : undefined,
            },
            items: lines,
            free_shipping: !!v.free_shipping,
            shipping_fee: v.free_shipping ? 0 : (v.shipping_fee ?? 0),
            order_discount: v.order_discount ?? 0,
            prepaid_amount: v.prepaid_amount ?? 0,
            surcharge: v.surcharge ?? 0,
            is_cod: isCod, cod_amount: codAmount,
            note: [v.note_internal, attachments.length ? `\n— Tệp đính kèm:\n${attachments.map((a) => `${a.name}: ${a.url}`).join('\n')}` : ''].filter(Boolean).join('') || undefined,
            tags,
            meta: {
                assignee_user_id: v.assignee_user_id || undefined,
                care_user_id: v.care_user_id || undefined,
                marketer_user_id: v.marketer_user_id || undefined,
                expected_delivery_date: v.expected_delivery_date ? dayjs(v.expected_delivery_date).format('YYYY-MM-DD') : undefined,
                gender: v.gender || undefined,
                dob: v.dob ? dayjs(v.dob).format('YYYY-MM-DD') : undefined,
                email: v.email || undefined,
                print_note: v.note_print || undefined,
                collect_fee_on_return_only: !!v.collect_fee_on_return_only,
                carrier_account_id: carrierAccountId || undefined,   // FE-only hint — không lưu DB (ManualOrderService sẽ strip)
            },
        }, {
            onSuccess: (o) => { message.success(andPrint ? 'Đã tạo đơn — chuyển sang in phiếu giao hàng.' : 'Đã tạo đơn'); navigate(`/orders/${o.id}${andPrint ? '?print=1' : ''}`); },
            onError: (e) => message.error(errorMessage(e)),
        });
    }).catch(() => message.error('Vui lòng kiểm tra lại thông tin.'));

    // F2 / F4 hotkeys
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'F2') { e.preventDefault(); submit(false); }
            else if (e.key === 'F4') { e.preventDefault(); submit(true); }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [items, phone, shipAddress, tags, attachments]);

    const addTag = () => {
        const t = tagInput.trim();
        if (!t || tags.includes(t)) { setTagInput(''); setShowTagInput(false); return; }
        setTags([...tags, t]); setTagInput(''); setShowTagInput(false);
    };
    const removeTag = (t: string) => setTags(tags.filter((x) => x !== t));

    const beforeUpload = (file: RcFile) => {
        if (file.size / 1024 / 1024 > 10) { message.error('Tệp tối đa 10MB.'); return Upload.LIST_IGNORE; }
        upload.mutate({ file, folder: 'order-attachments' }, {
            onSuccess: (r) => setAttachments((a) => [...a, { url: r.url, name: file.name }]),
            onError: (e) => message.error(errorMessage(e)),
        });
        return false;
    };

    const carrier = carrierAccounts.find((c) => c.id === carrierAccountId) ?? null;
    const carrierMeta = carrier ? (CARRIER_META[carrier.carrier.toLowerCase()] ?? { name: carrier.carrier, color: 'default' }) : null;

    return (
        <div style={{ paddingBottom: 80 }}>
            <PageHeader
                title={<Space size="middle"><Link to="/orders"><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>Tạo đơn thủ công</span></Space>}
                subtitle="Đơn nguồn ngoài sàn (website / Facebook / Zalo / hotline) — trừ chung kho, vào cùng luồng xử lý"
            />
            <Form form={form} layout="vertical" initialValues={{
                channel_mode: 'online', sub_source: undefined, is_cod: false, free_shipping: false, collect_fee_on_return_only: false,
                shipping_fee: 0, order_discount: 0, prepaid_amount: 0, surcharge: 0,
            }}>
                <Row gutter={16}>
                    {/* ============== LEFT COLUMN ============== */}
                    <Col xs={24} lg={16}>
                        {/* Sản phẩm */}
                        <Card
                            size="small" style={{ marginBottom: 16 }}
                            title={<Space size={16}><span style={{ fontSize: 15, fontWeight: 600 }}>Sản phẩm</span></Space>}
                            extra={(
                                <Space size={8} wrap>
                                    <Form.Item name="channel_mode" noStyle>
                                        <Segmented size="small" options={[{ value: 'online', label: 'Online' }, { value: 'offline', label: 'Offline' }]} />
                                    </Form.Item>
                                    <Form.Item name="sub_source" noStyle>
                                        <Radio.Group size="small" optionType="button" buttonStyle="solid">
                                            {SUB_SOURCES.slice(0, 4).map((s) => <Radio.Button key={s.value} value={s.value}>{s.label}</Radio.Button>)}
                                        </Radio.Group>
                                    </Form.Item>
                                    {channels.length > 0 && (
                                        <Popover trigger="click" placement="bottomRight"
                                            content={(
                                                <div style={{ width: 280, maxHeight: 320, overflowY: 'auto' }}>
                                                    <Radio.Group style={{ width: '100%' }}
                                                        onChange={(e) => form.setFieldsValue({ channel_account_id: e.target.value })}
                                                        value={summary?.channel_account_id as number | undefined}>
                                                        {channels.map((c) => (
                                                            <Radio key={c.id} value={c.id} style={{ display: 'flex', padding: '6px 8px' }}>
                                                                <Space><ShopOutlined /><span>{c.name}</span></Space>
                                                            </Radio>
                                                        ))}
                                                    </Radio.Group>
                                                </div>
                                            )}>
                                            <Button size="small" icon={<ShopOutlined />}>
                                                {channels.find((c) => c.id === summary?.channel_account_id)?.name ?? 'Chọn gian hàng'}
                                            </Button>
                                        </Popover>
                                    )}
                                </Space>
                            )}
                        >
                            <OrderItemsEditor value={items} onChange={setItems} />
                        </Card>

                        <Row gutter={16}>
                            {/* Thanh toán */}
                            <Col xs={24} md={12}>
                                <Card size="small" style={{ marginBottom: 16 }}
                                    title={<span style={{ fontSize: 15, fontWeight: 600 }}>Thanh toán</span>}>
                                    <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                        <Space wrap size={16}>
                                            <Form.Item name="free_shipping" valuePropName="checked" noStyle><Checkbox>Miễn phí giao hàng</Checkbox></Form.Item>
                                            <Form.Item name="collect_fee_on_return_only" valuePropName="checked" noStyle><Checkbox>Chỉ thu phí nếu hoàn</Checkbox></Form.Item>
                                        </Space>
                                        <PayRow label="Phí vận chuyển" name="shipping_fee" disabled={!!summary?.free_shipping} />
                                        <PayRow label="Giảm giá đơn hàng" name="order_discount" />
                                        <PayRow label="Tiền chuyển khoản" name="prepaid_amount" />
                                        <PayRow label="Phụ thu" name="surcharge" />
                                    </Space>
                                    <div style={{ marginTop: 12, paddingTop: 12, borderTop: '1px solid #f0f0f0' }}>
                                        <SummaryRow label="Tổng số tiền" value={vnd(totals.itemTotal + totals.shippingFee + totals.surcharge)} />
                                        <SummaryRow label="Giảm giá" value={vnd(totals.totalDiscount)} valueClass="g" />
                                        <SummaryRow label="Sau giảm giá" value={vnd(totals.afterDiscount)} />
                                        <SummaryRow label="Tiền cần thu" value={vnd(totals.needCollect)} />
                                        <SummaryRow label="Đã thanh toán" value={vnd(totals.prepaid)} />
                                        <SummaryRow label="Còn thiếu" value={vnd(Math.max(0, totals.needCollect))} valueClass="r" strong />
                                    </div>
                                </Card>
                            </Col>

                            {/* Ghi chú */}
                            <Col xs={24} md={12}>
                                <Card size="small" style={{ marginBottom: 16 }}
                                    title={<span style={{ fontSize: 15, fontWeight: 600 }}>Ghi chú</span>}>
                                    <NoteTabs />
                                    <Upload beforeUpload={beforeUpload} multiple showUploadList={false}>
                                        <div style={{ width: '100%', height: 70, border: '1px dashed #d9d9d9', borderRadius: 6, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', background: '#fafafa', marginTop: 8 }}>
                                            <PaperClipOutlined style={{ fontSize: 18, color: '#bfbfbf' }} />
                                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>Tải lên (ảnh / file ≤ 10MB)</Typography.Text>
                                        </div>
                                    </Upload>
                                    {attachments.length > 0 && (
                                        <div style={{ marginTop: 8 }}>
                                            {attachments.map((a, i) => (
                                                <Tag key={i} closable onClose={() => setAttachments(attachments.filter((_, j) => j !== i))} style={{ marginBottom: 4 }}>
                                                    <a href={a.url} target="_blank" rel="noreferrer">{a.name}</a>
                                                </Tag>
                                            ))}
                                        </div>
                                    )}
                                </Card>
                            </Col>
                        </Row>
                    </Col>

                    {/* ============== RIGHT COLUMN ============== */}
                    <Col xs={24} lg={8}>
                        {/* Thông tin */}
                        <Card size="small" style={{ marginBottom: 16 }}
                            title={<Space><span style={{ fontSize: 15, fontWeight: 600 }}>Thông tin</span><UpOutlined rotate={collapsed ? 180 : 0} style={{ cursor: 'pointer', color: '#8c8c8c' }} onClick={() => setCollapsed((c) => !c)} /></Space>}
                        >
                            {!collapsed && (
                                <>
                                    <KvRow label="Tạo lúc">
                                        <Typography.Text>{dayjs().format('HH:mm DD/MM/YYYY')}</Typography.Text>
                                    </KvRow>
                                    <KvRow label="NV xử lý">
                                        <Form.Item name="assignee_user_id" noStyle>
                                            <UserPicker members={members} placeholder="Chọn NV xử lý" />
                                        </Form.Item>
                                    </KvRow>
                                    <KvRow label="NV chăm sóc">
                                        <Form.Item name="care_user_id" noStyle>
                                            <UserPicker members={members} placeholder="Chọn NV chăm sóc" />
                                        </Form.Item>
                                    </KvRow>
                                    <KvRow label="Marketer">
                                        <Form.Item name="marketer_user_id" noStyle>
                                            <UserPicker members={members} placeholder="Chọn Marketer" />
                                        </Form.Item>
                                    </KvRow>
                                    <div style={{ marginTop: 8 }}>
                                        {tags.map((t) => <Tag key={t} closable onClose={() => removeTag(t)} color="blue" style={{ marginBottom: 4 }}>{t}</Tag>)}
                                        {showTagInput ? (
                                            <Input size="small" autoFocus style={{ width: 120 }} value={tagInput}
                                                onChange={(e) => setTagInput(e.target.value)} onPressEnter={addTag} onBlur={addTag} maxLength={50} />
                                        ) : (
                                            <Tag onClick={() => setShowTagInput(true)} style={{ cursor: 'pointer', background: '#fafafa', borderStyle: 'dashed' }}>+ Thêm thẻ</Tag>
                                        )}
                                    </div>
                                </>
                            )}
                        </Card>

                        {/* Khách hàng */}
                        <Card size="small" style={{ marginBottom: 16 }}
                            title={<span style={{ fontSize: 15, fontWeight: 600 }}>Khách hàng</span>}
                            extra={(
                                <Space>
                                    <Form.Item name="gender" noStyle>
                                        <Radio.Group size="small" buttonStyle="solid" optionType="button" options={[
                                            { value: 'male', label: 'Nam' }, { value: 'female', label: 'Nữ' }, { value: 'other', label: 'Khác' },
                                        ]} />
                                    </Form.Item>
                                </Space>
                            )}
                        >
                            <Row gutter={8}>
                                <Col span={12}><Form.Item name="buyer_name" style={{ marginBottom: 8 }}><Input prefix={<UserOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tên khách hàng" maxLength={255} /></Form.Item></Col>
                                <Col span={12}><Form.Item style={{ marginBottom: 8 }}>
                                    <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="SĐT" maxLength={32}
                                        suffix={lookup.isFetching ? <Typography.Text type="secondary" style={{ fontSize: 11 }}>…tra cứu</Typography.Text> : (customerData?.customer ? <Tooltip title="Khách đã có trong sổ"><InfoCircleOutlined style={{ color: '#1677ff' }} /></Tooltip> : null)} />
                                </Form.Item></Col>
                                <Col span={12}><Form.Item name="email" style={{ marginBottom: 8 }}><Input placeholder="Địa chỉ email" maxLength={255} /></Form.Item></Col>
                                <Col span={12}><Form.Item name="dob" style={{ marginBottom: 8 }}><DatePicker style={{ width: '100%' }} placeholder="Ngày sinh" format="DD/MM/YYYY" /></Form.Item></Col>
                            </Row>
                            <CustomerWarning data={customerData} />
                        </Card>

                        {/* Nhận hàng */}
                        <Card size="small" style={{ marginBottom: 16 }}
                            title={<span style={{ fontSize: 15, fontWeight: 600 }}>Nhận hàng</span>}
                            extra={(
                                <Popover trigger="click" open={addrPickerOpen} onOpenChange={setAddrPickerOpen} placement="bottomRight"
                                    content={(
                                        <AddressPicker
                                            value={shipAddress} oldAddresses={oldAddresses}
                                            onPick={(p) => {
                                                setShipAddress(p);
                                                if (p.name) form.setFieldsValue({ recipient_name: p.name });
                                                if (p.phone && !phone) setPhone(p.phone);
                                                if (p.address) form.setFieldsValue({ recipient_address: p.address });
                                                setAddrPickerOpen(false);
                                            }}
                                        />
                                    )}>
                                    <Button size="small" icon={<EnvironmentOutlined />}>Chọn địa chỉ</Button>
                                </Popover>
                            )}
                        >
                            <Form.Item name="expected_delivery_date" style={{ marginBottom: 8 }}>
                                <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" placeholder="Dự kiến nhận hàng" suffixIcon={<CalendarOutlined />} />
                            </Form.Item>
                            <Row gutter={8}>
                                <Col span={12}><Form.Item name="recipient_name" style={{ marginBottom: 8 }}><Input placeholder="Tên người nhận" maxLength={255} /></Form.Item></Col>
                                <Col span={12}><Form.Item name="recipient_phone" style={{ marginBottom: 8 }}><Input placeholder="Số điện thoại" maxLength={32} /></Form.Item></Col>
                            </Row>
                            <Form.Item name="recipient_address" style={{ marginBottom: 8 }}>
                                <Input placeholder="Địa chỉ chi tiết (số nhà, tên đường)" maxLength={500} />
                            </Form.Item>
                            {/* Inline summary: tỉnh / huyện / xã đã chọn từ picker */}
                            <Popover trigger="click" open={addrPickerOpen ? false : undefined} placement="bottomLeft" content={(
                                <AddressPicker value={shipAddress} oldAddresses={oldAddresses}
                                    onPick={(p) => { setShipAddress(p); if (p.name) form.setFieldsValue({ recipient_name: p.name }); if (p.address) form.setFieldsValue({ recipient_address: p.address }); }} />
                            )}>
                                <Input readOnly
                                    placeholder="Chọn địa chỉ (Tỉnh / Quận / Phường)"
                                    value={[shipAddress.ward, shipAddress.district, shipAddress.province].filter(Boolean).join(', ')}
                                    suffix={<EnvironmentOutlined style={{ color: '#bfbfbf' }} />}
                                    style={{ cursor: 'pointer' }}
                                    onClick={() => setAddrPickerOpen(true)}
                                />
                            </Popover>
                        </Card>

                        {/* Vận chuyển */}
                        <Card size="small" style={{ marginBottom: 16 }}
                            title={<span style={{ fontSize: 15, fontWeight: 600 }}>Vận chuyển</span>}
                            extra={(
                                <Popover trigger="click" placement="bottomRight" content={(
                                    <div style={{ width: 260, maxHeight: 320, overflowY: 'auto' }}>
                                        <Radio.Group style={{ width: '100%' }} value={carrierAccountId} onChange={(e) => setCarrierAccountId(e.target.value)}>
                                            <Radio value={null} style={{ display: 'flex', padding: '6px 8px' }}>
                                                <Space><CarOutlined /><span>Tự vận chuyển</span></Space>
                                            </Radio>
                                            {carrierAccounts.filter((c) => c.is_active).map((c) => {
                                                const m = CARRIER_META[c.carrier.toLowerCase()] ?? { name: c.carrier, color: 'default' };
                                                return (
                                                    <Radio key={c.id} value={c.id} style={{ display: 'flex', padding: '6px 8px' }}>
                                                        <Space><Tag color={m.color} icon={<CarOutlined />} style={{ marginInlineEnd: 0 }}>{m.name}</Tag><span>{c.name}</span>{c.is_default && <Tag color="blue">Mặc định</Tag>}</Space>
                                                    </Radio>
                                                );
                                            })}
                                        </Radio.Group>
                                    </div>
                                )}>
                                    <Button size="small" icon={<CarOutlined />}>
                                        {carrier ? (carrierMeta?.name ?? carrier.carrier) : 'Đơn vị VC'}
                                    </Button>
                                </Popover>
                            )}
                        >
                            <Row gutter={8} align="middle">
                                <Col span={6}><Typography.Text type="secondary">Kích thước</Typography.Text></Col>
                                <Col span={18}>
                                    <Space.Compact block>
                                        <Form.Item name="dim_l" noStyle><InputNumber min={0} placeholder="0" style={{ width: '33%' }} addonAfter="cm" /></Form.Item>
                                        <Form.Item name="dim_w" noStyle><InputNumber min={0} placeholder="0" style={{ width: '33%' }} addonAfter="cm" /></Form.Item>
                                        <Form.Item name="dim_h" noStyle><InputNumber min={0} placeholder="0" style={{ width: '34%' }} addonAfter="cm" /></Form.Item>
                                    </Space.Compact>
                                </Col>
                            </Row>
                            <Row gutter={8} style={{ marginTop: 8 }}>
                                <Col span={12}><Form.Item name="tracking_no" noStyle><Input placeholder="Mã vận đơn" maxLength={120} /></Form.Item></Col>
                                <Col span={12}><Form.Item name="ship_fee_carrier" noStyle><InputNumber min={0} placeholder="Phí" style={{ width: '100%' }} addonAfter="₫" /></Form.Item></Col>
                            </Row>
                            {carrier && <div style={{ marginTop: 8 }}><CarrierBadge code={carrier.carrier} /></div>}
                        </Card>
                    </Col>
                </Row>
            </Form>

            {/* Sticky bottom bar */}
            <div style={{
                position: 'fixed', left: 0, right: 0, bottom: 0, background: '#fff', borderTop: '1px solid #f0f0f0',
                padding: '10px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', zIndex: 9,
                boxShadow: '0 -2px 8px rgba(0,0,0,0.04)',
            }}>
                <Space size={24}>
                    <Typography.Text>Tiền cần thu: <Typography.Text strong style={{ color: '#cf1322' }}>{vnd(totals.needCollect)}</Typography.Text></Typography.Text>
                    <Typography.Text>COD: <Form.Item name="is_cod" valuePropName="checked" noStyle><Checkbox /></Form.Item> <Typography.Text strong style={{ color: summary?.is_cod ? '#cf1322' : '#8c8c8c' }}>{vnd(summary?.is_cod ? totals.needCollect : 0)}</Typography.Text></Typography.Text>
                </Space>
                <Space>
                    <Button icon={<PrinterOutlined />} onClick={() => submit(true)} loading={create.isPending}>In (F4)</Button>
                    <Button type="primary" icon={<SaveOutlined />} onClick={() => submit(false)} loading={create.isPending}>Lưu (F2)</Button>
                </Space>
            </div>
        </div>
    );
}

// --------- helpers ----------

function PayRow({ label, name, disabled }: { label: string; name: string; disabled?: boolean }) {
    return (
        <Row align="middle" gutter={8}>
            <Col flex="auto"><Typography.Text type="secondary">{label}</Typography.Text></Col>
            <Col style={{ width: 180 }}>
                <Form.Item name={name} noStyle>
                    <InputNumber min={0} disabled={disabled} style={{ width: '100%', textAlign: 'right' }} addonAfter="₫" controls={false} />
                </Form.Item>
            </Col>
        </Row>
    );
}

function SummaryRow({ label, value, strong, valueClass }: { label: string; value: string; strong?: boolean; valueClass?: 'g' | 'r' }) {
    const color = valueClass === 'g' ? '#389e0d' : valueClass === 'r' ? '#cf1322' : undefined;
    return (
        <Row justify="space-between" style={{ padding: '4px 0' }}>
            <Typography.Text type="secondary">{label}</Typography.Text>
            <Typography.Text style={{ color }} strong={strong}>{value}</Typography.Text>
        </Row>
    );
}

function KvRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <Row align="middle" style={{ marginBottom: 8 }}>
            <Col span={10}><Typography.Text type="secondary">{label}</Typography.Text></Col>
            <Col span={14}>{children}</Col>
        </Row>
    );
}

function UserPicker({ value, onChange, members, placeholder }: { value?: number | null; onChange?: (v: number | null) => void; members: Array<{ id: number; name: string; email: string }>; placeholder: string }) {
    return (
        <Popover trigger="click" placement="bottomRight" content={(
            <div style={{ width: 260, maxHeight: 320, overflowY: 'auto' }}>
                <Radio.Group style={{ width: '100%' }} value={value ?? undefined} onChange={(e) => onChange?.(e.target.value)}>
                    {members.length === 0 ? <Typography.Text type="secondary">Chưa có thành viên.</Typography.Text> : null}
                    {members.map((m) => (
                        <Radio key={m.id} value={m.id} style={{ display: 'flex', padding: '6px 8px' }}>
                            <Space size={8}>
                                <Avatar size={22} style={{ background: '#722ed1', fontSize: 12 }}>{(m.name || m.email).slice(0, 2).toUpperCase()}</Avatar>
                                <span>{m.name || m.email}</span>
                            </Space>
                        </Radio>
                    ))}
                </Radio.Group>
            </div>
        )}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', justifyContent: 'flex-end' }}>
                {value && members.find((m) => m.id === value) ? (
                    <>
                        <Avatar size={22} style={{ background: '#722ed1', fontSize: 12 }}>{(members.find((m) => m.id === value)!.name || members.find((m) => m.id === value)!.email).slice(0, 2).toUpperCase()}</Avatar>
                        <Typography.Text>{members.find((m) => m.id === value)!.name || members.find((m) => m.id === value)!.email}</Typography.Text>
                    </>
                ) : <Typography.Text type="secondary">{placeholder}</Typography.Text>}
            </div>
        </Popover>
    );
}

function NoteTabs() {
    const [tab, setTab] = useState<'internal' | 'print'>('internal');
    return (
        <>
            <Radio.Group size="small" buttonStyle="solid" optionType="button" value={tab} onChange={(e) => setTab(e.target.value)} style={{ marginBottom: 8 }}>
                <Radio.Button value="internal">Nội bộ</Radio.Button>
                <Radio.Button value="print">Để in</Radio.Button>
            </Radio.Group>
            <Form.Item name={tab === 'internal' ? 'note_internal' : 'note_print'} noStyle>
                <Input.TextArea rows={4} maxLength={2000} placeholder={tab === 'internal' ? 'Viết ghi chú hoặc /shortcut để ghi chú nhanh' : 'Ghi chú sẽ hiển thị trên phiếu in'} />
            </Form.Item>
        </>
    );
}

function CustomerWarning({ data }: { data: CustomerLookupResult | undefined }) {
    if (!data?.customer) return null;
    const open = data.open_orders ?? [];
    const returning = data.returning_orders ?? [];
    if (open.length === 0 && returning.length === 0 && !data.customer.is_blocked) return null;
    return (
        <Alert
            style={{ marginTop: 8 }}
            type={returning.length > 0 || data.customer.is_blocked ? 'warning' : 'info'}
            showIcon
            message={(
                <Space direction="vertical" size={2} style={{ width: '100%' }}>
                    {data.customer.is_blocked && <Typography.Text strong style={{ color: '#cf1322' }}>Khách đang bị chặn — kiểm tra trước khi tạo đơn.</Typography.Text>}
                    {open.length > 0 && (
                        <div>
                            <Typography.Text strong>Đang có {open.length} đơn chưa hoàn thành:</Typography.Text>
                            <div style={{ marginTop: 4 }}>{open.slice(0, 10).map((o) => <OrderIdTag key={o.id} order={o} />)}{open.length > 10 ? <Typography.Text type="secondary"> · +{open.length - 10}</Typography.Text> : null}</div>
                        </div>
                    )}
                    {returning.length > 0 && (
                        <div>
                            <Typography.Text strong style={{ color: '#cf1322' }}>{returning.length} đơn đang/đã hoàn:</Typography.Text>
                            <div style={{ marginTop: 4 }}>{returning.slice(0, 10).map((o) => <OrderIdTag key={o.id} order={o} danger />)}{returning.length > 10 ? <Typography.Text type="secondary"> · +{returning.length - 10}</Typography.Text> : null}</div>
                        </div>
                    )}
                </Space>
            )}
        />
    );
}

function OrderIdTag({ order, danger }: { order: { id: number; order_number: string | null; status: string }; danger?: boolean }) {
    return (
        <Link to={`/orders/${order.id}`} target="_blank">
            <Tag color={danger ? 'red' : 'blue'} style={{ marginBottom: 4 }}>
                <BarcodeOutlined style={{ marginInlineEnd: 4 }} />
                {order.order_number ?? `#${order.id}`}
            </Tag>
        </Link>
    );
}
