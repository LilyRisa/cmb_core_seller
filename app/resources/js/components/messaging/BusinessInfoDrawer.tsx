import { useEffect } from 'react';
import { Drawer, Form, Input, Button, Space, App as AntApp } from 'antd';
import type { BusinessInfo } from '@/lib/messagingConfig';
import { useSetChannelBusinessInfo, useBulkSetChannelBusinessInfo } from '@/lib/messagingConfig';
import { errorMessage } from '@/lib/api';

const FIELDS: { name: keyof BusinessInfo; label: string; textarea?: boolean }[] = [
    { name: 'shop_name', label: 'Tên shop' },
    { name: 'phone', label: 'Số điện thoại' },
    { name: 'address', label: 'Địa chỉ' },
    { name: 'email', label: 'Email' },
    { name: 'working_hours', label: 'Giờ làm việc' },
    { name: 'website', label: 'Website' },
    { name: 'warranty_policy', label: 'Chính sách bảo hành', textarea: true },
    { name: 'extra_note', label: 'Thông tin thêm', textarea: true },
];

/**
 * Drawer nhập/sửa thông tin cửa hàng theo page — AI dùng để trả lời có ngữ cảnh
 * (SĐT, địa chỉ, giờ làm việc, chính sách bảo hành...).
 *
 * Hai chế độ:
 * - Đơn lẻ: truyền `channelId` + `initial` (prefill từ page đang chọn).
 * - Hàng loạt: truyền `bulkIds` (nhiều page đã tick chọn) — form trống, áp dụng
 *   cùng một bộ thông tin cho tất cả (ghi đè), bỏ qua `channelId`/`initial`.
 */
export function BusinessInfoDrawer({
    open,
    channelId,
    initial,
    bulkIds,
    onClose,
    onSaved,
}: {
    open: boolean;
    channelId: number | null;
    initial: BusinessInfo | null;
    /** Danh sách id áp dụng hàng loạt — có giá trị thì bỏ qua channelId/initial. */
    bulkIds?: number[];
    onClose: () => void;
    /** Gọi sau khi lưu thành công (vd để bỏ chọn các page đã áp dụng hàng loạt). */
    onSaved?: () => void;
}) {
    const { message } = AntApp.useApp();
    const [form] = Form.useForm<BusinessInfo>();
    const save = useSetChannelBusinessInfo();
    const bulkSave = useBulkSetChannelBusinessInfo();
    const isBulk = (bulkIds?.length ?? 0) > 0;

    useEffect(() => {
        if (open) form.setFieldsValue(isBulk ? {} : (initial ?? {}));
    }, [open, initial, isBulk, form]);

    const onSave = async () => {
        try {
            const values = await form.validateFields();
            if (isBulk) {
                await bulkSave.mutateAsync({ ids: bulkIds!, business_info: values });
                message.success(`Đã áp dụng thông tin cửa hàng cho ${bulkIds!.length} trang.`);
            } else {
                if (channelId == null) return;
                await save.mutateAsync({ id: channelId, business_info: values });
                message.success('Đã lưu thông tin cửa hàng.');
            }
            onSaved?.();
            onClose();
        } catch (e) {
            // form.validateFields() reject không đi qua đây (không có field bắt buộc) — chỉ lỗi API.
            message.error(errorMessage(e, 'Không lưu được thông tin cửa hàng.'));
        }
    };

    return (
        <Drawer
            title={isBulk ? `Áp dụng thông tin cửa hàng cho ${bulkIds!.length} trang` : 'Thông tin cửa hàng'}
            open={open}
            onClose={onClose}
            width={420}
            destroyOnClose
            extra={
                <Space>
                    <Button onClick={onClose}>Huỷ</Button>
                    <Button type="primary" loading={save.isPending || bulkSave.isPending} onClick={onSave}>Lưu</Button>
                </Space>
            }
        >
            <Form form={form} layout="vertical">
                {FIELDS.map((f) => (
                    <Form.Item key={f.name} name={f.name} label={f.label}>
                        {f.textarea ? <Input.TextArea rows={3} /> : <Input />}
                    </Form.Item>
                ))}
            </Form>
        </Drawer>
    );
}
