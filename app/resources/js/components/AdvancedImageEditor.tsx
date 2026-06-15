import { useMemo, useState } from 'react';
import { App as AntApp, Button, Result, Spin } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import FilerobotImageEditor, { TABS } from 'react-filerobot-image-editor';
import { tenantApi, errorMessage } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';

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
 * Trình sửa ảnh NÂNG CAO dùng chung (crop, resize, xoay, lật, bộ lọc, finetune, thêm
 * chữ/hình, watermark, undo/redo — react-filerobot-image-editor). Khi lưu: xuất ảnh →
 * upload `/media/image` → gọi `onSaved(url)`. Component KHÔNG biết ảnh được lưu vào đâu
 * (nháp DB hay kho tạm) — nơi gọi quyết định qua `onSaved`.
 */
export function AdvancedImageEditor({
    source,
    onSaved,
    onClose,
}: {
    source: string;
    onSaved: (newUrl: string) => void | Promise<void>;
    onClose: () => void;
}) {
    const { message } = AntApp.useApp();
    const tenantId = useCurrentTenantId();
    const client = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const [saving, setSaving] = useState(false);

    const handleSave = async (edited: { imageBase64?: string; fullName?: string }) => {
        if (!client || !edited.imageBase64) return;
        setSaving(true);
        try {
            const file = dataUrlToFile(edited.imageBase64, edited.fullName ?? 'edited.png');
            const form = new FormData();
            form.append('image', file);
            form.append('folder', 'listings');
            const { data } = await client.post<{ data: { url: string } }>('/media/image', form);
            await onSaved(data.data.url);
        } catch (e) {
            message.error(errorMessage(e));
        } finally {
            setSaving(false);
        }
    };

    if (!source) {
        return <Result status="warning" title="Thiếu ảnh để sửa" extra={<Button onClick={onClose}>Quay lại</Button>} />;
    }

    return (
        <div style={{ height: 'calc(100vh - 120px)', minHeight: 520, display: 'flex', flexDirection: 'column' }}>
            <div style={{ marginBottom: 8 }}>
                <Button icon={<ArrowLeftOutlined />} onClick={onClose} disabled={saving}>Quay lại không lưu</Button>
                {saving && <Spin size="small" style={{ marginLeft: 12 }} />}
            </div>
            <div style={{ flex: 1, minHeight: 0 }}>
                <FilerobotImageEditor
                    source={source}
                    onSave={(edited) => handleSave(edited as { imageBase64?: string; fullName?: string })}
                    onClose={onClose}
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

export default AdvancedImageEditor;
