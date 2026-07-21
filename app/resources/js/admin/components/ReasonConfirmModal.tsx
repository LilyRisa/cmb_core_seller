// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.1 — "High-impact" tier
// của xác nhận rủi ro: hành động ảnh hưởng khả năng vận hành của tenant, tài khoản người khác,
// hoặc tiền (khoá tenant, khoá/mở tài khoản user, đổi gói, xoá kênh đang hoạt động...). Bắt buộc
// gõ lý do ≥10 ký tự, validate qua Form.rules (báo lỗi ngay dưới field) thay vì message.error rời
// rạc. Tier "Standard" (bật/tắt, xoá bản ghi không ảnh hưởng người khác) tiếp tục dùng
// `Popconfirm` trực tiếp — không cần qua hook này.
//
// Heuristic cho hành động mới chưa liệt kê trong spec: High-impact nếu có thể khoá tenant/user
// khỏi tài khoản của họ, đảo ngược tiền đã thu/nợ, hoặc khó hoàn tác nếu không có CSKH can thiệp.
//
// Export chính là hook `useReasonConfirm()` (không phải component `<ReasonConfirmModal>` như tên
// file/spec §5.1 gợi ý) — hook phù hợp hơn vì cần truy cập `App.useApp()` (modal/message) theo
// đúng context gọi, xem `ReasonConfirmOptions` bên dưới cho API.

import type { ReactNode } from 'react';
import { App, Form, Input, Typography } from 'antd';
import { errorMessage } from '@/lib/api';

export interface ReasonConfirmOptions {
    title: ReactNode;
    danger?: boolean;
    warningText?: ReactNode;
    okText?: string;
    reasonLabel?: string;
    reasonPlaceholder?: string;
    onConfirm: (reason: string) => Promise<void>;
}

export function useReasonConfirm() {
    const { modal, message } = App.useApp();
    const [form] = Form.useForm<{ reason: string }>();

    return function confirmWithReason(opts: ReasonConfirmOptions) {
        form.resetFields();
        modal.confirm({
            title: opts.title,
            okText: opts.okText ?? 'Xác nhận',
            okType: opts.danger ? 'danger' : 'primary',
            cancelText: 'Huỷ',
            content: (
                <div>
                    {opts.warningText && (
                        <Typography.Paragraph type="warning" style={{ marginBottom: 8 }}>
                            {opts.warningText}
                        </Typography.Paragraph>
                    )}
                    <Form form={form} layout="vertical" style={{ marginTop: 12 }}>
                        <Form.Item
                            name="reason"
                            label={opts.reasonLabel ?? 'Lý do (≥10 ký tự — sẽ ghi vào audit log)'}
                            rules={[{ required: true, min: 10, whitespace: true, message: 'Lý do phải có tối thiểu 10 ký tự.' }]}
                        >
                            <Input.TextArea rows={3} placeholder={opts.reasonPlaceholder} />
                        </Form.Item>
                    </Form>
                </div>
            ),
            onOk: async () => {
                const values = await form.validateFields();
                try {
                    await opts.onConfirm(values.reason.trim());
                } catch (e) {
                    message.error(errorMessage(e));
                    throw e;
                }
            },
        });
    };
}
