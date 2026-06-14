import { Button, Tabs } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { useQueryClient } from '@tanstack/react-query';
import { PageHeader } from '@/components/PageHeader';
import { useCurrentTenantId } from '@/lib/tenant';
import { ListingDraftsTable } from '@/features/products/ListingDraftsTable';

/**
 * Trang 3 — "Chờ đẩy lên sàn" gồm 2 tab:
 *  - Chờ đẩy: bản nháp chưa đăng (draft/ready/failed/pushing) — soạn, đẩy lẻ/hàng loạt, xóa.
 *  - Lịch sử đã đẩy: listing đã đẩy thành công (reviewing/live) — xem lại + xóa khỏi danh sách.
 */
export function PublishablePage() {
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();

    return (
        <div>
            <PageHeader
                title="Chờ đẩy lên sàn"
                subtitle="Bản nháp listing chưa đăng — soạn nốt, đẩy lẻ hoặc đẩy hàng loạt; xem lại sản phẩm đã đẩy ở tab Lịch sử"
                extra={
                    <Button
                        icon={<ReloadOutlined />}
                        onClick={() => qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] })}
                    >
                        Làm mới
                    </Button>
                }
            />
            <Tabs
                items={[
                    {
                        key: 'pending',
                        label: 'Chờ đẩy',
                        children: (
                            <ListingDraftsTable
                                statuses={['ready', 'draft', 'failed', 'pushing']}
                                showPush
                                emptyText="Chưa có bản nháp nào. Tạo nháp từ mục “Sản phẩm sao chép”."
                            />
                        ),
                    },
                    {
                        key: 'history',
                        label: 'Lịch sử đã đẩy',
                        children: (
                            <ListingDraftsTable
                                statuses={['reviewing', 'live', 'published']}
                                history
                                emptyText="Chưa có sản phẩm nào được đẩy lên sàn."
                            />
                        ),
                    },
                ]}
            />
        </div>
    );
}
