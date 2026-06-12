import { Button } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { useQueryClient } from '@tanstack/react-query';
import { PageHeader } from '@/components/PageHeader';
import { getCurrentTenantId } from '@/lib/auth';
import { ListingDraftsTable } from '@/features/products/ListingDraftsTable';

/**
 * Trang 2 — "Đã có trên sàn": listing đã/đang đăng trên gian hàng (live, pushing).
 * Cho phép sửa lại (mở editor), sao chép sang gian hàng khác (clone) và làm mới trạng thái.
 */
export function OnChannelPage() {
    const qc = useQueryClient();
    const tenantId = getCurrentTenantId();

    return (
        <div>
            <PageHeader
                title="Sản phẩm đã có trên sàn"
                subtitle="Listing đã đăng lên gian hàng — sửa lại, sao chép sang gian hàng khác hoặc làm mới trạng thái"
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
                statuses={['live', 'published', 'pushing']}
                emptyText="Chưa có sản phẩm nào đã đăng lên sàn. Đăng từ mục “Chờ đẩy lên sàn”."
            />
        </div>
    );
}
