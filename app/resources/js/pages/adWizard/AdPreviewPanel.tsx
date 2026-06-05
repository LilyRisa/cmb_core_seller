import { useEffect, useRef, useState } from 'react';
import { Empty, Segmented, Spin, Typography } from 'antd';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import { useAdPreviews, type AdPreview } from '@/lib/adWizard';

const { Text } = Typography;

const FORMATS: { label: string; value: string }[] = [
    { label: 'Di động', value: 'MOBILE_FEED_STANDARD' },
    { label: 'Máy tính', value: 'DESKTOP_FEED_STANDARD' },
    { label: 'Tin (Story)', value: 'INSTAGRAM_STORY' },
];

/** Khung xem trước quảng cáo — render iframe Graph generatepreviews cho bài đã chọn. */
export function AdPreviewPanel() {
    const accountId = useDraftStore((s) => s.accountId);
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const selectedAdKey = useDraftStore((s) => s.selectedAdKey);

    const previews = useAdPreviews();
    const [format, setFormat] = useState<string>('MOBILE_FEED_STANDARD');
    const [items, setItems] = useState<AdPreview[]>([]);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Resolve the focused ad's creative → a creative spec for the preview API.
    const adset = adsets.find((a) => a.key === selectedAdSetKey);
    const ad = adset?.ads.find((d) => d.key === selectedAdKey) ?? adset?.ads[0];
    const creative = ad?.creative;
    const pagePostId = creative?.page_post_id ?? null;
    const pageId = creative?.page_id ?? null;

    // Build the creative spec: existing post → object_story_id; new → object_story_spec.
    const creativeKey = `${pagePostId ?? ''}|${pageId ?? ''}|${creative?.primary_text ?? ''}|${creative?.headline ?? ''}|${creative?.link_url ?? ''}|${creative?.cta ?? ''}|${format}`;

    useEffect(() => {
        if (accountId == null) return;
        let spec: Record<string, unknown> | null = null;
        if (pagePostId != null && pagePostId !== '') {
            spec = { object_story_id: pagePostId };
        } else if (pageId != null && (creative?.primary_text || creative?.link_url)) {
            spec = {
                page_id: pageId,
                link_data: {
                    message: creative?.primary_text,
                    name: creative?.headline,
                    link: creative?.link_url || 'https://facebook.com',
                    call_to_action: creative?.cta ? { type: creative.cta } : undefined,
                },
            };
        }
        if (spec == null) { setItems([]); return; }

        if (debounceRef.current != null) clearTimeout(debounceRef.current);
        const specToSend = spec;
        debounceRef.current = setTimeout(() => {
            previews.mutate(
                { accountId, creative: specToSend, formats: [format] },
                { onSuccess: (res) => setItems(res), onError: () => setItems([]) },
            );
        }, 500);
        return () => { if (debounceRef.current != null) clearTimeout(debounceRef.current); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [accountId, creativeKey]);

    const body = items.find((i) => i.format === format)?.body ?? items[0]?.body ?? null;

    return (
        <div>
            <Segmented
                size="small"
                block
                value={format}
                onChange={(v) => setFormat(String(v))}
                options={FORMATS}
                style={{ marginBottom: 12 }}
            />
            {previews.isPending ? (
                <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
            ) : body != null ? (
                // Graph returns an <iframe> snippet — render it directly.
                <div style={{ overflow: 'hidden' }} dangerouslySetInnerHTML={{ __html: body }} />
            ) : (
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description={<Text type="secondary" style={{ fontSize: 12 }}>Chọn bài viết ở bước Nội dung để xem trước</Text>}
                />
            )}
        </div>
    );
}
