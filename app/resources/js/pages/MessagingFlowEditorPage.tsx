import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    addEdge,
    Background,
    type Connection,
    Controls,
    type Edge,
    MiniMap,
    type Node,
    ReactFlow,
    ReactFlowProvider,
    useEdgesState,
    useNodesState,
    useReactFlow,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { App as AntApp, Button, Checkbox, Input, Segmented, Select, Space, Spin, Tag, Tooltip, Typography } from 'antd';
import { ArrowLeftOutlined, CloudUploadOutlined, FileImageOutlined, PauseCircleOutlined, SaveOutlined } from '@ant-design/icons';
import { nanoid } from 'nanoid';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    type FlowNodeData,
    type FlowNodeType,
    type FlowStatus,
    type FlowTriggerType,
    STATUS_LABELS,
    TRIGGER_LABELS,
    useFlow,
    usePauseFlow,
    usePublishFlow,
    useSaveFlow,
} from '@/lib/messagingFlows';
import { defaultData, GROUP_LABELS, NODE_META, nodeTypes } from '@/features/messaging/flow/nodes';
import { NodeConfigDrawer } from '@/features/messaging/flow/NodeConfigDrawer';
import { PostPicker } from '@/features/messaging/flow/PostPicker';
import { PageMultiSelect } from '@/components/messaging/PageScope';

const STATUS_COLOR: Record<FlowStatus, string> = { draft: 'default', active: 'green', paused: 'orange', archived: 'default' };
const TRIGGER_OPTIONS: { label: string; value: FlowTriggerType; disabled?: boolean }[] = [
    { label: TRIGGER_LABELS.inbox_first_message, value: 'inbox_first_message' },
    { label: TRIGGER_LABELS.inbox_keyword, value: 'inbox_keyword' },
    { label: TRIGGER_LABELS.inbox_any, value: 'inbox_any' },
    { label: TRIGGER_LABELS.comment_any, value: 'comment_any' },
    { label: TRIGGER_LABELS.comment_on_post, value: 'comment_on_post' },
];

/**
 * Xóa cạnh (edge) stale khi bộ nút bấm của node thay đổi.
 * - validIds không rỗng → chỉ giữ edge không có sourceHandle hoặc sourceHandle thuộc validIds.
 * - validIds rỗng     → node tuyến tính: giữ edge không có sourceHandle, xóa mọi edge có id handle.
 */
function pruneButtonEdges(
    setEdges: (updater: (prev: Edge[]) => Edge[]) => void,
    nodeId: string,
    validIds: Set<string>,
): void {
    if (validIds.size > 0) {
        setEdges((es) => es.filter((e) => e.source !== nodeId || e.sourceHandle == null || validIds.has(e.sourceHandle)));
    } else {
        setEdges((es) => es.filter((e) => e.source !== nodeId || e.sourceHandle == null));
    }
}

export function MessagingFlowEditorPage() {
    return (
        <ReactFlowProvider>
            <FlowEditor />
        </ReactFlowProvider>
    );
}

