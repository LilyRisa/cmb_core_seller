import { useMemo } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Result, Spin } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import FilerobotImageEditor, { TABS } from 'react-filerobot-image-editor';
import { tenantApi, errorMessage } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import { useListing, useUpdateListing } from '@/features/products/hooks';

/** Chuyển dataURL (base64) từ trình sửa ảnh thành File để upload. */
function dataUrlToFile(dataUrl: string, filename: string): File {
    const [meta, b64] = dataUrl.split(',');
    const mime = /:(.*?);/.exec(meta)?.[1] ?? 'image/png';
    const bin = atob(b64);
    const arr = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i += 1) arr[i] = bin.charCodeAt(i);
    return new File([arr], filename, { type: mime });
}

/**
 * Trang sửa ảnh NÂNG CAO (riêng) — crop, resize, xoay, lật, bộ lọc, finetune, thêm
 * chữ/hình, watermark, undo/redo (dùng react-filerobot-image-editor). Khi lưu: xuất
 * ảnh → upload → thay vào media_refs của nháp → quay lại màn soạn nháp.
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

    const tenantId = useCurrentTenantId();
    const client = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const { data: listing, isLoading, isError } = useListing(Number.isFinite(listingId) ? listingId : null);
    const updateListing = useUpdateListing();

    const back = () => navigate(`/marketplace/listings/${listingId}/edit`);

    const handleSave = async (edited: { imageBase64?: string; fullName?: string }) => {
        if (!client || !listing || !edited.imageBase64) return;
        try {
            const file = dataUrlToFile(edited.imageBase64, edited.fullName ?? 'edited.png');
            const form = new FormData();
            form.append('image', file);
            form.append('folder', 'listings');
            const { data } = await client.post<{ data: { url: string } }>('/media/image', form);
            const newUrl = data.data.url;

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

    if (!sourceUrl) {
        return <Result status="warning" title="Thiếu ảnh để sửa" extra={<Button onClick={back}>Quay lại</Button>} />;
    }
    if (isError) {
        return <Result status="error" title="Không tải được bản nháp" extra={<Button onClick={back}>Quay lại</Button>} />;
    }
    if (isLoading || !listing) {
        return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;
    }

    return (
        <div style={{ height: 'calc(100vh - 120px)', minHeight: 520, display: 'flex', flexDirection: 'column' }}>
            <div style={{ marginBottom: 8 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={back}>Quay lại không lưu</Button>
            </div>
            <div style={{ flex: 1, minHeight: 0 }}>
                <FilerobotImageEditor
                    source={sourceUrl}
                    onSave={(edited) => handleSave(edited as { imageBase64?: string; fullName?: string })}
                    onClose={back}
                    tabsIds={[TABS.ADJUST, TABS.FINETUNE, TABS.FILTERS, TABS.ANNOTATE, TABS.WATERMARK, TABS.RESIZE]}
                    defaultTabId={TABS.ADJUST}
                    savingPixelRatio={1}
                    previewPixelRatio={1}
                    language="en"
                    Rotate={{ angle: 90, componentType: 'slider' }}
                />
            </div>
        </div>
    );
}

export default AdvancedImageEditorPage;
