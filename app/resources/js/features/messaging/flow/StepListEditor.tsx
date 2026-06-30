import { Alert, Button, Dropdown, Input, Radio, Space, Tag, Typography, Upload } from 'antd';
import {
    ArrowDownOutlined,
    ArrowUpOutlined,
    DeleteOutlined,
    PaperClipOutlined,
    PlusOutlined,
    UploadOutlined,
} from '@ant-design/icons';
import type { RcFile } from 'antd/es/upload';
import { nanoid } from 'nanoid';
import type { UseMutationResult } from '@tanstack/react-query';
import type { FlowAttachment, FlowButton, FlowMediaKind, FlowStep, FlowStepType } from '@/lib/messagingFlows';
import { STEP_META, newStep } from './steps';

/** Kiểu hook upload truyền từ NodeConfigDrawer xuống. */
type UploadHook = UseMutationResult<FlowAttachment, Error, { file: File; kind: FlowMediaKind }>;

export interface StepListEditorProps {
    value: FlowStep[];
    onChange: (steps: FlowStep[]) => void;
    upload: UploadHook;
    readOnly?: boolean;
}

/**
 * Danh sách bước có thể sắp xếp (nút ↑/↓) cho node "Gửi tin".
 * Mỗi bước hiển thị editor phù hợp với loại (send_text / send_media / send_buttons).
 * Nút "+ Tạo bước" là Dropdown phân nhóm "Gửi tin" lấy từ STEP_META.
 *
 * Ràng buộc Phase 2A: tối đa 1 bước send_buttons, luôn là bước cuối.
 */
export function StepListEditor({ value, onChange, upload, readOnly }: StepListEditorProps) {
    const hasButtons = value.some((s) => s.type === 'send_buttons');
    const buttonsNotLast = hasButtons && value[value.length - 1]?.type !== 'send_buttons';

    const updateStep = (idx: number, patch: Partial<FlowStep>) =>
        onChange(value.map((s, i) => (i === idx ? ({ ...s, ...patch } as FlowStep) : s)));

    const deleteStep = (idx: number) => onChange(value.filter((_, i) => i !== idx));

    const moveStep = (idx: number, dir: -1 | 1) => {
        const next = [...value];
        const target = idx + dir;
        [next[idx], next[target]] = [next[target], next[idx]];
        onChange(next);
    };

    const addStep = (type: FlowStepType) => {
        const newS = newStep(type);
        // send_buttons phải là bước cuối — chèn bước mới TRƯỚC nó nếu đã tồn tại
        if (hasButtons && type !== 'send_buttons') {
            const next = [...value];
            next.splice(value.length - 1, 0, newS);
            onChange(next);
        } else {
            onChange([...value, newS]);
        }
    };

    const menuItems = (Object.keys(STEP_META) as FlowStepType[]).map((type) => ({
        key: type,
        icon: STEP_META[type].icon,
        label: STEP_META[type].label,
        disabled: type === 'send_buttons' && hasButtons,
    }));

    return (
        <div>
            {buttonsNotLast && (
                <Alert
                    type="warning"
                    showIcon
                    message='Bước "Gửi nút bấm" phải là bước cuối cùng trong danh sách'
                    style={{ marginBottom: 12 }}
                />
            )}

            {value.length === 0 && (
                <Typography.Text type="secondary" style={{ display: 'block', marginBottom: 12, fontSize: 12 }}>
                    Chưa có bước nào. Nhấn "+ Tạo bước" để thêm.
                </Typography.Text>
            )}

            {value.map((step, idx) => {
                const isSendButtons = step.type === 'send_buttons';
                // send_buttons phải là bước cuối — cấm di chuyển nó lên hoặc di chuyển bước khác xuống dưới nó
                const nextIsSendButtons = value[idx + 1]?.type === 'send_buttons';
                const canUp = idx > 0 && !isSendButtons;
                const canDown = idx < value.length - 1 && !isSendButtons && !nextIsSendButtons;

                return (
                    <div
                        key={step.id}
                        style={{
                            border: '1px solid #e8e8e8',
                            borderRadius: 8,
                            padding: '10px 12px',
                            marginBottom: 10,
                            background: '#fafafa',
                        }}
                    >
                        {/* Header: tên bước + nút điều khiển */}
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 }}>
                            <Space size={6}>
                                {STEP_META[step.type].icon}
                                <Typography.Text strong style={{ fontSize: 13 }}>
                                    {STEP_META[step.type].label}
                                </Typography.Text>
                            </Space>
                            <Space size={2}>
                                <Button
                                    size="small"
                                    type="text"
                                    icon={<ArrowUpOutlined />}
                                    disabled={readOnly || !canUp}
                                    onClick={() => moveStep(idx, -1)}
                                    title="Di chuyển lên"
                                />
                                <Button
                                    size="small"
                                    type="text"
                                    icon={<ArrowDownOutlined />}
                                    disabled={readOnly || !canDown}
                                    onClick={() => moveStep(idx, 1)}
                                    title="Di chuyển xuống"
                                />
                                <Button
                                    size="small"
                                    type="text"
                                    danger
                                    icon={<DeleteOutlined />}
                                    disabled={readOnly}
                                    onClick={() => deleteStep(idx)}
                                    title="Xoá bước"
                                />
                            </Space>
                        </div>

                        {/* Editor theo loại bước */}
                        {step.type === 'send_text' && (
                            <Input.TextArea
                                rows={3}
                                maxLength={2000}
                                placeholder="Nội dung văn bản..."
                                value={step.text ?? ''}
                                disabled={readOnly}
                                onChange={(e) => updateStep(idx, { text: e.target.value })}
                            />
                        )}

                        {step.type === 'send_media' && (
                            <SendMediaEditor
                                step={step}
                                readOnly={readOnly}
                                upload={upload}
                                onChange={(patch) => updateStep(idx, patch)}
                            />
                        )}

                        {step.type === 'send_buttons' && (
                            <SendButtonsEditor
                                step={step}
                                readOnly={readOnly}
                                onChange={(patch) => updateStep(idx, patch)}
                            />
                        )}
                    </div>
                );
            })}

            {!readOnly && (
                <Dropdown
                    menu={{
                        items: [{ type: 'group', label: 'Gửi tin', children: menuItems }],
                        onClick: ({ key }) => addStep(key as FlowStepType),
                    }}
                    trigger={['click']}
                >
                    <Button type="dashed" icon={<PlusOutlined />} block>
                        + Tạo bước
                    </Button>
                </Dropdown>
            )}
        </div>
    );
}

