import { useMemo, useState } from 'react';
import { App, Button, Card, DatePicker, Form, Input, Modal, Radio, Select, Space, Statistic, Table, Tag, Tooltip, Typography, Upload } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { BankOutlined, FileExcelOutlined, PlusOutlined, ReloadOutlined, WalletOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatAmount } from '@/lib/accounting';
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
                <Typography.Text strong style={{ color: v < 0 ? '#cf1322' : '#1668dc' }}>
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
                    <Statistic title="Tổng tiền mặt + ngân hàng" value={totalBalance} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#1668dc' }} />
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

            <CreateCashAccountModal open={createOpen} onClose={() => setCreateOpen(false)} />
            <ImportStatementModal open={importOpen} onClose={() => setImportOpen(false)} accounts={accounts} />
        </div>
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
                const amount = Number(cols[1].replace(/[^\d\-]/g, ''));
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
        } catch (e) {
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
