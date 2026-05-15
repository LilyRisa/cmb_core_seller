import { useEffect, useState } from 'react';
import { Alert, App as AntApp, Button, Card, Input, Radio, Space, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan, useTenant, useUpdateTenant } from '@/lib/tenant';

/**
 * /settings/print — "Mẫu in":
 *   - **Khổ phiếu** (`print.label_size`): mỗi đơn 1 trang theo khổ này. Áp cho phiếu giao hàng / packing /
 *     hoá đơn. Tem AWB thật của sàn/ĐVVC giữ nguyên khổ gốc. SPEC 0013.
 *   - **Nội dung in mặc định** (`print.default_note`, SPEC 0021): khi tạo đơn manual nếu user không nhập
 *     "Nội dung" (tab "Để in"), hệ thống tự gắn nội dung này vào phiếu. Hỗ trợ multi-line.
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

export function SettingsPrintPage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const update = useUpdateTenant();
    const canManage = useCan('tenant.settings');
    const [size, setSize] = useState<string>(DEFAULT_SIZE);
    const [defaultNote, setDefaultNote] = useState<string>('');

    useEffect(() => {
        if (!tenant) return;
        const cur = (tenant.settings?.print ?? {}) as Record<string, unknown>;
        setSize(typeof cur.label_size === 'string' && cur.label_size ? cur.label_size : DEFAULT_SIZE);
        setDefaultNote(typeof cur.default_note === 'string' ? cur.default_note : '');
    }, [tenant]);

    const save = () => update.mutate({ settings: { print: { label_size: size, default_note: defaultNote.trim() || null } } }, {
        onSuccess: () => message.success('Đã lưu cài đặt phiếu in'),
        onError: (e) => message.error(errorMessage(e)),
    });

    if (!tenant) return <Card loading title="Mẫu in" />;

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Card title="Khổ phiếu in">
                <Typography.Paragraph type="secondary" style={{ marginBottom: 16 }}>
                    Khi in <b>phiếu giao hàng / packing slip / hoá đơn</b>, hệ thống render PDF theo khổ này, <b>mỗi đơn 1 trang</b>.
                    Tem / vận đơn <b>thật của sàn hoặc ĐVVC</b> luôn giữ nguyên khổ gốc của họ (không tự vẽ lại).
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

            <Card title="Nội dung in mặc định (đơn thủ công)">
                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>
                    Khi tạo đơn thủ công nếu bạn không nhập <b>"Nội dung"</b> ở tab <b>"Để in"</b>, hệ thống sẽ tự gắn nội dung dưới đây vào phiếu giao hàng.
                    Có thể là lời cảm ơn, chính sách đổi trả, hotline CSKH…
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
