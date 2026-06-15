import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { Button, Result } from 'antd';
import { AdvancedImageEditor } from '@/components/AdvancedImageEditor';
import { useMarketplaceEditStore } from '@/lib/marketplace/editStore';

/**
 * Trang sửa ảnh NÂNG CAO cho sản phẩm ĐÃ có trên sàn. Ảnh đã sửa được ghi vào KHO TẠM
 * (Zustand) — KHÔNG đẩy lên sàn ngay; quay lại trang sửa để đẩy theo loạt. Phần trình sửa
 * dùng chung {@see AdvancedImageEditor}.
 *
 * Route: /marketplace/on-channel/:id/images/edit?url=<encoded image url>
 *
 * Chỉ mở từ trang sửa (kho tạm đã có dữ liệu listing này). Mở trực tiếp/refresh khi kho
 * trống ⇒ không đủ ngữ cảnh, mời quay lại.
 */
export function MarketplaceImageEditorPage() {
    const { id } = useParams();
    const listingId = Number(id);
    const [params] = useSearchParams();
    const sourceUrl = params.get('url') ?? '';
    const navigate = useNavigate();

    const storeId = useMarketplaceEditStore((s) => s.id);
    const replaceImage = useMarketplaceEditStore((s) => s.replaceImage);

    const back = () => navigate(`/marketplace/on-channel/${listingId}/edit`);

    if (storeId !== listingId) {
        return (
            <Result
                status="info"
                title="Mở trình sửa ảnh từ trang sửa sản phẩm"
                subTitle="Hãy mở lại từ nút sửa ảnh trên trang sửa sản phẩm để giữ đúng ngữ cảnh."
                extra={<Button type="primary" onClick={back}>Về trang sửa</Button>}
            />
        );
    }

    const handleSaved = (newUrl: string) => {
        replaceImage(sourceUrl, newUrl);
        back();
    };

    return <AdvancedImageEditor source={sourceUrl} onSaved={handleSaved} onClose={back} />;
}

export default MarketplaceImageEditorPage;
