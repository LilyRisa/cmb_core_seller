import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Card, Form, Input, InputNumber, Modal, Popconfirm, Radio, Select, Space, Switch, Table, Tag } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MessagingNav } from '@/components/MessagingNav';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type AutoReplyRule, type RuleTrigger, type ThreadType, useAutoRules, useDeleteRule, useSaveRule, useTemplates } from '@/lib/messagingConfig';
import { PageMultiSelect, PageScopeTags } from '@/components/messaging/PageScope';

const TRIGGER_LABELS: Record<RuleTrigger, string> = {
    first_message: 'Chào lần đầu / bình luận đầu',
    schedule: 'Theo lịch (vắng mặt)',
    order_status: 'Theo trạng thái đơn',
    away_no_response: 'NV chưa trả lời',
    keyword: 'Từ khoá',
    comment_any: 'Mọi bình luận mới',
};

type ThreadChoice = 'message' | 'comment' | 'both';

/** Map filter.thread_types ↔ lựa chọn radio. Vắng = chỉ DM (comment cần khai báo tường minh). */
function threadChoiceFrom(threadTypes: ThreadType[] | undefined): ThreadChoice {
    const has = (t: ThreadType) => (threadTypes ?? []).includes(t);
    if (has('comment') && has('message')) return 'both';
    if (has('comment')) return 'comment';
    return 'message';
}

function threadTypesFrom(choice: ThreadChoice): ThreadType[] {
    if (choice === 'both') return ['message', 'comment'];
    if (choice === 'comment') return ['comment'];
    return ['message'];
}

function commentTargetChoiceFrom(t: { public?: boolean; private?: boolean } | undefined): 'public' | 'private' | 'both' {
    if (t?.public && t?.private) return 'both';
    if (t?.private) return 'private';
    return 'public';
}

function commentTargetFrom(choice: 'public' | 'private' | 'both'): { public: boolean; private: boolean } {
    return { public: choice === 'public' || choice === 'both', private: choice === 'private' || choice === 'both' };
}

