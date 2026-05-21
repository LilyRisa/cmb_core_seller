import { useState } from 'react';
import { App as AntApp, Button, ColorPicker, Empty, Input, Modal, Popconfirm, Space, Tag, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { type MessagingTag, useDeleteTag, useMessagingTags, useSaveTag } from '@/lib/messaging';

const PRESETS = ['#2563EB', '#16A34A', '#DC2626', '#D97706', '#7C3AED', '#0891B2', '#DB2777', '#475569'];

export function TagManagerModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const { data: tags } = useMessagingTags();
    const save = useSaveTag();
    const del = useDeleteTag();
    const [name, setName] = useState('');
    const [color, setColor] = useState('#2563EB');
    const [editingId, setEditingId] = useState<number | null>(null);

    const reset = () => { setName(''); setColor('#2563EB'); setEditingId(null); };

    const submit = () => {
        const n = name.trim();
        if (!n) { message.warning('Nhập tên thẻ.'); return; }
        save.mutate({ id: editingId ?? undefined, name: n, color }, {
            onSuccess: () => { reset(); message.success(editingId ? 'Đã cập nhật thẻ.' : 'Đã tạo thẻ.'); },
            onError: (e) => message.error(errorMessage(e, 'Không lưu được thẻ.')),
        });
    };

    return (
        <Modal title="Quản lý thẻ" open={open} onCancel={() => { reset(); onClose(); }} footer={null} width={460}>
            <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                <Space.Compact style={{ width: '100%' }}>
                    <ColorPicker value={color} presets={[{ label: 'Gợi ý', colors: PRESETS }]} onChange={(c) => setColor(c.toHexString())} />
                    <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Tên thẻ" maxLength={40} onPressEnter={submit} />
                    <Button type="primary" icon={<PlusOutlined />} loading={save.isPending} onClick={submit}>
                        {editingId ? 'Lưu' : 'Thêm'}
                    </Button>
                    {editingId && <Button onClick={reset}>Huỷ</Button>}
                </Space.Compact>

                {(tags ?? []).length === 0 ? (
                    <Empty description="Chưa có thẻ nào" />
                ) : (
                    <Space direction="vertical" size={6} style={{ display: 'flex' }}>
                        {(tags ?? []).map((t: MessagingTag) => (
                            <div key={t.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <Tag color={t.color} style={{ cursor: 'pointer' }} onClick={() => { setEditingId(t.id); setName(t.name); setColor(t.color); }}>{t.name}</Tag>
                                <Popconfirm title="Xoá thẻ này?" description="Sẽ gỡ thẻ khỏi mọi hội thoại." okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                                    onConfirm={() => del.mutate(t.id, { onSuccess: () => message.success('Đã xoá thẻ.'), onError: (e) => message.error(errorMessage(e)) })}>
                                    <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                                </Popconfirm>
                            </div>
                        ))}
                    </Space>
                )}
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>Bấm vào thẻ để sửa tên/màu.</Typography.Text>
            </Space>
        </Modal>
    );
}
