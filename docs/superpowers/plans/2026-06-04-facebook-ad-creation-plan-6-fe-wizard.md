# Facebook Ad Creation — Plan 6: Frontend Wizard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`).

**Goal:** A desktop-only 6-step wizard that lets a non-expert build & publish a Facebook ad — objective → budget → audience → placements → creative (pick a Page post w/ like/comment/share or new creative) → review/preview/publish — with autosaved drafts, an AntD Guided Tour, and an AI suggestion slide-over.

**Architecture:** TanStack Query hooks in `lib/adWizard.tsx` (all the Plan 1–5 APIs). A Zustand store holds the in-progress draft + dirty flag; the page autosaves (debounced PATCH). `AdWizardPage` renders an AntD `Steps` rail + per-step form + live preview column. A "Quảng cáo của tôi" drafts list + "Tạo quảng cáo" button is the entry. **Verification: no JS test runner exists — each task verifies with `npm run typecheck` (the touched files must add NO new errors) and `npx eslint <files>` (clean).** Match the existing `pages/MarketingDashboardPage.tsx` AntD style.

**Tech Stack:** React 18, Vite, AntD 5.29 (incl. `Tour`), `@ant-design/icons`, TanStack Query 5, Zustand 5, React Router. axios via `@/lib/api` `tenantApi`.

