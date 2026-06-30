import { useEffect, useState } from 'react';
import { Alert, App as AntApp, Button, Card, Input, Radio, Space, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useCan, useTenant, useUpdateTenant } from '@/lib/tenant';

/**
 * /settings/print — "Mẫu in":
 *   - **Khổ phiếu** (`print.label_size`) — dùng cho PDF (Gotenberg). Phiếu giao đơn manual in HTML responsive
 *     theo khổ máy in, không bị ràng buộc bởi giá trị này.
 *   - **Nội dung in mặc định** (`print.default_note`).
 *   - **Người gửi / Địa chỉ lấy hàng** (`print.senders`): danh sách nhiều người gửi (tên · SĐT · địa chỉ);
 *     khi in phiếu giao hàng đơn thủ công, chọn 1 bản ghi → map vào khối "Địa chỉ lấy hàng" của phiếu.
 *
 * owner/admin sửa được.
 */
const SIZES: Array<{ value: string; label: string; hint: string }> = [
    { value: 'A6', label: 'A6 (105×148mm)', hint: 'Tem nhiệt phổ biến — 1 phiếu/trang' },
    { value: '100x150mm', label: '100×150mm', hint: 'Tem nhiệt 10×15cm' },
    { value: '80mm', label: '80mm (cuộn nhiệt)', hint: 'Máy in bill nhiệt khổ 80mm, dài tự động' },
    { value: 'A5', label: 'A5 (148×210mm)', hint: '1 phiếu/trang' },
    { value: 'A4', label: 'A4 (210×297mm)', hint: 'Máy in văn phòng — 1 phiếu/trang' },
];
const DEFAULT_SIZE = 'A6';
const DEFAULT_NOTE_PLACEHOLDER = 'Vd: "Cảm ơn quý khách! Đổi/trả trong 7 ngày kèm hộp nguyên seal. Hotline: 0901234567"';

interface SenderProfile { id: string; name: string; phone: string; address: string; is_default?: boolean }

function newId(): string {
    return (typeof crypto !== 'undefined' && crypto.randomUUID) ? crypto.randomUUID().slice(0, 8) : Math.random().toString(36).slice(2, 10);
}

