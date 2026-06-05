import { useState } from 'react';
import { Button, Input, Space, Tag, Tooltip, Typography } from 'antd';
import { CloseOutlined, CopyOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';

const { Text } = Typography;

export function AdSetSelector() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const selectAdSet = useDraftStore((s) => s.selectAdSet);
    const addAdSet = useDraftStore((s) => s.addAdSet);
    const removeAdSet = useDraftStore((s) => s.removeAdSet);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);
    const duplicateAdSet = useDraftStore((s) => s.duplicateAdSet);

    const [editingKey, setEditingKey] = useState<string | null>(null);
    const [editingName, setEditingName] = useState('');

    const selectedAdSet = adsets.find((a) => a.key === selectedAdSetKey);

    function startEdit(key: string, currentName: string, e: React.MouseEvent) {
        e.stopPropagation();
        setEditingKey(key);
        setEditingName(currentName);
    }

    function commitEdit() {
        if (editingKey != null && editingName.trim() !== '') {
            updateAdSet(editingKey, { name: editingName.trim() });
        }
        setEditingKey(null);
    }

    function handleEditKeyDown(e: React.KeyboardEvent) {
        if (e.key === 'Enter') commitEdit();
        if (e.key === 'Escape') setEditingKey(null);
    }

    return (
        <div style={{ marginBottom: 16 }}>
            <Space size={4} wrap>
                <Text type="secondary" style={{ fontSize: 12, marginRight: 4 }}>
                    Nhóm quảng cáo:
                </Text>

                {adsets.map((adset) => {
                    const isSelected = adset.key === selectedAdSetKey;
                    const isEditing = editingKey === adset.key;

                    return (
                        <Tag
                            key={adset.key}
                            color={isSelected ? 'blue' : undefined}
                            style={{
                                cursor: 'pointer',
                                userSelect: 'none',
                                padding: '2px 8px',
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: 4,
                            }}
                            onClick={() => selectAdSet(adset.key)}
                        >
                            {isEditing ? (
                                <Input
                                    size="small"
                                    value={editingName}
                                    onChange={(e) => setEditingName(e.target.value)}
                                    onBlur={commitEdit}
                                    onKeyDown={handleEditKeyDown}
                                    autoFocus
                                    style={{ width: 100, padding: '0 4px', height: 20, fontSize: 12 }}
                                    onClick={(e) => e.stopPropagation()}
                                />
                            ) : (
                                <span>{adset.name}</span>
                            )}

                            {isSelected && !isEditing && (
                                <Tooltip title="Đổi tên">
                                    <EditOutlined
                                        style={{ fontSize: 11, marginLeft: 2, color: '#1677ff' }}
                                        onClick={(e) => startEdit(adset.key, adset.name, e)}
                                    />
                                </Tooltip>
                            )}

                            {isSelected && !isEditing && (
                                <Tooltip title="Nhân bản nhóm (Ctrl+C, Ctrl+V)">
                                    <CopyOutlined
                                        style={{ fontSize: 11, marginLeft: 2, color: '#1677ff' }}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            duplicateAdSet(adset.key);
                                        }}
                                    />
                                </Tooltip>
                            )}

                            {adsets.length > 1 && (
                                <Tooltip title="Xoá nhóm này">
                                    <CloseOutlined
                                        style={{ fontSize: 10, marginLeft: 2, color: '#999' }}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            removeAdSet(adset.key);
                                        }}
                                    />
                                </Tooltip>
                            )}
                        </Tag>
                    );
                })}

                <Button
                    size="small"
                    icon={<PlusOutlined />}
                    onClick={() => addAdSet()}
                    style={{ height: 24, fontSize: 12 }}
                >
                    Thêm nhóm
                </Button>
            </Space>

            {selectedAdSet != null && (
                <div style={{ marginTop: 4 }}>
                    <Text type="secondary" style={{ fontSize: 11 }}>
                        Đang chỉnh sửa:{' '}
                        <Text strong style={{ fontSize: 11 }}>
                            {selectedAdSet.name}
                        </Text>
                    </Text>
                </div>
            )}
        </div>
    );
}
