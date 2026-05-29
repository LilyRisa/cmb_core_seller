import { Handle, Position, type NodeProps } from '@xyflow/react';
import {
    AppstoreOutlined,
    BranchesOutlined,
    ClockCircleOutlined,
    FlagOutlined,
    MessageOutlined,
    PaperClipOutlined,
    PlayCircleOutlined,
    RobotOutlined,
} from '@ant-design/icons';
import type { ReactNode } from 'react';
import type { FlowNodeData, FlowNodeType } from '@/lib/messagingFlows';

/**
 * Metadata + thành phần node cho canvas reactflow. Mỗi loại node = 1 card + handle.
 * Thêm loại node mới (S5: AI, hành động) = thêm 1 mục NODE_META + 1 card ở đây.
 */

export interface NodeMeta {
    type: FlowNodeType;
    label: string;
    group: 'start' | 'send' | 'wait' | 'branch' | 'ai' | 'end';
    icon: ReactNode;
    /** Có thể kéo từ palette vào canvas (trigger là entry, tạo sẵn — không kéo thêm). */
    draggable: boolean;
}

export const NODE_META: NodeMeta[] = [
    { type: 'trigger', label: 'Bắt đầu', group: 'start', icon: <PlayCircleOutlined />, draggable: false },
    { type: 'send_message', label: 'Gửi tin nhắn', group: 'send', icon: <MessageOutlined />, draggable: true },
    { type: 'send_buttons', label: 'Gửi tin có nút bấm', group: 'send', icon: <AppstoreOutlined />, draggable: true },
    { type: 'wait_reply', label: 'Chờ khách trả lời', group: 'wait', icon: <ClockCircleOutlined />, draggable: true },
    { type: 'condition', label: 'Rẽ nhánh theo từ khoá', group: 'branch', icon: <BranchesOutlined />, draggable: true },
    { type: 'ai_reply', label: 'AI trả lời', group: 'ai', icon: <RobotOutlined />, draggable: true },
    { type: 'end', label: 'Kết thúc', group: 'end', icon: <FlagOutlined />, draggable: true },
];

export const GROUP_LABELS: Record<NodeMeta['group'], string> = {
    start: 'Bắt đầu',
    send: 'Gửi tin',
    wait: 'Hỏi / Chờ',
    branch: 'Rẽ nhánh',
    ai: 'AI',
    end: 'Kết thúc',
};

export function metaFor(type: FlowNodeType): NodeMeta | undefined {
    return NODE_META.find((m) => m.type === type);
}

/** Dữ liệu mặc định khi thả 1 node mới vào canvas. */
export function defaultData(type: FlowNodeType): FlowNodeData {
    switch (type) {
        case 'send_message':
            return { text: '' };
        case 'send_buttons':
            return { text: '', buttons: [] };
        case 'condition':
            return { keywords: [], match: 'any' };
        default:
            return {};
    }
}

const CARD: React.CSSProperties = {
    border: '1px solid #d9d9d9',
    borderRadius: 8,
    background: '#fff',
    minWidth: 190,
    maxWidth: 240,
    boxShadow: '0 1px 2px rgba(0,0,0,0.06)',
    fontSize: 13,
};
const HEADER: React.CSSProperties = { display: 'flex', alignItems: 'center', gap: 6, padding: '6px 10px', borderBottom: '1px solid #f0f0f0', fontWeight: 600 };
const BODY: React.CSSProperties = { padding: '8px 10px', color: '#595959', whiteSpace: 'pre-wrap', wordBreak: 'break-word' };

function Shell({ selected, color, icon, title, children }: { selected?: boolean; color: string; icon: ReactNode; title: string; children?: ReactNode }) {
    return (
        <div style={{ ...CARD, borderColor: selected ? color : '#d9d9d9', boxShadow: selected ? `0 0 0 2px ${color}33` : CARD.boxShadow }}>
            <div style={{ ...HEADER, color }}>
                {icon}
                <span style={{ color: '#262626' }}>{title}</span>
            </div>
            {children != null && <div style={BODY}>{children}</div>}
        </div>
    );
}

const empty = <span style={{ color: '#bfbfbf' }}>Chưa cấu hình</span>;

export function TriggerNode({ selected }: NodeProps) {
    return (
        <>
            <Shell selected={selected} color="#52c41a" icon={<PlayCircleOutlined />} title="Bắt đầu" />
            <Handle type="source" position={Position.Bottom} />
        </>
    );
}

