import { useMemo, useState } from 'react';
import { App, Badge, Button, Card, Drawer, Form, Input, InputNumber, Modal, Popconfirm, Radio, Segmented, Space, Switch, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CheckCircleOutlined, DeleteOutlined, EditOutlined, FilterOutlined, PlusOutlined, SearchOutlined, StopOutlined } from '@ant-design/icons';
import { ACCOUNT_TYPE_COLOR, ACCOUNT_TYPE_LABEL, AccountType, ChartAccount, useChartAccounts, useCreateChartAccount, useDeleteChartAccount, useUpdateChartAccount } from '@/lib/accounting';
import { AccountTreeSelect } from '@/components/accounting/AccountTreeSelect';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

const TYPE_FILTERS: Array<{ value: AccountType | 'all'; label: string }> = [
    { value: 'all', label: 'Tất cả' },
    { value: 'asset', label: 'Tài sản' },
    { value: 'liability', label: 'Nợ phải trả' },
    { value: 'equity', label: 'Vốn CSH' },
    { value: 'revenue', label: 'Doanh thu' },
    { value: 'expense', label: 'Chi phí' },
    { value: 'cogs', label: 'Giá vốn' },
];

interface TreeRow extends ChartAccount {
    children?: TreeRow[];
}

function buildTree(rows: ChartAccount[]): TreeRow[] {
    const byId = new Map<number, TreeRow>();
    rows.forEach((a) => byId.set(a.id, { ...a, children: [] }));
    const roots: TreeRow[] = [];
    rows.forEach((a) => {
        const r = byId.get(a.id)!;
        if (a.parent_id && byId.has(a.parent_id)) {
            byId.get(a.parent_id)!.children!.push(r);
        } else {
            roots.push(r);
        }
    });
    // Bỏ children rỗng để Table không vẽ expand icon dư.
    const cleanup = (n: TreeRow): TreeRow => {
        if (!n.children || n.children.length === 0) {
            const { children, ...rest } = n;
            return rest as TreeRow;
        }
        n.children = n.children.map(cleanup);
        return n;
    };

    return roots.map(cleanup);
}

