import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
    Alert, AutoComplete, App as AntApp, Avatar, Badge, Button, Card, Checkbox, Col, DatePicker, Form, Input, InputNumber, Modal,
    Popover, Radio, Row, Segmented, Space, Switch, Tabs, Tag, Tooltip, Typography, Upload,
} from 'antd';
import {
    ArrowLeftOutlined, BarcodeOutlined, CalculatorOutlined, CheckCircleFilled, CloseCircleFilled,
    EnvironmentOutlined, FacebookFilled, LockOutlined, MoreOutlined, PaperClipOutlined,
    PrinterOutlined, SaveOutlined, SearchOutlined, UpOutlined, UserOutlined, WalletOutlined, WarningOutlined,
} from '@ant-design/icons';
import type { RcFile } from 'antd/es/upload';
import type { FormInstance } from 'antd';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { OrderItemsEditor, PickerTrigger, type OrderLineInput } from '@/components/OrderItemsEditor';
import { AddressPicker, type PickedAddress } from '@/components/AddressPicker';
import { AddressAutocomplete } from '@/components/AddressAutocomplete';
import { errorMessage } from '@/lib/api';
import { useCreateManualOrder, useUpdateManualOrder, useUploadImage, useWarehouses, type Sku } from '@/lib/inventory';
import { useOrder } from '@/lib/orders';
import { useCarrierAccounts, useShippingQuote } from '@/lib/fulfillment';
import { useTenant, useTenantMembers } from '@/lib/tenant';
import { useAuth } from '@/lib/auth';
import { useCustomerLookup, useCustomers, type BadReportWarning, type Customer, type CustomerLookupResult } from '@/lib/customers';

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

