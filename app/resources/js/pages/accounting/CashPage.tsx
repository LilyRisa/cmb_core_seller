import { useMemo, useState } from 'react';
import { App, Badge, Button, Card, DatePicker, Drawer, Form, Input, Modal, Radio, Segmented, Select, Space, Statistic, Table, Tag, Tooltip, Typography, Upload } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { BankOutlined, CheckCircleOutlined, FileExcelOutlined, PlusOutlined, ReloadOutlined, StopOutlined, SwapOutlined, WalletOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { BankStatement, BankStatementLine, formatAmount, useBankStatementDetail, useBankStatements, useIgnoreBankLine, useMatchBankLine } from '@/lib/accounting';
import { useReceipts } from '@/lib/accountingAr';
import { useVendorPayments } from '@/lib/accountingAp';
import { useAuth, getCurrentTenantId } from '@/lib/auth';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from '@/lib/api';
import { AccountingSetupBanner } from './AccountingSetupBanner';

function useScopedApi() {
    const { data: user } = useAuth();
    const tenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

interface CashAccount {
    id: number;
    code: string;
    name: string;
    kind: 'cash' | 'bank' | 'ewallet' | 'cod_intransit';
    bank_name: string | null;
    account_no: string | null;
    account_holder: string | null;
    currency: string;
    gl_account_id: number;
    gl_account_code?: string;
    is_active: boolean;
    description: string | null;
    balance: number;
}

export function CashPage() {
    const api = useScopedApi();
    const [createOpen, setCreateOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [reconcileId, setReconcileId] = useState<number | null>(null);
    const canConfig = useCan('accounting.config');
    const canPost = useCan('accounting.post');

    const { data: accounts = [], isFetching, refetch } = useQuery({
        queryKey: ['accounting', 'cash-accounts'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: CashAccount[] }>('/accounting/cash-accounts');
            return data.data;
        },
    });

    const columns: ColumnsType<CashAccount> = [
        {
            title: 'Mã / Tên',
            render: (_, r) => (
                <Space size={6} direction="vertical" style={{ display: 'flex' }}>
                    <Space size={6}>
                        <Tag style={{ marginInlineEnd: 0, fontFamily: 'ui-monospace, monospace' }}>{r.code}</Tag>
                        <Typography.Text strong>{r.name}</Typography.Text>
                    </Space>
                    {r.bank_name && (
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                            {r.bank_name}{r.account_no ? ` · ${r.account_no}` : ''}{r.account_holder ? ` · ${r.account_holder}` : ''}
                        </Typography.Text>
                    )}
                </Space>
            ),
        },
        {
            title: 'Loại',
            dataIndex: 'kind',
            width: 130,
            align: 'center',
            render: (k: CashAccount['kind']) => (
                <Tag color={k === 'cash' ? 'green' : k === 'bank' ? 'blue' : k === 'ewallet' ? 'purple' : 'orange'} icon={k === 'cash' ? <WalletOutlined /> : <BankOutlined />}>
                    {k === 'cash' ? 'Tiền mặt' : k === 'bank' ? 'Ngân hàng' : k === 'ewallet' ? 'Ví điện tử' : 'COD đang về'}
                </Tag>
            ),
        },
        {
            title: 'TK GL',
            dataIndex: 'gl_account_code',
            width: 90,
            align: 'center',
            render: (c: string | undefined) => c ? <Tag color="default" style={{ marginInlineEnd: 0, fontFamily: 'ui-monospace, monospace' }}>{c}</Tag> : '—',
        },
        {
            title: 'Số dư hiện tại',
            dataIndex: 'balance',
            width: 170,
            align: 'right',
            render: (v: number) => (
                <Typography.Text strong style={{ color: v < 0 ? '#cf1322' : '#2563EB' }}>
                    {formatAmount(v)} ₫
                </Typography.Text>
            ),
        },
    ];

    const totalBalance = accounts.reduce((s, a) => s + a.balance, 0);

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />
            <Card
                title={<Typography.Title level={5} style={{ margin: 0 }}>Quỹ & Ngân hàng</Typography.Title>}
                extra={(
                    <Space>
                        <Tooltip title="Tải lại"><Button icon={<ReloadOutlined />} onClick={() => refetch()} /></Tooltip>
                        {canConfig && <Button icon={<PlusOutlined />} onClick={() => setCreateOpen(true)}>Tạo quỹ/tài khoản</Button>}
                        {canPost && <Button type="primary" icon={<FileExcelOutlined />} onClick={() => setImportOpen(true)}>Import sao kê</Button>}
                    </Space>
                )}
            >
                <div style={{ marginBottom: 16 }}>
                    <Statistic title="Tổng tiền mặt + ngân hàng" value={totalBalance} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#2563EB' }} />
                </div>
                <Table<CashAccount>
                    rowKey="id"
                    dataSource={accounts}
                    columns={columns}
                    loading={isFetching}
                    pagination={false}
                    size="middle"
                    scroll={{ x: 800 }}
                    locale={{ emptyText: 'Chưa có quỹ/tài khoản nào — bấm "Tạo quỹ/tài khoản".' }}
                />
            </Card>

            <BankStatementsCard accounts={accounts} onReconcile={(id) => setReconcileId(id)} />

            <CreateCashAccountModal open={createOpen} onClose={() => setCreateOpen(false)} />
            <ImportStatementModal open={importOpen} onClose={() => setImportOpen(false)} accounts={accounts} />
            <ReconcileDrawer statementId={reconcileId} onClose={() => setReconcileId(null)} canPost={canPost} accounts={accounts} />
        </div>
    );
}

/** Danh sách sao kê đã import + nút vào màn hình đối chiếu. */
function BankStatementsCard({ accounts, onReconcile }: { accounts: CashAccount[]; onReconcile: (id: number) => void }) {
    const { data: statements = [], isFetching, error } = useBankStatements();
    const acctMap = useMemo(() => new Map(accounts.map((a) => [a.id, a])), [accounts]);

    // Gói chưa bật accounting_advanced ⇒ 402 ⇒ ẩn card (đối chiếu là tính năng nâng cao).
    const status = (error as { response?: { status?: number } })?.response?.status;
    if (status === 402) {
        return (
            <Card style={{ marginTop: 16 }} title={<Typography.Title level={5} style={{ margin: 0 }}>Sao kê & đối chiếu ngân hàng</Typography.Title>}>
                <Typography.Paragraph type="warning" style={{ margin: 0 }}>Tính năng đối chiếu ngân hàng thuộc gói nâng cao (accounting_advanced). Nâng gói để sử dụng.</Typography.Paragraph>
            </Card>
        );
    }

    const columns: ColumnsType<BankStatement> = [
        { title: 'Tài khoản', dataIndex: 'cash_account_id', render: (id: number) => { const a = acctMap.get(id); return a ? <Space size={4}><Tag style={{ marginInlineEnd: 0, fontFamily: 'ui-monospace, monospace' }}>{a.code}</Tag><Typography.Text>{a.name}</Typography.Text></Space> : `#${id}`; } },
        { title: 'Kỳ sao kê', width: 220, render: (_, r) => `${dayjs(r.period_start).format('DD/MM/YYYY')} — ${dayjs(r.period_end).format('DD/MM/YYYY')}` },
        { title: 'Số dòng', dataIndex: 'lines_count', width: 90, align: 'right' },
        { title: 'Tiền vào', dataIndex: 'total_in', width: 140, align: 'right', render: (v: number) => <Typography.Text style={{ color: '#3f8600' }}>{formatAmount(v)}</Typography.Text> },
        { title: 'Tiền ra', dataIndex: 'total_out', width: 140, align: 'right', render: (v: number) => <Typography.Text style={{ color: '#cf1322' }}>{formatAmount(v)}</Typography.Text> },
        { title: 'Nguồn', dataIndex: 'imported_from', width: 110, render: (s: string) => <Tag>{s}</Tag> },
        { title: 'Thao tác', width: 130, align: 'right', render: (_, r) => <Button size="small" type="link" icon={<SwapOutlined />} onClick={() => onReconcile(r.id)}>Đối chiếu</Button> },
    ];

    return (
        <Card style={{ marginTop: 16 }} title={<Typography.Title level={5} style={{ margin: 0 }}>Sao kê & đối chiếu ngân hàng</Typography.Title>} styles={{ body: { padding: 0 } }}>
            <Table<BankStatement>
                rowKey="id"
                dataSource={statements}
                columns={columns}
                loading={isFetching}
                pagination={false}
                size="middle"
                scroll={{ x: 900 }}
                locale={{ emptyText: 'Chưa có sao kê nào — bấm "Import sao kê" ở trên để tải lên.' }}
            />
        </Card>
    );
}

const LINE_STATUS_META: Record<BankStatementLine['status'], { color: string; label: string }> = {
    unmatched: { color: 'gold', label: 'Chưa khớp' },
    matched: { color: 'green', label: 'Đã khớp' },
    ignored: { color: 'default', label: 'Bỏ qua' },
};

/** Drawer đối chiếu: từng dòng sao kê → khớp với phiếu thu/chi/bút toán, hoặc bỏ qua. */
function ReconcileDrawer({ statementId, onClose, canPost, accounts }: { statementId: number | null; onClose: () => void; canPost: boolean; accounts: CashAccount[] }) {
    const { data, isLoading } = useBankStatementDetail(statementId);
    const acct = accounts.find((a) => a.id === data?.cash_account_id);
    const lines = data?.lines ?? [];
    const matched = lines.filter((l) => l.status === 'matched').length;
    const ignored = lines.filter((l) => l.status === 'ignored').length;
    const pending = lines.length - matched - ignored;

    return (
        <Drawer
            open={statementId != null}
            onClose={onClose}
            width={920}
            destroyOnClose
            title={data ? `Đối chiếu sao kê — ${acct ? acct.name : '#' + data.cash_account_id}` : 'Đối chiếu sao kê'}
        >
            {isLoading && <Typography.Text type="secondary">Đang tải…</Typography.Text>}
            {data && (
                <>
                    <Space size={16} wrap style={{ marginBottom: 16 }}>
                        <Badge color="green" text={`Đã khớp: ${matched}`} />
                        <Badge color="gold" text={`Chưa khớp: ${pending}`} />
                        <Badge color="default" text={`Bỏ qua: ${ignored}`} />
                        <Typography.Text type="secondary">Kỳ {dayjs(data.period_start).format('DD/MM/YYYY')} — {dayjs(data.period_end).format('DD/MM/YYYY')}</Typography.Text>
                    </Space>
                    <Table<BankStatementLine>
                        rowKey="id"
                        dataSource={lines}
                        pagination={false}
                        size="small"
                        scroll={{ x: 820 }}
                        columns={[
                            { title: 'Ngày', dataIndex: 'txn_date', width: 100, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
                            { title: 'Đối tác / Nội dung', render: (_, r) => (<Space direction="vertical" size={0}><Typography.Text>{r.counter_party ?? '—'}</Typography.Text>{r.memo && <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.memo}</Typography.Text>}</Space>) },
                            { title: 'Số tiền', dataIndex: 'amount', width: 140, align: 'right', render: (v: number) => <Typography.Text strong style={{ color: v >= 0 ? '#3f8600' : '#cf1322' }}>{v >= 0 ? '+' : ''}{formatAmount(v)}</Typography.Text> },
                            { title: 'Trạng thái', dataIndex: 'status', width: 110, render: (s: BankStatementLine['status']) => <Tag color={LINE_STATUS_META[s].color}>{LINE_STATUS_META[s].label}</Tag> },
                            { title: 'Đối chiếu', width: 240, render: (_, r) => <MatchControl line={r} canPost={canPost} /> },
                        ]}
                    />
                </>
            )}
        </Drawer>
    );
}

/** Điều khiển khớp 1 dòng: chọn loại chứng từ + chứng từ ứng viên (ưu tiên trùng số tiền), hoặc bỏ qua. */
function MatchControl({ line, canPost }: { line: BankStatementLine; canPost: boolean }) {
    const match = useMatchBankLine();
    const ignore = useIgnoreBankLine();
    const { message } = App.useApp();
    const [open, setOpen] = useState(false);

    if (line.status === 'matched') {
        return <Typography.Text type="success">Khớp: {line.matched_ref_type === 'customer_receipt' ? 'Phiếu thu' : line.matched_ref_type === 'vendor_payment' ? 'Phiếu chi' : 'Bút toán'} #{line.matched_ref_id}</Typography.Text>;
    }
    if (line.status === 'ignored') {
        return <Typography.Text type="secondary">Đã bỏ qua</Typography.Text>;
    }
    if (!canPost) return <Typography.Text type="secondary">—</Typography.Text>;

    return (
        <Space size={4}>
            <Button size="small" type="primary" ghost icon={<CheckCircleOutlined />} onClick={() => setOpen(true)}>Khớp</Button>
            <Tooltip title="Bỏ qua dòng này">
                <Button size="small" type="text" icon={<StopOutlined />} loading={ignore.isPending}
                    onClick={async () => { try { await ignore.mutateAsync(line.id); message.success('Đã bỏ qua dòng.'); } catch (e) { message.error(errorMessage(e)); } }}
                />
            </Tooltip>
            <MatchModal line={line} open={open} onClose={() => setOpen(false)} onMatch={match} />
        </Space>
    );
}

function MatchModal({ line, open, onClose, onMatch }: { line: BankStatementLine; open: boolean; onClose: () => void; onMatch: ReturnType<typeof useMatchBankLine> }) {
    const { message } = App.useApp();
    // amount > 0 (tiền vào) ⇒ ưu tiên phiếu thu; < 0 (tiền ra) ⇒ phiếu chi.
    const isInflow = line.amount >= 0;
    const [refType, setRefType] = useState<'customer_receipt' | 'vendor_payment' | 'journal_entry'>(isInflow ? 'customer_receipt' : 'vendor_payment');
    const [refId, setRefId] = useState<number | undefined>();

    const receipts = useReceipts({ status: 'confirmed', per_page: 100 });
    const payments = useVendorPayments({ status: 'confirmed', per_page: 100 });

    const abs = Math.abs(line.amount);
    const options = useMemo(() => {
        if (refType === 'customer_receipt') {
            return (receipts.data?.data ?? []).map((r) => ({ value: r.id, label: `${r.code} · ${formatAmount(r.amount)} ₫`, exact: r.amount === abs }));
        }
        if (refType === 'vendor_payment') {
            return (payments.data?.data ?? []).map((p) => ({ value: p.id, label: `${p.code} · ${formatAmount(p.amount)} ₫`, exact: p.amount === abs }));
        }
        return [];
    }, [refType, receipts.data, payments.data, abs]);

    // Sắp xếp ứng viên trùng số tiền lên đầu.
    const sortedOptions = useMemo(() => [...options].sort((a, b) => Number(b.exact) - Number(a.exact)).map((o) => ({ value: o.value, label: o.exact ? `(Trùng số tiền) ${o.label}` : o.label })), [options]);

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Khớp dòng sao kê với chứng từ"
            okText="Khớp"
            cancelText="Huỷ"
            destroyOnClose
            okButtonProps={{ disabled: refType === 'journal_entry' ? !refId : !refId, loading: onMatch.isPending }}
            onOk={async () => {
                if (!refId) return;
                try {
                    await onMatch.mutateAsync({ id: line.id, ref_type: refType, ref_id: refId, journal_entry_id: refType === 'journal_entry' ? refId : undefined });
                    message.success('Đã khớp dòng sao kê.');
                    onClose();
                } catch (e) { message.error(errorMessage(e)); }
            }}
        >
            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                <div>
                    <Typography.Text type="secondary">Dòng sao kê: </Typography.Text>
                    <Typography.Text strong style={{ color: isInflow ? '#3f8600' : '#cf1322' }}>{isInflow ? '+' : ''}{formatAmount(line.amount)} ₫</Typography.Text>
                    {line.memo && <Typography.Text type="secondary"> · {line.memo}</Typography.Text>}
                </div>
                <Segmented<typeof refType>
                    value={refType}
                    onChange={(v) => { setRefType(v as typeof refType); setRefId(undefined); }}
                    options={[
                        { value: 'customer_receipt', label: 'Phiếu thu' },
                        { value: 'vendor_payment', label: 'Phiếu chi' },
                        { value: 'journal_entry', label: 'Bút toán (ID)' },
                    ]}
                />
                {refType === 'journal_entry' ? (
                    <Input
                        type="number"
                        placeholder="Nhập ID bút toán cần khớp"
                        onChange={(e) => setRefId(Number(e.target.value) || undefined)}
                    />
                ) : (
                    <Select
                        showSearch
                        value={refId}
                        placeholder={`Chọn ${refType === 'customer_receipt' ? 'phiếu thu' : 'phiếu chi'} (ưu tiên trùng số tiền)`}
                        style={{ width: '100%' }}
                        optionFilterProp="label"
                        options={sortedOptions}
                        onChange={(v) => setRefId(v as number)}
                        notFoundContent="Không có chứng từ phù hợp"
                    />
                )}
            </Space>
        </Modal>
    );
}

function CreateCashAccountModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const [form] = Form.useForm();
    const api = useScopedApi();
    const qc = useQueryClient();
    const { message } = App.useApp();
    const create = useMutation({
        mutationFn: async (vars: Record<string, unknown>) => {
            const { data } = await api!.post('/accounting/cash-accounts', vars);
            return data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', 'cash-accounts'] }),
    });

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Tạo quỹ/tài khoản ngân hàng"
            okText="Tạo"
            cancelText="Huỷ"
            destroyOnClose
            confirmLoading={create.isPending}
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    await create.mutateAsync(v);
                    message.success('Đã tạo.');
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form form={form} layout="vertical" initialValues={{ kind: 'bank', gl_account_code: '1121' }} preserve={false}>
                <Form.Item label="Mã" name="code" rules={[{ required: true, max: 32 }]}><Input placeholder="vd BANK-VCB" /></Form.Item>
                <Form.Item label="Tên" name="name" rules={[{ required: true, max: 255 }]}><Input placeholder="vd Vietcombank — TK chính" /></Form.Item>
                <Form.Item label="Loại" name="kind" rules={[{ required: true }]}>
                    <Radio.Group optionType="button" buttonStyle="solid">
                        <Radio.Button value="cash">Tiền mặt</Radio.Button>
                        <Radio.Button value="bank">Ngân hàng</Radio.Button>
                        <Radio.Button value="ewallet">Ví điện tử</Radio.Button>
                        <Radio.Button value="cod_intransit">COD đang về</Radio.Button>
                    </Radio.Group>
                </Form.Item>
                <Form.Item label="TK kế toán (GL)" name="gl_account_code" rules={[{ required: true }]} tooltip="Mã TK trong CoA (vd 1111 cho tiền mặt, 1121 cho TGNH).">
                    <Input placeholder="1111 / 1121" />
                </Form.Item>
                <Form.Item label="Tên ngân hàng" name="bank_name"><Input maxLength={100} /></Form.Item>
                <Form.Item label="Số tài khoản" name="account_no"><Input maxLength={64} /></Form.Item>
                <Form.Item label="Chủ tài khoản" name="account_holder"><Input maxLength={255} /></Form.Item>
            </Form>
        </Modal>
    );
}

