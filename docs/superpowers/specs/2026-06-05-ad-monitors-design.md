# Giám sát tự động chiến dịch/nhóm (auto-rules) — Design

> Mỗi chiến dịch/nhóm có thể bật **giám sát**: tự **tăng ngân sách** nếu chi phí/kết quả rẻ (dưới ngưỡng) và tự **tạm dừng** nếu chi phí/kết quả vượt ngưỡng. Chạy nền **30 phút/lần**, có **email** khi hành động.
> Ngày: 2026-06-05 · Trạng thái: approved (chủ uỷ quyền). Làm trên `main`.

## 1. Quy tắc
- **Chi phí/kết quả (CPR)** = `spend / results`. `results` theo mục tiêu (tin nhắn / chuyển đổi / leads — như cột "Kết quả").
- **Tăng ngân sách**: nếu `CPR < increase_below` và `results >= min_results` ⇒ ngân sách mới = `min(current × (1 + step%/100), max_daily_budget?)`.
- **Tạm dừng**: nếu `CPR > pause_above` và `results >= min_results` ⇒ đặt status `PAUSED`.
- Hai ngưỡng rời nhau; nếu cấu hình chồng (increase_below ≥ pause_above) ⇒ **pause thắng** (an toàn).
- `min_results` (mặc định 1) tránh hành động khi dữ liệu nhiễu/0.

## 2. Cấp giám sát + ràng buộc ngân sách
- **Chiến dịch CBO** (campaign có `daily_budget`): giám sát được **cả tăng ngân sách + tạm dừng** (chỉnh ngân sách cấp campaign).
- **Chiến dịch ngân sách-theo-nhóm** (campaign không có budget): giám sát cấp campaign **chỉ tạm dừng** (không có ngân sách campaign để tăng).
- **Nhóm quảng cáo**: cài đặt **đơn lẻ cho từng nhóm** — tăng ngân sách nhóm + tạm dừng nhóm.
- **Ưu tiên**: nếu **chiến dịch đã bật giám sát** ⇒ **bỏ qua** giám sát của các nhóm thuộc chiến dịch đó.
- Phải **hiển thị** chiến dịch/nhóm nào đang được giám sát (icon + cột).

## 3. Backend
- Migration `ad_monitors`: `id`, `tenant_id`(idx), `ad_account_id`(idx), `target_level`(campaign|adset), `target_external_id`(idx), `enabled`, `increase_enabled`, `increase_below`(int VND null), `increase_step_pct`(int, def 20), `max_daily_budget`(int VND null), `pause_enabled`, `pause_above`(int VND null), `min_results`(int def 1), `last_evaluated_at`, `last_action`(null), `last_action_at`(null), `created_by` null, timestamps; `unique(ad_account_id,target_level,target_external_id)`.
- Model `AdMonitor` (BelongsToTenant, casts bool/int).
- Service `AdMonitorEvaluator::evaluateAccount(AdAccount)`:
  - Lấy insights **today** cấp campaign + adset (connector `fetchInsights`, không cache).
  - Tập campaign đang giám sát ⇒ bỏ qua adset-monitor có parent thuộc tập đó.
  - `results` qua `resultValue(objective, dto)` (mirror FE `resultOf`); objective của adset = objective campaign cha.
  - Áp luật ⇒ `connector->updateEntity` (budget hoặc status) + cập nhật `AdEntity` cục bộ + `last_action/last_evaluated_at` + gửi email.
  - `evaluateAll()` lặp account active.
- Job `RunAdMonitors` (queue `marketing-sync`, ShouldBeUnique) ⇒ `evaluateAll()`. Lịch **everyThirtyMinutes** trong `routes/console.php` (`ads-monitors-eval`).
- Notification `AdMonitorActionNotification` (mail) gửi Owner/Admin khi có hành động (gộp danh sách hành động/account).
- Controller `AdMonitorController`: `index(account)` (liệt kê để hiện trạng thái), `upsert(account)` (tạo/sửa theo target), `destroy(monitor)`. Gate `marketing.view`/`marketing.ads.create`.
- Routes group marketing.

