import { create } from 'zustand';
import { nanoid } from 'nanoid';
import type { Field, Paper, SampleProfile, Template } from '@/lib/shippingLabelTypes';
import { PAPER_PRESETS } from '@/lib/shippingLabelTypes';
import { clampBox } from './coords';

type Meta = { id: number | null; name: string; paper: Paper; paper_w_mm: number; paper_h_mm: number; is_default: boolean };
type Snapshot = { meta: Meta; fields: Field[] };

type EditorState = {
    meta: Meta;
    fields: Field[];
    selection: string[];
    history: { past: Snapshot[]; future: Snapshot[] };
    sampleProfile: SampleProfile;
    zoom: number;
    grid: 0 | 1 | 2 | 5;
};

type EditorActions = {
    init: (tpl: Template | null) => void;
    addField: (field: Field) => void;
    updateField: (id: string, patch: Partial<Field>) => void;
    commitTransform: (id: string, box: { x: number; y: number; w: number; h: number; rotation?: number }) => void;
    removeFields: (ids: string[]) => void;
    setMeta: (patch: Partial<Pick<Meta, 'name'>>) => void;
    setPaper: (paper: Paper, w?: number, h?: number) => { needsConfirm: boolean };
    setSelection: (ids: string[]) => void;
    undo: () => void;
    redo: () => void;
    setSampleProfile: (p: SampleProfile) => void;
    setZoom: (z: number) => void;
    setGrid: (g: 0 | 1 | 2 | 5) => void;
    toPayload: () => Omit<Template, 'id' | 'created_at' | 'updated_at'>;
};

const HISTORY_LIMIT = 50;

const blankMeta = (): Meta => ({ id: null, name: '', paper: 'A6', paper_w_mm: 105, paper_h_mm: 148, is_default: false });

function pushHistory(state: EditorState): Snapshot[] {
    return [...state.history.past, { meta: state.meta, fields: state.fields }].slice(-HISTORY_LIMIT);
}

export const useEditorStore = create<EditorState & EditorActions>((set, get) => ({
    meta: blankMeta(),
    fields: [],
    selection: [],
    history: { past: [], future: [] },
    sampleProfile: 'one_item_short_address',
    zoom: 2,
    grid: 1,

    init: (tpl) => set({
        meta: tpl
            ? { id: tpl.id, name: tpl.name, paper: tpl.paper, paper_w_mm: tpl.paper_w_mm, paper_h_mm: tpl.paper_h_mm, is_default: tpl.is_default }
            : blankMeta(),
        fields: tpl?.schema?.fields ?? [],
        selection: [],
        history: { past: [], future: [] },
    }),

    addField: (field) => set((s) => {
        const id = field.id || nanoid(8);
        // Stagger so consecutive adds don't pile on the same (x,y) and become unselectable.
        const offset = (s.fields.length % 8) * 3;
        const placed: Field = {
            ...field,
            id,
            x: Math.min(field.x + offset, Math.max(0, s.meta.paper_w_mm - field.w)),
            y: Math.min(field.y + offset, Math.max(0, (s.meta.paper_h_mm > 0 ? s.meta.paper_h_mm : 9999) - field.h)),
        } as Field;
        return {
            history: { past: pushHistory(s), future: [] },
            fields: [...s.fields, placed],
            selection: [id],
        };
    }),

    // No history push here: Inspector fires onChange per keystroke; commit-on-blur debouncing
    // is a later iteration (spec §7.1 — debounce 300ms). For now, style/text changes aren't undoable.
    updateField: (id, patch) => set((s) => ({
        fields: s.fields.map((f) => (f.id === id ? ({ ...f, ...patch } as Field) : f)),
    })),

    commitTransform: (id, box) => set((s) => {
        const clamped = clampBox(box, s.meta.paper_w_mm, s.meta.paper_h_mm);
        return {
            history: { past: pushHistory(s), future: [] },
            fields: s.fields.map((f) => (f.id === id ? ({ ...f, ...clamped, rotation: box.rotation ?? f.rotation } as Field) : f)),
        };
    }),

    removeFields: (ids) => set((s) => ({
        history: { past: pushHistory(s), future: [] },
        fields: s.fields.filter((f) => !ids.includes(f.id)),
        selection: [],
    })),

    setMeta: (patch) => set((s) => ({ meta: { ...s.meta, ...patch } })),

    setPaper: (paper, w, h) => {
        const dim = paper === 'custom' ? { w: w ?? 100, h: h ?? 100 } : PAPER_PRESETS[paper];
        const s = get();
        const fitsAll = s.fields.every((f) => f.x + f.w <= dim.w && (dim.h === 0 || f.y + f.h <= dim.h));
        set({
            history: { past: pushHistory(s), future: [] },
            meta: { ...s.meta, paper, paper_w_mm: dim.w, paper_h_mm: dim.h },
        });
        return { needsConfirm: !fitsAll };
    },

    setSelection: (ids) => set({ selection: ids }),

    undo: () => set((s) => {
        const last = s.history.past[s.history.past.length - 1];
        if (!last) return s;
        return {
            history: { past: s.history.past.slice(0, -1), future: [{ meta: s.meta, fields: s.fields }, ...s.history.future] },
            meta: last.meta, fields: last.fields, selection: [],
        };
    }),

    redo: () => set((s) => {
        const next = s.history.future[0];
        if (!next) return s;
        return {
            history: { past: [...s.history.past, { meta: s.meta, fields: s.fields }], future: s.history.future.slice(1) },
            meta: next.meta, fields: next.fields, selection: [],
        };
    }),

    setSampleProfile: (p) => set({ sampleProfile: p }),
    setZoom: (z) => set({ zoom: Math.max(0.5, Math.min(6, z)) }),
    setGrid: (g) => set({ grid: g }),

    toPayload: () => {
        const s = get();
        return {
            name: s.meta.name,
            paper: s.meta.paper,
            paper_w_mm: s.meta.paper_w_mm,
            paper_h_mm: s.meta.paper_h_mm,
            schema_version: 1,
            schema: { fields: s.fields },
            is_default: s.meta.is_default,
        };
    },
}));
