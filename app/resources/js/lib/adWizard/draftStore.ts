import { create } from 'zustand';
import type { AdDraftPayload, AdObjective } from '@/lib/adWizard';

type WizardState = {
    draftId: number | null;
    accountId: number | null;
    step: number;
    name: string;
    objective: AdObjective | null;
    payload: AdDraftPayload;
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
    markSaved: () => void;
};

export const useDraftStore = create<WizardState & WizardActions>()((set) => ({
    draftId: null, accountId: null, step: 0, name: '', objective: null, payload: {}, dirty: false,
    load: (d) => set({ draftId: d.id, accountId: d.accountId, name: d.name ?? '', objective: d.objective, payload: d.payload ?? {}, dirty: false, step: 0 }),
    reset: (accountId) => set({ draftId: null, accountId, step: 0, name: '', objective: null, payload: {}, dirty: false }),
    setStep: (step) => set({ step }),
    setName: (name) => set({ name, dirty: true }),
    setObjective: (objective) => set({ objective, dirty: true }),
    patchPayload: (patch) => set((s) => ({ payload: { ...s.payload, ...patch }, dirty: true })),
    patchCreative: (patch) => set((s) => ({ payload: { ...s.payload, creative: { ...s.payload.creative, ...patch } }, dirty: true })),
    markSaved: () => set({ dirty: false }),
}));
