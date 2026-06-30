import { AppstoreOutlined, MessageOutlined, PictureOutlined } from '@ant-design/icons';
import { Tag } from 'antd';
import type { ReactNode } from 'react';
import type { FlowStep, FlowStepType } from '@/lib/messagingFlows';

/**
 * Registry bước (step) cho node-with-steps Phase 2A.
 * Thêm loại bước mới = thêm 1 entry vào STEP_META.
 * Chuỗi type phải ổn định (lưu trong JSONB) — đừng đổi về sau.
 */

export interface StepMeta {
    label: string;
    group: 'send';
    icon: ReactNode;
    defaultFn: () => Omit<FlowStep, 'id'>;
}

export const STEP_META: Record<FlowStepType, StepMeta> = {
    send_text: {
        label: 'Gửi văn bản',
        group: 'send',
        icon: <MessageOutlined />,
        defaultFn: () => ({ type: 'send_text', text: '' }),
    },
    send_media: {
        label: 'Gửi ảnh/video/file',
        group: 'send',
        icon: <PictureOutlined />,
        defaultFn: () => ({ type: 'send_media', kind: 'image' }),
    },
    send_buttons: {
        label: 'Gửi nút bấm',
        group: 'send',
        icon: <AppstoreOutlined />,
        defaultFn: () => ({ type: 'send_buttons', text: '', buttons: [] }),
    },
};

/** Tạo 1 bước mới với id ngẫu nhiên và dữ liệu mặc định theo loại. */
export function newStep(type: FlowStepType): FlowStep {
    return { id: crypto.randomUUID(), ...STEP_META[type].defaultFn() };
}

const KIND_LABEL: Record<NonNullable<FlowStep['kind']>, string> = {
    image: 'Ảnh',
    video: 'Video',
    audio: 'Âm thanh',
    file: 'File',
};

/** Mini-card chỉ đọc xem trước 1 bước — dùng trong node card và danh sách step. */
export function StepViewer({ step }: { step: FlowStep }) {
    if (step.type === 'send_text') {
        const preview = step.text?.trim();
        return (
            <span style={{ color: preview ? '#262626' : '#bfbfbf', fontSize: 12 }}>
                {preview
                    ? preview.length > 60
                        ? preview.slice(0, 60) + '…'
                        : preview
                    : '(chưa có nội dung)'}
            </span>
        );
    }

    if (step.type === 'send_media') {
        const label = step.kind ? KIND_LABEL[step.kind] : 'Tệp';
        return <Tag color="blue" style={{ fontSize: 11 }}>{label}</Tag>;
    }

    if (step.type === 'send_buttons') {
        const buttons = step.buttons ?? [];
        return (
            <span style={{ fontSize: 12 }}>
                {step.text?.trim() && (
                    <span style={{ color: '#262626', marginRight: 4 }}>
                        {step.text.length > 40 ? step.text.slice(0, 40) + '…' : step.text}
                    </span>
                )}
                {buttons.length === 0 && <span style={{ color: '#bfbfbf' }}>(chưa có nút)</span>}
                {buttons.map((b) => (
                    <Tag key={b.id} color="purple" style={{ fontSize: 11, marginBottom: 2 }}>
                        {b.label || '(nút)'}
                    </Tag>
                ))}
            </span>
        );
    }

    // fallback cho loại step chưa biết
    return <span style={{ color: '#bfbfbf', fontSize: 12 }}>{step.type}</span>;
}
