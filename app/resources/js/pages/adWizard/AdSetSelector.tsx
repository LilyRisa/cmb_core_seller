import { useState } from 'react';
import { Button, Dropdown, Input, Segmented, Space, Tag, Tooltip, Typography } from 'antd';
import { CloseOutlined, CopyOutlined, EditOutlined, ExperimentOutlined, PlusOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import type { AbVariable } from '@/lib/adWizard';

const { Text } = Typography;

const AB_VARIABLE_LABEL: Record<AbVariable, string> = {
    creative: 'Nội dung',
    audience: 'Đối tượng',
    placement: 'Vị trí',
};

export function AdSetSelector() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const selectAdSet = useDraftStore((s) => s.selectAdSet);
    const addAdSet = useDraftStore((s) => s.addAdSet);
    const removeAdSet = useDraftStore((s) => s.removeAdSet);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);
    const duplicateAdSet = useDraftStore((s) => s.duplicateAdSet);
    const createAbTest = useDraftStore((s) => s.createAbTest);

    const [editingKey, setEditingKey] = useState<string | null>(null);
    const [editingName, setEditingName] = useState('');
    const [abVariable, setAbVariable] = useState<AbVariable>('creative');
    const [abOpen, setAbOpen] = useState(false);

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

                            {adset.experiment != null && !isEditing && (
                                <Tooltip title={`A/B test theo ${AB_VARIABLE_LABEL[adset.experiment.variable]}`}>
                                    <ExperimentOutlined style={{ fontSize: 11, color: '#722ed1' }} />
                                </Tooltip>
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

                {selectedAdSetKey != null && (
                    <Dropdown
                        open={abOpen}
                        onOpenChange={setAbOpen}
                        trigger={['click']}
                        dropdownRender={() => (
                            <div style={{ background: '#fff', padding: 12, borderRadius: 8, boxShadow: '0 2px 8px rgba(0,0,0,0.15)', width: 280 }}>
                                <Text strong style={{ fontSize: 12 }}>Tạo A/B test — chọn biến số thử nghiệm</Text>
                                <div style={{ margin: '8px 0' }}>
                                    <Segmented
                                        size="small"
                                        value={abVariable}
                                        onChange={(v) => setAbVariable(v as AbVariable)}
                                        options={(Object.keys(AB_VARIABLE_LABEL) as AbVariable[]).map((v) => ({ label: AB_VARIABLE_LABEL[v], value: v }))}
                                    />
                                </div>
                                <Text type="secondary" style={{ fontSize: 11, display: 'block', marginBottom: 8 }}>
                                    Tạo 2 biến thể [A]/[B] từ nhóm đang chọn; chỉ khác nhau ở <b>{AB_VARIABLE_LABEL[abVariable]}</b>. So sánh chỉ số ở báo cáo sau khi chạy.
                                </Text>
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<ExperimentOutlined />}
                                    onClick={() => { createAbTest(selectedAdSetKey, abVariable); setAbOpen(false); }}
                                    block
                                >
                                    Tạo biến thể A/B
                                </Button>
                            </div>
                        )}
                    >
                        <Button size="small" icon={<ExperimentOutlined />} style={{ height: 24, fontSize: 12 }}>
                            A/B Test
                        </Button>
                    </Dropdown>
                )}
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
