# ADR-0022: Tách cài đặt AI auto-reply theo nhóm kênh + quy tắc ưu tiên giữa AI auto-reply và automation flow

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-30
- **Người quyết định:** Team (chủ shop yêu cầu; duyệt phương án "surgical")
- **Liên quan:** SPEC-0024 (§3.3, §4.6 auto-reply + AI guardrail), ADR-0018 (AiAssistant), `2026-05-28-facebook-automation-flow-builder-design.md`, `2026-05-26-comment-autoreply-and-unified-composer-design.md`

## Bối cảnh

Trên mỗi tin nhắn đến (`MessageReceived`) hiện có **ba hệ thống trả lời tự động chạy song song, không biết nhau**:

1. **AI auto-reply** — `AiAutoModeOnInbound` → `AiSuggestionService::autoRespond`. Cổng duy nhất là `messaging_settings.auto_mode` + `ai_enabled`, **cấp tenant toàn cục**, trả lời **mọi** tin trên **mọi** kênh (cả sàn TMĐT lẫn Facebook), không phân biệt nền tảng.
2. **Automation flow** — `StartFlowOnInbound` → `FlowEngine`. Có trigger `inbox_first_message`, `inbox_keyword`, và `inbox_any` ("Mọi tin nhắn" — catch-all). Trên thực tế UI flow builder mặc định `provider=facebook_page` ⇒ flow hiện **chỉ áp dụng cho Facebook**.
3. **Auto-reply rule phẳng** — `RunAutoReplyOnInbound` → `AutoReplyEngine` (`first_message`, `keyword`, `schedule`, …).

Hai vấn đề:

- **Không tách được cấu hình sàn vs Facebook.** Chủ shop muốn bật AI tự động cho Facebook nhưng vẫn xử lý sàn theo cách khác (hoặc ngược lại). Một công tắc `auto_mode` chung không cho phép điều này.
- **Xung đột "trả lời tất cả".** AI auto-reply (trả lời tất cả) và flow `inbox_any` (xử lý tất cả tin) cùng trả lời mọi tin ⇒ khách nhận 2 phản hồi. Đồng thời AI không được đặt **sau** xử lý tin-đầu-tiên / từ-khoá như mong muốn nghiệp vụ.

## Quyết định

### 1. Tách `auto_mode` thành 2 nhóm kênh

`messaging_settings` thêm 2 cột: `auto_mode_marketplace` (sàn TMĐT: tiktok/shopee/lazada/manual) và `auto_mode_facebook` (facebook_page). `ai_enabled` (bật AI tổng) và `ai_provider_code` **giữ chung cấp tenant** — chỉ công tắc "AI tự gửi tất cả" mới tách (nó là thứ gây xung đột). Cột `auto_mode` cũ giữ lại (deprecated, backfill sang 2 cột mới) để không phá dữ liệu/migration trên DB dùng chung.

Phân nhóm provider tập trung tại 1 chỗ — helper `Messaging\Support\MessagingChannelGroup::forProvider()` — để không rải tên `facebook_page` khắp core. `facebook_page` ⇒ `facebook`; còn lại ⇒ `marketplace`.

### 2. Mô hình ưu tiên 2 tầng (per nhóm kênh)

- **Tầng 1 — xử lý cụ thể, LUÔN ưu tiên:** `first_message` + `keyword` (cả trigger flow `inbox_first_message`/`inbox_keyword` lẫn rule phẳng `first_message`/`keyword`). Ngoài ra: **flow run đang `active`/`waiting`** (hội thoại đang giữa luồng) cũng thuộc Tầng 1.
- **Tầng 2 — bắt tất cả, LOẠI TRỪ LẪN NHAU:** AI auto-reply-all **XOR** flow `inbox_any`. Chỉ một cái được chiếm chỗ Tầng 2 cho mỗi nhóm kênh.

### 3. Cổng ưu tiên cho AI (runtime, deterministic)

`AiAutoModeOnInbound` trước khi gọi `autoRespond`:
- Chọn công tắc theo nhóm kênh của conversation (`auto_mode_facebook` hoặc `auto_mode_marketplace`).
- **Bỏ qua AI nếu một handler Tầng 1 _khớp_ tin này** — kiểm tra "có khớp không", **KHÔNG** kiểm tra "đã trả lời chưa". Vì 3 hệ thống chạy trên hàng đợi song song nên trạng thái "đã gửi" là race-condition; còn "có khớp" là tất định và đúng yêu cầu nghiệp vụ ("nếu **khớp** từ khoá hoặc luồng tin đầu thì không dùng AI").
- Tái dùng `FlowMatcher::matching()` và `AutoReplyEngine::matches()` (predicate mới, không fire) để tránh lệch logic từ khoá/tin-đầu.

### 4. Loại trừ lẫn nhau Tầng 2 (lúc lưu — auto-disable một chiều)

- Bật `auto_mode_facebook` (false→true) ⇒ **tạm dừng (pause)** mọi flow `inbox_any` provider `facebook_page` đang `active`.
- Xuất bản/đưa về `active` một flow `inbox_any` (facebook) ⇒ **tắt** `auto_mode_facebook`.
- **Một chiều:** tắt cái này về sau **không** tự bật lại cái kia (tránh kích hoạt bất ngờ); người dùng tự bật lại.
- Backend là lưới an toàn (enforce ở `MessagingSettingsController` + `AutomationFlowController`); FE hiển thị cảnh báo + xác nhận trước khi lưu.

### Phương án đã loại

- **Unified dispatcher** (gộp 3 listener thành 1 bộ điều phối): sạch hơn về lâu dài và còn khử trùng lặp keyword-rule vs keyword-flow, nhưng phạm vi lớn, rủi ro hồi quy cao, và giải quyết loại trùng lặp người dùng chưa nêu ⇒ YAGNI. Chọn **surgical**: tách settings + 1 cổng ưu tiên + loại trừ lúc lưu.
- **Chặn cứng (không cho lưu khi cả hai bật):** kém mượt; người dùng yêu cầu "bật cái này thì tắt cái kia" ⇒ chọn auto-disable + cảnh báo.

## Hệ quả

**Tích cực:**
- Cấu hình AI tự động độc lập cho sàn vs Facebook.
- Không còn trả lời trùng giữa AI và flow `inbox_any`; AI luôn là phương án cuối (sau tin-đầu/từ-khoá/flow đang chạy).
- Phạm vi nhỏ, tái dùng matcher sẵn có ⇒ ít hồi quy.

**Tiêu cực / đánh đổi:**
- Cột `auto_mode` cũ thành "dead" (deprecated) — nợ kỹ thuật nhỏ, dọn sau.
- Helper `MessagingChannelGroup` biết tên `facebook_page` — chấp nhận được vì đã có tiền lệ (`ChannelAccount::messagingConnectorCode`, `IntegrationsServiceProvider`) và gom về 1 chỗ.
- Cổng ưu tiên thêm vài truy vấn (FlowMatcher + rule predicate) mỗi inbound khi AI auto bật — chạy trên queue `messaging-ai`, không chặn webhook.

**Việc phải làm theo sau:**
- Khi có rules-engine generic (route-around ở `AutoReplyEngine`), cân nhắc gộp về unified dispatcher.
- Nếu sau này flow mở cho provider sàn, mở rộng loại trừ Tầng 2 cho nhóm `marketplace` (logic đã viết theo nhóm, chỉ cần bật UI).
