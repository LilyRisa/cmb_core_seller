import { Button } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { useQueryClient } from '@tanstack/react-query';
import { PageHeader } from '@/components/PageHeader';
import { useCurrentTenantId } from '@/lib/tenant';
import { ListingDraftsTable } from '@/features/products/ListingDraftsTable';

/**
 * Trang 3 — "Chờ đẩy lên sàn": bản nháp đã sẵn sàng (ready) hoặc còn thiếu (draft/failed).
 * Cho phép đẩy lẻ / đẩy hàng loạt, sửa và sao chép sang gian hàng khác.
 */
export function PublishablePage() {
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();

    return (
        <div>
            <PageHeader
                title="Chờ đẩy lên sàn"
                subtitle="Bản nháp listing chưa đăng — soạn nốt, đẩy lẻ hoặc đẩy hàng loạt lên các gian hàng đã kết nối"
                extra={
                    <Button
                        icon={<ReloadOutlined />}
                        onClick={() => qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] })}
                    >
                        Làm mới
                    </Button>
                }
            />
            <ListingDraftsTable
                statuses={['ready', 'draft', 'failed']}
                showPush
                emptyText="Chưa có bản nháp nào. Tạo nháp từ mục “Sản phẩm sao chép”."
            />
        </div>
    );
}