function FlowEditor() {
    const { id } = useParams();
    const flowId = Number(id);
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.rule.manage');
    const { data: flow, isLoading } = useFlow(Number.isFinite(flowId) ? flowId : null);
    const save = useSaveFlow();
    const publish = usePublishFlow();
    const pause = usePauseFlow();
    const { screenToFlowPosition } = useReactFlow();

    const [nodes, setNodes, onNodesChange] = useNodesState<Node>([]);
    const [edges, setEdges, onEdgesChange] = useEdgesState<Edge>([]);
    const [selectedId, setSelectedId] = useState<string | null>(null);
    const [invalid, setInvalid] = useState<Set<string>>(new Set());
    const [name, setName] = useState('');
    const [triggerType, setTriggerType] = useState<FlowTriggerType>('inbox_first_message');
    const [keywords, setKeywords] = useState<string[]>([]);
    const [postIds, setPostIds] = useState<string[]>([]);
    const [limitByPost, setLimitByPost] = useState(false); // flow inbox giới hạn theo bài viết
    const [pickerOpen, setPickerOpen] = useState(false);
    const [status, setStatus] = useState<FlowStatus>('draft');
    const [appliesAllPages, setAppliesAllPages] = useState(true);
    const [pageIds, setPageIds] = useState<number[]>([]);

    useEffect(() => {
        if (!flow) return;
        setNodes((flow.graph?.nodes ?? []).map((n) => ({ id: n.id, type: n.type, position: n.position, data: n.data })));
        setEdges((flow.graph?.edges ?? []).map((e) => ({ id: e.id, source: e.source, target: e.target, sourceHandle: e.sourceHandle ?? null })));
        setName(flow.name);
        setTriggerType(flow.trigger_type);
        setKeywords(Array.isArray((flow.trigger_config as { keywords?: string[] })?.keywords) ? ((flow.trigger_config as { keywords?: string[] }).keywords ?? []) : []);
        const cfgPostIds = Array.isArray((flow.trigger_config as { post_ids?: string[] })?.post_ids) ? ((flow.trigger_config as { post_ids?: string[] }).post_ids ?? []) : [];
        setPostIds(cfgPostIds);
        // Flow inbox đã lưu post_ids ⇒ bật sẵn "giới hạn theo bài viết".
        setLimitByPost(['inbox_first_message', 'inbox_keyword', 'inbox_any'].includes(flow.trigger_type) && cfgPostIds.length > 0);
        setStatus(flow.status);
        setAppliesAllPages(flow.applies_all_pages ?? true);
        setPageIds(flow.channel_account_ids ?? []);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flow?.id]);

    const onConnect = useCallback((c: Connection) => setEdges((es) => addEdge({ ...c, id: nanoid(8) }, es)), [setEdges]);

    const onDragOver = useCallback((e: React.DragEvent) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; }, []);
    const onDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('application/flow-node') as FlowNodeType;
        if (!type) return;
        const position = screenToFlowPosition({ x: e.clientX, y: e.clientY });
        setNodes((ns) => [...ns, { id: nanoid(8), type, position, data: defaultData(type) }]);
    }, [screenToFlowPosition, setNodes]);

    const updateNodeData = useCallback((nodeId: string, data: FlowNodeData) => {
        setNodes((ns) => ns.map((n) => (n.id === nodeId ? { ...n, data } : n)));
        const node = nodes.find((n) => n.id === nodeId);
        if (node?.type === 'send_buttons') {
            // Node send_buttons gốc: prune edge không khớp nút postback hiện tại
            const ids = new Set((data.buttons ?? []).filter((b) => b.type !== 'url').map((b) => b.id));
            pruneButtonEdges(setEdges, nodeId, ids);
        } else if (node?.type === 'send_message') {
            // Node send_message có steps: prune edge theo nút postback của bước send_buttons cuối
            const steps = data.steps;
            if (steps && steps.length > 0) {
                const last = steps[steps.length - 1];
                const lastPostbacks =
                    last.type === 'send_buttons'
                        ? (last.buttons ?? []).filter((b) => b.type !== 'url')
                        : [];
                pruneButtonEdges(setEdges, nodeId, new Set(lastPostbacks.map((b) => b.id)));
            }
            // Không có steps (chế độ cũ) → không prune, giữ nguyên
        }
    }, [nodes, setNodes, setEdges]);

    const serialize = useCallback(() => ({
        nodes: nodes.map((n) => ({ id: n.id, type: n.type as FlowNodeType, position: n.position, data: (n.data ?? {}) as FlowNodeData })),
        edges: edges.map((e) => ({ id: e.id, source: e.source, target: e.target, sourceHandle: e.sourceHandle ?? null })),
    }), [nodes, edges]);

    const isInboxTrigger = triggerType === 'inbox_first_message' || triggerType === 'inbox_keyword' || triggerType === 'inbox_any';
    const triggerConfig = useCallback(() => {
        if (triggerType === 'comment_on_post') return { post_ids: postIds };
        const cfg: Record<string, unknown> = {};
        if (triggerType === 'inbox_keyword') cfg.keywords = keywords;
        // Flow inbox tùy chọn giới hạn theo bài viết nguồn (chỉ chạy cho DM đến từ
        // bình luận trên bài đã chọn) — SPEC 2026-06-09.
        if (isInboxTrigger && limitByPost && postIds.length > 0) cfg.post_ids = postIds;
        return cfg;
    }, [triggerType, keywords, postIds, isInboxTrigger, limitByPost]);

    const payload = useCallback(() => ({
        id: flowId,
        name,
        trigger_type: triggerType,
        trigger_config: triggerConfig(),
        graph: serialize(),
        applies_all_pages: appliesAllPages,
        channel_account_ids: appliesAllPages ? [] : pageIds,
    }), [flowId, name, triggerType, triggerConfig, serialize, appliesAllPages, pageIds]);

    const onSaveDraft = () => save.mutate(payload(), {
        onSuccess: () => message.success('Đã lưu nháp'),
        onError: (e) => message.error(errorMessage(e)),
    });

    const onPublish = async () => {
        try {
            await save.mutateAsync(payload());
            const { flow: f, disabledFacebookAi } = await publish.mutateAsync(flowId);
            setInvalid(new Set());
            setStatus(f.status);
            message.success(
                disabledFacebookAi
                    ? 'Đã xuất bản — kịch bản đang chạy. Đã tắt AI tự động trả lời Facebook (chỉ một trong hai được chạy).'
                    : 'Đã xuất bản — kịch bản đang chạy.',
            );
        } catch (e: unknown) {
            const errs = (e as { response?: { data?: { error?: { details?: { errors?: { node_id?: string }[] } } } } })?.response?.data?.error?.details?.errors;
            if (Array.isArray(errs)) {
                setInvalid(new Set(errs.map((x) => x.node_id).filter((x): x is string => !!x)));
                message.error('Kịch bản chưa hợp lệ — các bước viền đỏ cần sửa.');
            } else {
                message.error(errorMessage(e));
            }
        }
    };

    const onPause = () => pause.mutate(flowId, {
        onSuccess: (f) => { setStatus(f.status); message.success('Đã tạm dừng'); },
        onError: (e) => message.error(errorMessage(e)),
    });

    const displayNodes = useMemo(
        () => nodes.map((n) => (invalid.has(n.id) ? { ...n, style: { outline: '2px solid #ff4d4f', borderRadius: 8 } } : { ...n, style: undefined })),
        [nodes, invalid],
    );

    const selectedNode = nodes.find((n) => n.id === selectedId) ?? null;

    if (isLoading) return <div style={{ padding: 48, textAlign: 'center' }}><Spin /></div>;
    if (!flow) return <div style={{ padding: 24 }}><Typography.Text type="secondary">Không tìm thấy kịch bản.</Typography.Text></div>;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100vh - 64px)' }}>
            {/* Topbar */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '8px 16px', borderBottom: '1px solid #f0f0f0', flexWrap: 'wrap' }}>
                <Button type="text" icon={<ArrowLeftOutlined />} onClick={() => navigate('/messaging/flows')} />
                <Input value={name} onChange={(e) => setName(e.target.value)} disabled={!canManage} style={{ width: 240 }} maxLength={160} placeholder="Tên kịch bản" />
                <Tag color={STATUS_COLOR[status]}>{STATUS_LABELS[status]}</Tag>
                <Segmented<FlowTriggerType>
                    value={triggerType}
                    onChange={(v) => setTriggerType(v)}
                    disabled={!canManage}
                    options={TRIGGER_OPTIONS}
                />
                {triggerType === 'inbox_keyword' && (
                    <Select mode="tags" tokenSeparators={[',']} value={keywords} onChange={setKeywords} disabled={!canManage}
                        placeholder="Từ khoá kích hoạt" style={{ minWidth: 200 }} open={false} suffixIcon={null} />
                )}
                {triggerType === 'comment_on_post' && (
                    <Button icon={<FileImageOutlined />} onClick={() => setPickerOpen(true)} disabled={!canManage}>
                        {postIds.length > 0 ? `Đã chọn ${postIds.length} bài viết` : 'Chọn bài viết'}
                    </Button>
                )}
                {isInboxTrigger && (
                    <Tooltip title="Chỉ chạy cho tin nhắn đến từ bình luận trên bài viết đã chọn (khách bình luận → được nhắn riêng → trả lời trong Messenger). Bỏ trống = áp dụng mọi tin nhắn.">
                        <Checkbox checked={limitByPost} onChange={(e) => setLimitByPost(e.target.checked)} disabled={!canManage}>
                            Giới hạn theo bài viết
                        </Checkbox>
                    </Tooltip>
                )}
                {isInboxTrigger && limitByPost && (
                    <Button icon={<FileImageOutlined />} onClick={() => setPickerOpen(true)} disabled={!canManage}>
                        {postIds.length > 0 ? `Đã chọn ${postIds.length} bài viết` : 'Chọn bài viết'}
                    </Button>
                )}
                <Tooltip title="Phạm vi trang áp dụng kịch bản (SPEC 0035)">
                    <Segmented
                        value={appliesAllPages ? 'all' : 'pick'}
                        onChange={(v) => setAppliesAllPages(v === 'all')}
                        options={[{ label: 'Tất cả trang', value: 'all' }, { label: 'Chọn trang', value: 'pick' }]}
                        disabled={!canManage}
                    />
                </Tooltip>
                {!appliesAllPages && (
                    <div style={{ minWidth: 220 }}>
                        <PageMultiSelect value={pageIds} onChange={setPageIds} disabled={!canManage} placeholder="Chọn trang" provider={flow?.provider} />
                    </div>
                )}
                <div style={{ flex: 1 }} />
                {canManage && <Button icon={<SaveOutlined />} loading={save.isPending} onClick={onSaveDraft}>Lưu nháp</Button>}
                {canManage && status === 'active' && <Button icon={<PauseCircleOutlined />} loading={pause.isPending} onClick={onPause}>Tạm dừng</Button>}
                {canManage && (
                    <Tooltip title="Lưu & kiểm tra rồi cho kịch bản chạy">
                        <Button type="primary" icon={<CloudUploadOutlined />} loading={save.isPending || publish.isPending} onClick={onPublish}>Xuất bản</Button>
                    </Tooltip>
                )}
            </div>

            <div style={{ display: 'flex', flex: 1, minHeight: 0 }}>
                {/* Palette */}
                {canManage && (
                    <div style={{ width: 200, borderRight: '1px solid #f0f0f0', padding: 12, overflowY: 'auto' }}>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>Kéo bước vào sơ đồ</Typography.Text>
                        {(['send', 'wait', 'branch', 'ai', 'end'] as const).map((group) => (
                            <div key={group} style={{ marginTop: 12 }}>
                                <Typography.Text strong style={{ fontSize: 12 }}>{GROUP_LABELS[group]}</Typography.Text>
                                <Space direction="vertical" style={{ width: '100%', marginTop: 6 }}>
                                    {NODE_META.filter((m) => m.group === group && m.draggable).map((m) => (
                                        <div
                                            key={m.type}
                                            draggable
                                            onDragStart={(e) => { e.dataTransfer.setData('application/flow-node', m.type); e.dataTransfer.effectAllowed = 'move'; }}
                                            style={{ border: '1px dashed #d9d9d9', borderRadius: 6, padding: '6px 10px', cursor: 'grab', display: 'flex', alignItems: 'center', gap: 8, background: '#fafafa' }}
                                        >
                                            {m.icon}<span style={{ fontSize: 13 }}>{m.label}</span>
                                        </div>
                                    ))}
                                </Space>
                            </div>
                        ))}
                    </div>
                )}

                {/* Canvas */}
                <div style={{ flex: 1 }} onDrop={onDrop} onDragOver={onDragOver}>
                    <ReactFlow
                        nodes={displayNodes}
                        edges={edges}
                        nodeTypes={nodeTypes}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={(_, node) => setSelectedId(node.id)}
                        onPaneClick={() => setSelectedId(null)}
                        nodesDraggable={canManage}
                        nodesConnectable={canManage}
                        edgesFocusable={canManage}
                        fitView
                    >
                        <Background />
                        <Controls />
                        <MiniMap pannable zoomable />
                    </ReactFlow>
                </div>

                <NodeConfigDrawer
                    node={selectedNode}
                    open={selectedNode != null}
                    flowId={flowId}
                    onClose={() => setSelectedId(null)}
                    onChange={updateNodeData}
                    readOnly={!canManage}
                />

                <PostPicker open={pickerOpen} value={postIds} onClose={() => setPickerOpen(false)} onChange={setPostIds} />
            </div>
        </div>
    );
}
