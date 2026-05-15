import { useEffect, useMemo, useState } from 'react';
import { App, Button, Card, Input, Segmented, Space, Statistic, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ReloadOutlined, SearchOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { AccountBalance, AccountType, ACCOUNT_TYPE_COLOR, ACCOUNT_TYPE_LABEL, ChartAccount, formatAmount, useBalances, useChartAccounts, useFiscalPeriods, useRecomputeBalances } from '@/lib/accounting';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

/**
 * Trang Cân đối số phát sinh (live) — Phase 7.1.
 *  - Đọc `account_balances` đã materialized; cho phép recompute trên 1 kỳ.
 *  - Báo cáo BCTC đầy đủ (B01/B02/B03) sẽ được mở rộng ở Phase 7.5.
 */
export function BalancesPage() {
    const { data: periods = [] } = useFiscalPeriods({ kind: 'month' });
    const defaultPeriod = useMemo(() => periods.find((p) => p.code === dayjs().format('YYYY-MM'))?.code ?? periods[0]?.code, [periods]);
    const [period, setPeriod] = useState<string | undefined>();
    useEffect(() => {
        if (!period && defaultPeriod) setPeriod(defaultPeriod);
    }, [defaultPeriod, period]);

    const { data: balances = [], isFetching, refetch } = useBalances(period ?? null);
    const { data: accounts = [] } = useChartAccounts({ active_only: true });
    const accountMap = useMemo(() => new Map<number, ChartAccount>(accounts.map((a) => [a.id, a])), [accounts]);
    const [q, setQ] = useState('');
    const [typeFilter, setTypeFilter] = useState<AccountType | 'all'>('all');
    const recompute = useRecomputeBalances();
    const { message } = App.useApp();
    const canConfig = useCan('accounting.config');

    const rows = useMemo(() => {
        return balances
            .map((b) => ({ ...b, account: accountMap.get(b.account_id) }))
            .filter((r) => {
                if (!r.account) return false;
                if (typeFilter !== 'all' && r.account.type !== typeFilter) return false;
                if (q) {
                    const search = q.toLowerCase();
                    if (!r.account.code.toLowerCase().includes(search) && !r.account.name.toLowerCase().includes(search)) return false;
                }
                return true;
            });
    }, [balances, accountMap, typeFilter, q]);

    const totals = useMemo(() => rows.reduce((acc, r) => {
        acc.debit += r.debit; acc.credit += r.credit; return acc;
    }, { debit: 0, credit: 0 }), [rows]);

    const columns: ColumnsType<AccountBalance & { account?: ChartAccount }> = [
        {
            title: 'Mã TK',
            dataIndex: 'account_code',
            width: 130,
            render: (_, r) => (
                <Space size={4}>
                    <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{r.account?.code ?? ''}</Typography.Text>
                    {r.account && <Tag color={ACCOUNT_TYPE_COLOR[r.account.type]} style={{ marginInlineEnd: 0, fontSize: 11 }}>{ACCOUNT_TYPE_LABEL[r.account.type]}</Tag>}
                </Space>
            ),
        },
        {
            title: 'Tên tài khoản',
            render: (_, r) => r.account?.name ?? '',
            ellipsis: true,
        },
        {
            title: 'Dư đầu',
            dataIndex: 'opening',
            width: 150,
            align: 'right',
            render: (v: number) => v === 0 ? <Typography.Text type="secondary">0</Typography.Text> : <Typography.Text>{formatAmount(v)}</Typography.Text>,
        },
        {
            title: 'Phát sinh Nợ',
            dataIndex: 'debit',
            width: 150,
            align: 'right',
            render: (v: number) => v === 0 ? <Typography.Text type="secondary">0</Typography.Text> : <Typography.Text strong>{formatAmount(v)}</Typography.Text>,
        },
        {
            title: 'Phát sinh Có',
            dataIndex: 'credit',
            width: 150,
            align: 'right',
            render: (v: number) => v === 0 ? <Typography.Text type="secondary">0</Typography.Text> : <Typography.Text strong>{formatAmount(v)}</Typography.Text>,
        },
        {
            title: 'Dư cuối',
            dataIndex: 'closing',
            width: 160,
            align: 'right',
            render: (v: number) => {
                const isNeg = v < 0;
                return (
                    <Typography.Text strong style={{ color: isNeg ? '#cf1322' : undefined }}>
                        {formatAmount(v)}
                    </Typography.Text>
                );
            },
        },
    ];

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />

            <Card
                title={
                    <Space size={10}>
                        <Typography.Title level={5} style={{ margin: 0 }}>Cân đối số phát sinh</Typography.Title>
                        {period && <Tag color="blue">{period}</Tag>}
                    </Space>
                }
                extra={
                    <Space>
                        <Tooltip title="Tải lại"><Button icon={<ReloadOutlined />} onClick={() => refetch()} /></Tooltip>
                        {canConfig && period && (
                            <Button type="primary" loading={recompute.isPending} onClick={async () => {
                                try {
                                    const r = await recompute.mutateAsync(period);
                                    message.success(`Đã tính lại — ${r.rows} TK.`);
                                    refetch();
                                } catch (e) { message.error(errorMessage(e)); }
                            }}>Tính lại số dư</Button>
                        )}
                    </Space>
                }
                styles={{ body: { padding: 0 } }}
            >
                <div style={{ padding: '12px 16px', borderBottom: '1px solid #f0f0f0', display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                    <Space size={6}>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Kỳ</span>
                        <Segmented<string>
                            value={period}
                            onChange={(v) => setPeriod(v as string)}
                            options={periods.slice(0, 12).map((p) => ({ value: p.code, label: p.code }))}
                        />
                    </Space>
                    <Space size={6}>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Loại</span>
                        <Segmented<AccountType | 'all'>
                            value={typeFilter}
                            onChange={(v) => setTypeFilter(v as AccountType | 'all')}
                            options={[
                                { value: 'all', label: 'Tất cả' },
                                { value: 'asset', label: 'TS' },
                                { value: 'liability', label: 'Nợ' },
                                { value: 'equity', label: 'Vốn' },
                                { value: 'revenue', label: 'DT' },
                                { value: 'expense', label: 'CP' },
                                { value: 'cogs', label: 'GVHB' },
                            ]}
                        />
                    </Space>
                    <Input
                        prefix={<SearchOutlined style={{ color: '#8c8c8c' }} />}
                        allowClear
                        placeholder="Tìm mã / tên TK…"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        style={{ maxWidth: 280 }}
                    />
                </div>
                <div style={{ padding: '16px 16px 0', display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16 }}>
                    <Statistic title="Tổng phát sinh Nợ" value={totals.debit} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                    <Statistic title="Tổng phát sinh Có" value={totals.credit} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                    <Statistic title="Chênh lệch (Nợ-Có)" value={totals.debit - totals.credit} suffix="₫" valueStyle={{ color: totals.debit === totals.credit ? '#3f8600' : '#cf1322' }} formatter={(v) => formatAmount(Number(v))} />
                    <Statistic title="Số TK có phát sinh" value={rows.filter((r) => r.debit > 0 || r.credit > 0).length} />
                </div>

                <Table
                    rowKey="id"
                    dataSource={rows}
                    columns={columns}
                    loading={isFetching}
                    pagination={{ pageSize: 50, showSizeChanger: true, pageSizeOptions: [20, 50, 100], showTotal: (t) => `${t} tài khoản` }}
                    size="middle"
                    scroll={{ x: 900 }}
                    locale={{ emptyText: 'Chưa có phát sinh ở kỳ này, hoặc số dư chưa được tính. Bấm "Tính lại số dư".' }}
                    style={{ marginTop: 16 }}
                />
            </Card>
        </div>
    );
}
