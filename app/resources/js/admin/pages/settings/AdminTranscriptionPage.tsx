// Trang admin "AI chuyển giọng nói (STT)" — chọn provider AI RIÊNG cho bước
// transcribe tin nhắn thoại. Tách hoàn toàn khỏi trang "Nhà cung cấp AI". SPEC 2026-07-05.
import { useEffect, useState } from 'react';
import { Card, Radio, Space, Typography, Tag, Button, Alert, message } from 'antd';
import { AudioOutlined, CheckCircleOutlined, CloseCircleOutlined, QuestionCircleOutlined, ExperimentOutlined } from '@ant-design/icons';
import { useTranscriptionConfig, useSaveTranscription, useTestTranscription } from '../../lib/aiTranscription';

const NONE = '';

export function AdminTranscriptionPage() {
    const { data, isLoading } = useTranscriptionConfig();
    const save = useSaveTranscription();
    const test = useTestTranscription();
    const [selected, setSelected] = useState<string>(NONE);

    useEffect(() => {
        if (data) setSelected(data.selected_provider_code ?? NONE);
    }, [data]);

    const onSave = async () => {
        await save.mutateAsync(selected);
        message.success('Đã lưu provider chuyển giọng nói.');
    };

    const onTest = async () => {
        if (selected === NONE) {
            message.warning('Chọn một provider để thử.');
            return;
        }
        const r = await test.mutateAsync(selected);
        if (r.ok) message.success(`Thử transcribe OK. Phản hồi: ${r.text ?? '(rỗng)'}`);
        else message.error(`Thử thất bại: ${r.message ?? r.reason ?? 'lỗi'}`);
    };

    return (
        <Card
            loading={isLoading}
            title={<Space><AudioOutlined /> AI chuyển giọng nói (STT)</Space>}
        >
            <Alert
                type="info"
                showIcon
                style={{ marginBottom: 16 }}
                message="Provider này chỉ dùng để chuyển giọng nói khi khách gửi tin nhắn thoại — độc lập với model chat."
                description="Chưa chọn (Không cấu hình) ⇒ tắt tính năng chuyển giọng nói. Phải bấm 'Thử transcribe' thành công (Đã xác minh) mới lưu được. Muốn model STT khác: tạo provider mới ở trang 'Nhà cung cấp AI' rồi chọn tại đây."
            />

            <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)}>
                <Space direction="vertical" style={{ width: '100%' }}>
                    <Radio value={NONE}>
                        <Typography.Text strong>(Không cấu hình)</Typography.Text>
                        <Typography.Text type="secondary"> — tắt chuyển giọng nói</Typography.Text>
                    </Radio>
                    {(data?.providers ?? []).filter((p) => p.is_active).map((p) => (
                        <Radio key={p.code} value={p.code}>
                            <Space>
                                <Typography.Text strong>{p.display_name || p.code}</Typography.Text>
                                <Typography.Text code>{p.default_model}</Typography.Text>
                                {p.transcription_verified === true
                                    ? <Tag color="green" icon={<CheckCircleOutlined />}>Đã xác minh</Tag>
                                    : p.transcription_verified === false
                                        ? <Tag color="red" icon={<CloseCircleOutlined />}>Thất bại</Tag>
                                        : <Tag icon={<QuestionCircleOutlined />}>Chưa kiểm tra</Tag>}
                            </Space>
                        </Radio>
                    ))}
                </Space>
            </Radio.Group>

            <div style={{ marginTop: 24 }}>
                <Space>
                    <Button
                        type="primary"
                        onClick={onSave}
                        loading={save.isPending}
                        disabled={selected !== NONE && data?.providers.find((p) => p.code === selected)?.transcription_verified !== true}
                    >
                        Lưu
                    </Button>
                    <Button icon={<ExperimentOutlined />} onClick={onTest} loading={test.isPending} disabled={selected === NONE}>
                        Thử transcribe
                    </Button>
                </Space>
            </div>
        </Card>
    );
}
