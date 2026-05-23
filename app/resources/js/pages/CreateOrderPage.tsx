import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
    Alert, App as AntApp, Avatar, Badge, Button, Card, Checkbox, Col, DatePicker, Form, Input, InputNumber, Modal,
    Popover, Radio, Row, Segmented, Space, Tabs, Tag, Tooltip, Typography, Upload,
} from 'antd';
import {
    ArrowLeftOutlined, BarcodeOutlined, CalendarOutlined, CheckCircleFilled, CloseCircleFilled,
    EnvironmentOutlined, FacebookFilled, LockOutlined, MoreOutlined, PaperClipOutlined,
    PrinterOutlined, SaveOutlined, SearchOutlined, UpOutlined,
} from '@ant-design/icons';
import type { RcFile } from 'antd/es/upload';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { OrderItemsEditor, PickerTrigger, type OrderLineInput } from '@/components/OrderItemsEditor';
import { AddressPicker, type PickedAddress } from '@/components/AddressPicker';
import { AddressAutocomplete } from '@/components/AddressAutocomplete';
import { errorMessage } from '@/lib/api';
import { useCreateManualOrder, useUpdateManualOrder, useUploadImage, useWarehouses, type Sku } from '@/lib/inventory';
import { useOrder } from '@/lib/orders';
import { useTenantMembers } from '@/lib/tenant';
import { useAuth } from '@/lib/auth';
import { useCustomerLookup, type CustomerLookupResult } from '@/lib/customers';

// ============================================================================
//  Tạo đơn thủ công — match taodon.png / taodon2.png (BigSeller-inspired POS)
// ============================================================================
// Khác bản trước (Sprint 1):
//  - Card "Sản phẩm" có inline search bar + tabs Sản phẩm/Combo + checkbox Còn hàng + (F9) — đúng ảnh.
//  - Khách hàng SĐT → tự copy xuống Nhận hàng (real-time, từ char 1). Mất sync khi user gõ tay vào
//    Nhận hàng SĐT (recipientPhoneSynced=false).
//  - CustomerWarning trigger theo `phone` (useCustomerLookup tự debounce); hiển thị khi customer có
//    open_orders hoặc returning_orders. KEY = phone (đã chuẩn hoá hash ở BE — SPEC 0021).
//  - Đẹp hơn: bottom bar dùng Space (không nhúng Form.Item vào Typography.Text), inputs Thanh toán
//    dùng suffix "₫" thay vì addonAfter (gọn hơn theo ảnh), summary list dùng dotted underline.
//  - Auto fill: pickup pre-fill recipient_name/recipient_address từ địa chỉ đã chọn (chỉ khi rỗng).
//  - Customer is_blocked ⇒ tag đỏ rõ + tooltip lý do.
//  - Skip "negProfit" check trên trang tạo (đơn manual chưa có giá vốn).
// ============================================================================

const vnd = (n: number) => `${(n || 0).toLocaleString('vi-VN')}`;

const SUB_SOURCES = [
    { value: 'website', label: 'Website' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'zalo', label: 'Zalo' },
    { value: 'hotline', label: 'Hotline' },
];

const SUB_SOURCE_ICONS: Record<string, React.ReactNode> = {
    facebook: <FacebookFilled style={{ color: '#1877f2' }} />,
};

/** Nháp 1 đơn (serialize được) — dùng để lưu localStorage + khôi phục tab. */
export type OrderDraft = {
    items: OrderLineInput[];
    phone: string;
    shipAddress: PickedAddress;
    tags: string[];
    attachments: Array<{ url: string; name: string }>;
    form: Record<string, unknown>;
};

interface CreateOrderFormProps {
    /** Tab đang hiển thị? Chỉ tab active bắt hotkey F2/F4. Mặc định true (chế độ sửa/đứng riêng). */
    active?: boolean;
    /** Chế độ tạo (có tab): gọi thay vì navigate sau khi lưu — để parent reset tab. */
    onSaved?: (orderId: number) => void;
    /** Báo nháp lên parent (debounce). null = sạch (chưa nhập SĐT). Parent lưu localStorage. */
    onDraftChange?: (draft: OrderDraft | null) => void;
    /** Nháp khôi phục khi mở lại tab (từ localStorage). */
    initialDraft?: OrderDraft | null;
    /** Nhúng trong workspace nhiều tab ⇒ ẩn PageHeader riêng (workspace có header chung). */
    embedded?: boolean;
    /** Nhúng trong Drawer/khung hẹp (vd khung chat) ⇒ thanh nút dưới dạng sticky thay vì fixed toàn màn. */
    compact?: boolean;
}