/** /messaging/auto-rules — quản lý quy tắc trả lời tự động (SPEC-0024 §6.2). */
export function MessagingAutoRulesPage() {
    const { message } = AntApp.useApp();
    // Trả lời tự động theo nền tảng: ?platform=zalo_oa ⇒ chỉ rule provider zalo_oa + tạo rule gắn provider đó.
    const [params] = useSearchParams();
    const platform = params.get('platform') ?? 'facebook_page';
    const canManage = useCan('messaging.rule.manage');
    const { data, isFetching } = useAutoRules(platform);
    const save = useSaveRule();
    const del = useDeleteRule();
    const [editing, setEditing] = useState<AutoReplyRule | null>(null);
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();
    const trigger = Form.useWatch('trigger', form) as RuleTrigger | undefined;
    const threadChoice = (Form.useWatch('thread_choice', form) as ThreadChoice | undefined) ?? 'message';
    const actionKind = (Form.useWatch('action_kind', form) as 'raw' | 'template' | 'ai_reply' | undefined) ?? 'raw';
    const appliesAll = (Form.useWatch('applies_all_pages', form) as boolean | undefined) ?? false;
    const templates = useTemplates().data?.data ?? [];

    const openForm = (r?: AutoReplyRule) => {
        setEditing(r ?? null);
        form.setFieldsValue(r
            ? {
                ...r,
                raw_text: r.action?.raw_text,
                template_id: r.action?.template_id,
                action_kind: r.action?.kind ?? 'raw',
                thread_choice: threadChoiceFrom(r.filter?.thread_types),
                comment_target_choice: commentTargetChoiceFrom(r.action?.comment_target),
                minutes: r.trigger_config?.minutes,
                order_status: r.trigger_config?.order_status,
                keywords: Array.isArray(r.trigger_config?.keywords) ? r.trigger_config.keywords : [],
                applies_all_pages: r.applies_all_pages,
                channel_account_ids: r.channel_account_ids ?? [],
            }
            : { enabled: true, trigger: 'first_message', cooldown_seconds: 3600, priority: 100, keywords: [], thread_choice: 'message', action_kind: 'raw', comment_target_choice: 'public', applies_all_pages: false, channel_account_ids: [] });
        setOpen(true);
    };

    const submit = () => form.validateFields().then((v) => {
        const trigger_config: Record<string, unknown> =
            v.trigger === 'away_no_response' ? { minutes: v.minutes ?? 15 }
                : v.trigger === 'order_status' ? { order_status: v.order_status }
                    : v.trigger === 'schedule' ? { window: v.window, tz: 'Asia/Ho_Chi_Minh' }
                        : v.trigger === 'keyword' ? { keywords: v.keywords ?? [] }
                            : {};

        const threadTypes = threadTypesFrom(v.thread_choice ?? 'message');
        const action: AutoReplyRule['action'] = v.action_kind === 'template'
            ? { kind: 'template', template_id: v.template_id }
            : v.action_kind === 'ai_reply'
                ? { kind: 'ai_reply' }
                : { kind: 'raw', raw_text: v.raw_text };
        if (threadTypes.includes('comment')) {
            action.comment_target = commentTargetFrom(v.comment_target_choice ?? 'public');
        }

        const payload = {
            ...(editing ? { id: editing.id } : {}),
            name: v.name, trigger: v.trigger, enabled: v.enabled, cooldown_seconds: v.cooldown_seconds, priority: v.priority,
            trigger_config,
            // Khoá rule theo nền tảng (AutoReplyEngine: providers rỗng = mọi provider) → tránh rule Zalo bắn cho Facebook.
            filter: { thread_types: threadTypes, providers: [platform] },
            action,
            applies_all_pages: !!v.applies_all_pages,
            channel_account_ids: v.applies_all_pages ? [] : (v.channel_account_ids ?? []),
        };
        save.mutate(payload, {
            onSuccess: () => { message.success('Đã lưu quy tắc'); setOpen(false); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const columns: ColumnsType<AutoReplyRule> = [
        { title: 'Tên', dataIndex: 'name', width: 180, ellipsis: { showTitle: true } },
        { title: 'Kích hoạt khi', dataIndex: 'trigger', width: 180, render: (t: RuleTrigger) => <Tag color="blue">{TRIGGER_LABELS[t]}</Tag> },
        { title: 'Áp dụng', width: 120, render: (_, r) => {
            const c = threadChoiceFrom(r.filter?.thread_types);
            return <Tag color={c === 'comment' ? 'gold' : c === 'both' ? 'geekblue' : 'green'}>{c === 'comment' ? 'Bình luận' : c === 'both' ? 'Cả hai' : 'Tin nhắn'}</Tag>;
        } },
        { title: 'Phạm vi trang', width: 200, render: (_, r) => (
            <PageScopeTags appliesAllPages={r.applies_all_pages} channelAccountIds={r.channel_account_ids} />
        ) },
        { title: 'Nội dung', width: 240, render: (_, r) => {
            if (r.action?.kind === 'ai_reply') return <Tag color="purple">AI tự soạn</Tag>;
            if (r.action?.kind === 'template') return <span style={{ color: '#64748B' }}>Mẫu nhanh</span>;
            return <span style={{ color: '#64748B', display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={r.action?.raw_text ?? undefined}>{r.action?.raw_text ?? '—'}</span>;
        } },
        { title: 'Cooldown', dataIndex: 'cooldown_seconds', width: 110, render: (v) => `${Math.round(v / 60)} phút` },
        { title: 'Bật', dataIndex: 'enabled', width: 70, render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Bật' : 'Tắt'}</Tag> },
        ...(canManage ? [{ title: '', width: 90, render: (_: unknown, r: AutoReplyRule) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => openForm(r)} />
                <Popconfirm title="Xoá quy tắc?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                    onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Trả lời tự động" subtitle="Chào mừng, trả lời theo lịch/vắng mặt, theo trạng thái đơn — chống spam bằng cooldown."
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm()}>Thêm quy tắc</Button>} />
            <MessagingNav />
            <Card>
                <Table<AutoReplyRule> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} scroll={{ x: 'max-content' }} />
            </Card>

            <Modal open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={save.isPending}
                title={editing ? 'Sửa quy tắc' : 'Thêm quy tắc'} okText="Lưu" cancelText="Huỷ">
                <Form form={form} layout="vertical">
                    <Form.Item name="name" label="Tên" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="trigger" label="Kích hoạt khi" rules={[{ required: true }]}>
                        <Select options={Object.entries(TRIGGER_LABELS).map(([value, label]) => ({ value, label }))} />
                    </Form.Item>
                    <Form.Item name="thread_choice" label="Áp dụng cho">
                        <Radio.Group optionType="button" buttonStyle="solid">
                            <Radio.Button value="message">Tin nhắn</Radio.Button>
                            <Radio.Button value="comment">Bình luận</Radio.Button>
                            <Radio.Button value="both">Cả hai</Radio.Button>
                        </Radio.Group>
                    </Form.Item>
                    <Form.Item name="applies_all_pages" label="Áp dụng cho trang" valuePropName="checked"
                        extra="Bật = áp mọi trang. Tắt = chỉ các trang được chọn (ưu tiên hơn quy tắc 'tất cả trang').">
                        <Switch checkedChildren="Tất cả trang" unCheckedChildren="Chọn trang" />
                    </Form.Item>
                    {!appliesAll && (
                        <Form.Item name="channel_account_ids" label="Trang áp dụng"
                            rules={[{ required: true, type: 'array', min: 1, message: 'Chọn ít nhất 1 trang hoặc bật "Tất cả trang"' }]}>
                            <PageMultiSelect provider={platform} />
                        </Form.Item>
                    )}
                    {(threadChoice === 'comment' || threadChoice === 'both') && (
                        <Form.Item name="comment_target_choice" label="Với bình luận, gửi" extra="Trả lời công khai dưới bình luận và/hoặc nhắn riêng cho người bình luận.">
                            <Radio.Group optionType="button" buttonStyle="solid">
                                <Radio.Button value="public">Trả lời công khai</Radio.Button>
                                <Radio.Button value="private">Nhắn riêng</Radio.Button>
                                <Radio.Button value="both">Cả hai</Radio.Button>
                            </Radio.Group>
                        </Form.Item>
                    )}
                    {trigger === 'away_no_response' && (
                        <Form.Item name="minutes" label="Sau bao nhiêu phút NV chưa trả lời"><InputNumber min={1} max={1440} style={{ width: '100%' }} /></Form.Item>
                    )}
                    {trigger === 'order_status' && (
                        <Form.Item name="order_status" label="Trạng thái đơn" rules={[{ required: true }]}>
                            <Select options={['delivered', 'shipped', 'cancelled', 'processing'].map((s) => ({ value: s, label: s }))} />
                        </Form.Item>
                    )}
                    {trigger === 'schedule' && (
                        <Form.Item name="window" label="Khung giờ (vd 22:00-08:00)" rules={[{ required: true }]}><Input placeholder="22:00-08:00" /></Form.Item>
                    )}
                    {trigger === 'keyword' && (
                        <Form.Item
                            name="keywords"
                            label="Từ khoá kích hoạt"
                            rules={[{ required: true, type: 'array', min: 1, message: 'Nhập ít nhất 1 từ khoá' }]}
                            extra="Nhập từ khoá rồi nhấn Enter để thêm. Quy tắc fire khi tin nhắn chứa ít nhất 1 từ khoá."
                        >
                            <Select mode="tags" placeholder="Nhập từ khoá, Enter để thêm" tokenSeparators={[',']} />
                        </Form.Item>
                    )}
                    <Form.Item name="action_kind" label="Nội dung trả lời">
                        <Radio.Group optionType="button" buttonStyle="solid">
                            <Radio.Button value="raw">Văn bản cố định</Radio.Button>
                            <Radio.Button value="template">Mẫu nhanh</Radio.Button>
                            <Radio.Button value="ai_reply">AI tự soạn</Radio.Button>
                        </Radio.Group>
                    </Form.Item>
                    {actionKind === 'raw' && (
                        <Form.Item name="raw_text" label="Văn bản" rules={[{ required: true, message: 'Nhập nội dung trả lời' }]}><Input.TextArea rows={3} /></Form.Item>
                    )}
                    {actionKind === 'template' && (
                        <Form.Item name="template_id" label="Chọn mẫu" rules={[{ required: true, message: 'Chọn 1 mẫu tin' }]}>
                            <Select
                                showSearch
                                optionFilterProp="label"
                                placeholder="Chọn mẫu trả lời nhanh"
                                options={templates.map((t) => ({ value: t.id, label: t.name }))}
                            />
                        </Form.Item>
                    )}
                    {actionKind === 'ai_reply' && (
                        <Form.Item label="AI tự soạn">
                            <Tag color="purple">AI</Tag>
                            <span style={{ color: '#64748B', fontSize: 13 }}>AI tự sinh nội dung qua bộ lọc ý định (khiếu nại/hoàn tiền/khẩn cấp → chuyển nhân viên, không tự gửi). Cần đã bật AI ở phần Cài đặt.</span>
                        </Form.Item>
                    )}
                    <Space size="large">
                        <Form.Item name="cooldown_seconds" label="Cooldown (giây)"><InputNumber min={0} max={86400} /></Form.Item>
                        <Form.Item name="priority" label="Ưu tiên"><InputNumber min={0} max={1000} /></Form.Item>
                        <Form.Item name="enabled" label="Bật" valuePropName="checked"><Switch /></Form.Item>
                    </Space>
                </Form>
            </Modal>
        </div>
    );
}
