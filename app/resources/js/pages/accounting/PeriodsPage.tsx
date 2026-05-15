import { useState } from 'react';
import { App, Button, Card, Input, Modal, Popconfirm, Segmented, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CalendarOutlined, CheckOutlined, LockOutlined, PlusOutlined, UnlockOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { FiscalPeriod, PERIOD_STATUS_COLOR, PeriodKind, useEnsureYearPeriods, useFiscalPeriods, usePeriodAction } from '@/lib/accounting';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

export function PeriodsPage() {
    const currentYear = dayjs().year();
    const [kind, setKind] = useState<PeriodKind>('month');
    const [year, setYear] = useState<number>(currentYear);
    const [closeTarget, setCloseTarget] = useState<FiscalPeriod | null>(null);
    const [ensureOpen, setEnsureOpen] = useState(false);
    const [ensureYear, setEnsureYearValue] = useState<number>(currentYear + 1);

    const { data: periods = [], isFetching } = useFiscalPeriods({ kind, year });
    const action = usePeriodAction();
    const ensure = useEnsureYearPeriods();
    const canClose = useCan('accounting.close_period');
    const canConfig = useCan('accounting.config');
    const { message } = App.useApp();

    const columns: ColumnsType<FiscalPeriod> = [
        {
            title: 'Mã kỳ',
            dataIndex: 'code',
            width: 110,
            render: (code: string) => (
                <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{code}</Typography.Text>
            ),
        },
        {
            title: 'Khoảng thời gian',
            width: 240,
            render: (_, r) => `${dayjs(r.start_date).format('DD/MM/YYYY')} — ${dayjs(r.end_date).format('DD/MM/YYYY')}`,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 140,
            render: (s: FiscalPeriod['status'], r) => (
                <Tag color={PERIOD_STATUS_COLOR[s]} icon={s === 'locked' ? <LockOutlined /> : s === 'closed' ? <CheckOutlined /> : null}>
                    {r.status_label}
                </Tag>
            ),
        },
        {
            title: 'Đóng lúc',
            dataIndex: 'closed_at',
            width: 180,
            render: (t: string | null) => t ? dayjs(t).format('DD/MM/YYYY HH:mm') : <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Ghi chú đóng kỳ',
            dataIndex: 'close_note',
            ellipsis: true,
            render: (n: string | null) => n ?? <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Thao tác',
            width: 280,
            align: 'right',
            render: (_, r) => (
                <Space size={4}>
                    {r.status === 'open' && canClose && (
                        <Button size="small" type="primary" ghost icon={<CheckOutlined />} onClick={() => setCloseTarget(r)}>Đóng kỳ</Button>
                    )}
                    {r.status === 'closed' && canClose && (
                        <Popconfirm
                            title="Mở lại kỳ?"
                            description="Chặn nếu kỳ kế tiếp đã đóng/khoá."
                            okText="Mở lại"
                            cancelText="Huỷ"
                            onConfirm={async () => {
                                try {
                                    await action.mutateAsync({ code: r.code, action: 'reopen' });
                                    message.success(`Đã mở lại kỳ ${r.code}.`);
                                } catch (e) { message.error(errorMessage(e)); }
                            }}
                        >
                            <Tooltip title="Mở lại"><Button size="small" icon={<UnlockOutlined />} /></Tooltip>
                        </Popconfirm>
                    )}
                    {r.status === 'closed' && canConfig && (
                        <Popconfirm
                            title="Khoá vĩnh viễn?"
                            description="Sau khi khoá, không bao giờ mở lại được. Dùng khi đã nộp tờ khai chính thức."
                            okText="Khoá"
                            okButtonProps={{ danger: true }}
                            cancelText="Huỷ"
                            onConfirm={async () => {
                                try {
                                    await action.mutateAsync({ code: r.code, action: 'lock' });
                                    message.success(`Đã khoá kỳ ${r.code}.`);
                                } catch (e) { message.error(errorMessage(e)); }
                            }}
                        >
                            <Tooltip title="Khoá vĩnh viễn"><Button size="small" danger icon={<LockOutlined />} /></Tooltip>
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />

            <Card
                title={
                    <Space size={10}>
                        <Typography.Title level={5} style={{ margin: 0 }}>Kỳ kế toán</Typography.Title>
                        <Tag color="blue">Năm dương lịch</Tag>
                    </Space>
                }
                extra={canConfig ? (
                    <Button icon={<PlusOutlined />} onClick={() => setEnsureOpen(true)}>Tạo kỳ cho năm khác</Button>
                ) : null}
                styles={{ body: { padding: 0 } }}
            >
                <div style={{ padding: '12px 16px', borderBottom: '1px solid #f0f0f0', display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                    <Space size={6}>
                        <CalendarOutlined style={{ color: '#8c8c8c' }} />
                        <Segmented<PeriodKind>
                            value={kind}
                            onChange={(v) => setKind(v as PeriodKind)}
                            options={[
                                { value: 'month', label: 'Tháng' },
                                { value: 'quarter', label: 'Quý' },
                                { value: 'year', label: 'Năm' },
                            ]}
                        />
                    </Space>
                    <Space size={6}>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Năm</span>
                        <Input
                            type="number"
                            value={year}
                            min={2020}
                            max={2099}
                            onChange={(e) => setYear(parseInt(e.target.value, 10) || currentYear)}
                            style={{ width: 100 }}
                        />
                    </Space>
                </div>

                <Table<FiscalPeriod>
                    rowKey="id"
                    dataSource={periods}
                    columns={columns}
                    loading={isFetching}
                    pagination={false}
                    size="middle"
                    scroll={{ x: 900 }}
                    locale={{ emptyText: 'Chưa có kỳ kế toán cho năm này — bấm "Tạo kỳ cho năm khác" hoặc khởi tạo TT133.' }}
                />
            </Card>

            <Modal
                open={closeTarget != null}
                onCancel={() => setCloseTarget(null)}
                title={closeTarget ? `Đóng kỳ ${closeTarget.code}` : ''}
                okText="Đóng kỳ"
                cancelText="Huỷ"
                destroyOnClose
                confirmLoading={action.isPending}
                onOk={async () => {
                    if (!closeTarget) return;
                    const note = (document.getElementById('close-note') as HTMLTextAreaElement)?.value;
                    try {
                        await action.mutateAsync({ code: closeTarget.code, action: 'close', note });
                        message.success(`Đã đóng kỳ ${closeTarget.code}.`);
                        setCloseTarget(null);
                    } catch (e) { message.error(errorMessage(e)); }
                }}
            >
                <Typography.Paragraph>
                    Sau khi đóng kỳ <b>{closeTarget?.code}</b>, mọi bút toán mới có ngày trong kỳ này sẽ bị từ chối.
                    Bút toán đảo cho entry đã đóng sẽ tự động post vào kỳ mở kế tiếp.
                </Typography.Paragraph>
                <Typography.Text type="secondary">Ghi chú đóng kỳ (tuỳ chọn):</Typography.Text>
                <Input.TextArea id="close-note" rows={3} maxLength={500} placeholder="vd: Đã đối chiếu tồn kho + đối soát sàn." style={{ marginTop: 4 }} />
            </Modal>

            <Modal
                open={ensureOpen}
                onCancel={() => setEnsureOpen(false)}
                title="Tạo kỳ kế toán cho năm khác"
                okText="Tạo"
                cancelText="Huỷ"
                destroyOnClose
                confirmLoading={ensure.isPending}
                onOk={async () => {
                    try {
                        const r = await ensure.mutateAsync(ensureYear);
                        message.success(`Đã tạo ${r.created} kỳ cho năm ${ensureYear}.`);
                        setEnsureOpen(false);
                    } catch (e) { message.error(errorMessage(e)); }
                }}
            >
                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                    <Typography.Paragraph>Hệ thống sẽ tạo: 12 tháng + 4 quý + 1 năm cho năm bạn chọn (bỏ qua kỳ đã tồn tại).</Typography.Paragraph>
                    <Space size={8}>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Năm</span>
                        <Input type="number" value={ensureYear} min={2020} max={2099}
                            onChange={(e) => setEnsureYearValue(parseInt(e.target.value, 10) || currentYear)}
                            style={{ width: 120 }} />
                    </Space>
                </Space>
            </Modal>
        </div>
    );
}
