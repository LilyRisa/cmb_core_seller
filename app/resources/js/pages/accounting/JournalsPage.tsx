import { useState } from 'react';
import { App, Badge, Button, Card, DatePicker, Drawer, Form, Input, InputNumber, Modal, Popconfirm, Segmented, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { FileTextOutlined, FilterOutlined, PlusOutlined, ReloadOutlined, RollbackOutlined, SearchOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { CreateJournalPayload, formatAmount, JournalEntry, JournalLine, useCreateJournal, useFiscalPeriods, useJournalDetail, useJournals, useReverseJournal } from '@/lib/accounting';
import { AccountTreeSelect } from '@/components/accounting/AccountTreeSelect';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

type SourceFilter = 'all' | 'auto' | 'manual';

export function JournalsPage() {
    const [periodCode, setPeriodCode] = useState<string | undefined>();
    const [source, setSource] = useState<SourceFilter>('all');
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(20);
    const [showId, setShowId] = useState<number | null>(null);
    const [createOpen, setCreateOpen] = useState(false);

    const { data: periods = [] } = useFiscalPeriods({ kind: 'month' });
    const { data: journals, isFetching, refetch } = useJournals({
        period: periodCode,
        source_module: source === 'all' ? undefined : source,
        q: q || undefined,
        page,
        per_page: perPage,
    });

    const canPost = useCan('accounting.post');
    const canView = useCan('accounting.view');

    const columns: ColumnsType<JournalEntry> = [
        {
            title: 'Mã JE',
            dataIndex: 'code',
            width: 160,
            render: (code, r) => (
                <Space size={6}>
                    <Typography.Link onClick={() => setShowId(r.id)} style={{ fontFamily: 'ui-monospace, monospace' }}>{code}</Typography.Link>
                    {r.is_auto && <Tag color="processing" style={{ marginInlineEnd: 0, fontSize: 11 }}>tự động</Tag>}
                    {r.is_reversal_of_id && <Tag color="warning" style={{ marginInlineEnd: 0, fontSize: 11 }}>đảo</Tag>}
                    {r.is_adjustment && <Tag color="purple" style={{ marginInlineEnd: 0, fontSize: 11 }}>điều chỉnh</Tag>}
                </Space>
            ),
        },
        {
            title: 'Ngày hạch toán',
            dataIndex: 'posted_at',
            width: 130,
            render: (d: string) => dayjs(d).format('DD/MM/YYYY'),
        },
        {
            title: 'Kỳ',
            dataIndex: 'period_code',
            width: 90,
            render: (c: string | undefined) => c ? <Tag>{c}</Tag> : '—',
        },
        {
            title: 'Diễn giải',
            dataIndex: 'narration',
            ellipsis: { showTitle: true },
            render: (n: string | null, r) => (
                <Space size={4} style={{ maxWidth: 380 }}>
                    <Typography.Text style={{ maxWidth: 360 }} ellipsis={{ tooltip: n ?? '' }}>{n ?? '—'}</Typography.Text>
                    <Tag color="default" style={{ marginInlineEnd: 0, fontSize: 11 }}>{r.source_module}</Tag>
                </Space>
            ),
        },
        {
            title: 'Tổng Nợ',
            dataIndex: 'total_debit',
            width: 140,
            align: 'right',
            render: (v: number) => <Typography.Text>{formatAmount(v)} ₫</Typography.Text>,
        },
        {
            title: 'Tổng Có',
            dataIndex: 'total_credit',
            width: 140,
            align: 'right',
            render: (v: number) => <Typography.Text>{formatAmount(v)} ₫</Typography.Text>,
        },
        {
            title: 'Thao tác',
            width: 160,
            align: 'right',
            render: (_, r) => (
                <Space size={4}>
                    <Tooltip title="Xem chi tiết"><Button size="small" type="text" icon={<FileTextOutlined />} onClick={() => setShowId(r.id)} /></Tooltip>
                    {canPost && !r.is_reversal_of_id && (
                        <ReverseButton entry={r} />
                    )}
                </Space>
            ),
        },
    ];

    if (!canView) {
        return (
            <Card>
                <Typography.Paragraph type="warning">Vai trò hiện tại không có quyền xem sổ nhật ký.</Typography.Paragraph>
            </Card>
        );
    }

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />

            <Card
                title={
                    <Space size={10}>
                        <Typography.Title level={5} style={{ margin: 0 }}>Sổ nhật ký chung</Typography.Title>
                        <Badge count={journals?.meta.total ?? 0} overflowCount={9999} color="#1668dc" />
                    </Space>
                }
                extra={
                    <Space>
                        <Tooltip title="Tải lại"><Button icon={<ReloadOutlined />} onClick={() => refetch()} /></Tooltip>
                        {canPost && (
                            <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateOpen(true)}>Tạo bút toán tay</Button>
                        )}
                    </Space>
                }
                styles={{ body: { padding: 0 } }}
            >
                <div style={{ padding: '12px 16px', borderBottom: '1px solid #f0f0f0', display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                    <Space size={6}>
                        <FilterOutlined style={{ color: '#8c8c8c' }} />
                        <Segmented<SourceFilter>
                            value={source}
                            onChange={(v) => { setSource(v as SourceFilter); setPage(1); }}
                            options={[
                                { value: 'all', label: 'Tất cả' },
                                { value: 'auto', label: 'Tự động' },
                                { value: 'manual', label: 'Thủ công' },
                            ]}
                        />
                    </Space>
                    <Space size={6}>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Kỳ</span>
                        <Segmented
                            value={periodCode ?? 'all'}
                            onChange={(v) => { setPeriodCode(v === 'all' ? undefined : (v as string)); setPage(1); }}
                            options={[
                                { value: 'all', label: 'Tất cả' },
                                ...periods.slice(0, 6).map((p) => ({ value: p.code, label: p.code })),
                            ]}
                        />
                    </Space>
                    <Input
                        prefix={<SearchOutlined style={{ color: '#8c8c8c' }} />}
                        allowClear
                        placeholder="Tìm mã JE / diễn giải…"
                        value={q}
                        onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ maxWidth: 320 }}
                    />
                </div>

                <Table<JournalEntry>
                    rowKey="id"
                    dataSource={journals?.data ?? []}
                    columns={columns}
                    loading={isFetching}
                    pagination={{
                        current: page,
                        pageSize: perPage,
                        total: journals?.meta.total ?? 0,
                        showSizeChanger: true,
                        pageSizeOptions: [10, 20, 50, 100],
                        showTotal: (t) => `Tổng ${t} bút toán`,
                        onChange: (p, ps) => { setPage(p); setPerPage(ps); },
                    }}
                    scroll={{ x: 1000 }}
                    size="middle"
                    locale={{ emptyText: 'Chưa có bút toán nào. Hoạt động kho/đơn sẽ tự ghi sổ; hoặc bấm "Tạo bút toán tay".' }}
                />
            </Card>

            <JournalDetailDrawer id={showId} onClose={() => setShowId(null)} />
            <CreateJournalModal open={createOpen} onClose={() => setCreateOpen(false)} defaultPeriod={periodCode} />
        </div>
    );
}

