# Nhân bản node cây nháp (Ctrl+C / Ctrl+V) — sub-feature H — Design

> Nhân bản nhóm quảng cáo / quảng cáo trong cây nháp của wizard bằng phím tắt + nút bấm.
> Ngày: 2026-06-05 · Trạng thái: approved (bám roadmap/backlog, chủ uỷ quyền). FE-only, làm trên `main`.

## 1. Phạm vi
- Nhân bản **nhóm quảng cáo** (kèm toàn bộ quảng cáo con) và **quảng cáo** trong nháp.
- Phím tắt cấp wizard: **Ctrl/Cmd+C** sao chép nhóm đang chọn, **Ctrl/Cmd+V** dán (nhân bản), **Ctrl/Cmd+D** nhân bản nhanh.
- Nút "Nhân bản" (CopyOutlined) ở thẻ nhóm (AdSetSelector) và thẻ quảng cáo (StepCreative) để dễ thấy.
- Ngoài phạm vi: nhân bản **cả chiến dịch** (campaign) — wizard hiện 1 chiến dịch/nháp; clone campaign = follow-up nếu cần nhiều campaign/nháp.

## 2. Store (`draftStore.ts`)
- Helper `cloneAdNode(ad)` / `cloneAdSetNode(adset)`: deep-clone (JSON) + **key mới** cho node và mọi ad con, **reset `external_id = null`** (bản sao là entity chưa xuất bản, không trỏ tới object FB gốc), thêm hậu tố tên " (sao chép)".
- State thêm `clipboard: ClipboardItem | null` (`{kind:'adset',node}` | `{kind:'ad',node}`).
- Actions:
  - `duplicateAdSet(key)`: chèn bản sao ngay sau node gốc, chọn bản sao.
  - `duplicateAd(adsetKey, adKey)`: thêm bản sao vào cuối `ads` của nhóm.
  - `copyAdSet(key)` / `copyAd(adsetKey, adKey)`: nạp clipboard.
  - `pasteClipboard(targetAdsetKey?)`: clipboard 'adset' → thêm bản sao nhóm + chọn; 'ad' → thêm vào `targetAdsetKey ?? selectedAdSetKey ?? adsets[0]`.
- Mọi thay đổi đi qua `mergeTree` (đánh dấu dirty ⇒ autosave) như các action cây khác.

## 3. UI
- `AdSetSelector`: icon CopyOutlined ở nhóm đang chọn → `duplicateAdSet`.
- `StepCreative`: icon CopyOutlined trên mỗi thẻ quảng cáo → `duplicateAd(selectedAdSetKey, ad.key)`.
- `AdWizardPage`: listener keydown toàn cục cho Ctrl/Cmd + C/V/D.
  - **Bỏ qua** khi focus ở INPUT/TEXTAREA/contentEditable, hoặc khi đang bôi đen text (Ctrl+C giữ hành vi copy text mặc định).
  - Ctrl+V chỉ chặn mặc định khi clipboard nội bộ có nội dung.
  - Phản hồi bằng `message.success`. Icons @ant-design/icons. Không emoji.

## 4. Bất biến / không đụng
- FE-only; không đụng backend/DTO/mapper. Bản sao reset `external_id` ⇒ publish sẽ tạo entity mới (idempotent vẫn đảm bảo ở backend publish).
- Không thêm phụ thuộc ngoài.

## 5. Testing
- Gates FE: typecheck + lint + build. (Logic store thuần, kiểm bằng typecheck + thao tác tay; không có test runner JS trong repo.)

## 6. Giới hạn / nối tiếp
- Clone campaign + nhiều campaign/nháp → follow-up. Ctrl+C/V cấp quảng cáo (ad) hiện qua nút; phím tắt ad-level cần nâng selected-ad lên store → follow-up.