## 4. Frontend
- Hooks `useAdMonitors(accountId)`, `useUpsertMonitor`, `useDeleteMonitor`.
- Nút **"Giám sát"** (icon) ở mỗi dòng chiến dịch/nhóm (bảng phẳng + cây) → `MonitorConfigDrawer`:
  - Hiện target; campaign CBO → cả 2 mục; campaign ngân-sách-nhóm → chỉ "Tạm dừng" (ghi chú); adset → cả 2.
  - Trường: bật/tắt; "Tăng nếu CPR <" (VND) + bước % + trần ngân sách; "Tạm dừng nếu CPR >" (VND); min results.
  - Xoá giám sát.
- **Chỉ báo**: dòng đang giám sát hiện icon (AlertOutlined/EyeOutlined) — trong cây và bảng. Nếu campaign giám sát, nhóm con hiện ghi chú "bị campaign ghi đè".

## 5. Bất biến / Testing
- Pass-through write qua `updateEntity` (đã có). Bảng mới tenant-scoped. Tiền VND integer.
- BE: evaluator tăng ngân sách khi CPR rẻ; tạm dừng khi CPR đắt; bỏ qua adset khi campaign giám sát; min_results chặn; email gửi khi hành động. Controller upsert/list/destroy + tenant isolation. Http::fake cho connector.
- FE: typecheck + lint + build.

## 5b. Rà soát Graph API (đối chiếu tài liệu Facebook)
Đã kiểm tra các call chính khớp tài liệu (06/2026):
- **Insights** `GET /{id}/insights` (actions/level/date_preset/time_range): từ **10/06/2025** Meta **bỏ qua** `use_unified_attribution_setting`/`action_report_time` và **tự khớp Ads Manager** (attribution theo ad-set, action_report_time=mixed) ⇒ KHÔNG cần thêm tham số; số "Kết quả" lệch 0 là do thiếu action_type ⇒ đã mở rộng `primaryResult()`.
- **Tạm dừng / sửa**: `POST /{id}` với `status` (ACTIVE/PAUSED/ARCHIVED) và `daily_budget` ⇒ đúng. Lưu ý: tạm dừng campaign **lan xuống** adset/ad (đồng bộ kế tiếp sẽ phản ánh ở bảng).
- **Pixel**: `GET /{account}/adspixels?fields=id,name,last_fired_time,is_unavailable` (field hợp lệ); chia sẻ `POST /{pixel}/shared_accounts` tham số **`business`** + **`account_id`** (id số, bỏ `act_`) ⇒ đúng tên tham số.
- **Tài khoản**: `me/adaccounts?fields=...,business{id,name,profile_picture_uri}` — `profile_picture_uri` là field hợp lệ của Business.
Kết luận: không cần sửa endpoint; chỉ tinh chỉnh trích xuất `actions` (đã làm).

## 5c. Sửa case kết quả = 0 (chi hết ngân sách)
- Khi `results = 0`: KHÔNG chia (tránh /0). **Tạm dừng** nếu `spend >= pause_above` (đã chi tới ngưỡng mà chưa có kết quả ⇒ chi phí/kết quả coi như đã vượt). `min_results` chỉ chặn **tăng ngân sách**, KHÔNG chặn tạm dừng campaign đang đốt tiền.
- Khi `results > 0`: tạm dừng nếu `spend/results > pause_above`; tăng nếu `>= min_results` và `spend/results < increase_below`.

## 6. Giới hạn / nối tiếp
- Cửa sổ đánh giá cố định **today**; chọn khoảng (7/14 ngày) là follow-up.
- Tăng ngân sách theo % (chưa hỗ trợ theo số tiền cố định) — follow-up.
- Giám sát cấp quảng cáo (ad) — chưa (chỉ campaign/adset).
