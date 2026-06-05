import { create } from 'zustand';
import type { AdDraftPayload, AdNode, AdObjective, AdSetNode } from '@/lib/adWizard';

// Monotonic key generator (no external dep). Each adset/ad gets a stable client key.
let keySeq = 0;
const nextKey = (p: string) => `${p}-${Date.now().toString(36)}-${(keySeq++).toString(36)}`;

const emptyAd = (): AdNode => ({ key: nextKey('ad'), name: 'Quảng cáo 1', external_id: null, creative: { mode: 'page_post' } });

/** Deep-clone an ad node with a fresh key; clones are unpublished (external_id reset). */
function cloneAdNode(ad: AdNode, suffix = ' (sao chép)'): AdNode {
    const copy = JSON.parse(JSON.stringify(ad)) as AdNode;
    return { ...copy, key: nextKey('ad'), external_id: null, name: (ad.name ?? 'Quảng cáo') + suffix };
}

/** Deep-clone an ad set node — fresh keys for the set and every ad inside it. */
function cloneAdSetNode(adset: AdSetNode, suffix = ' (sao chép)'): AdSetNode {
    const copy = JSON.parse(JSON.stringify(adset)) as AdSetNode;
    return {
        ...copy,
        key: nextKey('adset'),
        external_id: null,
        name: (adset.name ?? 'Nhóm') + suffix,
        ads: (copy.ads ?? []).map((ad) => ({ ...ad, key: nextKey('ad'), external_id: null })),
    };
}

/** What was copied via Ctrl+C — an ad set or a single ad, ready to paste/clone. */
export type ClipboardItem =
    | { kind: 'adset'; node: AdSetNode }
    | { kind: 'ad'; node: AdNode };
const emptyAdSet = (n: number): AdSetNode => ({
    key: nextKey('adset'), name: `Nhóm ${n}`, budget: { daily_major: undefined }, targeting: {},
    placements: 'automatic', placement_platforms: [], schedule: { start_time: null }, external_id: null, ads: [emptyAd()],
});

/** Legacy flat payload → one ad set + one ad; tree payload → itself. */
export function normalizeAdSets(payload: AdDraftPayload): AdSetNode[] {
    if (Array.isArray(payload.adsets) && payload.adsets.length > 0) {
        return payload.adsets.map((as) => ({
            ...as,
            key: as.key ?? nextKey('adset'),
            ads: (as.ads ?? []).map((ad) => ({ ...ad, key: ad.key ?? nextKey('ad') })),
        }));
    }
    const hasFlat = payload.creative != null || payload.targeting != null || payload.budget != null;
    if (!hasFlat) return [emptyAdSet(1)];
    return [{
        key: nextKey('adset'), name: 'Nhóm 1', budget: payload.budget, targeting: payload.targeting,
        placements: payload.placements ?? 'automatic',
        placement_platforms: (payload.placement_platforms as string[] | undefined) ?? [],
        schedule: payload.schedule, external_id: null,
        ads: [{ key: nextKey('ad'), name: 'Quảng cáo 1', external_id: null, creative: payload.creative ?? { mode: 'page_post' } }],
    }];
}

type WizardState = {
    draftId: number | null;
    accountId: number | null;
    step: number;
    name: string;
    objective: AdObjective | null;
    payload: AdDraftPayload;
    adsets: AdSetNode[];
    selectedAdSetKey: string | null;
    clipboard: ClipboardItem | null;
    dirty: boolean;
};

type WizardActions = {
    load: (d: { id: number; accountId: number; name: string | null; objective: AdObjective | null; payload: AdDraftPayload }) => void;
    reset: (accountId: number) => void;
    setStep: (step: number) => void;
    setName: (name: string) => void;
    setObjective: (objective: AdObjective) => void;
    patchPayload: (patch: Partial<AdDraftPayload>) => void;
    patchCreative: (patch: Partial<NonNullable<AdDraftPayload['creative']>>) => void;
    // Tree actions
    selectAdSet: (key: string) => void;
    addAdSet: () => void;
    removeAdSet: (key: string) => void;
    updateAdSet: (key: string, patch: Partial<AdSetNode>) => void;
    addAd: (adsetKey: string) => void;
    removeAd: (adsetKey: string, adKey: string) => void;
    updateAd: (adsetKey: string, adKey: string, patch: Partial<AdNode>) => void;
    // Clone (Ctrl+C / Ctrl+V) — duplicate nodes in the draft tree.
    duplicateAdSet: (key: string) => void;
    duplicateAd: (adsetKey: string, adKey: string) => void;
    copyAdSet: (key: string) => void;
    copyAd: (adsetKey: string, adKey: string) => void;
    pasteClipboard: (targetAdsetKey?: string) => void;
    setBudgetMode: (mode: 'campaign' | 'adset') => void;
    setCampaignBudget: (major: number) => void;
    markSaved: () => void;
};

function mergeTree(s: WizardState, adsets: AdSetNode[]): Partial<WizardState> {
    return { adsets, payload: { ...s.payload, adsets }, dirty: true };
}

