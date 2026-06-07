# SPEC 0033: Inbox composer nhận biết cửa sổ (Human Agent 7 ngày + utility template)

- **Trạng thái:** Draft
- **Phase:** 7.x (Messaging) — mở rộng
- **Module backend liên quan:** Messaging (chính), Integrations/Messaging
- **Tác giả / Ngày:** Claude · 2026-06-07
- **Liên quan:** SPEC 0024, SPEC 0032 (utility messages — **nền tảng**), `extensibility-rules.md`

## 1. Vấn đề & mục tiêu

Sau khi Meta khai tử message tag (xem SPEC 0032), UX inbox hiện tại cho phép nhân viên "gắn thẻ" (`POST_PURCHASE_UPDATE`…) khi ngoài cửa sổ 24h — nay đã hỏng. Cần thay bằng mô hình đúng policy:

- Trong **24h**: gõ text tự do (`RESPONSE`).
- **24h → 7 ngày**, có quyền **Human Agent** đã duyệt: text tự do của **nhân viên người thật** (`tag=HUMAN_AGENT` — tag duy nhất còn sống).
- **Ngoài 7 ngày**: **khóa ô nhập text tự do**, bắt **chọn utility template đã duyệt** (SPEC 0032) để gửi.

## 2. Trong / ngoài phạm vi

- **Trong:** composer `MessagingPage` tính cửa sổ còn lại; trong cửa sổ cho text (gắn `HUMAN_AGENT` tự động khi 24h–7d); ngoài cửa sổ khóa text + picker chọn utility template approved; gửi qua `queueUtilityTemplate`. Bỏ UX gắn-thẻ cũ (popover chọn tag chết).
- **Ngoài:** Quản lý/đăng ký template (đã ở SPEC 0032). Tin tự động (notifier — SPEC 0032).

## 3. Luồng chính

1. Mở hội thoại FB DM. FE đọc `last_inbound_at` + cờ Human Agent của Page.
2. Tính trạng thái: `in_24h` | `in_7d_human_agent` | `outside`.
3. `in_24h`/`in_7d_human_agent` ⇒ ô soạn bình thường (gửi text; BE gắn `HUMAN_AGENT` khi quá 24h và Page bật Human Agent).
4. `outside` ⇒ ô text khóa; hiện picker "Chọn mẫu tin tiện ích" (list template approved của Page); chọn + điền biến (nếu có) ⇒ gửi `queueUtilityTemplate`.

## 4. Hành vi & quy tắc

- **Tag duy nhất còn dùng = `HUMAN_AGENT`**, chỉ cho tin nhân viên gõ tay trong 7 ngày, chỉ khi Page có quyền Human Agent (cờ trên channel account meta). Không có quyền ⇒ cửa sổ chỉ 24h.
- **Ngoài cửa sổ:** chỉ utility template approved; không cho text tự do (đúng policy Meta).
- **Luật vàng:** FE/BE gate theo năng lực (`outbound.utility_template`, policy `humanAgentWindowHours`), không theo tên sàn.

## 5. Dữ liệu

- Không bảng mới. Cờ `human_agent` trên `channel_accounts.meta` (bool) — set khi tenant xác nhận đã được Meta cấp quyền Human Agent (hoặc suy từ app feature). API trả `outbound_window` (remaining + mode) cho conversation để FE render.

## 6. API & UI

- Conversation resource thêm `outbound_window`: `{ mode: 'open'|'human_agent'|'template_only', free_until, human_agent_until }`.
- FE `MessagingPage`: thay block `needsTag` + tag-attach popover bằng logic 3 trạng thái + template picker. Endpoint list template approved: tái dùng `GET /messaging/utility-templates?status=approved&channel_account_id=`.

## 7. Edge case

- Page không có template approved nào mà hội thoại đã ngoài cửa sổ ⇒ thông báo "Cần tạo & duyệt mẫu tin tiện ích" + link sang Settings (SPEC 0032).
- `last_inbound_at` null ⇒ coi như ngoài cửa sổ.

## 8. Bảo mật & PII

- Như SPEC 0024/0032; chỉ thao tác trên Page thuộc tenant.

## 9. Kiểm thử

- Unit: tính mode cửa sổ (24h/7d/outside) theo `last_inbound_at` + cờ human agent.
- Feature: gửi text ngoài 7 ngày bị chặn (422); gửi template approved ngoài cửa sổ OK.
- FE: typecheck + build; trạng thái composer theo mode.

## 10. Tiêu chí hoàn thành

- [ ] Inbox không còn UX gắn tag chết.
- [ ] Trong 7 ngày (Human Agent) gõ text được; ngoài 7 ngày bắt chọn template approved.
- [ ] Conversation resource trả `outbound_window`.

## 11. Câu hỏi mở

- Cờ Human Agent: tenant tự bật trong Settings hay suy tự động từ app review? (đề xuất: toggle trong Settings, mặc định tắt).