/** Giao thất bại — thu tiền: mặc định số tiền thu khi chưa cấu hình theo shop (settings.shipping.failed_collect_amount). */
const DEFAULT_FAILED_COLLECT_AMOUNT = 30000;

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
    // Gợi ý phí GHTK (cô lập): chỉ bật khi tenant có tài khoản GHTK đang hoạt động. Carrier khác không
    // hỗ trợ tính phí ⇒ không hiện nút. Gợi ý là ƯỚC TÍNH (KL mặc định) — user bấm "Áp dụng" mới ghi vào ô.
    const { data: carrierAccounts } = useCarrierAccounts();
    const ghtkAccount = useMemo(() => (carrierAccounts ?? []).find((a) => a.carrier === 'ghtk' && a.is_active), [carrierAccounts]);
    const quoteFee = useShippingQuote();
    const [feeSuggestion, setFeeSuggestion] = useState<number | null>(null);
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
    // Giao thất bại — thu tiền (reuse checkbox "collect_fee_on_return_only"): mặc định số tiền thu
    // theo cấu hình shop (settings.shipping.failed_collect_amount) nếu có, fallback hằng số 30.000đ.
    const { data: tenantDetail } = useTenant();
    const shopFailedCollectDefault = useMemo(() => {
        const shipping = tenantDetail?.settings?.shipping as Record<string, unknown> | undefined;
        const v = shipping?.failed_collect_amount;
        return typeof v === 'number' && v > 0 ? v : DEFAULT_FAILED_COLLECT_AMOUNT;
    }, [tenantDetail]);

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
    const [recipientNameSynced, setRecipientNameSynced] = useState(true);
    const [infoModalOpen, setInfoModalOpen] = useState(false);
    const [nameSearch, setNameSearch] = useState('');
    const [debouncedName, setDebouncedName] = useState('');
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
    // Đo chiều rộng ô tìm sản phẩm ⇒ danh sách gợi ý (popover) dài đúng bằng ô input.
    const productSearchRef = useRef<HTMLDivElement>(null);
    const [productPanelWidth, setProductPanelWidth] = useState<number>();
    useEffect(() => {
        const el = productSearchRef.current;
        if (!el || typeof ResizeObserver === 'undefined') return;
        const ro = new ResizeObserver(() => setProductPanelWidth(el.offsetWidth));
        ro.observe(el);
        setProductPanelWidth(el.offsetWidth);
        return () => ro.disconnect();
    }, []);
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
        const c = customerData?.customer;
        if (!c) return;
        const cur = form.getFieldsValue(['buyer_name', 'customer_source', 'customer_address', 'avatar_url', 'dob']);
        // Prefill hồ sơ khách (SPEC 0038 v2) khi tra theo SĐT — chỉ điền field đang trống để không đè user.
        const patch: Record<string, unknown> = {};
        if (!cur.buyer_name && c.name) patch.buyer_name = c.name;
        if (!cur.customer_source && c.source) patch.customer_source = c.source;
        if (!cur.customer_address && c.address) patch.customer_address = c.address;
        if (!cur.avatar_url && c.avatar_url) patch.avatar_url = c.avatar_url;
        if (!cur.dob && c.dob) patch.dob = dayjs(c.dob);
        if (Object.keys(patch).length) form.setFieldsValue(patch);
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
            avatar_url: meta.avatar_url ?? undefined,
            customer_source: meta.customer_source ?? undefined,
            customer_address: meta.customer_address ?? undefined,
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
            failed_collect_amount: o.failed_collect_amount ?? undefined,
            note_internal: o.note ?? undefined,
            note_print: (meta.print_note as string) ?? undefined,
        });

        setEditPrefilled(true);
    }, [isEdit, editingOrder, editPrefilled, form]);

    // Checkbox "Chỉ thu phí nếu hoàn" mới TÍCH (chưa có số tiền) ⇒ nạp mặc định của shop/hằng số.
    // Guard bằng getFieldValue (không phải state) nên KHÔNG đè giá trị vừa nạp từ đơn đang sửa
    // (effect edit-load chạy trước, cùng lượt commit, vì được khai báo phía trên).
    const collectFeeOnReturnOnly = Form.useWatch('collect_fee_on_return_only', form) as boolean | undefined;
    useEffect(() => {
        if (collectFeeOnReturnOnly && form.getFieldValue('failed_collect_amount') == null) {
            form.setFieldValue('failed_collect_amount', shopFailedCollectDefault);
        }
    }, [collectFeeOnReturnOnly, shopFailedCollectDefault, form]);

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
        // Trả vượt giá trị đơn ⇒ "còn thiếu" âm ⇒ COD đẩy ĐVVC sẽ là 0đ (cảnh báo cho user).
        const overpaid = afterDiscount - prepaid < 0;
        return { itemTotal, itemDiscount, orderDiscount, totalDiscount: itemDiscount + orderDiscount, shippingFee, surcharge, prepaid, afterDiscount, needCollect, overpaid, freeShipping };
    }, [items, summary]);

    // ---- Ví trả trước của khách (SPEC 2026-06-26) ----
    const walletBalance = customerData?.customer?.prepaid_balance ?? 0;
    const walletCustomerId = customerData?.customer?.id ?? null;
    const [useWallet, setUseWallet] = useState(false);
    const [walletDeductShip, setWalletDeductShip] = useState(true);
    // Số tiền trừ ví = min(số dư, mục tiêu). Mục tiêu = sau giảm giá (gồm/không gồm ship tuỳ toggle).
    const walletAmount = useMemo(() => {
        if (!useWallet || walletBalance <= 0) return 0;
        const target = walletDeductShip ? totals.afterDiscount : Math.max(0, totals.afterDiscount - totals.shippingFee);
        return Math.min(walletBalance, Math.max(0, target));
    }, [useWallet, walletBalance, walletDeductShip, totals.afterDiscount, totals.shippingFee]);
    // Áp số tiền ví vào ô "đã trả trước" ⇒ COD tự tính lại (BE nguồn sự thật). Tắt ví ⇒ trả ô về 0.
    useEffect(() => {
        if (useWallet) form.setFieldValue('prepaid_amount', walletAmount);
    }, [useWallet, walletAmount, form]);
    // Khách đổi (không còn ví) ⇒ tự tắt dùng ví.
    useEffect(() => {
        if (walletBalance <= 0 && useWallet) { setUseWallet(false); }
    }, [walletBalance, useWallet]);

    // Gợi ý phí GHTK: ước tính KL = tổng SL × 500g (mặc định, vì dòng hàng chưa mang KL). Lỗi/empty ⇒
    // thông báo nhẹ, KHÔNG chặn. Không tự đè ô "Phí vận chuyển" — user bấm "Áp dụng".
    const onSuggestFee = async () => {
        if (!ghtkAccount) {
            return;
        }
        if (!shipAddress.province || (!shipAddress.district && !shipAddress.ward)) {
            message.warning('Cần địa chỉ nhận (tỉnh + quận/huyện hoặc phường/xã) để tính phí GHTK.');
            return;
        }
        const totalQty = items.reduce((s, l) => s + (l.quantity || 0), 0);
        try {
            const q = await quoteFee.mutateAsync({
                carrier_account_id: ghtkAccount.id,
                weight_grams: Math.max(100, totalQty * 500),
                value: totals.itemTotal,
                recipient: {
                    province: shipAddress.province, district: shipAddress.district,
                    ward: shipAddress.ward || undefined, address: (form.getFieldValue('recipient_address') as string) || undefined,
                },
            });
            if (q) {
                setFeeSuggestion(q.fee);
            } else {
                setFeeSuggestion(null);
                message.info('GHTK chưa trả phí cho địa chỉ này.');
            }
        } catch {
            setFeeSuggestion(null);
            message.warning('Không lấy được phí GHTK, thử lại sau.');
        }
    };

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
        return {
            sub_source: (v.sub_source as string) || undefined,
            // Đơn tạo thủ công mặc định "Chờ xử lý"; chỉ sang "Đang xử lý" sau khi chuẩn bị hàng + đẩy ĐVVC.
            status: 'pending' as const,
            buyer: { name: (v.buyer_name as string) || (v.recipient_name as string) || undefined, phone: phone || undefined },
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
            },
            items: lines,
            free_shipping: !!v.free_shipping,
            shipping_fee: v.free_shipping ? 0 : ((v.shipping_fee as number) ?? 0),
            order_discount: (v.order_discount as number) ?? 0,
            prepaid_amount: (v.prepaid_amount as number) ?? 0,
            // Ví trả trước (SPEC 2026-06-26): gửi customer_id + phần trừ ví để BE trừ số dư + COD theo prepaid.
            customer_id: useWallet && walletCustomerId ? walletCustomerId : undefined,
            wallet_amount: useWallet ? walletAmount : undefined,
            surcharge: (v.surcharge as number) ?? 0,
            // COD = tiền còn thiếu; BE là nguồn sự thật (tự suy từ grand_total − prepaid). Gửi kèm để nhất quán.
            is_cod: totals.needCollect > 0,
            // Giao thất bại — thu tiền: chỉ gửi >0 khi tích "Chỉ thu phí nếu hoàn"; BE map GHN cod_failed_amount /
            // VTP EXTRA_MONEY tại thời điểm đẩy ĐVVC, im lặng bỏ qua với ĐVVC không hỗ trợ (GHTK).
            failed_collect_amount: v.collect_fee_on_return_only ? ((v.failed_collect_amount as number) ?? 30000) : 0,
            warehouse_id: warehouseId ?? undefined,
            note: (v.note_internal as string) || undefined,
            tags,
            meta: {
                assignee_user_id: (v.assignee_user_id as number) || undefined,
                care_user_id: (v.care_user_id as number) || undefined,
                marketer_user_id: (v.marketer_user_id as number) || undefined,
                gender: (v.gender as string) || undefined,
                dob: v.dob ? dayjs(v.dob as string).format('YYYY-MM-DD') : undefined,
                email: (v.email as string) || undefined,
                // Hồ sơ khách nhập tay (SPEC 0038 v2) — persist vào hồ sơ khách khi link đơn.
                avatar_url: (v.avatar_url as string) || undefined,
                customer_source: (v.customer_source as string) || undefined,
                customer_address: (v.customer_address as string) || undefined,
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

    // Tên khách → tự điền Tên người nhận (cùng cơ chế SĐT): chỉ copy khi user chưa tự gõ Nhận hàng.
    const watchedBuyerName = Form.useWatch('buyer_name', form) as string | undefined;
    useEffect(() => {
        if (recipientNameSynced) form.setFieldsValue({ recipient_name: watchedBuyerName ?? '' });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [watchedBuyerName]);
    const watchedRecipientName = Form.useWatch('recipient_name', form) as string | undefined;
    useEffect(() => {
        const rn = (watchedRecipientName ?? '').toString();
        if (rn === '') { setRecipientNameSynced(true); return; }
        if (rn !== (watchedBuyerName ?? '')) setRecipientNameSynced(false);
    }, [watchedRecipientName, watchedBuyerName]);

    // Gợi ý khách theo TÊN (debounce 300ms) — chỉ trong tenant hiện tại (useCustomers đã scope).
    useEffect(() => {
        const t = setTimeout(() => setDebouncedName(nameSearch.trim()), 300);
        return () => clearTimeout(t);
    }, [nameSearch]);
    const nameSuggest = useCustomers({ q: debouncedName, per_page: 8 }, debouncedName.length >= 2);
    const nameOptions = (debouncedName.length >= 2 ? (nameSuggest.data?.data ?? []) : []).map((c) => ({
        key: String(c.id),
        value: c.name ?? '',
        customer: c,
        label: (
            <Space size={8} style={{ width: '100%' }}>
                <Avatar size={24} src={c.avatar_url || undefined} icon={<UserOutlined />} />
                <span style={{ flex: 1 }}>{c.name ?? '—'}</span>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>{c.phone_masked ?? ''}</Typography.Text>
                <Tag color="blue" style={{ marginInlineEnd: 0 }}>{c.lifetime_stats?.orders_total ?? 0} đơn</Tag>
            </Space>
        ),
    }));
    const onPickSuggestion = (_v: string, opt: { customer?: Customer }) => {
        const c = opt.customer;
        if (!c) return;
        form.setFieldsValue({ buyer_name: c.name ?? '' });
        setNameSearch('');   // đóng gợi ý
        if (c.phone) handlePhoneChange(c.phone);   // điền + sync SĐT ⇒ kích hoạt lookup
        if (recipientNameSynced) form.setFieldsValue({ recipient_name: c.name ?? '' });
    };

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

    // Ô SĐT khách — suffix LUÔN là 1 node (span cố định) để AntD không chuyển <input> trần ↔
    // affix-wrapper khi lookup bật ở ký tự thứ 9 (đổi cấu trúc DOM ⇒ remount input ⇒ MẤT focus).
    const phoneField = (
        <Input
            value={phone}
            onChange={(e) => handlePhoneChange(e.target.value)}
            placeholder="SĐT" maxLength={32}
            suffix={(
                <span style={{ display: 'inline-flex', width: 14, justifyContent: 'center' }}>
                    {lookup.isFetching ? '…' : (customerData?.customer ? <Tooltip title="Khách đã có trong sổ"><CheckCircleFilled style={{ color: '#52c41a' }} /></Tooltip> : null)}
                </span>
            )}
        />
    );

    // Khối địa chỉ nhận (autocomplete + picker Tỉnh/Quận/Phường) — dùng chung cho cả card "Nhận hàng"
    // (full) lẫn card gộp "Khách hàng / Nhận hàng" (compact, khung chat).
    const addressBlock = (
        <>
            <Form.Item name="recipient_address" style={{ marginBottom: 8 }} rules={[{ required: true, message: 'Địa chỉ chi tiết' }]}>
                <AddressAutocomplete
                    format={shipAddress.format ?? 'new'}
                    placeholder="Địa chỉ chi tiết — gõ cả tỉnh/quận/phường để được gợi ý (vd: 123 NTrai, Q.1, TP HCM)"
                    onPick={(s) => { setShipAddress((cur) => ({ ...cur, ...s.address })); }}
                />
            </Form.Item>
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
        </>
    );

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
                    description="Mọi thay đổi (sản phẩm, địa chỉ, tiền…) chỉ áp dụng trên hệ thống nội bộ — KHÔNG can thiệp vào vận đơn đã đẩy lên ĐVVC, nên dữ liệu nội bộ sẽ LỆCH so với ĐVVC. Nếu cần sửa thực tế giao hàng — hãy huỷ vận đơn này trên hệ ĐVVC trước, rồi đẩy lại đơn mới."
                />
            )}

            {isEdit && orderQuery.isLoading && (
                <Alert type="info" showIcon style={{ marginBottom: 16 }} message="Đang tải dữ liệu đơn để chỉnh sửa…" />
            )}

            <Form form={form} layout="vertical" initialValues={{
                channel_mode: 'online', sub_source: undefined, free_shipping: false, collect_fee_on_return_only: false,
                shipping_fee: 0, order_discount: 0, prepaid_amount: 0, surcharge: 0,
            }}>
                <Row gutter={16}>
                    {/* ========================= LEFT COLUMN ========================= */}
                    <Col xs={24} lg={compact ? 24 : 16}>
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
                            <div className="ord-product-search" style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                <Segmented
                                    size="small" value={productMode} onChange={(v) => setProductMode(v as 'sku' | 'combo')}
                                    options={[{ value: 'sku', label: 'Sản phẩm' }, { value: 'combo', label: 'Combo' }]}
                                />
                                <PickerTrigger
                                    open={pickerOpen} setOpen={setPickerOpen}
                                    query={productSearch}
                                    inStockOnly={inStockOnly}
                                    panelWidth={productPanelWidth}
                                    taken={new Set(items.map((r) => r.sku_id).filter((x): x is number => x != null))}
                                    onPickSku={(s: Sku) => {
                                        const existing = items.find((r) => r.sku_id === s.id);
                                        if (existing) setItems(items.map((r) => r.key === existing.key ? { ...r, quantity: r.quantity + 1 } : r));
                                        else setItems([...items, { key: `line-${Date.now()}`, sku_id: s.id, name: s.name, image: s.image_url ?? undefined, sku_code: s.sku_code, available: s.available_total ?? 0, quantity: 1, unit_price: s.ref_sale_price ?? 0, discount: 0 }]);
                                        setPickerOpen(false);
                                    }}
                                    onQuickCreate={() => { setItems([...items, { key: `line-${Date.now()}`, name: '', quantity: 1, unit_price: 0, discount: 0 }]); setPickerOpen(false); }}
                                >
                                    {/* ref để đo bề rộng; onClick (không onFocus) mở popover — tránh focus mở rồi click toggle đóng ngay lần đầu. */}
                                    <div ref={productSearchRef} style={{ width: '100%' }}>
                                    <Input
                                        size="middle"
                                        prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />}
                                        placeholder="Nhập mã, tên sản phẩm hoặc Barcode"
                                        value={productSearch}
                                        onChange={(e) => { setProductSearch(e.target.value); setPickerOpen(true); }}
                                        onClick={() => setPickerOpen(true)}
                                        className="ord-search-input"
                                        style={{ width: '100%' }}
                                        suffix={(
                                            <Space size={8}>
                                                <Checkbox checked={inStockOnly} onChange={(e) => setInStockOnly(e.target.checked)} className="ord-search-check">Còn hàng</Checkbox>
                                                <Tooltip title="Quét barcode"><Button type="text" size="small" icon={<BarcodeOutlined />} className="ord-scan-btn">(F9)</Button></Tooltip>
                                            </Space>
                                        )}
                                    />
                                    </div>
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
                            <Col xs={24} md={compact ? 24 : 12}>
                                <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                                    title={<span className="ord-card-title">Thanh toán</span>}
                                    extra={<Button type="text" size="small" icon={<MoreOutlined />} />}>
                                    <Space size={20} style={{ marginBottom: 10 }}>
                                        <Form.Item name="free_shipping" valuePropName="checked" noStyle><Checkbox>Miễn phí giao hàng</Checkbox></Form.Item>
                                        <Form.Item name="collect_fee_on_return_only" valuePropName="checked" noStyle><Checkbox>Chỉ thu phí nếu hoàn</Checkbox></Form.Item>
                                    </Space>
                                    {collectFeeOnReturnOnly && (
                                        <PayRow label="Số tiền thu khi giao thất bại" name="failed_collect_amount" max={50000000} step={1000} />
                                    )}
                                    <PayRow label="Phí vận chuyển" name="shipping_fee" disabled={!!summary?.free_shipping} />
                                    {ghtkAccount && (
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', margin: '-2px 0 8px' }}>
                                            <Button size="small" icon={<CalculatorOutlined />} loading={quoteFee.isPending}
                                                disabled={!!summary?.free_shipping || !shipAddress.province || (!shipAddress.district && !shipAddress.ward)}
                                                onClick={onSuggestFee}>Gợi ý phí GHTK</Button>
                                            {feeSuggestion !== null && (
                                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                                                    <Typography.Text type="secondary">GHTK (ước tính):</Typography.Text>
                                                    <b>{vnd(feeSuggestion)} đ</b>
                                                    <Button size="small" type="link" style={{ padding: 0 }}
                                                        onClick={() => { form.setFieldValue('shipping_fee', feeSuggestion); message.success('Đã áp dụng phí GHTK.'); }}>Áp dụng</Button>
                                                </span>
                                            )}
                                        </div>
                                    )}
                                    <PayRow label="Giảm giá đơn hàng" name="order_discount" />
                                    {walletBalance > 0 && (
                                        <div style={{ border: '1px solid #91caff', background: '#e6f4ff', borderRadius: 6, padding: '8px 10px', margin: '6px 0' }}>
                                            <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                                                <Typography.Text><WalletOutlined /> Ví trả trước: <b>{vnd(walletBalance)} đ</b></Typography.Text>
                                                <Switch checked={useWallet} onChange={setUseWallet} checkedChildren="Dùng ví" unCheckedChildren="Dùng ví" />
                                            </Space>
                                            {useWallet && (
                                                <div style={{ marginTop: 6 }}>
                                                    <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                                                        <Typography.Text style={{ fontSize: 13 }}>Trừ cả tiền ship vào ví</Typography.Text>
                                                        <Switch size="small" checked={walletDeductShip} onChange={setWalletDeductShip} />
                                                    </Space>
                                                    <Typography.Text type="success" style={{ fontSize: 13, display: 'block', marginTop: 4 }}>
                                                        Trừ ví đơn này: <b>{vnd(walletAmount)} đ</b> · Còn lại sau đơn: {vnd(Math.max(0, walletBalance - walletAmount))} đ
                                                    </Typography.Text>
                                                </div>
                                            )}
                                        </div>
                                    )}
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

                            {/* ---------- Ghi chú (ẩn ở khung chat — không cần cho đơn nhanh) ---------- */}
                            {!compact && (
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
                            )}
                        </Row>
                    </Col>

                    {/* ========================= RIGHT COLUMN ========================= */}
                    <Col xs={24} lg={compact ? 24 : 8}>
                        {/* ---------- Thông tin (ẩn ở khung chat — NV/marketer/thẻ không cần cho đơn nhanh) ---------- */}
                        {!compact && (
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
                        )}

                        {compact ? (
                            // ---------- Khách & nhận hàng — GỘP 1 card cho khung chat ----------
                            <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                                title={<span className="ord-card-title">Khách &amp; nhận hàng <span style={{ color: '#cf1322', marginInlineStart: 4 }}>*</span></span>}
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
                                <Row gutter={8}>
                                    <Col span={12}><Form.Item name="recipient_name" style={{ marginBottom: 8 }} rules={[{ required: true, message: 'Tên người nhận' }]}>
                                        <Input placeholder="Tên khách / người nhận *" maxLength={255} />
                                    </Form.Item></Col>
                                    <Col span={12}><Form.Item style={{ marginBottom: 8 }}>
                                        {phoneField}
                                    </Form.Item></Col>
                                </Row>
                                {addressBlock}
                                <CustomerWarning data={customerData} />
                            </Card>
                        ) : (
                            <>
                                {/* ---------- Khách hàng ---------- */}
                                <Card size="small" className="ord-card" style={{ marginBottom: 16 }}
                                    title={<span className="ord-card-title">Khách hàng</span>}
                                    extra={(
                                        <Space size={4}>
                                            <CustomerWarning data={customerData} />
                                            <Form.Item name="gender" noStyle>
                                                <GenderDropdown />
                                            </Form.Item>
                                            <Tooltip title="Thông tin khách hàng">
                                                <Button type="text" size="small" icon={<MoreOutlined />} onClick={() => setInfoModalOpen(true)} />
                                            </Tooltip>
                                        </Space>
                                    )}
                                >
                                    <Row gutter={8}>
                                        <Col span={12}><Form.Item name="buyer_name" style={{ marginBottom: 8 }}>
                                            <AutoComplete
                                                options={nameOptions}
                                                onSearch={setNameSearch}
                                                onSelect={onPickSuggestion}
                                                filterOption={false}
                                                popupMatchSelectWidth={320}
                                            >
                                                <Input placeholder="Tên khách hàng" maxLength={255} />
                                            </AutoComplete>
                                        </Form.Item></Col>
                                        <Col span={12}><Form.Item style={{ marginBottom: 8 }}>
                                            {phoneField}
                                        </Form.Item></Col>
                                        <Col span={24}><Form.Item name="email" rules={[{ type: 'email', message: 'Email không hợp lệ' }]} style={{ marginBottom: 8 }}>
                                            <Input placeholder="Địa chỉ email" maxLength={255} />
                                        </Form.Item></Col>
                                    </Row>
                                    <CustomerInfoModal open={infoModalOpen} onClose={() => setInfoModalOpen(false)} form={form} upload={upload} message={message} />
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
                                    {addressBlock}
                                </Card>
                            </>
                        )}

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
                    {/* COD đẩy ĐVVC = tiền CÒN THIẾU cuối cùng = grand_total − đã trả trước. Không còn checkbox
                        riêng (BE tự suy từ prepaid) — tránh bẫy "quên tick" làm đơn đẩy GHN ra COD 0đ. */}
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                        <span>COD (thu hộ):</span>
                        <b className={totals.needCollect > 0 ? 'ord-bottom-money' : 'ord-bottom-money-muted'}>{vnd(totals.needCollect)} đ</b>
                    </div>
                    {totals.overpaid && (
                        <Typography.Text type="warning" style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                            <WarningOutlined /> Đã trả trước vượt giá trị đơn — COD đẩy ĐVVC sẽ là 0đ.
                        </Typography.Text>
                    )}
                </Space>
                <Space>
                    <Button icon={<PrinterOutlined />} onClick={() => submit(true)} loading={submitting}>{isEdit ? 'Lưu & in' : 'In'} <kbd className="ord-kbd">F4</kbd></Button>
                    <Button type="primary" icon={<SaveOutlined />} onClick={() => submit(false)} loading={submitting}>{isEdit ? 'Lưu thay đổi' : 'Lưu'} <kbd className="ord-kbd-on-primary">F2</kbd></Button>
                </Space>
            </div>

            {/* ---------- Scoped styles ---------- */}
            <style>{`
                .create-order-page .ord-card { border-radius: 8px; }
                .create-order-page .ord-card .ant-card-head { min-height: 44px; padding: 0 16px; border-bottom: 1px solid #f0f0f0; }
                .create-order-page .ord-card-title { font-size: 15px; font-weight: 600; color: #262626; }
                .create-order-page .ord-card .ant-card-body { padding: 14px 16px; }
                /* To hơn 1 chút: ô nhập/chọn cao & rõ hơn (mặc định 32px → 38px) — không đổi layout. */
                .create-order-page .ant-input,
                .create-order-page .ant-input-affix-wrapper,
                .create-order-page .ant-input-number,
                .create-order-page .ant-picker { min-height: 38px; font-size: 14px; }
                .create-order-page .ant-input-affix-wrapper { padding-top: 3px; padding-bottom: 3px; }
                .create-order-page .ant-input-affix-wrapper > input.ant-input { min-height: 30px; }
                .create-order-page .ant-input-number .ant-input-number-input { height: 36px; }
                .create-order-page textarea.ant-input { min-height: 64px; }
                .create-order-page .ant-select-single { height: 38px; }
                .create-order-page .ant-select-single .ant-select-selector { height: 38px; padding-top: 3px; padding-bottom: 3px; }
                .create-order-page .ant-select-single .ant-select-selection-item,
                .create-order-page .ant-select-single .ant-select-selection-placeholder { line-height: 30px; }
                .create-order-page .ord-pill-btn { border-radius: 16px; height: 28px; padding: 0 12px; background: #fff; border-color: #d9d9d9; }
                .create-order-page .ord-pill-btn:hover { border-color: #1677ff; color: #1677ff; }
                .create-order-page .ord-product-search { display: flex; align-items: center; gap: 8px; }
                .create-order-page .ord-search-input.ant-input-affix-wrapper { border-radius: 6px; }
                .create-order-page .ord-search-check { font-size: 13px; color: #595959; }
                .create-order-page .ord-scan-btn { padding: 0 8px; height: 26px; font-size: 12px; color: #8c8c8c; background: #f5f5f5; border-radius: 4px; }
                .create-order-page .ord-empty-cart { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 28px 0; }
                .create-order-page .ord-empty-icon { position: relative; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; font-size: 44px; color: #d9d9d9; }
                .create-order-page .ord-empty-icon .ord-empty-bubble { position: absolute; top: -6px; right: -8px; background: #f0f0f0; color: #8c8c8c; border-radius: 999px; padding: 2px 8px; font-size: 12px; line-height: 1; }
                .create-order-page .ord-summary-list { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #eaeaea; display: flex; flex-direction: column; gap: 4px; }
                .create-order-page .ord-pay-row { display: flex; align-items: center; gap: 8px; padding: 5px 0; }
                .create-order-page .ord-pay-row label { flex: 1; color: #595959; font-size: 14px; }
                /* Đơn vị "đ" tách ra ngoài span, KHÔNG dùng suffix của InputNumber
                   (suffix tạo affix-wrapper ⇒ viền focus lệch/nhỏ hơn ô). Nhờ vậy ô là
                   .ant-input-number thuần: 1 viền duy nhất, focus khớp mép ô. */
                .create-order-page .ord-pay-input.ant-input-number { width: 200px; max-width: 55%; }
                .create-order-page .ord-pay-input .ant-input-number-input { text-align: right; }
                .create-order-page .ord-pay-unit { color: #8c8c8c; font-size: 13px; width: 12px; text-align: center; flex: none; }
                .create-order-page .ord-readonly { color: #262626; font-size: 14px; }
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

function PayRow({ label, name, disabled, max, step }: { label: string; name: string; disabled?: boolean; max?: number; step?: number }) {
    return (
        <div className="ord-pay-row">
            <label>{label}</label>
            <Form.Item name={name} noStyle>
                <InputNumber
                    min={0} max={max} step={step} disabled={disabled} className="ord-pay-input" controls={false}
                    formatter={(v) => `${v ?? 0}`.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}
                    parser={(v) => Number((v ?? '').toString().replace(/\D/g, '')) as 0}
                />
            </Form.Item>
            <span className="ord-pay-unit">đ</span>
        </div>
    );
}

function SummaryRow({ label, value, tone, strong }: { label: string; value: string; tone?: 'g' | 'r' | 'mute'; strong?: boolean }) {
    const color = tone === 'g' ? '#389e0d' : tone === 'r' ? '#cf1322' : tone === 'mute' ? '#bfbfbf' : '#262626';
    return (
        <Row justify="space-between" align="middle">
            <Typography.Text type="secondary" style={{ fontSize: 14 }}>{label}</Typography.Text>
            <Typography.Text style={{ color, fontSize: 14, fontWeight: strong ? 600 : 400 }}>{value} <span style={{ textDecoration: 'underline', textDecorationStyle: 'dotted' }}>đ</span></Typography.Text>
        </Row>
    );
}

function KvRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <Row align="middle" style={{ marginBottom: 10 }}>
            <Col span={10}><Typography.Text type="secondary" style={{ fontSize: 14 }}>{label}</Typography.Text></Col>
            <Col span={14} style={{ display: 'flex', justifyContent: 'flex-end' }}>{children}</Col>
        </Row>
    );
}

const CUSTOMER_SOURCES = [
    { label: 'Facebook', value: 'facebook' },
    { label: 'Zalo', value: 'zalo' },
    { label: 'Website', value: 'website' },
    { label: 'Shopee', value: 'shopee' },
    { label: 'TikTok', value: 'tiktok' },
    { label: 'Hotline', value: 'hotline' },
    { label: 'Giới thiệu', value: 'referral' },
    { label: 'Khác', value: 'other' },
];

// SPEC 0038 v2 — modal "Thông tin khách hàng" (nút 3 chấm): avatar, tên, ngày sinh/tuổi,
// địa chỉ, nguồn khách. Form.Items dùng chung `form` của trang ⇒ buildPayload đẩy vào
// order.meta → persist bền vững vào hồ sơ khách khi link đơn. forceRender để field luôn đăng ký.
function CustomerInfoModal({ open, onClose, form, upload, message }: {
    open: boolean;
    onClose: () => void;
    form: FormInstance;
    upload: ReturnType<typeof useUploadImage>;
    message: ReturnType<typeof AntApp.useApp>['message'];
}) {
    const avatarUrl = Form.useWatch('avatar_url', form) as string | undefined;
    const onAvatar = (file: RcFile) => {
        if (file.size / 1024 / 1024 > 5) { message.error('Ảnh đại diện tối đa 5MB.'); return Upload.LIST_IGNORE; }
        upload.mutate({ file, folder: 'customer-avatars' }, {
            onSuccess: (r) => form.setFieldsValue({ avatar_url: r.url }),
            onError: (e) => message.error(errorMessage(e)),
        });
        return false;
    };
    return (
        <Modal title="Thông tin khách hàng" open={open} onCancel={onClose} onOk={onClose} okText="Xong" forceRender>
            <Form.Item name="avatar_url" hidden><Input /></Form.Item>
            <div style={{ textAlign: 'center', marginBottom: 12 }}>
                <Upload showUploadList={false} beforeUpload={onAvatar} accept="image/*">
                    <Avatar size={72} src={avatarUrl || undefined} icon={<UserOutlined />} style={{ cursor: 'pointer' }} />
                    <div><Button type="link" size="small" loading={upload.isPending}>{avatarUrl ? 'Đổi ảnh đại diện' : 'Thêm ảnh đại diện'}</Button></div>
                </Upload>
            </div>
            <Form.Item name="buyer_name" label="Tên khách hàng" style={{ marginBottom: 12 }}>
                <Input maxLength={255} placeholder="Tên khách hàng" />
            </Form.Item>
            <Row gutter={12}>
                <Col span={12}>
                    <Form.Item name="dob" label="Ngày sinh / tuổi" style={{ marginBottom: 12 }}>
                        <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" placeholder="Ngày sinh" />
                    </Form.Item>
                </Col>
                <Col span={12}>
                    <Form.Item name="customer_address" label="Địa chỉ" style={{ marginBottom: 12 }}>
                        <Input maxLength={255} placeholder="Địa chỉ khách hàng" />
                    </Form.Item>
                </Col>
            </Row>
            <Form.Item name="customer_source" label="Nguồn khách hàng" style={{ marginBottom: 0 }}>
                <Radio.Group options={CUSTOMER_SOURCES} optionType="button" buttonStyle="solid" size="small" />
            </Form.Item>
        </Modal>
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

function UserPicker({ value, onChange, members, placeholder }: { value?: number | null; onChange?: (v: number | null) => void; members: Array<{ id: number; name: string; email: string | null }>; placeholder: string }) {
    const current = value ? members.find((m) => m.id === value) : null;
    return (
        <Popover trigger="click" placement="bottomRight" content={(
            <div style={{ width: 260, maxHeight: 320, overflowY: 'auto' }}>
                <Radio.Group style={{ width: '100%' }} value={value ?? undefined} onChange={(e) => onChange?.(e.target.value)}>
                    {members.length === 0 ? <Typography.Text type="secondary">Chưa có thành viên.</Typography.Text> : null}
                    {members.map((m) => (
                        <Radio key={m.id} value={m.id} style={{ display: 'flex', padding: '6px 8px' }}>
                            <Space size={8}>
                                <Avatar size={22} style={{ background: '#9254de', fontSize: 12 }}>{(m.name || m.email || '?').slice(0, 2).toUpperCase()}</Avatar>
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
                        <Avatar size={22} style={{ background: '#9254de', fontSize: 12 }}>{(current.name || current.email || '?').slice(0, 2).toUpperCase()}</Avatar>
                        <Typography.Text style={{ fontSize: 14 }}>{current.name || current.email}</Typography.Text>
                    </>
                ) : <Typography.Text type="secondary" style={{ fontSize: 14 }}>{placeholder}</Typography.Text>}
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

// SPEC 0038 v2 — thanh tỷ lệ đơn thành công (xanh, trái) / hoàn (đỏ, phải) + nút icon
// cảnh báo (sáng nền khi có cảnh báo) mở popover danh sách cảnh báo (read-only). Việc TẠO
// report nằm ở nút trên đơn hoàn (OrderDetailPage), không ở màn đang tạo đơn.
// Widget gọn đặt cạnh Giới tính: thanh tỷ lệ ngắn (~½ ô SĐT) + nút icon cảnh báo (sáng nền khi có).
function CustomerWarning({ data }: { data: CustomerLookupResult | undefined }) {
    if (!data?.bad_report) return null;
    const br = data.bad_report;
    const open = data.customer ? (data.open_orders ?? []) : [];
    const success = br.success_count;
    const fail = br.fail_count;
    const total = success + fail;
    const hasWarning = br.has_warning;

    return (
        <Space size={4}>
            {total > 0 && (
                <Tooltip title={(
                    <div style={{ fontSize: 12 }}>
                        <div><CheckCircleFilled style={{ color: '#52c41a', marginInlineEnd: 4 }} />Thành công: {success}</div>
                        <div><CloseCircleFilled style={{ color: '#cf1322', marginInlineEnd: 4 }} />Hoàn: {fail}</div>
                        {open.length > 0 && <div style={{ marginTop: 2 }}>Đang xử lý: {open.length}</div>}
                    </div>
                )}>
                    <div style={{ width: 72, display: 'flex', height: 8, borderRadius: 4, overflow: 'hidden', background: '#f0f0f0' }}>
                        <div style={{ width: `${(success / total) * 100}%`, background: '#52c41a' }} />
                        <div style={{ width: `${(fail / total) * 100}%`, background: '#cf1322' }} />
                    </div>
                </Tooltip>
            )}
            <Popover trigger="click" placement="bottomRight" title="Cảnh báo khách hàng" content={<WarningList warnings={br.warnings} />}>
                <Tooltip title="Xem cảnh báo">
                    <Button size="small" type={hasWarning ? 'primary' : 'text'} danger={hasWarning} icon={<WarningOutlined />} />
                </Tooltip>
            </Popover>
        </Space>
    );
}

function WarningList({ warnings }: { warnings: BadReportWarning[] }) {
    if (warnings.length === 0) {
        return <Typography.Text type="secondary" style={{ fontSize: 12 }}>Không có cảnh báo.</Typography.Text>;
    }
    return (
        <div style={{ width: 260, maxHeight: 260, overflowY: 'auto' }}>
            {warnings.map((w, i) => (
                <div key={i} style={{ padding: '4px 0', borderBottom: i < warnings.length - 1 ? '1px solid #f0f0f0' : undefined }}>
                    <div style={{ fontSize: 13 }}>{w.reason}</div>
                    {w.reported_at && <div style={{ fontSize: 11, color: '#999', marginTop: 2 }}>{dayjs(w.reported_at).format('DD/MM/YYYY')}</div>}
                </div>
            ))}
        </div>
    );
}