/* ─────────────────────────── SendMediaEditor ─────────────────────────── */

const KIND_ACCEPT: Record<FlowMediaKind, string> = {
    image: 'image/*',
    video: 'video/*',
    audio: 'audio/*',
    file: '.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv',
};

const KIND_LABEL_VN: Record<FlowMediaKind, string> = {
    image: 'Ảnh',
    video: 'Video',
    audio: 'Âm thanh',
    file: 'File',
};

function SendMediaEditor({
    step,
    readOnly,
    upload,
    onChange,
}: {
    step: FlowStep;
    readOnly?: boolean;
    upload: UploadHook;
    onChange: (patch: Partial<FlowStep>) => void;
}) {
    const kind = step.kind ?? 'image';

    const handleBeforeUpload = (file: RcFile) => {
        upload.mutate(
            { file, kind },
            {
                // Giữ nguyên kind trả về từ server (kể cả 'audio')
                onSuccess: (att) => onChange({ attachment: att, kind: att.kind }),
            },
        );
        return false; // tự upload, không để AntD gửi
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            <Radio.Group
                optionType="button"
                buttonStyle="solid"
                size="small"
                value={kind}
                disabled={readOnly}
                onChange={(e) =>
                    onChange({ kind: e.target.value as 'image' | 'video' | 'file', attachment: undefined })
                }
            >
                <Radio.Button value="image">Ảnh</Radio.Button>
                <Radio.Button value="video">Video</Radio.Button>
                <Radio.Button value="file">File</Radio.Button>
            </Radio.Group>

            {step.attachment ? (
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 8,
                        border: '1px solid #f0f0f0',
                        borderRadius: 6,
                        padding: '4px 8px',
                    }}
                >
                    <PaperClipOutlined />
                    <Tag style={{ fontSize: 11 }}>{KIND_LABEL_VN[kind]}</Tag>
                    <span
                        style={{
                            flex: 1,
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                            fontSize: 12,
                        }}
                    >
                        {step.attachment.filename ?? step.attachment.storage_path.split('/').pop()}
                    </span>
                    {!readOnly && (
                        <Button
                            size="small"
                            type="text"
                            danger
                            icon={<DeleteOutlined />}
                            onClick={() => onChange({ attachment: undefined })}
                        />
                    )}
                </div>
            ) : (
                <Upload
                    beforeUpload={handleBeforeUpload}
                    showUploadList={false}
                    disabled={readOnly}
                    accept={KIND_ACCEPT[kind]}
                >
                    <Button icon={<UploadOutlined />} loading={upload.isPending} disabled={readOnly} size="small">
                        Chọn {KIND_LABEL_VN[kind].toLowerCase()}
                    </Button>
                </Upload>
            )}
        </div>
    );
}

