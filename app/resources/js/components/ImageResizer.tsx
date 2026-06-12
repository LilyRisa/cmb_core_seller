import { useRef, useState } from 'react';
import { App as AntApp, Button, Modal, Radio, Slider, Space, Typography, Upload } from 'antd';
import { InboxOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useUploadImage } from '@/lib/inventory';

const PRESETS = [
    { label: 'Vuông 1:1 (1080)', w: 1080, h: 1080 },
    { label: 'Dọc 3:4 (1080×1440)', w: 1080, h: 1440 },
    { label: 'Gốc (không cắt)', w: 0, h: 0 },
];

/**
 * Trình resize ảnh client-side: chọn ảnh từ máy → đổi kích thước (canvas) theo
 * preset hoặc chiều rộng tuỳ chỉnh → upload (`/media/image`) → trả URL về.
 *
 * Cắt theo tỉ lệ preset bằng "cover" (giữ tâm). Preset "Gốc" chỉ thu nhỏ theo
 * chiều rộng, giữ nguyên tỉ lệ. Không phụ thuộc thư viện ngoài.
 */
export function ImageResizer({
    open,
    onClose,
    onUploaded,
}: {
    open: boolean;
    onClose: () => void;
    onUploaded: (url: string) => void;
}) {
    const { message } = AntApp.useApp();
    const upload = useUploadImage();
    const imgRef = useRef<HTMLImageElement | null>(null);
    const [dataUrl, setDataUrl] = useState<string | null>(null);
    const [natural, setNatural] = useState<{ w: number; h: number } | null>(null);
    const [presetIdx, setPresetIdx] = useState(0);
    const [width, setWidth] = useState(1080);

    const reset = () => {
        setDataUrl(null);
        setNatural(null);
        setPresetIdx(0);
        setWidth(1080);
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    const pickFile = (file: File) => {
        const reader = new FileReader();
        reader.onload = () => {
            const url = String(reader.result);
            const img = new Image();
            img.onload = () => {
                imgRef.current = img;
                setNatural({ w: img.naturalWidth, h: img.naturalHeight });
                setWidth(Math.min(1080, img.naturalWidth));
                setDataUrl(url);
            };
            img.src = url;
        };
        reader.readAsDataURL(file);
        return false; // chặn AntD tự upload
    };

    const apply = async () => {
        const img = imgRef.current;
        if (!img || !natural) return;
        const preset = PRESETS[presetIdx];

        let targetW: number;
        let targetH: number;
        if (preset.w === 0) {
            // Gốc: chỉ thu nhỏ theo chiều rộng đã chọn.
            targetW = Math.min(width, natural.w);
            targetH = Math.round((targetW * natural.h) / natural.w);
        } else {
            targetW = preset.w;
            targetH = preset.h;
        }

        const canvas = document.createElement('canvas');
        canvas.width = targetW;
        canvas.height = targetH;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, targetW, targetH);

        // Vẽ "cover": giữ tâm, lấp đầy khung đích.
        const scale = Math.max(targetW / natural.w, targetH / natural.h);
        const drawW = natural.w * scale;
        const drawH = natural.h * scale;
        ctx.drawImage(img, (targetW - drawW) / 2, (targetH - drawH) / 2, drawW, drawH);

        const blob: Blob | null = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', 0.9));
        if (!blob) {
            message.error('Không xử lý được ảnh.');
            return;
        }
        const file = new File([blob], `resized-${Date.now()}.jpg`, { type: 'image/jpeg' });
        upload.mutate(
            { file, folder: 'listings' },
            {
                onSuccess: ({ url }) => {
                    message.success('Đã tải ảnh lên.');
                    onUploaded(url);
                    handleClose();
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    return (
        <Modal
            title="Tải & chỉnh kích thước ảnh"
            open={open}
            onCancel={handleClose}
            okText="Áp dụng & tải lên"
            okButtonProps={{ disabled: !dataUrl, loading: upload.isPending }}
            onOk={apply}
            width={520}
        >
            {!dataUrl ? (
                <Upload.Dragger accept="image/*" showUploadList={false} beforeUpload={pickFile} multiple={false}>
                    <p className="ant-upload-drag-icon"><InboxOutlined /></p>
                    <p className="ant-upload-text">Kéo thả hoặc bấm để chọn ảnh</p>
                    <p className="ant-upload-hint">JPG / PNG / WebP — ảnh sẽ được resize trước khi tải lên.</p>
                </Upload.Dragger>
            ) : (
                <Space direction="vertical" style={{ width: '100%' }} size="middle">
                    <div style={{ textAlign: 'center', background: '#F1F5F9', borderRadius: 8, padding: 8 }}>
                        <img src={dataUrl} alt="preview" style={{ maxHeight: 240, maxWidth: '100%', objectFit: 'contain' }} />
                    </div>
                    <div>
                        <Typography.Text type="secondary">Khung ảnh</Typography.Text>
                        <Radio.Group
                            style={{ display: 'block', marginTop: 6 }}
                            value={presetIdx}
                            onChange={(e) => setPresetIdx(e.target.value)}
                            optionType="button"
                            buttonStyle="solid"
                            options={PRESETS.map((p, i) => ({ label: p.label, value: i }))}
                        />
                    </div>
                    {PRESETS[presetIdx].w === 0 && natural && (
                        <div>
                            <Typography.Text type="secondary">Chiều rộng: {width}px</Typography.Text>
                            <Slider min={200} max={natural.w} step={20} value={width} onChange={setWidth} />
                        </div>
                    )}
                    <Button onClick={reset}>Chọn ảnh khác</Button>
                </Space>
            )}
        </Modal>
    );
}