export const useDraftStore = create<WizardState & WizardActions>()((set) => ({
    draftId: null, accountId: null, step: 0, name: '', objective: null, payload: {}, adsets: [], selectedAdSetKey: null, clipboard: null, dirty: false,
    load: (d) => {
        const adsets = normalizeAdSets(d.payload ?? {});
        set({ draftId: d.id, accountId: d.accountId, name: d.name ?? '', objective: d.objective, payload: d.payload ?? {}, adsets, selectedAdSetKey: adsets[0]?.key ?? null, dirty: false, step: 0 });
    },
    reset: (accountId) => {
        const adsets = [emptyAdSet(1)];
        set({ draftId: null, accountId, step: 0, name: '', objective: null, payload: { adsets }, adsets, selectedAdSetKey: adsets[0].key, dirty: false });
    },
    setStep: (step) => set({ step }),
    setName: (name) => set({ name, dirty: true }),
    setObjective: (objective) => set({ objective, dirty: true }),
    patchPayload: (patch) => set((s) => ({ payload: { ...s.payload, ...patch }, dirty: true })),
    patchCreative: (patch) => set((s) => ({ payload: { ...s.payload, creative: { ...s.payload.creative, ...patch } }, dirty: true })),
    selectAdSet: (key) => set({ selectedAdSetKey: key }),
    addAdSet: () => set((s) => {
        const node = emptyAdSet(s.adsets.length + 1);
        const adsets = [...s.adsets, node];
        return { ...mergeTree(s, adsets), selectedAdSetKey: node.key };
    }),
    removeAdSet: (key) => set((s) => {
        const filtered = s.adsets.filter((a) => a.key !== key);
        const next = filtered.length ? filtered : [emptyAdSet(1)];
        return { ...mergeTree(s, next), selectedAdSetKey: next[0].key };
    }),
    updateAdSet: (key, patch) => set((s) => mergeTree(s, s.adsets.map((a) => (a.key === key ? { ...a, ...patch } : a)))),
    addAd: (adsetKey) => set((s) => mergeTree(s, s.adsets.map((a) => (a.key === adsetKey ? { ...a, ads: [...a.ads, { key: nextKey('ad'), name: `Quảng cáo ${a.ads.length + 1}`, external_id: null, creative: { mode: 'page_post' } }] } : a)))),
    removeAd: (adsetKey, adKey) => set((s) => mergeTree(s, s.adsets.map((a) => (a.key === adsetKey ? { ...a, ads: a.ads.filter((d) => d.key !== adKey) } : a)))),
    updateAd: (adsetKey, adKey, patch) => set((s) => mergeTree(s, s.adsets.map((a) => (a.key === adsetKey ? { ...a, ads: a.ads.map((d) => (d.key === adKey ? { ...d, ...patch } : d)) } : a)))),
    duplicateAdSet: (key) => set((s) => {
        const src = s.adsets.find((a) => a.key === key);
        if (src == null) return {};
        const clone = cloneAdSetNode(src);
        const idx = s.adsets.findIndex((a) => a.key === key);
        const adsets = [...s.adsets.slice(0, idx + 1), clone, ...s.adsets.slice(idx + 1)];
        return { ...mergeTree(s, adsets), selectedAdSetKey: clone.key };
    }),
    duplicateAd: (adsetKey, adKey) => set((s) => {
        const adset = s.adsets.find((a) => a.key === adsetKey);
        const src = adset?.ads.find((d) => d.key === adKey);
        if (adset == null || src == null) return {};
        const clone = cloneAdNode(src);
        return mergeTree(s, s.adsets.map((a) => (a.key === adsetKey ? { ...a, ads: [...a.ads, clone] } : a)));
    }),
    copyAdSet: (key) => set((s) => {
        const src = s.adsets.find((a) => a.key === key);
        return src == null ? {} : { clipboard: { kind: 'adset', node: src } };
    }),
    copyAd: (adsetKey, adKey) => set((s) => {
        const src = s.adsets.find((a) => a.key === adsetKey)?.ads.find((d) => d.key === adKey);
        return src == null ? {} : { clipboard: { kind: 'ad', node: src } };
    }),
    pasteClipboard: (targetAdsetKey) => set((s) => {
        const clip = s.clipboard;
        if (clip == null) return {};
        if (clip.kind === 'adset') {
            const clone = cloneAdSetNode(clip.node);
            return { ...mergeTree(s, [...s.adsets, clone]), selectedAdSetKey: clone.key };
        }
        const destKey = targetAdsetKey ?? s.selectedAdSetKey ?? s.adsets[0]?.key ?? null;
        if (destKey == null) return {};
        const clone = cloneAdNode(clip.node);
        return mergeTree(s, s.adsets.map((a) => (a.key === destKey ? { ...a, ads: [...a.ads, clone] } : a)));
    }),
    setBudgetMode: (mode) => set((s) => ({ payload: { ...s.payload, campaign: { ...s.payload.campaign, budget_mode: mode } }, dirty: true })),
    setCampaignBudget: (major) => set((s) => ({ payload: { ...s.payload, campaign: { ...s.payload.campaign, daily_budget_major: major } }, dirty: true })),
    markSaved: () => set({ dirty: false }),
}));
