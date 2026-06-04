# Ngân sách 2 cấp (CBO chiến dịch vs ngân sách nhóm) — sub-feature B — Design

> Cho chọn đặt ngân sách ở **cấp chiến dịch (CBO — Facebook tự phân bổ)** hoặc **cấp nhóm quảng cáo**.
> Ngày: 2026-06-05 · Trạng thái: approved (chủ uỷ quyền tự quyết). Bám cây của sub-feature C. ADR-0017.

## 1. Mục tiêu
Thêm lựa chọn cấp ngân sách. `budget_mode='campaign'` ⇒ campaign có `daily_budget` + `bid_strategy`, nhóm KHÔNG có ngân sách (CBO). `budget_mode='adset'` (mặc định, như C) ⇒ mỗi nhóm có ngân sách riêng, campaign không có.

## 2. Payload (thêm vào cây C)
`payload.campaign = { budget_mode: 'campaign'|'adset', daily_budget_major?: int }`. Mặc định `'adset'` (tương thích C/draft cũ — normalize không tạo `campaign` ⇒ coi như 'adset').

## 3. Connector (integration)
- `CampaignSpecDTO` thêm: `?int $dailyBudgetMajor = null`, `?string $currency = null`, `string $bidStrategy = 'LOWEST_COST_WITHOUT_CAP'`.
- `createCampaign`: nếu `dailyBudgetMajor !== null && > 0` → thêm `daily_budget` (FacebookMoney::toMinorUnits theo currency) + `bid_strategy`.
- `createAdSet`: `daily_budget` thành **có điều kiện** — chỉ gửi khi `dailyBudgetMajor > 0` (CBO ⇒ mapper truyền 0 ⇒ bỏ qua, đúng yêu cầu Graph: nhóm dưới CBO không có ngân sách).

## 4. Mapper (Marketing)
- `campaign(AdDraft, string $currency): CampaignSpecDTO` (đổi: thêm tham số currency): đọc `payload.campaign.budget_mode`; nếu `'campaign'` → `dailyBudgetMajor = payload.campaign.daily_budget_major`, `currency`. Ngược lại → budget null.
- `adSet(...)`: nếu `budget_mode==='campaign'` → `dailyBudgetMajor = 0` (CBO, createAdSet bỏ qua). Ngược lại → `node.budget.daily_major`.
- `PublishAdDraft`: gọi `mapper->campaign($draft, (string) $account->currency)`.

## 5. Frontend
- Store: `payload.campaign = { budget_mode, daily_budget_major }` + actions `setBudgetMode(mode)`, `setCampaignBudget(major)`.
- `StepBudget`: `Segmented` "Cấp ngân sách": **Chiến dịch (tối ưu tự động)** / **Nhóm quảng cáo**. 
  - `'campaign'` → 1 ô ngân sách chiến dịch/ngày + ghi chú "Facebook tự chia cho các nhóm"; ẩn ngân sách từng nhóm.
  - `'adset'` → ngân sách per-nhóm (như C, qua AdSetSelector + updateAdSet).
- `canProceed(1)`: campaign mode → campaign daily_budget > 0; adset mode → selected adset budget > 0.
- `StepReview`: nếu CBO hiển thị "Ngân sách chiến dịch (CBO)"; ngược lại tổng nhóm. `canPublish` cập nhật: campaign mode → campaign budget > 0; adset mode → mọi nhóm budget > 0.

## 6. Không đụng / Testing
- Additive: createAdSet đổi 1 dòng thành điều kiện (adset-level vẫn > 0 ⇒ y như cũ). campaign() thêm tham số currency (cập nhật caller + test).
- Test BE: createCampaign gửi daily_budget+bid_strategy khi CBO, không khi adset; createAdSet bỏ daily_budget khi major=0, có khi >0. Mapper: campaign CBO/adset; adSet budget 0 khi CBO. Publish vẫn xanh.
- Test FE: typecheck/lint; toggle đổi mode.

## 7. Build sequence
1. Connector (DTO + createCampaign CBO + createAdSet conditional) + test.
2. Mapper (campaign(currency) CBO-aware + adSet CBO) + Publish pass currency + test.
3. FE store + StepBudget toggle + Review.

## 8. Giới hạn / nối tiếp
- `bid_strategy` mặc định LOWEST_COST_WITHOUT_CAP (chi phí thấp nhất); chiến lược bid nâng cao (bid cap/cost cap) là follow-up.
- Lifetime budget (trọn đời) chưa — chỉ daily; follow-up.