export function CreateOrderForm({ active = true, onSaved, onDraftChange, initialDraft, embedded = false, compact = false }: CreateOrderFormProps) {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const { id: editIdRaw } = useParams();
    const editId = editIdRaw ? Number(editIdRaw) : null;
    const isEdit = editId != null && !Number.isNaN(editId);
    const [form] = Form.useForm();
    const create = useCreateManualOrder();
    const update = useUpdateManualOrder();
    // Khi sửa đơn — load full order detail (kèm items + shipments) để prefill toàn bộ form.
    const orderQuery = useOrder(isEdit ? editId : undefined);
    const editingOrder = isEdit ? orderQuery.data : undefined;
    const isPushed = !!editingOrder?.is_pushed_to_carrier;
    const submitting = create.isPending || update.isPending;
    const { data: members = [] } = useTenantMembers();
    const upload = useUploadImage();
    const { data: me } = useAuth();
    const meId = me?.id ?? null;

    // ---- warehouses ----
    const { data: warehouses = [] } = useWarehouses();
    const [warehouseId, setWarehouseId] = useState<number | null>(null);

    useEffect(() => {
        if (warehouseId == null && warehouses.length > 0) {
            setWarehouseId(warehouses.find((w) => w.is_default)?.id ?? warehouses[0].id);
        }
    }, [warehouses, warehouseId]);

    // ---- state ngoài form ----
    const [items, setItems] = useState<OrderLineInput[]>([]);
    const [phone, setPhone] = useState('');
    const [recipientPhoneSynced, setRecipientPhoneSynced] = useState(true);
    const [shipAddress, setShipAddress] = useState<PickedAddress>({});
    const [addrPickerOpen, setAddrPickerOpen] = useState(false);
    const [tags, setTags] = useState<string[]>([]);
    const [tagInput, setTagInput] = useState('');
    const [showTagInput, setShowTagInput] = useState(false);
    const [attachments, setAttachments] = useState<Array<{ url: string; name: string }>>([]);
    const [pickerOpen, setPickerOpen] = useState(false);
    const [productSearch, setProductSearch] = useState('');
    const [productMode, setProductMode] = useState<'sku' | 'combo'>('sku');
    const [inStockOnly, setInStockOnly] = useState(false);
    const [thongTinCollapsed, setThongTinCollapsed] = useState(false);
    const [draftRestored, setDraftRestored] = useState(false);

    // ---- queries ----
    // Lookup theo KEY = số điện thoại (đã chuẩn hoá hash phía BE — SPEC 0021).
    // Trigger ngay khi `phone` đủ 9 chữ số. Cảnh báo render trong card "Khách hàng".
    const lookup = useCustomerLookup(phone);
    const customerData: CustomerLookupResult | undefined = lookup.data;
    const oldAddresses = customerData?.addresses ?? [];

    // ---- effects ----
    useEffect(() => {
        if (!customerData?.customer) return;
        const cur = form.getFieldsValue(['buyer_name']);
        if (!cur.buyer_name && customerData.customer.name) form.setFieldsValue({ buyer_name: customerData.customer.name });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [customerData?.customer?.id, form]);

    useEffect(() => {
        if (meId != null) form.setFieldsValue({ assignee_user_id: meId });
    }, [meId, form]);

    // Khôi phục nháp khi mở lại tab — parent truyền `initialDraft` (đọc từ localStorage). Một
    // lần. KHÔNG hỏi Modal: các tab CHÍNH LÀ đơn chưa lưu (parent đã chọn để khôi phục).
    useEffect(() => {
        if (draftRestored) return;
        if (!isEdit && initialDraft) {
            if (initialDraft.items) setItems(initialDraft.items);
            if (initialDraft.phone) setPhone(initialDraft.phone);
            if (initialDraft.shipAddress) setShipAddress(initialDraft.shipAddress);
            if (initialDraft.tags) setTags(initialDraft.tags);
            if (initialDraft.attachments) setAttachments(initialDraft.attachments);
            if (initialDraft.form) form.setFieldsValue(initialDraft.form);
        }
        setDraftRestored(true);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // ---- Edit mode prefill — khi load xong order, đổ data vào form/state một lần duy nhất ----
    const [editPrefilled, setEditPrefilled] = useState(false);
    useEffect(() => {
        if (!isEdit || !editingOrder || editPrefilled) return;
        const o = editingOrder;
        const addr = (o.shipping_address ?? {}) as Record<string, string | undefined>;
        const meta = (o.meta ?? {}) as Record<string, unknown>;

        // Items mapping — OrderItem → OrderLineInput
        setItems((o.items ?? []).map((it, idx) => ({
            key: `line-${it.id ?? idx}`,
            sku_id: it.sku_id ?? undefined,
            name: it.name,
            image: it.image ?? undefined,
            sku_code: it.seller_sku ?? undefined,
            quantity: it.quantity,
            unit_price: it.unit_price,
            discount: it.discount ?? 0,
        })));

        // Phone — ưu tiên buyer_phone, fallback shipping_address.phone
        const ph = o.buyer_phone_masked && !o.buyer_phone_masked.includes('*') ? o.buyer_phone_masked : (addr.phone ?? '');
        setPhone(ph ?? '');

        // shipAddress — rebuild từ shipping_address
        setShipAddress({
            format: (addr.address_format as 'new' | 'old' | undefined) ?? (addr.district || addr.district_code ? 'old' : 'new'),
            province: addr.province ?? undefined,
            province_code: addr.province_code ?? undefined,
            district: addr.district ?? undefined,
            district_code: addr.district_code ?? undefined,
            ward: addr.ward ?? undefined,
            ward_code: addr.ward_code ?? undefined,
            address: addr.address ?? undefined,
            name: addr.name ?? undefined,
            phone: addr.phone ?? undefined,
        });

        setTags(o.tags ?? []);
        setAttachments(Array.isArray(meta.attachments) ? (meta.attachments as Array<{ url: string; name: string }>) : []);

        // Form fields — đổ value cho tất cả trường được render trong form
        form.setFieldsValue({
            sub_source: meta.sub_source ?? undefined,
            channel_mode: 'online',
            buyer_name: o.buyer_name ?? addr.name ?? undefined,
            email: meta.email ?? undefined,
            dob: meta.dob ? dayjs(meta.dob as string) : undefined,
            gender: meta.gender ?? undefined,
            expected_delivery_date: meta.expected_delivery_date ? dayjs(meta.expected_delivery_date as string) : undefined,
            recipient_name: addr.name ?? o.buyer_name ?? undefined,
            recipient_phone: addr.phone ?? ph ?? undefined,
            recipient_address: addr.address ?? undefined,
            assignee_user_id: (meta.assignee_user_id as number) ?? undefined,
            care_user_id: (meta.care_user_id as number) ?? undefined,
            marketer_user_id: (meta.marketer_user_id as number) ?? undefined,
            shipping_fee: o.shipping_fee ?? 0,
            order_discount: 0,
            prepaid_amount: (o as unknown as { prepaid_amount?: number }).prepaid_amount ?? 0,
            surcharge: (o as unknown as { surcharge?: number }).surcharge ?? 0,
            free_shipping: !!meta.free_shipping,
            collect_fee_on_return_only: !!meta.collect_fee_on_return_only,
            is_cod: !!o.is_cod,
            note_internal: o.note ?? undefined,
            note_print: (meta.print_note as string) ?? undefined,
        });

        setEditPrefilled(true);
    }, [isEdit, editingOrder, editPrefilled, form]);

    // ---- live totals ----
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
    // B7 fix — refactor: tách buildPayload + sendOrder thành function declaration top-level closure.
    // Trước đây `function sendOrder()` declared in block scope; hoisting trong strict mode block fragile.
    const buildPayload = (v: Record<string, unknown>) => {
        const lines = items.map((l) => ({
            sku_id: l.sku_id,
            name: l.sku_id ? undefined : l.name.trim(),
            image: l.sku_id ? undefined : (l.image || undefined),
            quantity: l.quantity, unit_price: l.unit_price, discount: l.discount,
        }));
        const isCod = !!v.is_cod;
        return {
            sub_source: (v.sub_source as string) || undefined,
            status: 'processing' as const,
            buyer: { name: (v.buyer_name as string) || undefined, phone: phone || undefined },
            recipient: {
                name: (v.recipient_name as string) || (v.buyer_name as string) || undefined,
                phone: (v.recipient_phone as string) || phone || undefined,
                address: (v.recipient_address as string) || undefined,
                address_format: shipAddress.format || 'new',
                province: shipAddress.province || undefined,
                province_code: shipAddress.province_code || undefined,
                district: shipAddress.district || undefined,
                district_code: shipAddress.district_code || undefined,
                ward: shipAddress.ward || undefined,
                ward_code: shipAddress.ward_code || undefined,
                expected_at: v.expected_delivery_date ? dayjs(v.expected_delivery_date as string).toISOString() : undefined,
            },
            items: lines,
            free_shipping: !!v.free_shipping,
            shipping_fee: v.free_shipping ? 0 : ((v.shipping_fee as number) ?? 0),
            order_discount: (v.order_discount as number) ?? 0,
            prepaid_amount: (v.prepaid_amount as number) ?? 0,
            surcharge: (v.surcharge as number) ?? 0,
            is_cod: isCod,
            warehouse_id: warehouseId ?? undefined,
            note: (v.note_internal as string) || undefined,
            tags,
            meta: {
                assignee_user_id: (v.assignee_user_id as number) || undefined,
                care_user_id: (v.care_user_id as number) || undefined,
                marketer_user_id: (v.marketer_user_id as number) || undefined,
                expected_delivery_date: v.expected_delivery_date ? dayjs(v.expected_delivery_date as string).format('YYYY-MM-DD') : undefined,
                gender: (v.gender as string) || undefined,
                dob: v.dob ? dayjs(v.dob as string).format('YYYY-MM-DD') : undefined,
                email: (v.email as string) || undefined,
                print_note: (v.note_print as string) || undefined,
                collect_fee_on_return_only: !!v.collect_fee_on_return_only,
                attachments: attachments.length > 0 ? attachments : undefined,
            },
        };
    };

    const sendOrder = (andPrint: boolean, payload: ReturnType<typeof buildPayload>) => {
        if (isEdit && editId) {
            update.mutate({ id: editId, payload }, {
                onSuccess: (o) => {
                    message.success(andPrint ? 'Đã lưu — chuyển sang in phiếu giao hàng.' : 'Đã lưu thay đổi');
                    navigate(`/orders/${o.id}${andPrint ? '?print=1' : ''}`);
                },
                onError: (e) => message.error(errorMessage(e)),
            });
            return;
        }
        create.mutate(payload, {
            onSuccess: (o) => {
                if (onSaved) {
                    // Chế độ tab: parent reset tab về form trắng + toast kèm link. KHÔNG navigate.
                    if (andPrint) window.open(`/orders/${o.id}?print=1`, '_blank');
                    onSaved(o.id);
                    return;
                }
                message.success(andPrint ? 'Đã tạo đơn — chuyển sang in phiếu giao hàng.' : 'Đã tạo đơn');
                navigate(`/orders/${o.id}${andPrint ? '?print=1' : ''}`);
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const submit = (andPrint?: boolean) => form.validateFields().then((v) => {
        if (items.length === 0) { message.error('Cần ít nhất một dòng hàng.'); return; }
        if (items.some((l) => l.uploading)) { message.error('Đang tải ảnh — vui lòng đợi.'); return; }
        if (items.some((l) => !l.sku_id && l.name.trim() === '')) { message.error('Dòng "sản phẩm nhanh" phải có tên.'); return; }
        if (!(v.recipient_name ?? '').trim()) { message.error('Cần điền tên người nhận.'); return; }
        const recipientPhone = (v.recipient_phone ?? phone ?? '').trim();
        if (!recipientPhone) { message.error('Cần điền số điện thoại người nhận.'); return; }
        if (!/^(0|\+84)\d{9,10}$/.test(recipientPhone)) { message.error('Số điện thoại người nhận không đúng định dạng Việt Nam (vd 0912xxxxxxx).'); return; }
        if (!(v.recipient_address ?? '').trim()) { message.error('Cần điền địa chỉ chi tiết.'); return; }
        const hasProvince = !!(shipAddress.province || shipAddress.province_code);
        const isOldFormat = shipAddress.format === 'old';
        const hasDistrict = !isOldFormat || !!(shipAddress.district || shipAddress.district_code);
        const hasWard = !!(shipAddress.ward || shipAddress.ward_code);
        if (!hasProvince || !hasDistrict || !hasWard) {
            message.error(isOldFormat ? 'Cần chọn đủ Tỉnh / Quận / Phường.' : 'Cần chọn đủ Tỉnh / Phường (chuẩn mới 2 cấp).');
            return;
        }

        const payload = buildPayload(v);

        // B6 fix — capture snapshot customer trước khi mở modal. Phòng trường hợp `customerData` đổi
        // giữa lúc user click confirm (vd lookup re-fetch trả null) ⇒ tránh `customerData!.customer!` crash.
        const blockedCustomer = customerData?.customer?.is_blocked ? customerData.customer : null;
        if (blockedCustomer) {
            Modal.confirm({
                title: 'Khách này đang BỊ CHẶN',
                width: 480,
                content: <span>Khách <b>{blockedCustomer.name ?? blockedCustomer.phone_masked ?? 'chưa rõ'}</b> đã bị chặn trong hệ thống. Vẫn tạo đơn?</span>,
                okText: 'Vẫn tạo đơn', okButtonProps: { danger: true }, cancelText: 'Huỷ',
                onOk: () => sendOrder(!!andPrint, payload),
            });
            return;
        }
        sendOrder(!!andPrint, payload);
    }).catch(() => message.error('Vui lòng kiểm tra lại thông tin.'));

    // F2 / F4 hotkeys — chỉ trigger từ outside input; CHỈ tab đang hiển thị mới bắt phím.
    useEffect(() => {
        if (!active) return;
        const onKey = (e: KeyboardEvent) => {
            const tag = (e.target as HTMLElement)?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA') return;
            if (e.key === 'F2') { e.preventDefault(); submit(false); }
            else if (e.key === 'F4') { e.preventDefault(); submit(true); }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active, items, phone, shipAddress, tags, attachments]);

    // ---- helpers ----
    const handlePhoneChange = (v: string) => {
        setPhone(v);
        // YÊU CẦU: auto-copy SĐT từ Khách hàng → Nhận hàng từ char 1.
        // Chỉ copy khi user CHƯA tự gõ vào Nhận hàng (synced=true). Một khi user tự gõ → ngừng sync.
        if (recipientPhoneSynced) {
            form.setFieldsValue({ recipient_phone: v });
        }
    };
    // B4 fix — Form.Item override onChange của child Input ⇒ handleRecipientPhoneChange không fire.
    // Watch recipient_phone qua Form.useWatch + useEffect để detect divergence từ customer phone.
    const watchedRecipientPhone = Form.useWatch('recipient_phone', form) as string | undefined;
    useEffect(() => {
        const rp = (watchedRecipientPhone ?? '').toString();
        if (rp === '') { setRecipientPhoneSynced(true); return; }
        if (rp !== phone) setRecipientPhoneSynced(false);
    }, [watchedRecipientPhone, phone]);

    // Báo nháp lên parent (debounce). "Đang tạo dở" = đã nhập SĐT khách HOẶC người nhận mà
    // chưa lưu ⇒ parent lưu localStorage + cảnh báo khi thoát. Sạch ⇒ báo null. `summary` (watch
    // toàn form) làm dep để mọi thay đổi field đều được lưu vào nháp.
    useEffect(() => {
        if (isEdit || !onDraftChange || !draftRestored) return;
        const t = setTimeout(() => {
            const dirty = phone.trim() !== '' || (watchedRecipientPhone ?? '').toString().trim() !== '';
            if (!dirty) { onDraftChange(null); return; }
            onDraftChange({ items, phone, shipAddress, tags, attachments, form: form.getFieldsValue() });
        }, 800);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [items, phone, shipAddress, tags, attachments, watchedRecipientPhone, summary, draftRestored, isEdit, onDraftChange]);

    const addTag = () => {
        // U12 (Sprint 2) — guard double-call: Enter triggers blur ⇒ both onPressEnter và onBlur gọi addTag.
        // Lần 2 đã có tag → setShowTagInput(false), không tạo duplicate (do `tags.includes(t)` check).
        const t = tagInput.trim();
        if (!t) { setTagInput(''); setShowTagInput(false); return; }
        if (!tags.includes(t)) setTags([...tags, t]);
        setTagInput(''); setShowTagInput(false);
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

    const subSource = (summary?.sub_source as string) || '';

    const headerTitle = isEdit
        ? `Sửa đơn ${editingOrder?.order_number ?? (editId ? `#${editId}` : '')}`
        : 'Tạo đơn thủ công';
    const headerSubtitle = isEdit
        ? 'Có thể sửa mọi mục — sản phẩm, địa chỉ, thanh toán. Lưu sẽ recompute tồn kho và tổng tiền.'
        : 'Đơn nguồn ngoài sàn (website / Facebook / Zalo / hotline) — trừ chung kho, vào cùng luồng xử lý';
    const pushedCarrierLabel = (editingOrder?.pushed_carrier ?? '').replace(/^manual_/, '').toUpperCase() || 'ĐVVC';

    return (
        <div className={`create-order-page${compact ? ' create-order-page--compact' : ''}`} style={{ paddingBottom: compact ? 0 : 88 }}>
            {!embedded && (
                <PageHeader
                    title={<Space size="middle"><Link to={isEdit && editId ? `/orders/${editId}` : '/orders'}><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>{headerTitle}</span></Space>}
                    subtitle={headerSubtitle}
                />
            )}

            {isEdit && isPushed && (
                <Alert
                    type="warning"
                    showIcon
                    icon={<LockOutlined />}
                    style={{ marginBottom: 16 }}
                    message={<><b>Đơn đã đẩy lên {pushedCarrierLabel}</b> — mã vận đơn: <code>{editingOrder?.shipment?.tracking_no ?? '—'}</code></>}
                    description="Mọi thay đổi (sản phẩm, địa chỉ, tiền…) chỉ áp dụng trên hệ thống nội bộ — KHÔNG can thiệp vào vận đơn đã đẩy lên ĐVVC. Nếu cần sửa thực tế giao hàng — hãy huỷ vận đơn này trên hệ ĐVVC trước, rồi đẩy lại đơn mới."
                />
            )}

            {isEdit && orderQuery.isLoading && (
                <Alert type="info" showIcon style={{ marginBottom: 16 }} message="Đang tải dữ liệu đơn để chỉnh sửa…" />
            )}

            <Form form={form} layout="vertical" initialValues={{
                channel_mode: 'online', sub_source: undefined, is_cod: false, free_shipping: false, collect_fee_on_return_only: false,
                shipping_fee: 0, order_discount: 0, prepaid_amount: 0, surcharge: 0,
            }}>
                <Row gutter={16}>
                    {/* ========================= LEFT COLUMN ========================= */}
                    <Col xs={24} lg={16}>
                        {/* ---------- Sản phẩm ---------- */}
                        <Card
                            size="small"
                            className="ord-card"
                            style={{ marginBottom: 16 }}
                            title={<span className="ord-card-title">Sản phẩm</span>}
                            extra={(
                                <Space size={8} wrap>
                                    <Form.Item name="channel_mode" noStyle>
                                        <Segmented size="small" options={[{ value: 'online', label: 'Online' }, { value: 'offline', label: 'Offline' }]} />
                                    </Form.Item>
                                    <Popover trigger="click" placement="bottomRight" content={(
                                        <Radio.Group value={subSource} onChange={(e) => form.setFieldsValue({ sub_source: e.target.value })} style={{ display: 'flex', flexDirection: 'column', minWidth: 180 }}>
                                            {SUB_SOURCES.map((s) => (
                                                <Radio key={s.value} value={s.value} style={{ padding: '6px 10px' }}>
                                                    <Space>{SUB_SOURCE_ICONS[s.value]}<span>{s.label}</span></Space>
                                                </Radio>
                                            ))}
                                        </Radio.Group>
                                    )}>
                                        <Button size="small" className="ord-pill-btn" icon={subSource ? SUB_SOURCE_ICONS[subSource] : null}>
                                            {SUB_SOURCES.find((s) => s.value === subSource)?.label ?? 'Chọn nguồn đơn'}
                                            <UpOutlined rotate={180} style={{ fontSize: 10, marginInlineStart: 4 }} />
                                        </Button>
                                    </Popover>
                                    {/* B (Sprint 2) — bỏ dropdown gian hàng: đơn manual không gắn channel_account_id; trước đây
                                        field UI gây hiểu lầm (BE silent drop). User nhập kênh bán qua "Chọn nguồn đơn" (sub_source). */}
                                </Space>
                            )}
                        >
                            {/* Inline search bar — khớp taodon.png */}
                            <div className="ord-product-search">
                                <Segmented
                                    size="small" value={productMode} onChange={(v) => setProductMode(v as 'sku' | 'combo')}
                                    options={[{ value: 'sku', label: 'Sản phẩm' }, { value: 'combo', label: 'Combo' }]}
                                />
                                <PickerTrigger
                                    open={pickerOpen} setOpen={setPickerOpen}
                                    taken={new Set(items.map((r) => r.sku_id).filter((x): x is number => x != null))}
                                    onPickSku={(s: Sku) => {
                                        const existing = items.find((r) => r.sku_id === s.id);
                                        if (existing) setItems(items.map((r) => r.key === existing.key ? { ...r, quantity: r.quantity + 1 } : r));
                                        else setItems([...items, { key: `line-${Date.now()}`, sku_id: s.id, name: s.name, image: s.image_url ?? undefined, sku_code: s.sku_code, available: s.available_total ?? 0, quantity: 1, unit_price: s.ref_sale_price ?? 0, discount: 0 }]);
                                        setPickerOpen(false);
                                    }}
                                    onQuickCreate={() => { setItems([...items, { key: `line-${Date.now()}`, name: '', quantity: 1, unit_price: 0, discount: 0 }]); setPickerOpen(false); }}
                                >
                                    <Input
                                        size="middle"
                                        prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />}
                                        placeholder="Nhập mã, tên sản phẩm hoặc Barcode"
                                        value={productSearch}
                                        onChange={(e) => { setProductSearch(e.target.value); setPickerOpen(true); }}
                                        onFocus={() => setPickerOpen(true)}
                                        className="ord-search-input"
                                        suffix={(
                                            <Space size={8}>
                                                <Checkbox checked={inStockOnly} onChange={(e) => setInStockOnly(e.target.checked)} className="ord-search-check">Còn hàng</Checkbox>
                                                <Tooltip title="Quét barcode"><Button type="text" size="small" icon={<BarcodeOutlined />} className="ord-scan-btn">(F9)</Button></Tooltip>
                                            </Space>
                                        )}
                                    />
                                </PickerTrigger>
                            </div>

                            {/* Items table or empty state */}
                            <div style={{ marginTop: 12, minHeight: 200 }}>
                                {items.length === 0 ? (
                                    <div className="ord-empty-cart">
                                        <div className="ord-empty-icon">
                                            <PrinterOutlined />
                                            <span className="ord-empty-bubble">…</span>
                                        </div>
                                        <Typography.Text type="secondary">Giỏ hàng trống</Typography.Text>
                                    </div>
                                ) : (
                                    <OrderItemsEditor value={items} onChange={setItems} tableOnly />
                                )}
                            </div>
                        </Card>

                        <Row gutter={16}>
                            {/* ---------- Thanh toán ---------- */}
                            <Col xs={24} md={12}>
                                <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                                    title={<span className="ord-card-title">Thanh toán</span>}
                                    extra={<Button type="text" size="small" icon={<MoreOutlined />} />}>
                                    <Space size={20} style={{ marginBottom: 10 }}>
                                        <Form.Item name="free_shipping" valuePropName="checked" noStyle><Checkbox>Miễn phí giao hàng</Checkbox></Form.Item>
                                        <Form.Item name="collect_fee_on_return_only" valuePropName="checked" noStyle><Checkbox>Chỉ thu phí nếu hoàn</Checkbox></Form.Item>
                                    </Space>
                                    <PayRow label="Phí vận chuyển" name="shipping_fee" disabled={!!summary?.free_shipping} />
                                    <PayRow label="Giảm giá đơn hàng" name="order_discount" />
                                    <PayRow label="Tiền chuyển khoản" name="prepaid_amount" />
                                    <PayRow label="Phụ thu" name="surcharge" />

                                    <div className="ord-summary-list">
                                        <SummaryRow label="Tổng số tiền" value={vnd(totals.itemTotal + totals.shippingFee + totals.surcharge)} />
                                        <SummaryRow label="Giảm giá" value={vnd(totals.totalDiscount)} tone={totals.totalDiscount > 0 ? 'g' : 'mute'} />
                                        <SummaryRow label="Sau giảm giá" value={vnd(totals.afterDiscount)} />
                                        <SummaryRow label="Tiền cần thu" value={vnd(totals.needCollect)} />
                                        <SummaryRow label="Đã thanh toán" value={vnd(totals.prepaid)} tone={totals.prepaid > 0 ? 'g' : 'mute'} />
                                        <SummaryRow label="Còn thiếu" value={vnd(Math.max(0, totals.needCollect))} tone="r" strong />
                                    </div>
                                </Card>
                            </Col>

                            {/* ---------- Ghi chú ---------- */}
                            <Col xs={24} md={12}>
                                <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                                    title={<span className="ord-card-title">Ghi chú</span>}>
                                    <NoteTabs />
                                    <Upload beforeUpload={beforeUpload} multiple showUploadList={false}>
                                        <div className="ord-upload-box">
                                            <PaperClipOutlined style={{ fontSize: 18, color: '#bfbfbf', marginBottom: 4 }} />
                                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>Tải lên</Typography.Text>
                                        </div>
                                    </Upload>
                                    {attachments.length > 0 && (
                                        <div style={{ marginTop: 8 }}>
                                            {attachments.map((a, i) => (
                                                <Tag key={i} closable color="blue" onClose={() => setAttachments(attachments.filter((_, j) => j !== i))} style={{ marginBottom: 4 }}>
                                                    <a href={a.url} target="_blank" rel="noreferrer">{a.name}</a>
                                                </Tag>
                                            ))}
                                        </div>
                                    )}
                                </Card>
                            </Col>
                        </Row>
                    </Col>

                    {/* ========================= RIGHT COLUMN ========================= */}
                    <Col xs={24} lg={8}>
                        {/* ---------- Thông tin ---------- */}
                        <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                            title={<Space size={4}><span className="ord-card-title">Thông tin</span><UpOutlined rotate={thongTinCollapsed ? 180 : 0} style={{ fontSize: 10, color: '#8c8c8c', cursor: 'pointer' }} onClick={() => setThongTinCollapsed((c) => !c)} /></Space>}
                        >
                            {!thongTinCollapsed && (
                                <>
                                    <KvRow label="Tạo lúc">
                                        <Typography.Text className="ord-readonly">{dayjs().format('HH:mm DD/MM/YYYY')}</Typography.Text>
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
                                        {tags.map((t) => <Tag key={t} closable color="blue" onClose={() => removeTag(t)} style={{ marginBottom: 4 }}>{t}</Tag>)}
                                        {showTagInput ? (
                                            <Input size="small" autoFocus style={{ width: 130 }} value={tagInput}
                                                onChange={(e) => setTagInput(e.target.value)} onPressEnter={addTag} onBlur={addTag} maxLength={50} />
                                        ) : (
                                            <Tag onClick={() => setShowTagInput(true)} className="ord-add-tag">+ Thêm thẻ</Tag>
                                        )}
                                    </div>
                                </>
                            )}
                        </Card>

                        {/* ---------- Khách hàng ---------- */}
                        <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                            title={<span className="ord-card-title">Khách hàng</span>}
                            extra={(
                                <Space size={4}>
                                    <Form.Item name="gender" noStyle>
                                        <GenderDropdown />
                                    </Form.Item>
                                    <Button type="text" size="small" icon={<MoreOutlined />} />
                                </Space>
                            )}
                        >
                            <Row gutter={8}>
                                <Col span={12}><Form.Item name="buyer_name" style={{ marginBottom: 8 }}>
                                    <Input placeholder="Tên khách hàng" maxLength={255} />
                                </Form.Item></Col>
                                <Col span={12}><Form.Item style={{ marginBottom: 8 }}>
                                    <Input
                                        value={phone}
                                        onChange={(e) => handlePhoneChange(e.target.value)}
                                        placeholder="SĐT" maxLength={32}
                                        suffix={lookup.isFetching ? <Typography.Text type="secondary" style={{ fontSize: 11 }}>…</Typography.Text> : (customerData?.customer ? <Tooltip title="Khách đã có trong sổ"><CheckCircleFilled style={{ color: '#52c41a' }} /></Tooltip> : null)}
                                    />
                                </Form.Item></Col>
                                <Col span={12}><Form.Item name="email" rules={[{ type: 'email', message: 'Email không hợp lệ' }]} style={{ marginBottom: 8 }}>
                                    <Input placeholder="Địa chỉ email" maxLength={255} />
                                </Form.Item></Col>
                                <Col span={12}><Form.Item name="dob" style={{ marginBottom: 8 }}>
                                    <DatePicker style={{ width: '100%' }} placeholder="Ngày sinh" format="DD/MM/YYYY" />
                                </Form.Item></Col>
                            </Row>
                            <CustomerWarning data={customerData} />
                        </Card>

                        {/* ---------- Nhận hàng ---------- */}
                        <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                            title={<span className="ord-card-title">Nhận hàng <span style={{ color: '#cf1322', marginInlineStart: 4 }}>*</span></span>}
                        >
                            {warehouses.length > 1 && (
                                <Form.Item label="Kho gửi" style={{ marginBottom: 8 }}>
                                    <Radio.Group value={warehouseId} onChange={(e) => setWarehouseId(e.target.value as number)}>
                                        {warehouses.map((w) => (
                                            <Radio key={w.id} value={w.id}>{w.name}{w.is_default ? ' (mặc định)' : ''}</Radio>
                                        ))}
                                    </Radio.Group>
                                </Form.Item>
                            )}
                            <Form.Item name="expected_delivery_date" style={{ marginBottom: 8 }}>
                                <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" placeholder="Dự kiến nhận hàng" suffixIcon={<CalendarOutlined />} />
                            </Form.Item>
                            <Row gutter={8}>
                                <Col span={12}><Form.Item name="recipient_name" style={{ marginBottom: 8 }} rules={[{ required: true, message: 'Tên người nhận' }]}>
                                    <Input placeholder="Tên người nhận *" maxLength={255} />
                                </Form.Item></Col>
                                <Col span={12}><Form.Item name="recipient_phone" style={{ marginBottom: 8 }} rules={[
                                    { required: true, message: 'SĐT người nhận' },
                                    { pattern: /^(0|\+84)\d{9,10}$/, message: 'SĐT không đúng định dạng VN' },
                                ]}>
                                    {/* B4 fix — bỏ custom onChange (Form.Item sẽ override). Sync detect qua Form.useWatch + useEffect ở trên. */}
                                    <Input placeholder="Số điện thoại *" maxLength={32} />
                                </Form.Item></Col>
                            </Row>
                            {/* SPEC 0021 — Autocomplete: user gõ "123 NTrai, P. Bến Nghé, Q.1, TP HCM" ⇒ parse tail
                                khớp Tỉnh→Quận→Phường, gợi ý dropdown. Click ⇒ fill `recipient_address` + `shipAddress`.
                                Hỗ trợ không dấu ("ha noi" khớp "Hà Nội").
                                B3 fix — KHÔNG truyền `value`/`onChange` ở đây: Form.Item tự inject. Trước đây custom
                                value qua `form.getFieldValue` ⇒ snapshot, không reactive ⇒ gõ không update. */}
                            <Form.Item name="recipient_address" style={{ marginBottom: 8 }} rules={[{ required: true, message: 'Địa chỉ chi tiết' }]}>
                                <AddressAutocomplete
                                    format={shipAddress.format ?? 'new'}
                                    placeholder="Địa chỉ chi tiết — gõ cả tỉnh/quận/phường để được gợi ý (vd: 123 NTrai, Q.1, TP HCM)"
                                    onPick={(s) => {
                                        setShipAddress((cur) => ({ ...cur, ...s.address }));
                                    }}
                                />
                            </Form.Item>
                            {/* U3 (Sprint 2) — single AddressPicker (Tỉnh/Quận/Phường) - gộp 2 popover trùng lặp.
                                Vẫn hỗ trợ "địa chỉ mới" (cascade GHN) + "địa chỉ cũ" (từ customer.addresses_meta). */}
                            <Popover trigger="click" open={addrPickerOpen} onOpenChange={setAddrPickerOpen} placement="bottomLeft" content={(
                                <AddressPicker value={shipAddress} oldAddresses={oldAddresses} onPick={(p) => {
                                    setShipAddress(p);
                                    if (p.name) form.setFieldsValue({ recipient_name: p.name });
                                    if (p.phone && !phone) { setPhone(p.phone); form.setFieldsValue({ recipient_phone: p.phone }); }
                                    if (p.address) form.setFieldsValue({ recipient_address: p.address });
                                    setAddrPickerOpen(false);
                                }} />
                            )}>
                                <Input
                                    readOnly
                                    placeholder={shipAddress.format === 'old' ? 'Tỉnh / Quận / Phường *' : 'Tỉnh / Phường (chuẩn mới) *'}
                                    value={[shipAddress.ward, shipAddress.district, shipAddress.province].filter(Boolean).join(', ')}
                                    suffix={(() => {
                                        const hasPick = !!(shipAddress.province || shipAddress.district || shipAddress.ward
                                            || shipAddress.province_code || shipAddress.district_code || shipAddress.ward_code);
                                        return (
                                            <Space size={6}>
                                                {hasPick && (
                                                    <CloseCircleFilled
                                                        aria-label="Xoá chọn tỉnh/quận/phường"
                                                        style={{ color: 'rgba(0,0,0,.25)', cursor: 'pointer', fontSize: 12 }}
                                                        onMouseEnter={(e) => { e.currentTarget.style.color = 'rgba(0,0,0,.45)'; }}
                                                        onMouseLeave={(e) => { e.currentTarget.style.color = 'rgba(0,0,0,.25)'; }}
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setShipAddress((cur) => ({
                                                                ...cur,
                                                                province: undefined, province_code: undefined,
                                                                district: undefined, district_code: undefined,
                                                                ward: undefined, ward_code: undefined,
                                                            }));
                                                        }}
                                                    />
                                                )}
                                                <EnvironmentOutlined style={{ color: '#bfbfbf' }} />
                                            </Space>
                                        );
                                    })()}
                                    style={{ cursor: 'pointer' }}
                                    onClick={() => setAddrPickerOpen(true)}
                                    status={(() => {
                                        if (!form.isFieldTouched('recipient_address')) return undefined;
                                        const old = shipAddress.format === 'old';
                                        const ok = !!shipAddress.province && (!old || !!shipAddress.district || !!shipAddress.district_code) && (!!shipAddress.ward || !!shipAddress.ward_code);
                                        return ok ? undefined : 'warning';
                                    })()}
                                />
                            </Popover>
                        </Card>

                        {/* B (Sprint 2) — bỏ Card "Vận chuyển" trong form tạo đơn:
                            - Đơn manual chọn ĐVVC ở bước "Chuẩn bị hàng" qua `CarrierAccountPicker` (đúng luồng GHN createOrder).
                            - Kích thước / phí thực sẽ do GHN trả về sau khi tạo vận đơn.
                            - User vẫn có thể chọn "Đơn vị VC ưa thích" gián tiếp qua picker khi prepare. */}
                    </Col>
                </Row>
            </Form>

            {/* ---------- Sticky bottom bar ----------
                B5 fix — Form.Item render <div> (block element); nested trong <span> là invalid HTML, React warn.
                Dùng <div style="inline-flex"> thay vì <span> để cho phép Form.Item làm child hợp lệ. */}
            <div className="ord-bottom-bar">
                <Space size={28} align="center">
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                        <span>Tiền cần thu:</span>
                        <b className="ord-bottom-money">{vnd(totals.needCollect)} đ</b>
                    </div>
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                        <span>COD:</span>
                        <Form.Item name="is_cod" valuePropName="checked" noStyle><Checkbox /></Form.Item>
                        <b className={summary?.is_cod ? 'ord-bottom-money' : 'ord-bottom-money-muted'}>{vnd(summary?.is_cod ? totals.needCollect : 0)} đ</b>
                    </div>
                </Space>
                <Space>
                    <Button icon={<PrinterOutlined />} onClick={() => submit(true)} loading={submitting}>{isEdit ? 'Lưu & in' : 'In'} <kbd className="ord-kbd">F4</kbd></Button>
                    <Button type="primary" icon={<SaveOutlined />} onClick={() => submit(false)} loading={submitting}>{isEdit ? 'Lưu thay đổi' : 'Lưu'} <kbd className="ord-kbd-on-primary">F2</kbd></Button>
                </Space>
            </div>

            {/* ---------- Scoped styles ---------- */}
            <style>{`
                .create-order-page .ord-card { border-radius: 8px; }
                .create-order-page .ord-card .ant-card-head { min-height: 40px; padding: 0 14px; border-bottom: 1px solid #f0f0f0; }
                .create-order-page .ord-card-title { font-size: 14px; font-weight: 600; color: #262626; }
                .create-order-page .ord-card .ant-card-body { padding: 12px 14px; }
                .create-order-page .ord-pill-btn { border-radius: 16px; height: 28px; padding: 0 12px; background: #fff; border-color: #d9d9d9; }
                .create-order-page .ord-pill-btn:hover { border-color: #1677ff; color: #1677ff; }
                .create-order-page .ord-product-search { display: flex; align-items: center; gap: 8px; }
                .create-order-page .ord-search-input.ant-input-affix-wrapper { border-radius: 6px; }
                .create-order-page .ord-search-check { font-size: 12px; color: #595959; }
                .create-order-page .ord-scan-btn { padding: 0 6px; height: 22px; font-size: 11px; color: #8c8c8c; background: #f5f5f5; border-radius: 4px; }
                .create-order-page .ord-empty-cart { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 28px 0; }
                .create-order-page .ord-empty-icon { position: relative; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; font-size: 44px; color: #d9d9d9; }
                .create-order-page .ord-empty-icon .ord-empty-bubble { position: absolute; top: -6px; right: -8px; background: #f0f0f0; color: #8c8c8c; border-radius: 999px; padding: 2px 8px; font-size: 12px; line-height: 1; }
                .create-order-page .ord-summary-list { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #eaeaea; display: flex; flex-direction: column; gap: 4px; }
                .create-order-page .ord-pay-row { display: flex; align-items: center; gap: 8px; padding: 4px 0; }
                .create-order-page .ord-pay-row label { flex: 1; color: #595959; font-size: 13px; }
                .create-order-page .ord-pay-input.ant-input-number { width: 180px; }
                .create-order-page .ord-pay-input .ant-input-number-input { text-align: right; }
                .create-order-page .ord-readonly { color: #262626; font-size: 13px; }
                .create-order-page .ord-add-tag { cursor: pointer; background: #fafafa; border-style: dashed; color: #8c8c8c; }
                .create-order-page .ord-add-tag:hover { color: #1677ff; border-color: #1677ff; }
                .create-order-page .ord-upload-box { width: 100%; height: 64px; border: 1px dashed #d9d9d9; border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; background: #fafafa; margin-top: 8px; }
                .create-order-page .ord-upload-box:hover { border-color: #1677ff; background: #f0f7ff; }
                .create-order-page .ord-dim-row { display: flex; align-items: center; gap: 6px; }
                .create-order-page .ord-dim-row .ant-input-number { width: 70px; }
                .create-order-page .ord-dim-row .ant-input-number .ant-input-number-input { text-align: center; }
                .create-order-page .ord-dim-x { color: #bfbfbf; }
                .create-order-page .ord-dim-unit { color: #8c8c8c; font-size: 12px; }
                .create-order-page .ord-bottom-bar { position: fixed; left: 0; right: 0; bottom: 0; background: #fff; border-top: 1px solid #f0f0f0; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; z-index: 9; box-shadow: 0 -2px 8px rgba(0,0,0,0.04); }
                /* Compact (Drawer/khung chat): bám đáy vùng cuộn thay vì cố định toàn màn hình. */
                .create-order-page--compact .ord-bottom-bar { position: sticky; left: auto; right: auto; bottom: 0; flex-wrap: wrap; gap: 8px; }
                .create-order-page .ord-bottom-money { color: #cf1322; font-weight: 600; }
                .create-order-page .ord-bottom-money-muted { color: #8c8c8c; }
                .create-order-page .ord-kbd { background: #f0f0f0; color: #8c8c8c; padding: 1px 6px; border-radius: 3px; font-size: 11px; margin-inline-start: 4px; font-family: inherit; }
                .create-order-page .ord-kbd-on-primary { background: rgba(255,255,255,0.25); color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 11px; margin-inline-start: 4px; font-family: inherit; }
            `}</style>
        </div>
    );
}

// ============================================================================
//  Tab workspace — tạo nhiều đơn liên tục (browser-like tabs). Chỉ cho route tạo
//  mới (/orders/new); sửa đơn (/orders/:id/edit) dùng form đơn lẻ.
// ============================================================================

const TABS_DRAFT_KEY = 'cmb.createOrder.tabs.v1';

function newTabKey(): string {
    return `tab-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`;
}

/** Đọc các tab có nháp đơn CHƯA LƯU từ localStorage (chỉ những đơn dở mới được giữ). */
function loadDraftTabs(): Array<{ key: string; draft: OrderDraft }> {
    try {
        const raw = localStorage.getItem(TABS_DRAFT_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed)
            ? parsed.filter((t): t is { key: string; draft: OrderDraft } => !!t && typeof t.key === 'string' && !!t.draft)
            : [];
    } catch { return []; }
}

function CreateOrderTabs() {
    const { notification } = AntApp.useApp();
    const navigate = useNavigate();

    // Khôi phục 1 lần khi mở trang: chỉ các tab có đơn chưa lưu; không có ⇒ 1 tab trắng.
    const restored = useRef(loadDraftTabs());
    const initialDrafts = useRef<Record<string, OrderDraft>>(
        Object.fromEntries(restored.current.map((t) => [t.key, t.draft])),
    );
    const [tabs, setTabs] = useState<string[]>(() =>
        restored.current.length > 0 ? restored.current.map((t) => t.key) : [newTabKey()],
    );
    const [drafts, setDrafts] = useState<Record<string, OrderDraft | null>>(() => ({ ...initialDrafts.current }));
    const [activeKey, setActiveKey] = useState<string>(() => tabs[0]);

    const dirtyCount = Object.values(drafts).filter((d) => d != null).length;
    const hasDirty = dirtyCount > 0;

    const persist = useCallback((map: Record<string, OrderDraft | null>) => {
        try {
            const arr = Object.entries(map).filter(([, d]) => d != null).map(([key, draft]) => ({ key, draft }));
            if (arr.length > 0) localStorage.setItem(TABS_DRAFT_KEY, JSON.stringify(arr));
            else localStorage.removeItem(TABS_DRAFT_KEY);
        } catch { /* quota — bỏ qua */ }
    }, []);

    const handleDraftChange = useCallback((key: string, draft: OrderDraft | null) => {
        setDrafts((prev) => {
            if (prev[key] === draft) return prev;
            const next = { ...prev, [key]: draft };
            persist(next);
            return next;
        });
    }, [persist]);

    const addTab = useCallback(() => {
        const key = newTabKey();
        setTabs((t) => [...t, key]);
        setActiveKey(key);
    }, []);

    const removeTab = useCallback((key: string) => {
        const doRemove = () => {
            const fallback = newTabKey(); // sinh 1 lần ⇒ updater thuần, an toàn StrictMode.
            setTabs((t) => {
                const next = t.filter((k) => k !== key);
                return next.length > 0 ? next : [fallback];
            });
            setActiveKey((cur) => {
                if (cur !== key) return cur;
                const next = tabs.filter((k) => k !== key);
                return next.length > 0 ? next[next.length - 1] : fallback;
            });
            setDrafts((prev) => { const n = { ...prev }; delete n[key]; persist(n); return n; });
        };
        if (drafts[key] != null) {
            Modal.confirm({
                title: 'Đơn đang tạo dở',
                content: 'Đơn này đã nhập SĐT nhưng chưa lưu. Đóng tab và bỏ nháp?',
                okText: 'Đóng & bỏ', okButtonProps: { danger: true }, cancelText: 'Giữ lại',
                onOk: doRemove,
            });
        } else {
            doRemove();
        }
    }, [tabs, drafts, persist]);

    const handleSaved = useCallback((key: string, orderId: number) => {
        // Lưu xong: reset tab về form trắng (key mới) ngay tại chỗ ⇒ tiếp tục tạo. Xoá nháp.
        const fresh = newTabKey();
        setTabs((t) => t.map((k) => (k === key ? fresh : k)));
        setActiveKey((cur) => (cur === key ? fresh : cur));
        setDrafts((prev) => { const n = { ...prev }; delete n[key]; n[fresh] = null; persist(n); return n; });
        notification.success({
            message: 'Đã tạo đơn',
            description: <a href={`/orders/${orderId}`} target="_blank" rel="noreferrer">Xem đơn vừa tạo →</a>,
            placement: 'topRight',
        });
    }, [persist, notification]);

    // Cảnh báo khi refresh / đóng tab / đóng trình duyệt lúc còn đơn dở.
    useEffect(() => {
        if (!hasDirty) return;
        const onBeforeUnload = (e: BeforeUnloadEvent) => { e.preventDefault(); e.returnValue = ''; };
        window.addEventListener('beforeunload', onBeforeUnload);
        return () => window.removeEventListener('beforeunload', onBeforeUnload);
    }, [hasDirty]);

    // Chặn nút Back trình duyệt khi còn đơn dở (component router không có useBlocker).
    // Push 1 state khi chuyển sạch→dở (dep `hasDirty`) để không cộng dồn lịch sử.
    useEffect(() => {
        if (!hasDirty) return;
        window.history.pushState(null, '', window.location.href);
        const onPop = () => {
            Modal.confirm({
                title: 'Còn đơn đang tạo dở',
                content: 'Có đơn đã nhập SĐT chưa lưu. Rời trang? (nháp được giữ để mở lại sau)',
                okText: 'Rời trang', okButtonProps: { danger: true }, cancelText: 'Ở lại',
                onOk: () => { window.removeEventListener('popstate', onPop); window.history.back(); },
                onCancel: () => { window.history.pushState(null, '', window.location.href); },
            });
        };
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, [hasDirty]);

    const leave = () => {
        if (dirtyCount > 0) {
            Modal.confirm({
                title: 'Còn đơn đang tạo dở',
                content: 'Có đơn đã nhập SĐT chưa lưu. Rời trang? (nháp được giữ để mở lại sau)',
                okText: 'Rời trang', cancelText: 'Ở lại', onOk: () => navigate('/orders'),
            });
        } else {
            navigate('/orders');
        }
    };

    const labelFor = (key: string, idx: number): string => {
        const d = drafts[key];
        const name = (d?.form?.buyer_name as string) || (d?.form?.recipient_name as string) || d?.phone || '';
        return name ? name.slice(0, 18) : `Đơn ${idx + 1}`;
    };

    const items = tabs.map((key, idx) => ({
        key,
        label: (
            <Space size={4}>
                {drafts[key] != null && <Badge status="warning" />}
                {labelFor(key, idx)}
            </Space>
        ),
        closable: tabs.length > 1,
        children: (
            <CreateOrderForm
                embedded
                active={key === activeKey}
                onSaved={(id) => handleSaved(key, id)}
                onDraftChange={(d) => handleDraftChange(key, d)}
                initialDraft={initialDrafts.current[key] ?? null}
            />
        ),
    }));

    return (
        <div>
            <PageHeader
                title={<Space size="middle"><Button type="text" icon={<ArrowLeftOutlined />} onClick={leave} /><span>Tạo đơn thủ công</span></Space>}
                subtitle="Mỗi tab là 1 đơn. Lưu xong tab tự reset để tạo tiếp; đơn chưa lưu (đã nhập SĐT) được giữ khi mở lại trang."
            />
            <Tabs
                type="editable-card"
                animated={false}
                activeKey={activeKey}
                onChange={setActiveKey}
                onEdit={(targetKey, action) => {
                    if (action === 'add') addTab();
                    else if (action === 'remove' && typeof targetKey === 'string') removeTab(targetKey);
                }}
                items={items}
                style={{ paddingInline: 8 }}
            />
        </div>
    );
}

/** Route component: sửa đơn → form đơn lẻ; tạo mới → workspace nhiều tab. */
export function CreateOrderPage() {
    const { id } = useParams();
    const isEdit = id != null && !Number.isNaN(Number(id));
    return isEdit ? <CreateOrderForm active /> : <CreateOrderTabs />;
}

// ============================================================================
//  Helpers
// ============================================================================

function PayRow({ label, name, disabled }: { label: string; name: string; disabled?: boolean }) {
    return (
        <div className="ord-pay-row">
            <label>{label}</label>
            <Form.Item name={name} noStyle>
                <InputNumber
                    min={0} disabled={disabled} className="ord-pay-input" controls={false}
                    formatter={(v) => `${v ?? 0}`.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}
                    parser={(v) => Number((v ?? '').toString().replace(/\D/g, '')) as 0}
                    suffix={<Typography.Text type="secondary" style={{ fontSize: 12 }}>đ</Typography.Text>}
                />
            </Form.Item>
        </div>
    );
}

function SummaryRow({ label, value, tone, strong }: { label: string; value: string; tone?: 'g' | 'r' | 'mute'; strong?: boolean }) {
    const color = tone === 'g' ? '#389e0d' : tone === 'r' ? '#cf1322' : tone === 'mute' ? '#bfbfbf' : '#262626';
    return (
        <Row justify="space-between" align="middle">
            <Typography.Text type="secondary" style={{ fontSize: 13 }}>{label}</Typography.Text>
            <Typography.Text style={{ color, fontSize: 13, fontWeight: strong ? 600 : 400 }}>{value} <span style={{ textDecoration: 'underline', textDecorationStyle: 'dotted' }}>đ</span></Typography.Text>
        </Row>
    );
}

function KvRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <Row align="middle" style={{ marginBottom: 8 }}>
            <Col span={10}><Typography.Text type="secondary" style={{ fontSize: 13 }}>{label}</Typography.Text></Col>
            <Col span={14} style={{ display: 'flex', justifyContent: 'flex-end' }}>{children}</Col>
        </Row>
    );
}

function GenderDropdown({ value, onChange }: { value?: string; onChange?: (v: string) => void }) {
    const opts = [
        { value: 'male', label: 'Nam' },
        { value: 'female', label: 'Nữ' },
        { value: 'other', label: 'Khác' },
    ];
    return (
        <Popover trigger="click" placement="bottomRight" content={(
            <Radio.Group value={value} onChange={(e) => onChange?.(e.target.value)} style={{ display: 'flex', flexDirection: 'column' }}>
                {opts.map((o) => <Radio key={o.value} value={o.value} style={{ padding: '4px 10px' }}>{o.label}</Radio>)}
            </Radio.Group>
        )}>
            <Button type="text" size="small">
                {opts.find((o) => o.value === value)?.label ?? 'Giới tính'}
                <UpOutlined rotate={180} style={{ fontSize: 10, marginInlineStart: 4 }} />
            </Button>
        </Popover>
    );
}

function UserPicker({ value, onChange, members, placeholder }: { value?: number | null; onChange?: (v: number | null) => void; members: Array<{ id: number; name: string; email: string }>; placeholder: string }) {
    const current = value ? members.find((m) => m.id === value) : null;
    return (
        <Popover trigger="click" placement="bottomRight" content={(
            <div style={{ width: 260, maxHeight: 320, overflowY: 'auto' }}>
                <Radio.Group style={{ width: '100%' }} value={value ?? undefined} onChange={(e) => onChange?.(e.target.value)}>
                    {members.length === 0 ? <Typography.Text type="secondary">Chưa có thành viên.</Typography.Text> : null}
                    {members.map((m) => (
                        <Radio key={m.id} value={m.id} style={{ display: 'flex', padding: '6px 8px' }}>
                            <Space size={8}>
                                <Avatar size={22} style={{ background: '#9254de', fontSize: 12 }}>{(m.name || m.email).slice(0, 2).toUpperCase()}</Avatar>
                                <span>{m.name || m.email}</span>
                            </Space>
                        </Radio>
                    ))}
                </Radio.Group>
            </div>
        )}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 6, cursor: 'pointer' }}>
                {current ? (
                    <>
                        <Avatar size={20} style={{ background: '#9254de', fontSize: 11 }}>{(current.name || current.email).slice(0, 2).toUpperCase()}</Avatar>
                        <Typography.Text style={{ fontSize: 13 }}>{current.name || current.email}</Typography.Text>
                    </>
                ) : <Typography.Text type="secondary" style={{ fontSize: 13 }}>{placeholder}</Typography.Text>}
                <UpOutlined rotate={180} style={{ fontSize: 10, color: '#bfbfbf' }} />
            </div>
        </Popover>
    );
}

function NoteTabs() {
    const [tab, setTab] = useState<'internal' | 'print'>('internal');
    return (
        <>
            <Segmented size="small" block value={tab} onChange={(v) => setTab(v as 'internal' | 'print')}
                options={[{ value: 'internal', label: 'Nội bộ' }, { value: 'print', label: 'Để in' }]}
                style={{ marginBottom: 8 }} />
            <Form.Item name="note_internal" noStyle hidden={tab !== 'internal'}>
                <Input.TextArea rows={4} maxLength={2000} placeholder="Viết ghi chú hoặc /shortcut để ghi chú nhanh" />
            </Form.Item>
            <Form.Item name="note_print" noStyle hidden={tab !== 'print'}>
                <Input.TextArea rows={4} maxLength={2000} placeholder="Ghi chú sẽ hiển thị trên phiếu in" />
            </Form.Item>
        </>
    );
}

function CustomerWarning({ data }: { data: CustomerLookupResult | undefined }) {
    if (!data?.customer) return null;
    const open = data.open_orders ?? [];
    const returning = data.returning_orders ?? [];
    if (open.length === 0 && returning.length === 0 && !data.customer.is_blocked) return null;
    const danger = returning.length > 0 || data.customer.is_blocked;
    return (
        <Alert
            style={{ marginTop: 10, borderRadius: 6 }}
            type={danger ? 'warning' : 'info'}
            showIcon
            message={(
                <Space direction="vertical" size={4} style={{ width: '100%' }}>
                    {data.customer.is_blocked && (
                        <Typography.Text strong style={{ color: '#cf1322' }}>Khách đang bị chặn — kiểm tra trước khi tạo đơn.</Typography.Text>
                    )}
                    {open.length > 0 && (
                        <div>
                            <Typography.Text strong style={{ fontSize: 13 }}>Đang có {open.length} đơn chưa hoàn thành</Typography.Text>
                            <div style={{ marginTop: 4 }}>
                                {open.slice(0, 10).map((o) => <OrderIdTag key={o.id} order={o} />)}
                                {open.length > 10 && <Typography.Text type="secondary">+{open.length - 10}</Typography.Text>}
                            </div>
                        </div>
                    )}
                    {returning.length > 0 && (
                        <div>
                            <Typography.Text strong style={{ color: '#cf1322', fontSize: 13 }}>{returning.length} đơn đang/đã hoàn</Typography.Text>
                            <div style={{ marginTop: 4 }}>
                                {returning.slice(0, 10).map((o) => <OrderIdTag key={o.id} order={o} danger />)}
                                {returning.length > 10 && <Typography.Text type="secondary">+{returning.length - 10}</Typography.Text>}
                            </div>
                        </div>
                    )}
                </Space>
            )}
        />
    );
}

function OrderIdTag({ order, danger }: { order: { id: number; order_number: string | null; status: string }; danger?: boolean }) {
    return (
        <Link to={`/orders/${order.id}`} target="_blank" style={{ marginInlineEnd: 4 }}>
            <Tag color={danger ? 'red' : 'blue'} style={{ marginBottom: 4, cursor: 'pointer' }}>
                <BarcodeOutlined style={{ marginInlineEnd: 4 }} />
                {order.order_number ?? `#${order.id}`}
            </Tag>
        </Link>
    );
}
