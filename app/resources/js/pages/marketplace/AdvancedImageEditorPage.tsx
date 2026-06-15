import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Result, Spin } from 'antd';
import { AdvancedImageEditor } from '@/components/AdvancedImageEditor';
import { errorMessage } from '@/lib/api';
import { useListing, useUpdateListing } from '@/features/products/hooks';

/**
 * Trang sửa ảnh NÂNG CAO cho NHÁP đăng sàn. Lưu ảnh đã sửa vào `media_refs` của nháp (DB)
 * rồi quay lại màn soạn nháp. Phần trình sửa dùng chung {@see AdvancedImageEditor}.
 *
 * Route: /marketplace/listings/:id/images/edit?url=<encoded image url>
 */
export function AdvancedImageEditorPage() {
    const { id } = useParams();
    const listingId = Number(id);
    const [params] = useSearchParams();
    const sourceUrl = params.get('url') ?? '';
    const navigate = useNavigate();
    const { message } = AntApp.useApp();

    const { data: listing, isLoading, isError } = useListing(Number.isFinite(listingId) ? listingId : null);
    const updateListing = useUpdateListing();

    const back = () => navigate(`/marketplace/listings/${listingId}/edit`);

    const handleSaved = async (newUrl: string) => {
        if (!listing) return;
        try {
            const current = listing.media_refs ?? [];
            const next = current.includes(sourceUrl)
                ? current.map((u) => (u === sourceUrl ? newUrl : u))
                : [...current, newUrl];
            await updateListing.mutateAsync({ id: listingId, payload: { media_refs: next } });
            message.success('Đã lưu ảnh đã chỉnh sửa.');
            back();
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    if (isError) {
        return <Result status="error" title="Không tải được bản nháp" extra={<Button onClick={back}>Quay lại</Button>} />;
    }
    if (isLoading || !listing) {
        return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;
    }

    return <AdvancedImageEditor source={sourceUrl} onSaved={handleSaved} onClose={back} />;
}

export default AdvancedImageEditorPage;