**Project UI rules (from the user's memory — enforce):** icons must be `@ant-design/icons`, **never emoji**; prefer `Segmented`/`Radio.Group` over `Select` for small option sets; validate-by-disable with inline hints. Money is integer VND.

**Conventions:** Files under `app/resources/js/`. Mirror `lib/marketing.tsx` (the `useScopedApi`/`tenantApi` + `useQuery`/`useMutation` pattern) and `lib/labelEditor/editorStore.ts` (Zustand). Run `npm` from `app/`.

---

### Task 1: API hooks + types (`lib/adWizard.tsx`)

**Files:**
- Create: `app/resources/js/lib/adWizard.tsx`

- [ ] **Step 1: Implement** the hooks file. (No test runner — verify by typecheck + lint in Step 2.)

```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** Facebook ad-creation wizard data layer — all calls via /api/v1/marketing/*. */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export type AdObjective = 'messages' | 'engagement' | 'traffic';
export type DraftStatus = 'draft' | 'publishing' | 'published' | 'failed';

export interface AdDraftPayload {
    budget?: { type?: 'daily'; daily_major?: number };
    schedule?: { start_time?: string | null };
    targeting?: Record<string, unknown>;
    placements?: 'automatic' | 'manual';
    creative?: {
        mode?: 'page_post' | 'new';
        page_id?: string;
        page_post_id?: string;
        image_hash?: string;
        primary_text?: string;
        headline?: string;
        link_url?: string;
        cta?: string;
    };
    [k: string]: unknown;
}

export interface AdDraft {
    id: number;
    ad_account_id: number;
    name: string | null;
    status: DraftStatus;
    objective: AdObjective | null;
    payload: AdDraftPayload;
    campaign_external_id: string | null;
    adset_external_id: string | null;
    ad_external_id: string | null;
    last_error: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface AdPage { id: string; name: string }
export interface AdPagePost {
    id: string; message: string | null; created_time: string;
    media_type: string; image_url: string | null;
    likes: number; comments: number; shares: number;
}
export interface TargetingOption { id: string; name: string; type: string; audience_size: number | null }
export interface AudienceSize { lower_bound: number | null; upper_bound: number | null }
export interface AdPreview { format: string; body: string }

const KEY = 'marketing-adwizard';

export function useAdDrafts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'drafts', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: AdDraft[] }>('/marketing/ad-drafts')).data.data,
    });
}

export function useAdDraft(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'draft', id, tenantId],
        enabled: api != null && id != null,
        queryFn: async () => (await api!.get<{ data: AdDraft }>(`/marketing/ad-drafts/${id}`)).data.data,
    });
}

export function useCreateDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { ad_account_id: number; name?: string; objective?: AdObjective; payload?: AdDraftPayload }) =>
            (await api!.post<{ data: AdDraft }>('/marketing/ad-drafts', input)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
    });
}

export function useUpdateDraft() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ id, patch }: { id: number; patch: { name?: string; objective?: AdObjective; payload?: AdDraftPayload } }) =>
            (await api!.patch<{ data: AdDraft }>(`/marketing/ad-drafts/${id}`, patch)).data.data,
    });
}

export function usePublishDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: { queued: boolean; status: DraftStatus } }>(`/marketing/ad-drafts/${id}/publish`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
    });
}

export function useDeleteDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/marketing/ad-drafts/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
    });
}

export function useAdPages(accountId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'pages', accountId, tenantId],
        enabled: api != null && accountId != null,
        queryFn: async () => (await api!.get<{ data: AdPage[] }>(`/marketing/ad-accounts/${accountId}/pages`)).data.data,
    });
}

export function usePagePosts(accountId: number | null, pageId: string | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'page-posts', accountId, pageId, tenantId],
        enabled: api != null && accountId != null && pageId != null,
        queryFn: async () => (await api!.get<{ data: AdPagePost[] }>(`/marketing/ad-accounts/${accountId}/pages/${pageId}/posts`)).data.data,
    });
}

export function useTargetingSearch() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ accountId, q, type }: { accountId: number; q: string; type?: string }) =>
            (await api!.get<{ data: TargetingOption[] }>(`/marketing/ad-accounts/${accountId}/targeting-search`, { params: { q, type } })).data.data,
    });
}

export function useAudienceEstimate() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ accountId, targeting, optimization_goal }: { accountId: number; targeting: Record<string, unknown>; optimization_goal?: string }) =>
            (await api!.post<{ data: AudienceSize }>(`/marketing/ad-accounts/${accountId}/audience-estimate`, { targeting, optimization_goal })).data.data,
    });
}

export function useAdPreviews() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ accountId, creative, formats }: { accountId: number; creative: Record<string, unknown>; formats?: string[] }) =>
            (await api!.post<{ data: AdPreview[] }>(`/marketing/ad-accounts/${accountId}/ad-previews`, { creative, formats })).data.data,
    });
}
```

> NOTE: verify `useCurrentTenantId` is exported from `@/lib/tenant` (it is — `lib/marketing.tsx` imports it). If the export name differs, match `lib/marketing.tsx`.

- [ ] **Step 2: Verify (typecheck + lint)**

Run (from `app/`):
```
npx tsc --noEmit -p tsconfig.json 2>&1 | grep "adWizard" || echo "NO adWizard type errors"
npx eslint resources/js/lib/adWizard.tsx
```
Expected: no errors referencing `adWizard.tsx`; eslint clean (no output).

- [ ] **Step 3: Commit**
```
git add app/resources/js/lib/adWizard.tsx
git commit -m "feat(ads-fe): adWizard data hooks (drafts/pages/posts/targeting/preview)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Wizard store + page shell + route + entry

**Files:**
- Create: `app/resources/js/lib/adWizard/draftStore.ts`
- Create: `app/resources/js/pages/AdWizardPage.tsx`
- Modify: `app/resources/js/app.tsx` (2 routes)
- Modify: `app/resources/js/pages/MarketingDashboardPage.tsx` (entry button + drafts list section)

- [ ] **Step 1: Implement the Zustand store** `lib/adWizard/draftStore.ts`:

```ts
import { create } from 'zustand';
import type { AdDraftPayload, AdObjective } from '@/lib/adWizard';

type WizardState = {
    draftId: number | null;
    accountId: number | null;
    step: number;            // 0..5
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
```

- [ ] **Step 2: Implement `pages/AdWizardPage.tsx`** — the shell. Use AntD `Steps`, `Card`, `Button`, `Spin`, `App.useApp` for messages. Behavior:
  - Read `:draftId` from `useParams`; if present, `useAdDraft(draftId)` → `store.load(...)` on success; else the page expects an `accountId` query param and `store.reset(accountId)`.
  - On first render with no draft id: call `useCreateDraft` once (with the accountId + empty payload), then `store.load` the created draft and replace the URL to `/marketing/ads/:id/edit` (so refresh restores).
  - **Autosave:** a `useEffect` watching `store.dirty` debounced ~800ms → `useUpdateDraft({ id, patch: { name, objective, payload } })` → `store.markSaved()`. Show a subtle "Đã lưu" tag.
  - Layout: left = `Steps` (vertical, 6 items: Mục tiêu, Ngân sách, Đối tượng, Vị trí, Nội dung, Xuất bản) bound to `store.step`; center = `<StepRouter step={store.step} />` (a switch rendering the step components from Tasks 3–6 — for THIS task render a placeholder `<div>` per step so it compiles); right = preview placeholder. Footer: "Quay lại"/"Tiếp tục" buttons calling `setStep`.
  - Icons from `@ant-design/icons` only.

  Provide a working shell with placeholder step bodies (`<Empty description={LABELS[step]} />` is fine) so Tasks 3–6 fill them. Export the page.

- [ ] **Step 3: Add routes** in `app.tsx` (inside the authed `<AppLayout>` group, near the marketing route):
```tsx
import { AdWizardPage } from '@/pages/AdWizardPage';
// ...
<Route path="marketing/ads/new" element={<AdWizardPage />} />
<Route path="marketing/ads/:draftId/edit" element={<AdWizardPage />} />
```

- [ ] **Step 4: Entry on `MarketingDashboardPage.tsx`** — add a `Button` (icon `PlusOutlined`) "Tạo quảng cáo" in the top Card that `navigate('/marketing/ads/new?accountId=' + selectedId)` (use `useNavigate`); and a small "Bản nháp của tôi" list (from `useAdDrafts`) with each draft linking to `/marketing/ads/{id}/edit` + status `Tag` + delete (`useDeleteDraft`). Keep it additive — do not break the existing report UI.

- [ ] **Step 5: Verify (typecheck + lint) + commit**
```
# from app/
npx tsc --noEmit -p tsconfig.json 2>&1 | grep -E "AdWizardPage|draftStore|adWizard" || echo "NO new type errors in wizard files"
npx eslint resources/js/pages/AdWizardPage.tsx resources/js/lib/adWizard/draftStore.ts resources/js/pages/MarketingDashboardPage.tsx
```
Expected: no wizard-file type errors; eslint clean. (The full `tsc` has pre-existing admin-page errors — ignore those, only the wizard files matter.)
```
git add app/resources/js/lib/adWizard/draftStore.ts app/resources/js/pages/AdWizardPage.tsx app/resources/js/app.tsx app/resources/js/pages/MarketingDashboardPage.tsx
git commit -m "feat(ads-fe): wizard shell (Steps + Zustand + autosave) + entry + drafts list

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Step 1 (Mục tiêu) + Step 2 (Ngân sách & Lịch)

**Files:**
- Create: `app/resources/js/pages/adWizard/StepObjective.tsx`
- Create: `app/resources/js/pages/adWizard/StepBudget.tsx`
- Modify: `app/resources/js/pages/AdWizardPage.tsx` (wire the two step components into the StepRouter)

- [ ] **Step 1: `StepObjective.tsx`** — `Segmented` or a row of `Card`-style `Radio.Button`s (icons from `@ant-design/icons`: `MessageOutlined`, `LikeOutlined`, `GlobalOutlined`) for the 3 objectives `messages | engagement | traffic` with VN labels ("Tin nhắn", "Tương tác", "Truy cập web"). Bind to `store.objective` via `store.setObjective`. Also a `name` `Input` ("Tên chiến dịch"). Below: a small AI hint `Alert` (static text per objective). `Segmented`/`Radio.Group` — NOT `Select`.

- [ ] **Step 2: `StepBudget.tsx`** — `Segmented` for budget type (only "Hằng ngày" in v1), an `InputNumber` for VND/day (formatter with thousands sep), bound to `store.patchPayload({ budget: { type: 'daily', daily_major } })`. A `DatePicker` for optional start time → `store.patchPayload({ schedule: { start_time } })`. Validate-by-disable: the page's "Tiếp tục" should require `objective` (step 1) and `daily_major > 0` (step 2) — pass a `canNext` boolean up or compute in the page.

- [ ] **Step 3: Wire into `AdWizardPage` StepRouter** (replace placeholders for step 0 and 1 with `<StepObjective/>` and `<StepBudget/>`).

- [ ] **Step 4: Verify + commit**
```
# from app/
npx tsc --noEmit -p tsconfig.json 2>&1 | grep -E "StepObjective|StepBudget|AdWizardPage" || echo "NO new type errors"
npx eslint resources/js/pages/adWizard/StepObjective.tsx resources/js/pages/adWizard/StepBudget.tsx resources/js/pages/AdWizardPage.tsx
git add app/resources/js/pages/adWizard/StepObjective.tsx app/resources/js/pages/adWizard/StepBudget.tsx app/resources/js/pages/AdWizardPage.tsx
git commit -m "feat(ads-fe): wizard steps 1-2 (objective + budget)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Step 3 (Đối tượng) + Step 4 (Vị trí)

**Files:**
- Create: `app/resources/js/pages/adWizard/StepAudience.tsx`
- Create: `app/resources/js/pages/adWizard/StepPlacements.tsx`
- Modify: `app/resources/js/pages/AdWizardPage.tsx` (wire)

- [ ] **Step 1: `StepAudience.tsx`** — country (default VN), age min/max (`Slider` range 13–65), gender `Segmented` (Tất cả/Nam/Nữ), interests via `Select mode="tags"` backed by `useTargetingSearch` (debounced search → options; this is the one legitimate `Select` use — a long searchable list). Build a Graph targeting spec object and `store.patchPayload({ targeting })`. Show audience size via `useAudienceEstimate` (call on targeting change, debounced) as a `Statistic` "Quy mô tệp ước tính".

- [ ] **Step 2: `StepPlacements.tsx`** — `Segmented` "Tự động (khuyến nghị)" / "Thủ công"; manual reveals `Checkbox.Group` (Facebook Feed, Reels, Stories, Instagram). Store under `payload.placements` (+ a `payload.targeting.publisher_platforms` style structure when manual). Keep simple: store `placements: 'automatic' | 'manual'` and the chosen platforms.

- [ ] **Step 3: Wire** steps 2 and 3 into the StepRouter.

- [ ] **Step 4: Verify + commit** (same typecheck-grep + eslint pattern; commit message `feat(ads-fe): wizard steps 3-4 (audience + placements)`).

---

### Task 5: Step 5 (Nội dung) + Page-post picker modal

**Files:**
- Create: `app/resources/js/pages/adWizard/StepCreative.tsx`
- Create: `app/resources/js/pages/adWizard/PagePostPickerModal.tsx`
- Modify: `app/resources/js/pages/AdWizardPage.tsx` (wire)

- [ ] **Step 1: `PagePostPickerModal.tsx`** — `Modal` showing `useAdPages(accountId)` in a `Select` (page chooser) + `usePagePosts(accountId, pageId)` rendered as a grid of `Card`s: each shows `image_url` (or a `PlayCircleOutlined` for video), `message` (ellipsis), and an engagement row with `@ant-design/icons` `LikeOutlined {likes}` · `MessageOutlined {comments}` · `ShareAltOutlined {shares}`. Selecting a post calls `onPick({ page_id, page_post_id })` and closes. **Engagement counts + media are the headline feature — render them prominently. Icons only, no emoji.**

- [ ] **Step 2: `StepCreative.tsx`** — `Segmented` "Dùng bài viết có sẵn" / "Tạo nội dung mới". Existing-post mode: a button "Chọn bài viết" opening `PagePostPickerModal`; once picked show the post summary (thumbnail + 👍/💬/↗ counts via icons) and store `creative.mode='page_post'`, `page_id`, `page_post_id`. New mode: `Input.TextArea` (primary_text), `Input` (headline), `Input` (link_url) + a note that image upload is coming soon (disabled). CTA `Segmented` limited to the objective's allowed set (messages→"Gửi tin nhắn"/MESSAGE_PAGE; traffic→"Tìm hiểu thêm"/LEARN_MORE, "Mua ngay"/SHOP_NOW; engagement→LEARN_MORE). Bind via `store.patchCreative`.

- [ ] **Step 3: Wire** step 4 into the StepRouter.

- [ ] **Step 4: Verify + commit** (`feat(ads-fe): wizard step 5 (creative) + Page-post picker (engagement+media)`).

---

### Task 6: Step 6 (Xem trước & Xuất bản) + Guided Tour + AI slide-over

**Files:**
- Create: `app/resources/js/pages/adWizard/StepReview.tsx`
- Create: `app/resources/js/pages/adWizard/WizardTour.tsx`
- Create: `app/resources/js/pages/adWizard/AiAssistantDrawer.tsx`
- Modify: `app/resources/js/pages/AdWizardPage.tsx` (wire + mount Tour + AI button)

- [ ] **Step 1: `StepReview.tsx`** — a `Descriptions` summary (objective, budget, audience size, creative). A live preview: `useAdPreviews({ accountId, creative })` rendering each returned `body` (iframe HTML) via `dangerouslySetInnerHTML` inside a bordered box (Facebook-rendered, safe-ish: it's Graph's own iframe markup). If the draft `last_error` is set, show an `Alert` error. Publish: `usePublishDraft(draftId)` button → on success `message.success('Đã gửi xuất bản')` + navigate to the drafts list; gate the button disabled until required fields present; if the publish API returns 422 (no ads_management), show the friendly error from the envelope.

- [ ] **Step 2: `WizardTour.tsx`** — AntD `Tour` with ~5 steps targeting refs on the rail/objective/budget/creative/publish (pass `ref`s down or use `getPopupContainer`). Open on first visit (localStorage flag `adwizard.tour.seen`) + a "Hướng dẫn" button to reopen.

- [ ] **Step 3: `AiAssistantDrawer.tsx`** — a `Drawer` (slide-over) with a short prompt input + an "Gợi ý" button. For v1 wire it to the existing forecast/generate AI if trivially available, OR render deterministic local suggestions based on the current step (e.g., budget tip, audience-too-broad tip) — **do not block** on a new backend; clearly label suggestions. A floating `Button` (icon `RobotOutlined`) on the page opens it.

- [ ] **Step 4: Wire** step 5 into the StepRouter; mount `<WizardTour/>` + the AI button in `AdWizardPage`.

- [ ] **Step 5: FINAL verify + commit**
```
# from app/
npx tsc --noEmit -p tsconfig.json 2>&1 | grep -E "adWizard|AdWizardPage|Step[A-Z]|WizardTour|AiAssistant|PagePostPicker" || echo "NO new type errors in wizard files"
npx eslint resources/js/pages/adWizard resources/js/pages/AdWizardPage.tsx resources/js/lib/adWizard.tsx resources/js/lib/adWizard/draftStore.ts
npm run lint
```
Expected: no wizard-file type errors; eslint clean. Then:
```
git add app/resources/js/pages/adWizard app/resources/js/pages/AdWizardPage.tsx
git commit -m "feat(ads-fe): wizard step 6 (review/preview/publish) + Guided Tour + AI drawer

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review (plan author)

**Spec coverage (§6 FE):** hooks ✓ (T1), shell + autosave + entry + drafts list ✓ (T2), 6 steps ✓ (T3–T6), Page-post picker with engagement+media ✓ (T5, headline feature), preview via generatepreviews iframe ✓ (T6), Guided Tour ✓ + AI slide-over ✓ (T6), desktop-only (no responsive work) ✓.

**Verification adaptation:** no JS test runner (per project memory), so tasks verify with `tsc --noEmit` (touched files add no errors) + `eslint`. The full `tsc`/`npm run build` has **pre-existing** admin-page errors unrelated to this work — only wizard files are gated.

**UI-rule compliance baked in:** `@ant-design/icons` only (no emoji); `Segmented`/`Radio` for objective/budget-type/gender/placements/CTA; `Select` only for the searchable interests + page chooser; validate-by-disable on "Tiếp tục"/"Xuất bản".

**Decisions:** AI drawer is deterministic/local for v1 (no new backend) to avoid scope creep; image upload for new-creative is surfaced as "coming soon" (existing-Page-post path is complete). iframe preview uses Graph's own markup via `dangerouslySetInnerHTML` in a contained box.

## Follow-ups (post-Plan-6)
- Media upload endpoint + connector `uploadImage/Video` for the new-creative path.
- Wire the AI drawer to a real marketing-AI suggestion endpoint if desired.
