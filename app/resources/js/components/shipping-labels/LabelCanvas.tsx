import { useEffect, useMemo, useRef } from 'react';
import { Group, Layer, Rect, Stage, Transformer } from 'react-konva';
import type Konva from 'konva';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { mm2px, px2mm, snap, clampBox, PX_PER_MM } from '@/lib/labelEditor/coords';
import { FieldNode } from './FieldNode';

/**
 * Editor canvas. Outer <Group> per field is the *single* authority for x/y/w/h/rotation —
 * Konva's Transformer scales/translates this node; inner FieldDef renderers draw at (0,0)
 * relative to it. Mixing two transform layers (outer wrapper + inner Group with own x/y)
 * caused fields to "vanish" after drag/resize because Konva-managed position on the outer
 * was never reconciled back into store, while inner kept rendering at stale field.{x,y}.
 */
export function LabelCanvas() {
    const stageRef = useRef<Konva.Stage | null>(null);
    const trRef = useRef<Konva.Transformer | null>(null);
    const layerRef = useRef<Konva.Layer | null>(null);

    const { meta, fields, selection, zoom, grid, commitTransform, setSelection } = useEditorStore();

    const paperHmm = meta.paper_h_mm > 0 ? meta.paper_h_mm : 200;     // 80mm roll → fixed editor height
    const widthPx = mm2px(meta.paper_w_mm, zoom);
    const heightPx = mm2px(paperHmm, zoom);
    const minSizePx = 5 * PX_PER_MM * zoom;                            // clampBox min = 5mm; mirror it live

    useEffect(() => {
        const tr = trRef.current;
        const layer = layerRef.current;
        if (!tr || !layer) return;
        const nodes = selection
            .map((id) => layer.findOne<Konva.Node>(`#${id}`))
            .filter((n): n is Konva.Node => Boolean(n));
        tr.nodes(nodes);
        tr.getLayer()?.batchDraw();
    }, [selection, fields]);

    const onClickStage = (e: Konva.KonvaEventObject<MouseEvent>) => {
        if (e.target === e.target.getStage()) setSelection([]);
    };

    /**
     * Drag end + transform end both come here. `node` is the outer field Group:
     *   - drag: scaleX/Y stay 1, only x/y change.
     *   - resize: scaleX/Y ≠ 1; we collapse them into w/h then reset scale to 1.
     * Commit pushes a history snapshot and clamps to paper bounds.
     */
    const onTransformEnd = (id: string) => (e: Konva.KonvaEventObject<Event>) => {
        const node = e.target as Konva.Node;
        const scaleX = node.scaleX();
        const scaleY = node.scaleY();
        const newWpx = Math.max(1, node.width() * scaleX);
        const newHpx = Math.max(1, node.height() * scaleY);
        node.scaleX(1);
        node.scaleY(1);
        const box = clampBox({
            x: snap(px2mm(node.x(), zoom), grid),
            y: snap(px2mm(node.y(), zoom), grid),
            w: snap(px2mm(newWpx, zoom), grid),
            h: snap(px2mm(newHpx, zoom), grid),
        }, meta.paper_w_mm, meta.paper_h_mm);
        commitTransform(id, { ...box, rotation: node.rotation() });
    };

    const gridDots = useMemo(() => {
        if (grid === 0) return null;
        const dots: Array<{ x: number; y: number }> = [];
        for (let y = 0; y <= paperHmm; y += grid) {
            for (let x = 0; x <= meta.paper_w_mm; x += grid) {
                dots.push({ x: mm2px(x, zoom), y: mm2px(y, zoom) });
            }
        }
        return dots;
    }, [grid, meta.paper_w_mm, paperHmm, zoom]);

    return (
        <Stage ref={stageRef} width={widthPx + 40} height={heightPx + 40} onClick={onClickStage}>
            <Layer x={20} y={20} listening={false}>
                <Rect width={widthPx} height={heightPx} fill="#fff" stroke="#bfbfbf" strokeWidth={1}
                      shadowBlur={4} shadowColor="rgba(0,0,0,0.08)" />
                {gridDots?.map((d, i) => <Rect key={i} x={d.x} y={d.y} width={1} height={1} fill="#e0e0e0" />)}
            </Layer>
            <Layer x={20} y={20} ref={layerRef}>
                {fields.map((f) => (
                    <Group
                        key={f.id}
                        id={f.id}
                        draggable
                        x={mm2px(f.x, zoom)}
                        y={mm2px(f.y, zoom)}
                        width={mm2px(f.w, zoom)}
                        height={mm2px(f.h, zoom)}
                        rotation={f.rotation ?? 0}
                        onMouseDown={(e) => { e.cancelBubble = true; setSelection([f.id]); }}
                        onDragEnd={onTransformEnd(f.id)}
                        onTransformEnd={onTransformEnd(f.id)}>
                        <FieldNode field={f} zoom={zoom} />
                    </Group>
                ))}
            </Layer>
            <Layer x={20} y={20}>
                <Transformer
                    ref={trRef}
                    keepRatio={false}
                    rotateEnabled={false}
                    anchorSize={8}
                    borderStroke="#1677ff"
                    anchorStroke="#1677ff"
                    boundBoxFunc={(oldBox, newBox) => {
                        if (newBox.width < minSizePx || newBox.height < minSizePx) return oldBox;
                        return newBox;
                    }}
                />
            </Layer>
        </Stage>
    );
}