export function SettingsPrintPage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const update = useUpdateTenant();
    const canManage = useCan('tenant.settings');
    const [size, setSize] = useState<string>(DEFAULT_SIZE);
    const [defaultNote, setDefaultNote] = useState<string>('');
    const [senders, setSenders] = useState<SenderProfile[]>([]);

    useEffect(() => {
        if (!tenant) return;
        const cur = (tenant.settings?.print ?? {}) as Record<string, unknown>;
        setSize(typeof cur.label_size === 'string' && cur.label_size ? cur.label_size : DEFAULT_SIZE);
        setDefaultNote(typeof cur.default_note === 'string' ? cur.default_note : '');
        setSenders(Array.isArray(cur.senders)
            ? (cur.senders as SenderProfile[]).map((s) => ({ id: s.id || newId(), name: s.name ?? '', phone: s.phone ?? '', address: s.address ?? '', is_default: !!s.is_default }))
            : []);
    }, [tenant]);

    const patchSender = (id: string, p: Partial<SenderProfile>) => setSenders((arr) => arr.map((s) => (s.id === id ? { ...s, ...p } : s)));
    const setDefault = (id: string) => setSenders((arr) => arr.map((s) => ({ ...s, is_default: s.id === id })));
    const addSender = () => setSenders((arr) => [...arr, { id: newId(), name: '', phone: '', address: '', is_default: arr.length === 0 }]);
    const removeSender = (id: string) => setSenders((arr) => {
        const next = arr.filter((s) => s.id !== id);
        if (next.length > 0 && !next.some((s) => s.is_default)) next[0].is_default = true;
        return next;
    });

    const save = () => {
        // Bỏ bản ghi rỗng; đảm bảo có đúng 1 mặc định.
        const cleaned = senders.filter((s) => s.name.trim() !== '' || s.address.trim() !== '')
            .map((s) => ({ id: s.id, name: s.name.trim(), phone: s.phone.trim(), address: s.address.trim(), is_default: !!s.is_default }));
        if (cleaned.length > 0 && !cleaned.some((s) => s.is_default)) cleaned[0].is_default = true;
        // settings.print là 1 object ⇒ phải gửi đủ các khoá (array_replace nông sẽ thay cả object print).
        update.mutate({ settings: { print: { label_size: size, default_note: defaultNote.trim() || null, senders: cleaned } } }, {
            onSuccess: () => message.success('Đã lưu cài đặt phiếu in'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    if (!tenant) return <Card loading title="Mẫu in" />;

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Card title="Khổ phiếu in (PDF)">
                <Typography.Paragraph type="secondary" style={{ marginBottom: 16 }}>
                    Áp dụng khi tải/in PDF (packing slip / hoá đơn). Riêng <b>phiếu giao hàng đơn thủ công</b> in thẳng từ
                    trình duyệt và <b>tự co theo khổ máy in</b> bạn chọn (nhiệt K80, A6, A5, A4…). Tem / vận đơn thật của
                    sàn hoặc ĐVVC luôn giữ nguyên khổ gốc.
                </Typography.Paragraph>
                <Radio.Group value={size} onChange={(e) => setSize(e.target.value)} disabled={!canManage}>
                    <Space direction="vertical">
                        {SIZES.map((s) => (
                            <Radio key={s.value} value={s.value}>
                                {s.label} <Typography.Text type="secondary" style={{ fontSize: 12 }}>— {s.hint}</Typography.Text>
                            </Radio>
                        ))}
                    </Space>
                </Radio.Group>
            </Card>

            <Card title="Người gửi / Địa chỉ lấy hàng (phiếu giao hàng)">
                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>
                    Khai nhiều người gửi (tên · SĐT · địa chỉ lấy hàng). Khi in <b>phiếu giao hàng đơn thủ công</b>, chọn 1 bản ghi
                    để map vào khối <b>"Địa chỉ lấy hàng"</b> của phiếu. Bản <b>mặc định</b> được chọn sẵn. Bỏ trống ⇒ lấy theo kho của đơn.
                </Typography.Paragraph>
                <Space direction="vertical" size={10} style={{ width: '100%' }}>
                    {senders.map((s) => (
                        <div key={s.id} style={{ border: '1px solid #f0f0f0', borderRadius: 8, padding: 10 }}>
                            <Space style={{ width: '100%', marginBottom: 6 }} styles={{ item: { flex: 1 } }}>
                                <Input disabled={!canManage} placeholder="Tên người gửi / cửa hàng" value={s.name} onChange={(e) => patchSender(s.id, { name: e.target.value })} />
                                <Input disabled={!canManage} placeholder="Số điện thoại" style={{ maxWidth: 180 }} value={s.phone} onChange={(e) => patchSender(s.id, { phone: e.target.value })} />
                            </Space>
                            <Input.TextArea disabled={!canManage} placeholder="Địa chỉ lấy hàng đầy đủ (số nhà, đường, phường/xã, quận/huyện, tỉnh/TP)"
                                autoSize={{ minRows: 1, maxRows: 3 }} value={s.address} onChange={(e) => patchSender(s.id, { address: e.target.value })} />
                            <div style={{ marginTop: 8, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <Radio checked={!!s.is_default} disabled={!canManage} onChange={() => setDefault(s.id)}>Đặt làm mặc định</Radio>
                                <Button danger size="small" type="text" icon={<DeleteOutlined />} disabled={!canManage} onClick={() => removeSender(s.id)}>Xoá</Button>
                            </div>
                        </div>
                    ))}
                    <Button type="dashed" icon={<PlusOutlined />} disabled={!canManage} onClick={addSender}>Thêm người gửi</Button>
                </Space>
            </Card>

            <Card title="Nội dung in mặc định (đơn thủ công)">
                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>
                    Khi tạo đơn thủ công nếu bạn không nhập <b>"Nội dung"</b> ở tab <b>"Để in"</b>, hệ thống sẽ tự gắn nội dung dưới đây vào phiếu giao hàng.
                </Typography.Paragraph>
                <Input.TextArea
                    rows={4} maxLength={2000} disabled={!canManage}
                    value={defaultNote} onChange={(e) => setDefaultNote(e.target.value)}
                    placeholder={DEFAULT_NOTE_PLACEHOLDER}
                    showCount
                />
            </Card>

            <div>
                {canManage
                    ? <Button type="primary" loading={update.isPending} onClick={save}>Lưu cài đặt</Button>
                    : <Alert type="info" showIcon message="Chỉ Chủ sở hữu / Quản trị mới sửa được cài đặt này." />}
            </div>
        </Space>
    );
}
