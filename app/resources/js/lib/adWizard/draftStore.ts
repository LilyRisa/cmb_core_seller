import { create } from 'zustand';
import type { AdDraftPayload, AdNode, AdObjective, AdSetNode } from '@/lib/adWizard';

// Monotonic key generator (no external dep). Each adset/ad gets a stable client key.
let keySeq = 0;
const nextKey = (p: string) => `${p}-${Date.now().toString(36)}-${(keySeq++).toString(36)}`;

const emptyAd = (): AdNode => ({ key: nextKey('ad'), name: 'Quảng cáo 1', external_id: null, creative: { mode: 'page_post' } });
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
    setBudgetMode: (mode: 'campaign' | 'adset') => void;
    setCampaignBudget: (major: number) => void;
    markSaved: () => void;
};

function mergeTree(s: WizardState, adsets: AdSetNode[]): Partial<WizardState> {
    return { adsets, payload: { ...s.payload, adsets }, dirty: true };
}

export const useDraftStore = create<WizardState & WizardActions>()((set) => ({
    draftId: null, accountId: null, step: 0, name: '', objective: null, payload: {}, adsets: [], selectedAdSetKey: null, dirty: false,
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
    setBudgetMode: (mode) => set((s) => ({ payload: { ...s.payload, campaign: { ...s.payload.campaign, budget_mode: mode } }, dirty: true })),
    setCampaignBudget: (major) => set((s) => ({ payload: { ...s.payload, campaign: { ...s.payload.campaign, daily_budget_major: major } }, dirty: true })),
    markSaved: () => set({ dirty: false }),
}));