export function ChartOfAccountsPage() {
    const [typeFilter, setTypeFilter] = useState<AccountType | 'all'>('all');
    const [q, setQ] = useState('');
    const [showInactive, setShowInactive] = useState(false);
    const [editTarget, setEditTarget] = useState<ChartAccount | null>(null);
    const [createOpen, setCreateOpen] = useState(false);

    const filters = {
        type: typeFilter === 'all' ? undefined : typeFilter,
        q: q || undefined,
        active_only: !showInactive,
    };
    const { data: accounts = [], isFetching } = useChartAccounts(filters);
    const tree = useMemo(() => buildTree(accounts), [accounts]);
    const canConfig = useCan('accounting.config');

    const columns: ColumnsType<TreeRow> = [
        {
            title: 'Mã TK',
            dataIndex: 'code',
            width: 140,
            render: (code: string, row) => (
                <Space size={6}>
                    <Typography.Text strong style={{ fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', fontSize: 13 }}>{code}</Typography.Text>
                    {!row.is_postable && <Tag color="default" style={{ marginInlineEnd: 0 }}>tổng</Tag>}
                    {!row.is_active && <Tag color="red" style={{ marginInlineEnd: 0 }}>ẩn</Tag>}
                </Space>
            ),
        },
        {
            title: 'Tên tài khoản',
            dataIndex: 'name',
            ellipsis: { showTitle: true },
            render: (name: string) => <Typography.Text>{name}</Typography.Text>,
        },
        {
            title: 'Phân loại',
            dataIndex: 'type',
            width: 140,
            render: (type: AccountType) => (
                <Tag color={ACCOUNT_TYPE_COLOR[type]} style={{ marginInlineEnd: 0 }}>{ACCOUNT_TYPE_LABEL[type]}</Tag>
            ),
        },
        {
            title: 'Số dư bình thường',
            dataIndex: 'normal_balance',
            width: 140,
            align: 'center',
            render: (nb: 'debit' | 'credit') => (
                <Tag color={nb === 'debit' ? 'blue' : 'orange'} style={{ marginInlineEnd: 0 }}>
                    {nb === 'debit' ? 'Bên Nợ' : 'Bên Có'}
                </Tag>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'is_active',
            width: 110,
            align: 'center',
            render: (active: boolean) => active
                ? <Badge status="success" text="Đang dùng" />
                : <Badge status="default" text="Đã ẩn" />,
        },
        {
            title: 'Thao tác',
            width: 110,
            align: 'right',
            render: (_, row) => canConfig ? (
                <Space size={4}>
                    <Tooltip title="Sửa">
                        <Button size="small" type="text" icon={<EditOutlined />} onClick={() => setEditTarget(row)} />
                    </Tooltip>
                    <DeleteButton row={row} />
                </Space>
            ) : null,
        },
    ];

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />

            <Card
                title={
                    <Space size={10}>
                        <Typography.Title level={5} style={{ margin: 0 }}>Hệ thống tài khoản</Typography.Title>
                        <Tag color="blue">TT133 — DN nhỏ & vừa</Tag>
                    </Space>
                }
                extra={canConfig ? <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateOpen(true)}>Thêm tài khoản con</Button> : null}
                styles={{ body: { padding: 0 } }}
            >
                <div style={{ padding: '12px 16px', borderBottom: '1px solid #f0f0f0', display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                    <Space size={6}>
                        <FilterOutlined style={{ color: '#8c8c8c' }} />
                        <Segmented<AccountType | 'all'>
                            value={typeFilter}
                            onChange={(v) => setTypeFilter(v as AccountType | 'all')}
                            options={TYPE_FILTERS.map((o) => ({ value: o.value, label: o.label }))}
                        />
                    </Space>
                    <Input
                        prefix={<SearchOutlined style={{ color: '#8c8c8c' }} />}
                        allowClear
                        placeholder="Tìm mã hoặc tên TK…"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        style={{ maxWidth: 320 }}
                    />
                    <Space size={6} style={{ marginLeft: 'auto' }}>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Hiển thị TK đã ẩn</span>
                        <Switch checked={showInactive} onChange={setShowInactive} />
                    </Space>
                </div>
                <Table<TreeRow>
                    dataSource={tree}
                    rowKey="id"
                    columns={columns}
                    loading={isFetching}
                    pagination={false}
                    size="middle"
                    expandable={{
                        defaultExpandAllRows: true,
                        rowExpandable: (r) => Array.isArray(r.children) && r.children.length > 0,
                    }}
                    scroll={{ x: 900 }}
                    locale={{ emptyText: 'Chưa có tài khoản nào — bấm "Khởi tạo TT133" ở banner phía trên.' }}
                />
            </Card>

            <EditAccountDrawer target={editTarget} onClose={() => setEditTarget(null)} />
            <CreateAccountModal open={createOpen} onClose={() => setCreateOpen(false)} />
        </div>
    );
}

function DeleteButton({ row }: { row: ChartAccount }) {
    const del = useDeleteChartAccount();
    const { message } = App.useApp();
    return (
        <Popconfirm
            title="Xoá tài khoản?"
            description="Chỉ xoá được nếu chưa có phát sinh và không có TK con."
            okText="Xoá"
            cancelText="Huỷ"
            okButtonProps={{ danger: true, loading: del.isPending }}
            onConfirm={async () => {
                try {
                    await del.mutateAsync(row.id);
                    message.success('Đã xoá tài khoản.');
                } catch (e) { message.error(errorMessage(e)); }
            }}
        >
            <Tooltip title="Xoá"><Button size="small" type="text" icon={<DeleteOutlined />} danger /></Tooltip>
        </Popconfirm>
    );
}

function EditAccountDrawer({ target, onClose }: { target: ChartAccount | null; onClose: () => void }) {
    const [form] = Form.useForm();
    const update = useUpdateChartAccount();
    const { message } = App.useApp();

    return (
        <Drawer
            open={target != null}
            onClose={onClose}
            title={target ? `Sửa ${target.code} · ${target.name}` : ''}
            destroyOnClose
            width={460}
            footer={
                <Space>
                    <Button onClick={onClose}>Đóng</Button>
                    <Button type="primary" loading={update.isPending} onClick={async () => {
                        try {
                            const v = await form.validateFields();
                            await update.mutateAsync({ id: target!.id, ...v });
                            message.success('Đã lưu.');
                            onClose();
                        } catch (e) {
                            if ((e as { errorFields?: unknown }).errorFields) return;
                            message.error(errorMessage(e));
                        }
                    }}>Lưu</Button>
                </Space>
            }
        >
            {target && (
                <Form form={form} layout="vertical" initialValues={{
                    name: target.name,
                    sort_order: target.sort_order,
                    is_active: target.is_active,
                    is_postable: target.is_postable,
                    description: target.description ?? '',
                }} preserve={false}>
                    <Form.Item label="Tên tài khoản" name="name" rules={[{ required: true, max: 255 }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item label="Thứ tự hiển thị" name="sort_order"><InputNumber min={0} style={{ width: 160 }} /></Form.Item>
                    <Form.Item label="Trạng thái" name="is_active" valuePropName="checked">
                        <Switch checkedChildren="Đang dùng" unCheckedChildren="Ẩn" />
                    </Form.Item>
                    <Form.Item label="Cho phép hạch toán trực tiếp" name="is_postable" valuePropName="checked"
                        tooltip="Tắt cho TK tổng — chỉ TK lá mới hạch toán bút toán.">
                        <Switch checkedChildren={<><CheckCircleOutlined /> Có</>} unCheckedChildren={<><StopOutlined /> Không</>} />
                    </Form.Item>
                    <Form.Item label="Mô tả" name="description"><Input.TextArea rows={3} maxLength={500} /></Form.Item>
                </Form>
            )}
        </Drawer>
    );
}

function CreateAccountModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const [form] = Form.useForm();
    const create = useCreateChartAccount();
    const { message } = App.useApp();

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Thêm tài khoản con"
            okText="Tạo"
            cancelText="Huỷ"
            confirmLoading={create.isPending}
            destroyOnClose
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    await create.mutateAsync(v);
                    message.success(`Đã tạo TK ${v.code}.`);
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form form={form} layout="vertical" initialValues={{ normal_balance: 'debit', type: 'asset', is_postable: true }} preserve={false}>
                <Form.Item label="Mã tài khoản (không có dấu cách)" name="code" rules={[
                    { required: true, max: 16 },
                    { pattern: /^[A-Za-z0-9_-]+$/, message: 'Chỉ chữ, số, _ -' },
                ]}><Input placeholder="vd 15611" /></Form.Item>
                <Form.Item label="Tên tài khoản" name="name" rules={[{ required: true, max: 255 }]}><Input /></Form.Item>
                <Form.Item label="Tài khoản cha (tuỳ chọn)" name="parent_code"
                    tooltip="Bỏ trống nếu là TK gốc. Chọn TK cha để gắn vào cây.">
                    <AccountTreeSelect onlyPostable={false} placeholder="Không có TK cha" />
                </Form.Item>
                <Form.Item label="Phân loại" name="type" rules={[{ required: true }]}>
                    <Radio.Group optionType="button" buttonStyle="solid"
                        options={[
                            { value: 'asset', label: 'TS' },
                            { value: 'liability', label: 'Nợ' },
                            { value: 'equity', label: 'Vốn' },
                            { value: 'revenue', label: 'DT' },
                            { value: 'expense', label: 'CP' },
                            { value: 'cogs', label: 'GVHB' },
                            { value: 'contra_revenue', label: 'Giảm trừ DT' },
                            { value: 'contra_asset', label: 'Hao mòn' },
                        ]}
                    />
                </Form.Item>
                <Form.Item label="Số dư bình thường" name="normal_balance" rules={[{ required: true }]}>
                    <Radio.Group optionType="button" buttonStyle="solid">
                        <Radio.Button value="debit">Bên Nợ</Radio.Button>
                        <Radio.Button value="credit">Bên Có</Radio.Button>
                    </Radio.Group>
                </Form.Item>
                <Form.Item label="Cho phép hạch toán trực tiếp" name="is_postable" valuePropName="checked"
                    tooltip="Bật cho TK lá; tắt nếu đây là TK tổng có các TK con khác.">
                    <Switch checkedChildren="Có" unCheckedChildren="Không" />
                </Form.Item>
                <Form.Item label="Thứ tự hiển thị" name="sort_order"><InputNumber min={0} style={{ width: 160 }} /></Form.Item>
            </Form>
        </Modal>
    );
}
