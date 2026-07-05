import { useState, useEffect, useRef } from 'react';
import {
    Alert,
    Button,
    Card,
    Empty,
    Form,
    Input,
    Space,
    Tag,
    Tooltip,
    Typography,
} from 'antd';
import type { SegmentedProps } from 'antd';
import { Segmented } from 'antd';
import {
    CloseOutlined,
    CopyOutlined,
    LinkOutlined,
    MessageOutlined,
    PictureOutlined,
    PlusOutlined,
    SwapOutlined,
} from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import type { AdObjective, AdNode } from '@/lib/adWizard';
import { PagePostPickerModal } from '@/pages/adWizard/PagePostPickerModal';

const { Text, Paragraph } = Typography;

type ContentMode = 'page_post' | 'new';

const MODE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Dùng bài viết có sẵn', value: 'page_post' },
    { label: 'Tạo nội dung mới', value: 'new' },
];

interface CtaOption {
    label: string;
    value: string;
}

const CTA_BY_OBJECTIVE: Record<AdObjective, CtaOption[]> = {
    messages: [{ label: 'Gửi tin nhắn', value: 'MESSAGE_PAGE' }],
    traffic: [
        { label: 'Tìm hiểu thêm', value: 'LEARN_MORE' },
        { label: 'Mua ngay', value: 'SHOP_NOW' },
    ],
    engagement: [{ label: 'Tìm hiểu thêm', value: 'LEARN_MORE' }],
    conversions: [
        { label: 'Mua ngay', value: 'SHOP_NOW' },
        { label: 'Tìm hiểu thêm', value: 'LEARN_MORE' },
    ],
};

// Nhãn VN cho CTA sẵn có của bài viết (chỉ để hiển thị khi promote bài có nút cố định).
const CTA_LABELS: Record<string, string> = {
    MESSAGE_PAGE: 'Gửi tin nhắn',
    SEND_MESSAGE: 'Gửi tin nhắn',
    LEARN_MORE: 'Tìm hiểu thêm',
    SHOP_NOW: 'Mua ngay',
    ORDER_NOW: 'Đặt hàng ngay',
    BUY_NOW: 'Mua ngay',
    SIGN_UP: 'Đăng ký',
    SUBSCRIBE: 'Đăng ký',
    BOOK_TRAVEL: 'Đặt ngay',
    BOOK_NOW: 'Đặt ngay',
    CONTACT_US: 'Liên hệ',
    CALL_NOW: 'Gọi ngay',
    WHATSAPP_MESSAGE: 'Nhắn WhatsApp',
    DOWNLOAD: 'Tải xuống',
    GET_OFFER: 'Nhận ưu đãi',
    GET_QUOTE: 'Nhận báo giá',
    APPLY_NOW: 'Đăng ký ngay',
    WATCH_MORE: 'Xem thêm',
    NO_BUTTON: 'Không có nút',
};

/** Nhãn hiển thị cho một mã CTA của Facebook (fallback về chính mã nếu chưa map). */
function ctaLabel(cta: string): string {
    return CTA_LABELS[cta] ?? cta;
}

// Local UI state for the picked post (image + message + existing CTA/link for preview, not stored in draft payload)
interface PickedPostSummary {
    image_url: string | null;
    message: string | null;
    link_url: string | null;
    cta_type: string | null;
}

interface AdEditorProps {
    adsetKey: string;
    ad: AdNode;
}