function ReverseButton({ entry }: { entry: JournalEntry }) {
    const rev = useReverseJournal();
    const { message } = App.useApp();
    return (
        <Popconfirm
            title={`Đảo bút toán ${entry.code}?`}
            description="Hệ thống tạo bút toán đảo (swap Nợ/Có). Hành động idempotent."
            okText="Đảo"
            cancelText="Huỷ"
            okButtonProps={{ loading: rev.isPending }}
            onConfirm={async () => {
                try {
                    const r = await rev.mutateAsync({ id: entry.id, reason: '' });
                    message.success(`Đã đảo — tạo ${r.code}.`);
                } catch (e) { message.error(errorMessage(e)); }
            }}
        >
            <Tooltip title="Đảo bút toán"><Button size="small" type="text" icon={<RollbackOutlined />} /></Tooltip>
        </Popconfirm>
    );
}

function JournalDetailDrawer({ id, onClose }: { id: number | null; onClose: () => void }) {
    const { data: entry, isLoading } = useJournalDetail(id);
    return (
        <Drawer
            open={id != null}
            onClose={onClose}
            title={entry ? `Bút toán ${entry.code}` : ''}
            width={780}
            destroyOnClose
        >
            {isLoading && <Typography.Text type="secondary">Đang tải…</Typography.Text>}
            {entry && (
                <>
                    <Space direction="vertical" size={4} style={{ marginBottom: 16, width: '100%' }}>
                        <Space wrap>
                            <Tag color={entry.is_auto ? 'processing' : 'success'}>{entry.is_auto ? 'Tự động' : 'Thủ công'}</Tag>
                            {entry.is_reversal_of_id && <Tag color="warning">Đảo của JE#{entry.is_reversal_of_id}</Tag>}
                            {entry.is_adjustment && <Tag color="purple">Điều chỉnh kỳ</Tag>}
                            <Tag color="default">Nguồn: {entry.source_module}.{entry.source_type}</Tag>
                            {entry.period_code && <Tag color="blue">Kỳ {entry.period_code}</Tag>}
                        </Space>
                        <Typography.Text>
                            <b>Ngày hạch toán:</b> {dayjs(entry.posted_at).format('DD/MM/YYYY')}
                        </Typography.Text>
                        {entry.narration && (
                            <Typography.Text><b>Diễn giải:</b> {entry.narration}</Typography.Text>
                        )}
                    </Space>

                    <Table<JournalLine>
                        rowKey="id"
                        dataSource={entry.lines ?? []}
                        pagination={false}
                        size="small"
                        bordered
                        columns={[
                            { title: '#', dataIndex: 'line_no', width: 50, align: 'center' },
                            {
                                title: 'Tài khoản',
                                dataIndex: 'account_code',
                                width: 240,
                                render: (code, r) => (
                                    <Space size={4}>
                                        <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{code}</Typography.Text>
                                        {r.account_name && <Typography.Text type="secondary" style={{ fontSize: 12 }}>· {r.account_name}</Typography.Text>}
                                    </Space>
                                ),
                            },
                            {
                                title: 'Nợ',
                                dataIndex: 'dr_amount',
                                width: 140,
                                align: 'right',
                                render: (v: number) => v > 0 ? <Typography.Text strong>{formatAmount(v)}</Typography.Text> : '',
                            },
                            {
                                title: 'Có',
                                dataIndex: 'cr_amount',
                                width: 140,
                                align: 'right',
                                render: (v: number) => v > 0 ? <Typography.Text strong>{formatAmount(v)}</Typography.Text> : '',
                            },
                            {
                                title: 'Diễn giải dòng',
                                dataIndex: 'memo',
                                ellipsis: true,
                                render: (m: string | null) => m ?? '',
                            },
                        ]}
                        summary={(rows) => {
                            const dr = rows.reduce((s, r) => s + (r.dr_amount as number), 0);
                            const cr = rows.reduce((s, r) => s + (r.cr_amount as number), 0);
                            return (
                                <Table.Summary.Row>
                                    <Table.Summary.Cell index={0} colSpan={2}><Typography.Text strong>Tổng</Typography.Text></Table.Summary.Cell>
                                    <Table.Summary.Cell index={1} align="right"><Typography.Text strong>{formatAmount(dr)}</Typography.Text></Table.Summary.Cell>
                                    <Table.Summary.Cell index={2} align="right"><Typography.Text strong>{formatAmount(cr)}</Typography.Text></Table.Summary.Cell>
                                    <Table.Summary.Cell index={3} />
                                </Table.Summary.Row>
                            );
                        }}
                    />
                </>
            )}
        </Drawer>
    );
}

interface LineFormRow {
    account_code?: string;
    dr_amount?: number;
    cr_amount?: number;
    memo?: string;
}

function CreateJournalModal({ open, onClose }: { open: boolean; onClose: () => void; defaultPeriod?: string }) {
    const [form] = Form.useForm<{ posted_at: dayjs.Dayjs; narration?: string; lines: LineFormRow[] }>();
    const create = useCreateJournal();
    const { message } = App.useApp();
    const lines: LineFormRow[] = Form.useWatch('lines', form) ?? [];
    const totalDr = lines.reduce((s, l) => s + (Number(l.dr_amount) || 0), 0);
    const totalCr = lines.reduce((s, l) => s + (Number(l.cr_amount) || 0), 0);
    const isBalanced = totalDr > 0 && totalDr === totalCr;

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Tạo bút toán tay"
            width={920}
            okText="Lưu bút toán"
            cancelText="Huỷ"
            destroyOnClose
            confirmLoading={create.isPending}
            okButtonProps={{ disabled: !isBalanced }}
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    const payload: CreateJournalPayload = {
                        posted_at: v.posted_at.format('YYYY-MM-DD'),
                        narration: v.narration,
                        lines: (v.lines ?? []).map((l) => ({
                            account_code: l.account_code!,
                            dr_amount: Number(l.dr_amount) || 0,
                            cr_amount: Number(l.cr_amount) || 0,
                            memo: l.memo,
                        })),
                    };
                    await create.mutateAsync(payload);
                    message.success('Đã ghi sổ bút toán.');
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form
                form={form}
                layout="vertical"
                initialValues={{
                    posted_at: dayjs(),
                    lines: [
                        { account_code: undefined, dr_amount: 0, cr_amount: 0 },
                        { account_code: undefined, dr_amount: 0, cr_amount: 0 },
                    ],
                }}
                preserve={false}
            >
                <Space size={12} style={{ width: '100%', alignItems: 'flex-start' }} wrap>
                    <Form.Item label="Ngày hạch toán" name="posted_at" rules={[{ required: true }]} style={{ width: 200, marginBottom: 0 }}>
                        <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item label="Diễn giải" name="narration" style={{ flex: 1, minWidth: 260, marginBottom: 0 }}>
                        <Input placeholder="vd: Tạm ứng văn phòng phẩm" maxLength={500} />
                    </Form.Item>
                </Space>

                <Form.List name="lines" rules={[{
                    validator: async (_, value) => {
                        if (!value || value.length < 2) {
                            throw new Error('Bút toán phải có ít nhất 2 dòng.');
                        }
                    },
                }]}>
                    {(fields, { add, remove }, { errors }) => (
                        <>
                            <Typography.Title level={5} style={{ marginTop: 16, marginBottom: 8 }}>Dòng bút toán</Typography.Title>
                            <div style={{ border: '1px solid #f0f0f0', borderRadius: 6 }}>
                                <div style={{ display: 'grid', gridTemplateColumns: '40px 1fr 130px 130px 200px 60px', gap: 8, padding: '8px 10px', background: '#fafafa', borderBottom: '1px solid #f0f0f0', fontSize: 12, color: 'rgba(0,0,0,0.55)' }}>
                                    <span>#</span><span>Tài khoản</span><span style={{ textAlign: 'right' }}>Nợ (₫)</span><span style={{ textAlign: 'right' }}>Có (₫)</span><span>Diễn giải dòng</span><span></span>
                                </div>
                                {fields.map((field, idx) => (
                                    <div key={field.key} style={{ display: 'grid', gridTemplateColumns: '40px 1fr 130px 130px 200px 60px', gap: 8, padding: '8px 10px', borderBottom: '1px solid #fafafa', alignItems: 'flex-start' }}>
                                        <Typography.Text>{idx + 1}</Typography.Text>
                                        <Form.Item name={[field.name, 'account_code']} rules={[{ required: true, message: 'Chọn TK' }]} style={{ marginBottom: 0 }}>
                                            <AccountTreeSelect onlyPostable placeholder="Chọn TK…" />
                                        </Form.Item>
                                        <Form.Item name={[field.name, 'dr_amount']} style={{ marginBottom: 0 }}>
                                            <InputNumber<number> min={0} step={1000} style={{ width: '100%' }} formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`} parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0} />
                                        </Form.Item>
                                        <Form.Item name={[field.name, 'cr_amount']} style={{ marginBottom: 0 }}>
                                            <InputNumber<number> min={0} step={1000} style={{ width: '100%' }} formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`} parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0} />
                                        </Form.Item>
                                        <Form.Item name={[field.name, 'memo']} style={{ marginBottom: 0 }}>
                                            <Input placeholder="Diễn giải dòng" maxLength={200} />
                                        </Form.Item>
                                        <Button type="text" danger size="small" onClick={() => remove(field.name)} disabled={fields.length <= 2}>Xoá</Button>
                                    </div>
                                ))}
                                <div style={{ display: 'grid', gridTemplateColumns: '40px 1fr 130px 130px 200px 60px', gap: 8, padding: '10px 10px', background: '#fafafa', alignItems: 'center', borderTop: '1px solid #f0f0f0' }}>
                                    <span></span>
                                    <Typography.Text strong>Tổng</Typography.Text>
                                    <Typography.Text strong style={{ textAlign: 'right' }}>{formatAmount(totalDr)}</Typography.Text>
                                    <Typography.Text strong style={{ textAlign: 'right' }}>{formatAmount(totalCr)}</Typography.Text>
                                    <Tag color={isBalanced ? 'green' : 'red'} style={{ marginInlineEnd: 0, alignSelf: 'center' }}>
                                        {isBalanced ? `Cân ✓` : `Lệch ${formatAmount(Math.abs(totalDr - totalCr))} ₫`}
                                    </Tag>
                                    <span />
                                </div>
                            </div>
                            <Form.ErrorList errors={errors} />
                            <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({ dr_amount: 0, cr_amount: 0 })} style={{ marginTop: 12 }} block>
                                Thêm dòng
                            </Button>
                        </>
                    )}
                </Form.List>
            </Form>
        </Modal>
    );
}
