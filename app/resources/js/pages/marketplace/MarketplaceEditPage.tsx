import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { Alert, App as AntApp, Button, Card, Image, Input, Modal, Result, Space, Spin, Tag, Typography, Upload } from 'antd';
import { CloudUploadOutlined, DeleteOutlined, EditOutlined, PictureOutlined, PlusOutlined, RobotOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { ImageResizer } from '@/components/ImageResizer';
import { RichTextEditor } from '@/components/RichTextEditor';
import { errorMessage, tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import { useChannelAccounts } from '@/lib/channels';
import type { ChannelListing } from '@/lib/inventory';
import { useAiSuggestMarketplaceDescription, useListingLimits, useMarketplaceDetail, useUpdateMarketplaceListing } from '@/features/products/hooks';
import type { MarketplaceEditPayload } from '@/features/products/api';
import { useMarketplaceEditStore, type MarketplaceEditDraft } from '@/lib/marketplace/editStore';

/**
 * Trang sửa một sản phẩm ĐÃ có trên sàn — bố cục & trải nghiệm giống trang soạn nháp,
 * giới hạn các trường sàn cho sửa live: tiêu đề / mô tả (AI gợi ý) / ảnh / giá theo SKU.
 *
 * Mọi thay đổi gom vào KHO TẠM (Zustand) — sửa ảnh nâng cao mở TRANG RIÊNG rồi quay lại
 * không mất dữ liệu; chỉ "Đẩy thay đổi lên sàn" mới đẩy theo loạt. Tồn KHÔNG sửa ở đây.
 */
export function MarketplaceEditPage() {
    const { id } = useParams();
    const listingId = Number(id);
    const navigate = useNavigate();
    const location = useLocation();
    const { message } = AntApp.useApp();
    const tenantId = useCurrentTenantId();

    const seed = (location.state as { listing?: ChannelListing } | null)?.listing;

    const { data: detail, isLoading, isError, error } = useMarketplaceDetail(Number.isFinite(listingId) ? listingId : null);
    const update = useUpdateMarketplaceListing();
    const ai = useAiSuggestMarketplaceDescription();

    const { data: channelData } = useChannelAccounts();
    const accounts = channelData?.data ?? [];
    const provider = seed ? (accounts.find((a) => a.id === seed.channel_account_id)?.provider ?? null) : null;
    const { data: limits } = useListingLimits(provider);
    const maxImages = limits?.max_images ?? 9;

    // Sàn dùng mô tả HTML (TikTok, Lazada) ⇒ trình soạn thảo (đậm/nghiêng/chèn ảnh…).
    // Shopee chỉ nhận text thuần ⇒ giữ ô nhập thường để tránh đẩy thẻ HTML lên sàn.
    const richDescription = provider === 'tiktok' || provider === 'lazada';

    const uploadClient = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);

    // Kho tạm — giữ thay đổi đang dở qua các lần điều hướng (trang sửa ảnh riêng).
    const draft = useMarketplaceEditStore((s) => s.draft);
    const storeBaseline = useMarketplaceEditStore((s) => s.baseline);
    const init = useMarketplaceEditStore((s) => s.init);
    const patch = useMarketplaceEditStore((s) => s.patch);
    const clear = useMarketplaceEditStore((s) => s.clear);

    const [aiSuggestion, setAiSuggestion] = useState<string | null>(null);
    const [resizerOpen, setResizerOpen] = useState(false);

    // Baseline để seed kho tạm: ưu tiên chi tiết đầy đủ từ sàn; chỉ fallback dữ liệu listing
    // (seed) khi tải chi tiết LỖI (tránh đẩy ảnh thiếu làm mất ảnh khác). null = đang tải.
    const baseline = useMemo<MarketplaceEditDraft | null>(() => {
        if (detail) {
            return {
                title: detail.title,
                description: detail.description,
                images: detail.images,
                prices: detail.skus.map((s) => ({ external_sku_id: s.external_sku_id, seller_sku: s.seller_sku, price: s.price })),
            };
        }
        if (isError && seed) {
            return {
                title: seed.title ?? '',
                description: '',
                images: seed.image ? [seed.image] : [],
                prices: [{ external_sku_id: seed.external_sku_id, seller_sku: seed.seller_sku ?? '', price: seed.price ?? 0 }],
            };
        }
        return null;
    }, [detail, isError, seed]);

    // Khởi tạo / nâng cấp kho tạm. Đổi listing ⇒ init mới; cùng listing & CHƯA chạm ⇒ re-seed
    // khi chi tiết đầy đủ tới. Đã chạm (gồm sửa ảnh) ⇒ giữ nguyên thay đổi đang dở.
    useEffect(() => {
        if (!baseline) return;
        const st = useMarketplaceEditStore.getState();
        if (st.id !== listingId) init(listingId, baseline);
        else if (!st.touched) init(listingId, baseline, true);
    }, [baseline, listingId, init]);

    const payload = useMemo<MarketplaceEditPayload>(() => {
        const p: MarketplaceEditPayload = {};
        if (!draft || !storeBaseline) return p;
        if (draft.title !== storeBaseline.title) p.title = draft.title;
        if (draft.description !== storeBaseline.description) p.description = draft.description;
        if (JSON.stringify(draft.images) !== JSON.stringify(storeBaseline.images)) p.images = draft.images;
        // Giá KHÔNG sửa trực tiếp ở đây — quản lý giá qua "Chiến dịch giảm giá".
        return p;
    }, [draft, storeBaseline]);

    const hasChanges = Object.keys(payload).length > 0;
    const back = () => navigate('/marketplace/on-channel');

    const addImage = (url: string) => {
        const u = url.trim();
        if (!u || !draft) return;
        if (draft.images.includes(u)) return;
        patch({ images: [...draft.images, u] });
    };

    const removeImage = (idx: number) => {
        if (!draft) return;
        patch({ images: draft.images.filter((_, i) => i !== idx) });
    };

    // Upload ảnh trực tiếp (square tile). Trả false để chặn antd tự upload.
    const uploadImageFile = async (file: File) => {
        if (!uploadClient || !draft) return false;
        if (draft.images.length >= maxImages) {
            message.warning(`Tối đa ${maxImages} ảnh cho sàn này.`);
            return false;
        }
        try {
            const form = new FormData();
            form.append('image', file);
            form.append('folder', 'listings');
            const { data } = await uploadClient.post<{ data: { url: string } }>('/media/image', form);
            addImage(data.data.url);
        } catch (e) {
            message.error(errorMessage(e));
        }
        return false;
    };

    // Tải 1 ảnh để CHÈN VÀO MÔ TẢ (TikTok/Lazada) — trả URL cho trình soạn thảo nhúng vào HTML.
    const uploadDescriptionImage = async (file: File): Promise<string> => {
        if (!uploadClient) throw new Error('Chưa sẵn sàng tải ảnh.');
        try {
            const form = new FormData();
            form.append('image', file);
            form.append('folder', 'listings');
            const { data } = await uploadClient.post<{ data: { url: string } }>('/media/image', form);
            return data.data.url;
        } catch (e) {
            message.error(errorMessage(e));
            throw e;
        }
    };

    const handleAiSuggest = () => {
        ai.mutate(
            { id: listingId, description: draft?.description },
            { onSuccess: (r) => setAiSuggestion(r.description), onError: (e) => message.error(errorMessage(e)) },
        );
    };

    const handlePush = () => {
        if (!hasChanges) return;
        update.mutate(
            { id: listingId, payload },
            {
                onSuccess: () => {
                    message.success('Đã đẩy thay đổi lên sàn.');
                    clear();
                    back();
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const header = (
        <PageHeader
            title={<Space><Button onClick={back}>Quay lại</Button><span>Sửa sản phẩm trên sàn</span>{provider && <Tag>{provider}</Tag>}</Space>}
            subtitle="Sửa tiêu đề / mô tả / ảnh rồi đẩy theo loạt lên sàn. Giá quản lý qua Chiến dịch giảm giá; tồn theo master SKU."
            extra={
                <Button type="primary" icon={<CloudUploadOutlined />} disabled={!hasChanges} loading={update.isPending} onClick={handlePush}>
                    Đẩy thay đổi lên sàn
                </Button>
            }
        />
    );

    if (!draft) {
        return (
            <div>
                {header}
                {isError && !seed ? (
                    <Result status="error" title="Không tải được sản phẩm từ sàn" subTitle={errorMessage(error)} extra={<Button onClick={back}>Quay lại</Button>} />
                ) : (
                    <div style={{ textAlign: 'center', padding: 48 }}><Spin tip="Đang tải sản phẩm từ sàn…"><div style={{ height: 1 }} /></Spin></div>
                )}
            </div>
        );
    }

    return (
        <div>
            {header}

            {isLoading && !detail && (
                <Alert style={{ marginBottom: 16 }} type="info" showIcon message="Đang tải mô tả & các SKU khác từ sàn…" />
            )}
            {isError && (
                <Alert
                    style={{ marginBottom: 16 }}
                    type="warning"
                    showIcon
                    message="Không tải được chi tiết đầy đủ từ sàn"
                    description="Bạn vẫn sửa được tiêu đề và ảnh cơ bản. Mô tả tạm thời không hiển thị — cân nhắc tải lại trước khi sửa ảnh để tránh thiếu ảnh."
                />
            )}

            <Card title="Thông tin" style={{ marginBottom: 16 }}>
                <Typography.Text type="secondary">Tiêu đề</Typography.Text>
                <Input style={{ marginTop: 4, marginBottom: 16 }} value={draft.title} onChange={(e) => patch({ title: e.target.value })} maxLength={300} showCount />

                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <Typography.Text type="secondary">Mô tả</Typography.Text>
                    <Button size="small" icon={<RobotOutlined />} loading={ai.isPending} onClick={handleAiSuggest}>AI gợi ý mô tả</Button>
                </div>
                <div style={{ marginTop: 4 }}>
                    {richDescription ? (
                        <RichTextEditor
                            value={draft.description}
                            onChange={(html) => patch({ description: html })}
                            uploadImage={uploadDescriptionImage}
                            placeholder="Mô tả sản phẩm hiển thị trên sàn"
                        />
                    ) : (
                        <Input.TextArea rows={6} value={draft.description} onChange={(e) => patch({ description: e.target.value })} placeholder="Mô tả sản phẩm hiển thị trên sàn" />
                    )}
                </div>
            </Card>

            <Card
                title="Hình ảnh"
                style={{ marginBottom: 16 }}
                extra={(
                    <Space>
                        <Typography.Text type={draft.images.length >= maxImages ? 'danger' : 'secondary'}>{draft.images.length}/{maxImages} ảnh</Typography.Text>
                        <Button icon={<PictureOutlined />} disabled={draft.images.length >= maxImages} onClick={() => setResizerOpen(true)}>Tải & resize ảnh</Button>
                    </Space>
                )}
            >
                <Space wrap size={12}>
                    {draft.images.map((url, idx) => (
                        <div key={`${url}-${idx}`} style={{ position: 'relative', width: 104, height: 104 }}>
                            <Image src={url} width={104} height={104} style={{ objectFit: 'cover', borderRadius: 8, border: '1px solid #f0f0f0' }} />
                            <Button
                                size="small"
                                type="primary"
                                shape="circle"
                                icon={<EditOutlined />}
                                title="Sửa ảnh nâng cao"
                                style={{ position: 'absolute', top: -8, left: -8 }}
                                onClick={() => navigate(`/marketplace/on-channel/${listingId}/images/edit?url=${encodeURIComponent(url)}`)}
                            />
                            <Button size="small" danger type="primary" shape="circle" icon={<DeleteOutlined />} style={{ position: 'absolute', top: -8, right: -8 }} onClick={() => removeImage(idx)} />
                        </div>
                    ))}
                    {draft.images.length < maxImages && (
                        <Upload accept="image/*" showUploadList={false} multiple beforeUpload={(file) => uploadImageFile(file as unknown as File)}>
                            <div style={{ width: 104, height: 104, border: '1px dashed #d9d9d9', borderRadius: 8, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', color: '#8c8c8c', background: '#fafafa' }}>
                                <PlusOutlined style={{ fontSize: 20 }} />
                                <span style={{ fontSize: 12, marginTop: 4 }}>Tải ảnh</span>
                            </div>
                        </Upload>
                    )}
                </Space>
                <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginTop: 12, marginBottom: 0 }}>
                    Ảnh nên vuông (1:1). Dùng nút sửa ảnh (góc trái) để cắt/chỉnh nâng cao ở trang riêng.
                </Typography.Paragraph>
            </Card>

            <Modal
                title="Mô tả do AI gợi ý"
                open={aiSuggestion !== null}
                onCancel={() => setAiSuggestion(null)}
                okText="Chấp nhận & thay thế"
                cancelText="Bỏ"
                onOk={() => { if (aiSuggestion !== null) patch({ description: aiSuggestion }); setAiSuggestion(null); }}
                width={640}
            >
                <Typography.Paragraph type="secondary">Xem trước nội dung AI gợi ý. Chấp nhận sẽ thay thế mô tả hiện tại (nhớ bấm “Đẩy thay đổi lên sàn”).</Typography.Paragraph>
                <Input.TextArea rows={12} value={aiSuggestion ?? ''} onChange={(e) => setAiSuggestion(e.target.value)} />
            </Modal>

            <ImageResizer open={resizerOpen} onClose={() => setResizerOpen(false)} onUploaded={(url) => addImage(url)} />
        </div>
    );
}
