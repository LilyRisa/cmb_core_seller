import { useEffect } from 'react';
import { App as AntApp, Button, Input, Segmented, Space, Spin, Typography } from 'antd';
import { SaveOutlined, UndoOutlined, RedoOutlined, EyeOutlined } from '@ant-design/icons';
import { useNavigate, useParams } from 'react-router-dom';
import { useShippingLabelTemplate, useCreateShippingLabelTemplate, useUpdateShippingLabelTemplate, usePreviewInlineShippingLabelTemplate } from '@/lib/shippingLabels';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { SAMPLE_PROFILES } from '@/lib/shippingLabelTypes';
import { LabelCanvas } from '@/components/shipping-labels/LabelCanvas';
import { FieldPalette } from '@/components/shipping-labels/FieldPalette';
import { FieldInspector } from '@/components/shipping-labels/FieldInspector';
import { PaperSettings } from '@/components/shipping-labels/PaperSettings';
import { errorMessage } from '@/lib/api';

export function ShippingLabelEditorPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const isNew = !id || id === 'new';
    const numericId = isNew ? null : Number(id);

    const { data: tpl, isLoading } = useShippingLabelTemplate(numericId);
    const init = useEditorStore((s) => s.init);
    const meta = useEditorStore((s) => s.meta);
    const setMeta = useEditorStore((s) => s.setMeta);
    const setSampleProfile = useEditorStore((s) => s.setSampleProfile);
    const sampleProfile = useEditorStore((s) => s.sampleProfile);
    const grid = useEditorStore((s) => s.grid);
    const setGrid = useEditorStore((s) => s.setGrid);
    const undo = useEditorStore((s) => s.undo);
    const redo = useEditorStore((s) => s.redo);
    const toPayload = useEditorStore((s) => s.toPayload);

    const create = useCreateShippingLabelTemplate();
    const update = useUpdateShippingLabelTemplate();
    const previewInline = usePreviewInlineShippingLabelTemplate();

    useEffect(() => {
        if (isNew) init(null);
        else if (tpl) init(tpl);
    }, [tpl, isNew, init]);

    const save = () => {
        if (!meta.name.trim()) { message.error('Cần đặt tên template'); return; }
        const payload = toPayload();
        if (isNew) {
            create.mutate(payload, {
                onSuccess: (created) => { message.success('Đã lưu'); navigate(`/settings/shipping-labels/${created.id}`, { replace: true }); },
                onError: (e) => message.error(errorMessage(e)),
            });
        } else {
            update.mutate({ id: numericId!, input: payload }, {
                onSuccess: () => message.success('Đã lưu'),
                onError: (e) => message.error(errorMessage(e)),
            });
        }
    };

    const preview = () => {
        previewInline.mutate({ ...toPayload(), sample_profile: sampleProfile }, {
            onSuccess: (r) => window.open(r.url, '_blank'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    if (!isNew && isLoading) return <Spin />;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100vh - 96px)' }}>
            <div style={{ padding: '8px 16px', borderBottom: '1px solid #f0f0f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                <Space>
                    <Input placeholder="Tên template" value={meta.name}
                           onChange={(e) => setMeta({ name: e.target.value })} style={{ width: 280 }} />
                    <PaperSettings />
                </Space>
                <Space>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Mẫu data:</Typography.Text>
                    <Segmented options={SAMPLE_PROFILES.map((p) => ({ label: p.replace(/_/g, ' '), value: p }))}
                        value={sampleProfile} onChange={(v) => setSampleProfile(v as typeof sampleProfile)} />
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Lưới:</Typography.Text>
                    <Segmented options={[{ label: 'Off', value: 0 }, { label: '1mm', value: 1 }, { label: '2mm', value: 2 }, { label: '5mm', value: 5 }]}
                        value={grid} onChange={(v) => setGrid(v as 0 | 1 | 2 | 5)} />
                    <Button icon={<UndoOutlined />} onClick={undo} />
                    <Button icon={<RedoOutlined />} onClick={redo} />
                    <Button icon={<EyeOutlined />} loading={previewInline.isPending} onClick={preview}>Xem trước PDF</Button>
                    <Button type="primary" icon={<SaveOutlined />} loading={create.isPending || update.isPending} onClick={save}>Lưu</Button>
                </Space>
            </div>
            <div style={{ display: 'flex', flex: 1, overflow: 'hidden' }}>
                <FieldPalette />
                <div style={{ flex: 1, overflow: 'auto', display: 'flex', alignItems: 'flex-start', justifyContent: 'center', padding: 24, background: '#fafafa' }}>
                    <LabelCanvas />
                </div>
                <FieldInspector />
            </div>
        </div>
    );
}
