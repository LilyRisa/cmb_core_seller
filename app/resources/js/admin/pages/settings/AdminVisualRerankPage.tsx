// Trang admin "AI chấm ảnh" — chọn provider AI RIÊNG cho bước vision re-rank
// (chấm ảnh top-5). Tách hoàn toàn khỏi trang "Nhà cung cấp AI". SPEC 2026-07-05.
import { useEffect, useState } from 'react';
import { Card, Radio, Space, Typography, Tag, Button, Alert, message } from 'antd';
import { PictureOutlined, CheckCircleOutlined, CloseCircleOutlined, ExperimentOutlined } from '@ant-design/icons';
import { useVisualRerank, useSaveVisualRerank, useTestVisualRerank } from '../../lib/visualRerank';

const NONE = '';

export function AdminVisualRerankPage() {
    const { data, isLoading } = useVisualRerank();
    const save = useSaveVisualRerank();
    const test = useTestVisualRerank();
    const [selected, setSelected] = useState<string>(NONE);

    useEffect(() => {
        if (data) setSelected(data.selected_provider_code ?? NONE);
    }, [data]);

    const onSave = async () => {
        await save.mutateAsync(selected);
        message.success('Đã lưu provider chấm ảnh.');
    };

    const onTest = async () => {
        if (selected === NONE) {
            message.warning('Chọn một provider để thử.');
            return;
        }
        const r = await test.mutateAsync(selected);
        if (r.ok) message.success(`Gửi ảnh thử OK. Phản hồi: ${r.sample ?? '(rỗng)'}`);
        else message.error(`Thử thất bại: ${r.message ?? r.reason ?? 'lỗi'}`);
    };

    return (
        <Card
            loading={isLoading}
            title={<Space><PictureOutlined /> AI chấm ảnh (vision re-rank)</Space>}
        >
            <Alert
                type="info"
                showIcon
                style={{ marginBottom: 16 }}
                message="Provider này chỉ dùng để chấm ảnh khi khách gửi ảnh — độc lập với model chat."
                description="Chưa chọn (Không cấu hình) ⇒ dùng model chat của hội thoại. Provider phải có model hỗ trợ vision. Muốn model vision khác: tạo provider mới ở trang 'Nhà cung cấp AI' rồi chọn tại đây."
            />

            <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)}>
                <Space direction="vertical" style={{ width: '100%' }}>
                    <Radio value={NONE}>
                        <Typography.Text strong>(Không cấu hình)</Typography.Text>
                        <Typography.Text type="secondary"> — dùng model chat</Typography.Text>
                    </Radio>
                    {(data?.providers ?? []).filter((p) => p.is_active).map((p) => (
                        <Radio key={p.code} value={p.code}>
                            <Space>
                                <Typography.Text strong>{p.display_name || p.code}</Typography.Text>
                                <Typography.Text code>{p.default_model}</Typography.Text>
                                {p.vision
                                    ? <Tag color="green" icon={<CheckCircleOutlined />}>Có vision</Tag>
                                    : <Tag color="red" icon={<CloseCircleOutlined />}>Không vision</Tag>}
                            </Space>
                        </Radio>
                    ))}
                </Space>
            </Radio.Group>

            <div style={{ marginTop: 24 }}>
                <Space>
                    <Button type="primary" onClick={onSave} loading={save.isPending}>Lưu</Button>
                    <Button icon={<ExperimentOutlined />} onClick={onTest} loading={test.isPending} disabled={selected === NONE}>
                        Gửi ảnh thử
                    </Button>
                </Space>
            </div>
        </Card>
    );
}
