import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, DatePicker, Radio, Space, Table, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { useAdminGrowthAttribution, type GrowthAttributionFilters, type GrowthAttributionRow } from '@admin/lib/admin';
import { formatMoney } from '@/lib/format';

const { RangePicker } = DatePicker;

const GROUP_BY_OPTIONS: Array<{ value: GrowthAttributionFilters['group_by']; label: string }> = [
    { value: 'utm_source', label: 'Nguồn (utm_source)' },
    { value: 'utm_campaign', label: 'Chiến dịch (utm_campaign)' },
    { value: 'utm_medium', label: 'Kênh (utm_medium)' },
];

export function AdminGrowthPage() {
    const navigate = useNavigate();
    const [groupBy, setGroupBy] = useState<GrowthAttributionFilters['group_by']>('utm_source');
    const [range, setRange] = useState<[dayjs.Dayjs, dayjs.Dayjs] | null>(null);

    const filters = useMemo<GrowthAttributionFilters>(() => ({
        group_by: groupBy,
        from: range?.[0]?.format('YYYY-MM-DD'),
        to: range?.[1]?.format('YYYY-MM-DD'),
    }), [groupBy, range]);

    const { data, isLoading } = useAdminGrowthAttribution(filters);

    const columns: ColumnsType<GrowthAttributionRow> = [
        { title: 'Nguồn', dataIndex: 'source', key: 'source' },
        { title: 'Đăng ký', dataIndex: 'signups', key: 'signups', width: 100 },
        { title: 'Đã lên gói', dataIndex: 'paid', key: 'paid', width: 110 },
        {
            title: 'Tỉ lệ chuyển đổi', dataIndex: 'conversion_rate', key: 'conversion_rate', width: 140,
            render: (v: number) => `${v}%`,
        },
        {
            title: 'Doanh thu', dataIndex: 'revenue_vnd', key: 'revenue_vnd', width: 160,
            render: (v: number) => formatMoney(v),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Tăng trưởng — Nguồn đăng ký"
                subtitle="Gom nhóm tenant theo UTM lúc đăng ký, đối chiếu với gói trả phí hiện tại. Nhấp 1 dòng để xem danh sách tenant thuộc nguồn đó."
            />

            <Card styles={{ body: { padding: 12 } }}>
                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Radio.Group value={groupBy} optionType="button" buttonStyle="solid"
                        onChange={(e) => setGroupBy(e.target.value)}
                        options={GROUP_BY_OPTIONS} />
                    <RangePicker
                        value={range}
                        onChange={(v) => setRange(v as [dayjs.Dayjs, dayjs.Dayjs] | null)}
                        placeholder={['Từ ngày đăng ký', 'Đến ngày đăng ký']}
                    />
                </Space>

                <Table<GrowthAttributionRow>
                    rowKey="source"
                    columns={columns}
                    dataSource={data ?? []}
                    loading={isLoading}
                    onRow={(r) => groupBy === 'utm_source'
                        ? {
                            onClick: () => navigate('/admin/tenants', { state: { presetUtmSource: r.source } }),
                            style: { cursor: 'pointer' },
                        }
                        : { style: { cursor: 'default' } }}
                    pagination={false}
                    size="middle"
                />
                {groupBy !== 'utm_source' && (
                    <Typography.Text type="secondary" style={{ display: 'block', marginTop: 8 }}>
                        Điều hướng sang danh sách tenant chỉ hỗ trợ khi gom nhóm theo utm_source.
                    </Typography.Text>
                )}
            </Card>
        </div>
    );
}
