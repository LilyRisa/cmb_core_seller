// Bộ chọn AI provider RIÊNG cho trợ lý Support ("Hỏi AI") — special-case của
// SettingRow cho key `help_assistant.provider_code`. Thay ô gõ code thủ công bằng
// Radio.Group lấy từ danh sách provider thật (/admin/ai-providers), gắn nhãn provider
// nào có embedding (RAG cần embedding; không có ⇒ rớt về keyword). Tự lưu khi chọn.
//
// Theo memory ui-avoid-select-prefer-radio: dùng Radio.Group (tập provider nhỏ),
// KHÔNG dùng <Select>.

import { Radio, Space, Spin, Tag, Typography } from 'antd';
import { useAiProviders } from '../lib/aiProviders';

export function HelpProviderSelect({ value, onSave }: { value: string | null; onSave: (v: string) => void }) {
    const { data, isLoading } = useAiProviders();
    if (isLoading) return <Spin size="small" />;

    const providers = data?.data ?? [];
    const current = (value as string) ?? '';
    const known = new Set(providers.map((p) => p.code));

    return (
        <Radio.Group value={current} onChange={(e) => onSave(e.target.value)}>
            <Space direction="vertical" size={6}>
                {/* Rỗng = tắt provider Support ⇒ widget Hỏi AI chạy tìm kiếm từ khoá. */}
                <Radio value="">
                    <Typography.Text>Tắt</Typography.Text>{' '}
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>— dùng tìm kiếm từ khoá (keyword)</Typography.Text>
                </Radio>

                {providers.map((p) => {
                    const hasEmbedding = !!p.capabilities?.embedding;
                    return (
                        <Radio key={p.code} value={p.code}>
                            <Space size={6} wrap>
                                <Typography.Text>{p.display_name ?? p.code}</Typography.Text>
                                <Typography.Text code style={{ fontSize: 11 }}>{p.code}</Typography.Text>
                                {hasEmbedding
                                    ? <Tag color="green">có embedding</Tag>
                                    : <Tag color="orange">không embedding → chỉ keyword</Tag>}
                                {!p.is_active && <Tag>đang tắt</Tag>}
                            </Space>
                        </Radio>
                    );
                })}

                {/* Code đã lưu nhưng không khớp provider nào (vd provider đã xoá / sai tên). */}
                {current !== '' && !known.has(current) && (
                    <Radio value={current}>
                        <Space size={6}>
                            <Typography.Text code style={{ fontSize: 11 }}>{current}</Typography.Text>
                            <Tag color="red">không tìm thấy provider</Tag>
                        </Space>
                    </Radio>
                )}
            </Space>
        </Radio.Group>
    );
}