/* ─────────────────────────── SendButtonsEditor ─────────────────────────── */

function SendButtonsEditor({
    step,
    readOnly,
    onChange,
}: {
    step: FlowStep;
    readOnly?: boolean;
    onChange: (patch: Partial<FlowStep>) => void;
}) {
    const buttons = step.buttons ?? [];

    const updateBtn = (idx: number, patch: Partial<FlowButton>) =>
        onChange({ buttons: buttons.map((b, i) => (i === idx ? { ...b, ...patch } : b)) });

    const addBtn = () => {
        if (buttons.length >= 3) return;
        onChange({ buttons: [...buttons, { id: nanoid(8), label: '', type: 'postback' }] });
    };

    const removeBtn = (idx: number) =>
        onChange({ buttons: buttons.filter((_, i) => i !== idx) });

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            <Input.TextArea
                rows={3}
                maxLength={640}
                placeholder="Nội dung tin nhắn kèm nút bấm..."
                value={step.text ?? ''}
                disabled={readOnly}
                onChange={(e) => onChange({ text: e.target.value })}
            />

            <Typography.Text strong style={{ fontSize: 12 }}>
                Nút bấm (tối đa 3)
            </Typography.Text>

            {buttons.map((btn, bidx) => (
                <StandaloneButtonRow
                    key={btn.id}
                    button={btn}
                    readOnly={readOnly}
                    onUpdate={(patch) => updateBtn(bidx, patch)}
                    onRemove={() => removeBtn(bidx)}
                />
            ))}

            <Button
                type="dashed"
                icon={<PlusOutlined />}
                size="small"
                disabled={readOnly || buttons.length >= 3}
                onClick={addBtn}
            >
                Thêm nút
            </Button>

            <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                Mỗi nút "trả lời bước" tạo 1 nhánh ra trên sơ đồ — nối nhánh tới bước kế tiếp.
            </Typography.Text>
        </div>
    );
}

/* ─────────────────────────── StandaloneButtonRow ─────────────────────────── */

function StandaloneButtonRow({
    button,
    readOnly,
    onUpdate,
    onRemove,
}: {
    button: FlowButton;
    readOnly?: boolean;
    onUpdate: (patch: Partial<FlowButton>) => void;
    onRemove: () => void;
}) {
    return (
        <div style={{ border: '1px solid #f0f0f0', borderRadius: 6, padding: 8 }}>
            <Space.Compact block>
                <Input
                    placeholder="Nhãn nút (vd: Mua hàng)"
                    maxLength={20}
                    value={button.label}
                    disabled={readOnly}
                    onChange={(e) => onUpdate({ label: e.target.value })}
                />
                <Button danger icon={<DeleteOutlined />} disabled={readOnly} onClick={onRemove} />
            </Space.Compact>
            <Radio.Group
                size="small"
                optionType="button"
                value={button.type}
                disabled={readOnly}
                style={{ marginTop: 6 }}
                onChange={(e) => onUpdate({ type: e.target.value as 'postback' | 'url' })}
            >
                <Radio.Button value="postback">Trả lời bước</Radio.Button>
                <Radio.Button value="url">Mở liên kết</Radio.Button>
            </Radio.Group>
            {button.type === 'url' && (
                <Input
                    placeholder="https://..."
                    value={button.url ?? ''}
                    disabled={readOnly}
                    style={{ marginTop: 6 }}
                    onChange={(e) => onUpdate({ url: e.target.value })}
                />
            )}
        </div>
    );
}