export function SendMessageNode({ data, selected }: NodeProps) {
    const d = data as FlowNodeData;
    const text = d.text?.trim();
    const atts = d.attachments ?? [];
    return (
        <>
            <Handle type="target" position={Position.Top} />
            <Shell selected={selected} color="#1677ff" icon={<MessageOutlined />} title="Gửi tin nhắn">
                <div>{text || (atts.length ? <span style={{ color: '#bfbfbf' }}>(không có chữ)</span> : empty)}</div>
                {atts.length > 0 && (
                    <div style={{ marginTop: 6, color: '#1677ff' }}>
                        <PaperClipOutlined /> {atts.length} tệp đính kèm
                    </div>
                )}
            </Shell>
            <Handle type="source" position={Position.Bottom} />
        </>
    );
}

export function AiReplyNode({ selected }: NodeProps) {
    return (
        <>
            <Handle type="target" position={Position.Top} />
            <Shell selected={selected} color="#eb2f96" icon={<RobotOutlined />} title="AI trả lời">
                Sinh câu trả lời bằng AI (dùng kho tri thức + chặn intent nhạy cảm)
                <div style={{ marginTop: 6, display: 'flex', justifyContent: 'space-between', fontSize: 11, color: '#8c8c8c' }}>
                    <span>đã trả lời ↙</span>
                    <span>↘ cần người</span>
                </div>
            </Shell>
            {/* "đã trả lời" = nhánh mặc định (sourceHandle null, khớp engine advance(null)). */}
            <Handle type="source" position={Position.Bottom} style={{ left: '25%', background: '#52c41a' }} />
            <Handle id="handoff" type="source" position={Position.Bottom} style={{ left: '75%', background: '#fa8c16' }} />
        </>
    );
}

export function SendButtonsNode({ data, selected }: NodeProps) {
    const d = data as FlowNodeData;
    const buttons = d.buttons ?? [];
    const postbacks = buttons.filter((b) => b.type !== 'url');
    return (
        <>
            <Handle type="target" position={Position.Top} />
            <Shell selected={selected} color="#722ed1" icon={<AppstoreOutlined />} title="Gửi tin có nút bấm">
                <div>{d.text?.trim() || empty}</div>
                <div style={{ marginTop: 6, display: 'flex', flexDirection: 'column', gap: 4 }}>
                    {buttons.length === 0 && <span style={{ color: '#bfbfbf' }}>Chưa có nút</span>}
                    {buttons.map((b) => (
                        <div key={b.id} style={{ border: '1px solid #efdbff', borderRadius: 4, padding: '2px 6px', background: '#f9f0ff', fontSize: 12 }}>
                            {b.label || '(nút)'} {b.type === 'url' ? '↗' : ''}
                        </div>
                    ))}
                </div>
            </Shell>
            {/* 1 handle / nút postback (id = button.id) — edge.sourceHandle khớp engine. */}
            {postbacks.map((b, i) => (
                <Handle
                    key={b.id}
                    id={b.id}
                    type="source"
                    position={Position.Bottom}
                    style={{ left: `${((i + 1) / (postbacks.length + 1)) * 100}%`, background: '#722ed1' }}
                />
            ))}
        </>
    );
}

export function WaitReplyNode({ selected }: NodeProps) {
    return (
        <>
            <Handle type="target" position={Position.Top} />
            <Shell selected={selected} color="#fa8c16" icon={<ClockCircleOutlined />} title="Chờ khách trả lời">Dừng tới khi khách nhắn tiếp</Shell>
            <Handle type="source" position={Position.Bottom} />
        </>
    );
}

export function ConditionNode({ data, selected }: NodeProps) {
    const d = data as FlowNodeData;
    const kw = d.keywords ?? [];
    return (
        <>
            <Handle type="target" position={Position.Top} />
            <Shell selected={selected} color="#13c2c2" icon={<BranchesOutlined />} title="Rẽ nhánh theo từ khoá">
                {kw.length ? kw.join(', ') : empty}
                <div style={{ marginTop: 6, display: 'flex', justifyContent: 'space-between', fontSize: 11, color: '#8c8c8c' }}>
                    <span>khớp ↙</span>
                    <span>↘ không khớp</span>
                </div>
            </Shell>
            <Handle id="match" type="source" position={Position.Bottom} style={{ left: '25%', background: '#52c41a' }} />
            <Handle id="no_match" type="source" position={Position.Bottom} style={{ left: '75%', background: '#ff4d4f' }} />
        </>
    );
}

export function EndNode({ selected }: NodeProps) {
    return (
        <>
            <Handle type="target" position={Position.Top} />
            <Shell selected={selected} color="#8c8c8c" icon={<FlagOutlined />} title="Kết thúc" />
        </>
    );
}

export const nodeTypes = {
    trigger: TriggerNode,
    send_message: SendMessageNode,
    send_buttons: SendButtonsNode,
    wait_reply: WaitReplyNode,
    condition: ConditionNode,
    ai_reply: AiReplyNode,
    end: EndNode,
};
