import { useState } from 'react';
import { App as AntApp, Button, Drawer, Empty, List, Popconfirm, Space, Spin, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, DeleteOutlined, FileTextOutlined } from '@ant-design/icons';
import { useDeleteSavedReport, useSavedReport, useSavedReports, type ReportRow } from '@/lib/marketing';
import { formatDate } from '@/lib/format';

const { Text } = Typography;

const LEVEL_VI: Record<string, string> = { campaign: 'Chiến dịch', adset: 'Nhóm QC', ad: 'Quảng cáo' };

interface Props {
    open: boolean;
    accountId: number | null;
    onClose: () => void;
}

function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}

/** Danh sách báo cáo đã lưu theo đợt lọc + xem lại snapshot chi tiết của từng đợt. */
export function SavedReportsDrawer({ open, accountId, onClose }: Props) {
    const { message } = AntApp.useApp();
    const { data: reports, isLoading } = useSavedReports(accountId);
    const [viewId, setViewId] = useState<number | null>(null);
    const { data: detail, isLoading: detailLoading } = useSavedReport(viewId);
    const deleteReport = useDeleteSavedReport();

    return (
        <Drawer
            open={open}
            onClose={() => { setViewId(null); onClose(); }}
            width={720}
            title={
                viewId != null ? (
                    <Space>
                        <Button type="text" icon={<ArrowLeftOutlined />} onClick={() => setViewId(null)} />
                        {detail?.name ?? 'Báo cáo'}
                    </Space>
                ) : (
                    <Space><FileTextOutlined />Báo cáo đã lưu</Space>
                )
            }
            destroyOnClose
        >
            {viewId != null ? (
                detailLoading || detail == null ? (
                    <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                ) : (
                    <div>
                        <Space wrap style={{ marginBottom: 12 }}>
                            <Tag>{LEVEL_VI[detail.level] ?? detail.level}</Tag>
                            <Text type="secondary">{detail.since} → {detail.until}</Text>
                            {detail.created_at && <Text type="secondary">· lưu {formatDate(detail.created_at)}</Text>}
                        </Space>
                        <Table<ReportRow>
                            rowKey="external_id"
                            size="small"
                            scroll={{ x: 'max-content' }}
                            pagination={{ defaultPageSize: 20, showSizeChanger: true }}
                            dataSource={detail.rows}
                            columns={[
                                { title: 'Tên', dataIndex: 'name', key: 'name', fixed: 'left', width: 180, render: (v: string | null, r) => v ?? r.external_id },
                                { title: 'Chi tiêu', key: 'spend', render: (_, r) => money(r.insights?.spend, detail.currency) },
                                { title: 'Hiển thị', key: 'impr', render: (_, r) => (r.insights?.impressions ?? 0).toLocaleString('vi-VN') },
                                { title: 'Click', key: 'clicks', render: (_, r) => (r.insights?.clicks ?? 0).toLocaleString('vi-VN') },
                                { title: 'CTR', key: 'ctr', render: (_, r) => (r.insights?.ctr == null ? '—' : r.insights.ctr.toFixed(2) + '%') },
                                { title: 'CPC', key: 'cpc', render: (_, r) => money(r.insights?.cpc, detail.currency) },
                                { title: 'Hội thoại', key: 'conv', render: (_, r) => (r.insights?.messaging_conversations ?? 0).toLocaleString('vi-VN') },
                                { title: 'Leads', key: 'leads', render: (_, r) => (r.insights?.leads ?? 0).toLocaleString('vi-VN') },
                            ]}
                        />
                    </div>
                )
            ) : isLoading ? (
                <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
            ) : reports == null || reports.length === 0 ? (
                <Empty description="Chưa có báo cáo nào được lưu. Bấm 'Lưu báo cáo' ở bảng để lưu đợt lọc hiện tại." />
            ) : (
                <List
                    dataSource={reports}
                    renderItem={(r) => (
                        <List.Item
                            actions={[
                                <Button key="view" type="link" size="small" onClick={() => setViewId(r.id)}>Xem</Button>,
                                <Popconfirm
                                    key="del"
                                    title="Xoá báo cáo này?"
                                    okText="Xoá" cancelText="Huỷ"
                                    onConfirm={() => deleteReport.mutate(r.id, { onSuccess: () => message.success('Đã xoá.') })}
                                >
                                    <Button type="text" size="small" danger icon={<DeleteOutlined />} />
                                </Popconfirm>,
                            ]}
                        >
                            <List.Item.Meta
                                title={r.name}
                                description={
                                    <Space wrap size={6}>
                                        <Tag>{LEVEL_VI[r.level] ?? r.level}</Tag>
                                        <Text type="secondary">{r.since} → {r.until}</Text>
                                        <Text type="secondary">· {r.row_count} dòng</Text>
                                        {r.created_at && <Text type="secondary">· {formatDate(r.created_at)}</Text>}
                                    </Space>
                                }
                            />
                        </List.Item>
                    )}
                />
            )}
        </Drawer>
    );
}
