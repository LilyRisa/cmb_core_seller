import { App as AntApp, Form, InputNumber, Segmented } from 'antd';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import type { Paper } from '@/lib/shippingLabelTypes';

const OPTIONS = ['A4', 'A5', 'A6', '100x150mm', '80mm', 'custom'];

export function PaperSettings() {
    const { modal } = AntApp.useApp();
    const meta = useEditorStore((s) => s.meta);
    const setPaper = useEditorStore((s) => s.setPaper);

    const handle = (next: Paper, w?: number, h?: number) => {
        const result = setPaper(next, w, h);
        if (result.needsConfirm) {
            modal.warning({
                title: 'Một số trường đang vượt khổ giấy mới',
                content: 'Bạn cần kéo lại các trường nằm ngoài vùng in trước khi lưu.',
                okText: 'Đã hiểu',
            });
        }
    };

    return (
        <Form layout="inline" size="small">
            <Form.Item label="Khổ giấy">
                <Segmented options={OPTIONS} value={meta.paper}
                    onChange={(v) => handle(v as Paper)} />
            </Form.Item>
            {meta.paper === 'custom' && (
                <>
                    <Form.Item label="W (mm)">
                        <InputNumber min={30} max={420} value={meta.paper_w_mm}
                            onChange={(v) => handle('custom', v ?? 100, meta.paper_h_mm)} />
                    </Form.Item>
                    <Form.Item label="H (mm)">
                        <InputNumber min={0} max={1200} value={meta.paper_h_mm}
                            onChange={(v) => handle('custom', meta.paper_w_mm, v ?? 100)} />
                    </Form.Item>
                </>
            )}
        </Form>
    );
}