function AdEditor({ adsetKey, ad }: AdEditorProps) {
    const objective = useDraftStore((s) => s.objective);
    const accountId = useDraftStore((s) => s.accountId);
    const updateAd = useDraftStore((s) => s.updateAd);

    const [modalOpen, setModalOpen] = useState(false);
    const [pickedSummary, setPickedSummary] = useState<PickedPostSummary | null>(null);

    const creative = ad.creative;
    const mode: ContentMode = (creative?.mode as ContentMode | undefined) ?? 'page_post';

    // When objective changes, reset CTA to the first valid option for the new objective
    useEffect(() => {
        if (objective == null) return;
        const allowed = CTA_BY_OBJECTIVE[objective];
        if (allowed.length === 0) return;
        const currentCta = creative?.cta;
        const isValid = currentCta != null && allowed.some((o) => o.value === currentCta);
        if (!isValid) {
            updateAd(adsetKey, ad.key, { creative: { ...creative, cta: allowed[0].value } });
        }
    // Only run when objective changes
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [objective]);

    const ctaOptions: SegmentedProps['options'] =
        objective != null
            ? CTA_BY_OBJECTIVE[objective].map((o) => ({ label: o.label, value: o.value }))
            : [];

    const currentCta = creative?.cta ?? (ctaOptions[0] as { value: string } | undefined)?.value;

    // CTA khi promote bài viết có sẵn: bài được đẩy qua object_story_id nên GIỮ NGUYÊN nút
    // của bài — người dùng không đổi được. Chỉ khi bài CHƯA có nút mới cho chọn. Chế độ
    // "tạo nội dung mới" thì luôn chọn được (nút đi vào link_data.call_to_action).
    const isPagePost = mode === 'page_post';
    const postPicked = isPagePost && creative?.page_post_id != null;
    const existingPostCta = isPagePost
        ? (pickedSummary?.cta_type ?? creative?.page_post_cta_type ?? null)
        : null;
    const hasFixedCta = existingPostCta != null && existingPostCta !== 'NO_BUTTON';
    const ctaSelectable = !isPagePost || (postPicked && !hasFixedCta);

    function handleModeChange(v: string | number) {
        updateAd(adsetKey, ad.key, { creative: { ...creative, mode: v as ContentMode } });
    }

    function handleCtaChange(v: string | number) {
        updateAd(adsetKey, ad.key, { creative: { ...creative, cta: v as string } });
    }

    function handlePick(p: {
        page_id: string;
        page_post_id: string;
        image_url: string | null;
        message: string | null;
        link_url: string | null;
        cta_type: string | null;
    }) {
        updateAd(adsetKey, ad.key, {
            creative: { ...creative, page_id: p.page_id, page_post_id: p.page_post_id, page_post_cta_type: p.cta_type ?? null },
        });
        setPickedSummary({
            image_url: p.image_url,
            message: p.message,
            link_url: p.link_url,
            cta_type: p.cta_type,
        });
    }

    // The "đích" hiện hữu của bài: nút Gửi tin nhắn (messages objective hoặc post CTA messenger) hoặc đường dẫn đã gắn.
    const showsMessenger =
        pickedSummary?.cta_type === 'MESSAGE_PAGE' || objective === 'messages';
    const existingLink = pickedSummary?.link_url ?? null;

    return (
        <Form layout="vertical">
            {/* Mode segmented */}
            <Form.Item label="Nội dung">
                <Segmented
                    options={MODE_OPTIONS}
                    value={mode}
                    onChange={handleModeChange}
                />
            </Form.Item>

            {/* page_post mode */}
            {mode === 'page_post' && (
                <>
                    <Form.Item>
                        <Button
                            icon={<PictureOutlined />}
                            onClick={() => setModalOpen(true)}
                        >
                            Chọn bài viết
                        </Button>
                    </Form.Item>

                    {creative?.page_post_id != null && (
                        <Form.Item label="Bài viết đã chọn">
                            <Card
                                size="small"
                                style={{ maxWidth: 400 }}
                                styles={{
                                    body: {
                                        display: 'flex',
                                        gap: 12,
                                        alignItems: 'flex-start',
                                    },
                                }}
                            >
                                {pickedSummary?.image_url != null && (
                                    <img
                                        src={pickedSummary.image_url}
                                        alt=""
                                        style={{
                                            width: 72,
                                            height: 72,
                                            objectFit: 'cover',
                                            borderRadius: 4,
                                            flexShrink: 0,
                                        }}
                                    />
                                )}
                                <Space direction="vertical" size={4} style={{ flex: 1, minWidth: 0 }}>
                                    {pickedSummary?.message != null ? (
                                        <Paragraph
                                            ellipsis={{ rows: 2 }}
                                            style={{ marginBottom: 0, fontSize: 13 }}
                                        >
                                            {pickedSummary.message}
                                        </Paragraph>
                                    ) : (
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            Post ID: {creative.page_post_id}
                                        </Text>
                                    )}
                                    {showsMessenger ? (
                                        <Tag
                                            icon={<MessageOutlined />}
                                            color="green"
                                            style={{ marginInlineEnd: 0, maxWidth: '100%' }}
                                        >
                                            Hành động: Gửi tin nhắn
                                        </Tag>
                                    ) : existingLink != null ? (
                                        <Tag
                                            icon={<LinkOutlined />}
                                            color="blue"
                                            style={{ marginInlineEnd: 0, maxWidth: '100%' }}
                                        >
                                            <a
                                                href={existingLink}
                                                target="_blank"
                                                rel="noreferrer"
                                                style={{
                                                    maxWidth: 240,
                                                    display: 'inline-block',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                    whiteSpace: 'nowrap',
                                                    verticalAlign: 'bottom',
                                                }}
                                            >
                                                Đường dẫn: {existingLink}
                                            </a>
                                        </Tag>
                                    ) : null}
                                    <Button
                                        size="small"
                                        icon={<SwapOutlined />}
                                        onClick={() => setModalOpen(true)}
                                    >
                                        Đổi bài
                                    </Button>
                                </Space>
                            </Card>
                        </Form.Item>
                    )}
                </>
            )}

            {/* new mode */}
            {mode === 'new' && (
                <>
                    <Form.Item label="Nội dung chính">
                        <Input.TextArea
                            rows={3}
                            value={creative?.primary_text ?? ''}
                            onChange={(e) =>
                                updateAd(adsetKey, ad.key, {
                                    creative: { ...creative, primary_text: e.target.value },
                                })
                            }
                            placeholder="Mô tả sản phẩm / dịch vụ..."
                            style={{ maxWidth: 520 }}
                        />
                    </Form.Item>
                    <Form.Item label="Tiêu đề">
                        <Input
                            value={creative?.headline ?? ''}
                            onChange={(e) =>
                                updateAd(adsetKey, ad.key, {
                                    creative: { ...creative, headline: e.target.value },
                                })
                            }
                            placeholder="Tiêu đề ngắn gọn"
                            style={{ maxWidth: 400 }}
                        />
                    </Form.Item>
                    <Form.Item label="Đường dẫn (URL)">
                        <Input
                            value={creative?.link_url ?? ''}
                            onChange={(e) =>
                                updateAd(adsetKey, ad.key, {
                                    creative: { ...creative, link_url: e.target.value },
                                })
                            }
                            placeholder="https://example.com"
                            style={{ maxWidth: 400 }}
                        />
                    </Form.Item>
                    <Alert
                        type="warning"
                        showIcon
                        message="Tải ảnh/video sẽ có ở bản cập nhật sau — hiện hãy dùng bài viết có sẵn."
                        style={{ maxWidth: 520 }}
                    />
                </>
            )}

            {/* CTA. Bài có sẵn (object_story_id) được promote nguyên trạng: có nút thì khoá +
                hiển thị tên; không có nút thì báo rõ (không thêm được nút cho bài có sẵn).
                Chế độ "tạo nội dung mới" mới cho tự chọn (nút đi vào link_data.call_to_action). */}
            {isPagePost && postPicked ? (
                <Form.Item label="Nút kêu gọi hành động (CTA)" style={{ marginTop: 16 }}>
                    <Space direction="vertical" size={4}>
                        {hasFixedCta ? (
                            <>
                                <Segmented options={[{ label: ctaLabel(existingPostCta as string), value: existingPostCta as string }]} value={existingPostCta as string} disabled />
                                <Text type="secondary" style={{ maxWidth: 520, display: 'inline-block' }}>
                                    Bài viết đã có sẵn nút này — quảng cáo giữ nguyên nút của bài, không đổi được.
                                </Text>
                            </>
                        ) : (
                            <Text type="secondary" style={{ maxWidth: 520, display: 'inline-block' }}>
                                Bài viết không có nút kêu gọi hành động. Quảng cáo dùng bài có sẵn sẽ giữ nguyên
                                trạng bài — muốn có nút CTA, hãy chọn "Tạo nội dung mới".
                            </Text>
                        )}
                    </Space>
                </Form.Item>
            ) : ctaSelectable && ctaOptions.length > 0 ? (
                <Form.Item label="Nút kêu gọi hành động (CTA)" style={{ marginTop: 16 }}>
                    <Segmented
                        options={ctaOptions}
                        value={currentCta}
                        onChange={handleCtaChange}
                    />
                </Form.Item>
            ) : null}

            {/* Advantage+ creative (điểm cải thiện tiêu chuẩn): Meta đã NGỪNG cờ gộp standard_enhancements
                (bật cờ này làm createAd fail — subcode 3858504). Gỡ toggle; sẽ làm lại theo từng tính năng sau. */}

            <PagePostPickerModal
                open={modalOpen}
                accountId={accountId}
                onPick={handlePick}
                onClose={() => setModalOpen(false)}
            />
        </Form>
    );
}

export function StepCreative() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const addAd = useDraftStore((s) => s.addAd);
    const removeAd = useDraftStore((s) => s.removeAd);
    const duplicateAd = useDraftStore((s) => s.duplicateAd);
    const selectAdInStore = useDraftStore((s) => s.selectAd);

    const adset = adsets.find((a) => a.key === selectedAdSetKey);

    const [selectedAdKey, setSelectedAdKey] = useState<string | null>(null);
    const prevAdsLengthRef = useRef<number>(0);

    // When adset changes, default to first ad; when ads.length grows, select the last (newly added) ad
    useEffect(() => {
        if (adset == null || adset.ads.length === 0) {
            prevAdsLengthRef.current = 0;
            return;
        }
        const prevLen = prevAdsLengthRef.current;
        prevAdsLengthRef.current = adset.ads.length;
        if (adset.ads.length > prevLen && prevLen > 0) {
            // A new ad was added — select it
            setSelectedAdKey(adset.ads[adset.ads.length - 1].key);
            return;
        }
        setSelectedAdKey((prev) => {
            // Keep current selection if it still exists in this ad set
            if (prev != null && adset.ads.some((d) => d.key === prev)) return prev;
            return adset.ads[0].key;
        });
    }, [adset]);

    // Mirror the focused ad into the store so wizard-level Ctrl+C/V/D can target it.
    useEffect(() => {
        if (adset == null || adset.ads.length === 0) {
            selectAdInStore(null);
            return;
        }
        const eff = selectedAdKey != null && adset.ads.some((d) => d.key === selectedAdKey)
            ? selectedAdKey
            : adset.ads[0].key;
        selectAdInStore(eff);
        return () => selectAdInStore(null);
    }, [adset, selectedAdKey, selectAdInStore]);

    if (adset == null) {
        return (
            <Empty description="Chọn hoặc thêm một nhóm quảng cáo" style={{ padding: 32 }} />
        );
    }

    const ads = adset.ads;
    const effectiveAdKey = selectedAdKey != null && ads.some((d) => d.key === selectedAdKey)
        ? selectedAdKey
        : (ads[0]?.key ?? null);

    const selectedAd = ads.find((d) => d.key === effectiveAdKey) ?? null;

    function handleAddAd() {
        if (selectedAdSetKey == null) return;
        addAd(selectedAdSetKey);
        // Select the newly added ad — it will be the last one; effect handles it via "adset" change
    }

    function handleRemoveAd(adKey: string) {
        if (selectedAdSetKey == null) return;
        removeAd(selectedAdSetKey, adKey);
        if (selectedAdKey === adKey) {
            const remaining = ads.filter((d) => d.key !== adKey);
            setSelectedAdKey(remaining[0]?.key ?? null);
        }
    }

    return (
        <div>
            {/* Ad tabs row */}
            <Space size={4} wrap style={{ marginBottom: 12 }}>
                <Text type="secondary" style={{ fontSize: 12, marginRight: 4 }}>
                    Quảng cáo:
                </Text>
                {ads.map((ad) => {
                    const isSelected = ad.key === effectiveAdKey;
                    return (
                        <Tag
                            key={ad.key}
                            color={isSelected ? 'blue' : undefined}
                            style={{
                                cursor: 'pointer',
                                userSelect: 'none',
                                padding: '2px 8px',
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: 4,
                            }}
                            onClick={() => setSelectedAdKey(ad.key)}
                        >
                            {ad.name}
                            {selectedAdSetKey != null && (
                                <Tooltip title="Nhân bản quảng cáo này">
                                    <CopyOutlined
                                        style={{ fontSize: 10, marginLeft: 2, color: '#1677ff' }}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            duplicateAd(selectedAdSetKey, ad.key);
                                        }}
                                    />
                                </Tooltip>
                            )}
                            {ads.length > 1 && (
                                <Tooltip title="Xoá quảng cáo này">
                                    <CloseOutlined
                                        style={{ fontSize: 10, marginLeft: 2, color: '#999' }}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleRemoveAd(ad.key);
                                        }}
                                    />
                                </Tooltip>
                            )}
                        </Tag>
                    );
                })}
                <Button
                    size="small"
                    icon={<PlusOutlined />}
                    onClick={handleAddAd}
                    style={{ height: 24, fontSize: 12 }}
                >
                    Thêm quảng cáo
                </Button>
            </Space>

            {/* Ad editor */}
            {selectedAd != null && selectedAdSetKey != null ? (
                <AdEditor
                    key={selectedAd.key}
                    adsetKey={selectedAdSetKey}
                    ad={selectedAd}
                />
            ) : (
                <Empty description="Thêm quảng cáo để bắt đầu" style={{ padding: 32 }} />
            )}
        </div>
    );
}
