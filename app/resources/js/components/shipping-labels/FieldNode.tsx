import type { Field } from '@/lib/shippingLabelTypes';
import { FIELD_REGISTRY } from './fieldTypes';
import { SAMPLE_DATA } from '@/lib/labelEditor/sampleData';
import { useEditorStore } from '@/lib/labelEditor/editorStore';

export function FieldNode({ field, zoom }: { field: Field; zoom: number }) {
    const selected = useEditorStore((s) => s.selection.includes(field.id));
    const profile = useEditorStore((s) => s.sampleProfile);
    const def = FIELD_REGISTRY[field.type];
    if (!def) return null;
    const Renderer = def.KonvaRenderer;
    return <Renderer field={field as any} ctx={SAMPLE_DATA[profile]} selected={selected} zoom={zoom} />;
}