interface ImportLine {
    txn_date: string;
    amount: number;
    counter_party?: string;
    memo?: string;
    external_ref?: string;
}

function ImportStatementModal({ open, onClose, accounts }: { open: boolean; onClose: () => void; accounts: CashAccount[] }) {
    const [form] = Form.useForm();
    const [csvLines, setCsvLines] = useState<ImportLine[]>([]);
    const [parseErr, setParseErr] = useState<string | null>(null);
    const api = useScopedApi();
    const qc = useQueryClient();
    const { message } = App.useApp();

    const importM = useMutation({
        mutationFn: async (vars: Record<string, unknown>) => {
            const { data } = await api!.post('/accounting/bank-statements/import', vars);
            return data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting'] }),
    });

    /** Parse CSV: "date,amount,counter_party,memo,external_ref". Comma trong memo cần quote. */
    const onFile = async (file: File) => {
        try {
            const text = await file.text();
            const rows = text.split(/\r?\n/).filter((l) => l.trim() !== '');
            const header = rows[0].toLowerCase();
            const startIdx = header.includes('date') || header.includes('ngày') ? 1 : 0;
            const out: ImportLine[] = [];
            for (let i = startIdx; i < rows.length; i++) {
                // simple split by comma, không hỗ trợ escape — đủ cho CSV phổ thông
                const cols = rows[i].split(',').map((c) => c.trim());
                if (cols.length < 2) continue;
                const date = cols[0];
                const amount = Number(cols[1].replace(/[^\d-]/g, ''));
                if (Number.isNaN(amount) || amount === 0) continue;
                out.push({
                    txn_date: dayjs(date, ['YYYY-MM-DD', 'DD/MM/YYYY', 'YYYY-MM-DD HH:mm:ss']).format('YYYY-MM-DD HH:mm:ss'),
                    amount,
                    counter_party: cols[2],
                    memo: cols[3],
                    external_ref: cols[4],
                });
            }
            setCsvLines(out);
            setParseErr(null);
        } catch {
            setParseErr('Không đọc được CSV. Vui lòng kiểm tra định dạng (UTF-8).');
        }
        return false; // chặn upload tự động — chỉ parse client
    };

    return (
        <Modal
            open={open}
            onCancel={() => { setCsvLines([]); setParseErr(null); onClose(); }}
            title="Import sao kê ngân hàng"
            width={840}
            okText="Import"
            cancelText="Huỷ"
            destroyOnClose
            okButtonProps={{ disabled: csvLines.length === 0 }}
            confirmLoading={importM.isPending}
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    await importM.mutateAsync({
                        cash_account_id: v.cash_account_id,
                        period_start: v.period[0].format('YYYY-MM-DD'),
                        period_end: v.period[1].format('YYYY-MM-DD'),
                        imported_from: 'csv',
                        lines: csvLines,
                    });
                    message.success(`Đã import ${csvLines.length} giao dịch.`);
                    setCsvLines([]); setParseErr(null);
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form form={form} layout="vertical" preserve={false}>
                <Form.Item label="Tài khoản ngân hàng" name="cash_account_id" rules={[{ required: true }]}>
                    <Select
                        placeholder="Chọn tài khoản"
                        options={accounts.filter((a) => a.kind === 'bank' || a.kind === 'cash').map((a) => ({ value: a.id, label: `${a.code} · ${a.name}` }))}
                    />
                </Form.Item>
                <Form.Item label="Kỳ sao kê" name="period" rules={[{ required: true }]}>
                    <DatePicker.RangePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item label="File CSV" tooltip="Định dạng: date,amount,counter_party,memo,external_ref. Amount âm = chi, dương = thu.">
                    <Upload
                        beforeUpload={onFile as never}
                        accept=".csv,text/csv"
                        maxCount={1}
                        showUploadList={false}
                    >
                        <Button icon={<FileExcelOutlined />}>Chọn CSV</Button>
                    </Upload>
                    {parseErr && <Typography.Paragraph type="danger" style={{ marginTop: 4 }}>{parseErr}</Typography.Paragraph>}
                    {csvLines.length > 0 && (
                        <Typography.Paragraph type="success" style={{ marginTop: 4 }}>
                            Đã parse {csvLines.length} dòng. Tổng thu: {formatAmount(csvLines.filter((l) => l.amount > 0).reduce((s, l) => s + l.amount, 0))} ₫,
                            Tổng chi: {formatAmount(Math.abs(csvLines.filter((l) => l.amount < 0).reduce((s, l) => s + l.amount, 0)))} ₫.
                        </Typography.Paragraph>
                    )}
                </Form.Item>
            </Form>
        </Modal>
    );
}
