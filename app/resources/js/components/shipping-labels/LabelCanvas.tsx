import { useEffect, useMemo, useRef } from 'react';
import { Group, Layer, Rect, Stage, Transformer } from 'react-konva';
import type Konva from 'konva';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { mm2px, px2mm, snap, clampBox } from '@/lib/labelEditor/coords';
import { FieldNode } from './FieldNode';

export function LabelCanvas() {
    const stageRef = useRef<Konva.Stage | null>(null);
    const trRef = useRef<Konva.Transformer | null>(null);
    const layerRef = useRef<Konva.Layer | null>(null);

    const { meta, fields, selection, zoom, grid, commitTransform, setSelection } = useEditorStore();

    const widthPx = mm2px(meta.paper_w_mm, zoom);
    const heightPx = mm2px(meta.paper_h_mm > 0 ? meta.paper_h_mm : 200, zoom);

    useEffect(() => {
        const tr = trRef.current;
        const layer = layerRef.current;
        if (!tr || !layer) return;
        const nodes = selection.map((id) => layer.findOne<Konva.Node>(`#${id}`)).filter(Boolean) as Konva.Node[];
        tr.nodes(nodes);
        tr.getLayer()?.batchDraw();
    }, [selection, fields]);

    const onClickStage = (e: Konva.KonvaEventObject<MouseEvent>) => {
        if (e.target === e.target.getStage()) setSelection([]);
    };

    const onTransformEnd = (id: string) => (e: Konva.KonvaEventObject<Event>) => {
        const node = e.target;
        const scaleX = node.scaleX();
        const scaleY = node.scaleY();
        const newW = node.width() * scaleX;
        const newH = node.height() * scaleY;
        node.scaleX(1);
        node.scaleY(1);
        const box = clampBox({
            x: snap(px2mm(node.x(), zoom), grid),
            y: snap(px2mm(node.y(), zoom), grid),
            w: snap(px2mm(newW, zoom), grid),
            h: snap(px2mm(newH, zoom), grid),
        }, meta.paper_w_mm, meta.paper_h_mm);
        commitTransform(id, { ...box, rotation: node.rotation() });
    };

    const gridDots = useMemo(() => {
        if (grid === 0) return null;
        const dots: Array<{ x: number; y: number }> = [];
        for (let y = 0; y <= meta.paper_w_mm; y += grid) {
            for (let x = 0; x <= meta.paper_w_mm; x += grid) {
                dots.push({ x: mm2px(x, zoom), y: mm2px(y, zoom) });
            }
        }
        return dots;
    }, [grid, meta.paper_w_mm, zoom]);

    return (
        <Stage ref={stageRef} width={widthPx + 40} height={heightPx + 40} onClick={onClickStage}>
            <Layer x={20} y={20}>
                <Rect width={widthPx} height={heightPx} fill="#fff" stroke="#bfbfbf" strokeWidth={1}
                      shadowBlur={4} shadowColor="rgba(0,0,0,0.08)" />
                {gridDots?.map((d, i) => <Rect key={i} x={d.x} y={d.y} width={1} height={1} fill="#e0e0e0" />)}
            </Layer>
            <Layer x={20} y={20} ref={layerRef}>
                {fields.map((f) => (
                    <Group key={f.id} id={f.id} draggable
                           onClick={(e) => { e.cancelBubble = true; setSelection([f.id]); }}
                           onDragEnd={onTransformEnd(f.id)} onTransformEnd={onTransformEnd(f.id)}>
                        <FieldNode field={f} zoom={zoom} />
                    </Group>
                ))}
            </Layer>
            <Layer x={20} y={20}>
                <Transformer ref={trRef} keepRatio={false}
                             boundBoxFunc={(_old, newBox) => newBox} />
            </Layer>
        </Stage>
    );
}
