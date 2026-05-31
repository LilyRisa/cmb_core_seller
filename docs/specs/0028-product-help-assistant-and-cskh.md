# SPEC-0028 — Trợ lý trợ giúp sản phẩm (RAG) + Hỏi CSKH

Status: Draft · Owner: Minh · Liên quan: [SPEC-0024](0024-omnichannel-messaging.md), [SPEC-0026](0026-custom-http-ai-provider.md)

## 1. Mục tiêu

Widget trợ giúp nổi (kéo–thả) trên app người dùng với 2 tab:
- **Hỏi AI** — RAG hỏi-đáp về **cách dùng hệ thống** (index từ `docs_user/rag_chunks.jsonl`).
- **Hỏi CSKH** — gửi câu hỏi vào hàng đợi, hiển thị thông báo phải chờ phản hồi.

Khác với knowledge messaging của tenant (SPEC-0024): đây là **trợ giúp sản phẩm GLOBAL**
cho người bán/nhân viên đang dùng phần mềm, dùng được cho **mọi gói**.

## 2. Ràng buộc & nguyên tắc

- **Không sửa lớp AI** (`Integrations/Ai/*`) — chỉ gọi qua `AiAssistantRegistry` (`embed()` +
  `generateReply()` với `systemPromptExtra` + `KnowledgeBase`). Provider help dùng **RIÊNG**,
  KHÔNG dùng `messaging_account_meta.ai_provider_code` của tenant. Super-admin chọn provider ở
  `/admin/settings` (nhóm AI): `help_assistant.provider_code` + `help_assistant.embedding_model`
  (system settings, fallback env `HELP_ASSISTANT_PROVIDER` / config). **Cập nhật 2026-05-30:**
  `help_assistant.provider_code` render bằng bộ chọn `HelpProviderSelect` (Radio.Group lấy từ
  `/admin/ai-providers`, gắn nhãn provider nào có embedding + "Tắt") thay ô gõ code thủ công.
- **Cập nhật 2026-05-31 — UX chat CSKH + âm thanh + api_key hiển thị:**
  - Widget tab "Hỏi CSKH" (`HelpChatWidget`) đổi sang giao diện CHAT như app nhắn tin: câu hỏi của
    người dùng (bong bóng phải, xanh) + trả lời CSKH (bong bóng trái, xám) + trạng thái "Đang chờ".
  - Realtime: `useSupportRequests(enabled, intervalMs)` polling — tab CSKH mở poll 8s; khi xuất hiện
    câu trả lời MỚI từ CSKH → phát âm thanh `public/quick-ting.mp3` (baseline lần đầu không kêu cho lịch sử cũ).
- **Cập nhật 2026-05-31 — Báo trả lời CSKH TOÀN CỤC (không cần mở widget):** phát hiện trả lời mới
  + âm thanh + badge chuyển từ `CskhTab` (chỉ mount khi mở tab) lên thân `HelpChatWidget` (luôn mount ở
  `AppLayout`). Widget poll nhẹ 20s kể cả khi đóng (cùng `queryKey` với CskhTab ⇒ React Query gộp observer,
  lấy nhịp nhanh nhất); khi đóng vẫn **kêu** + hiện **badge số** (`<Badge>`) trên nút nổi `CustomerServiceOutlined`.
  Mốc thông báo lưu `localStorage` theo tenant (`support.cskh.notify:{tid}` = `{notified, unseen}`) ⇒ không kêu
  lại khi reload/điều hướng, không kêu cho lịch sử cũ. Mở tab CSKH ⇒ badge về 0; mở widget khi có badge ⇒ tự
  nhảy thẳng tab "Hỏi CSKH". (Autoplay bị trình duyệt chặn tới khi user tương tác lần đầu — cố hữu.)
  - Trang admin "AI Trợ giúp" + "Nhà cung cấp AI": api_key giờ HIỂN THỊ THẲNG (reveal, Input thường)
    để super-admin xem/sửa trực tiếp — provider trả `api_key` plaintext qua API admin; Support reveal
    qua `system-settings/{key}/reveal`. (Key vẫn encrypted-at-rest trong DB.)
- **Cập nhật 2026-05-31 — Admin xem & trả lời yêu cầu CSKH:** thêm `AdminSupportRequestController`
  + routes `/api/v1/admin/support-requests` (guard `admin_web`, withoutGlobalScope TenantScope ⇒
  xuyên tenant): list (filter status/tenant/q, phân trang) + answer + close. Trang admin
  `AdminSupportRequestsPage` (menu "Yêu cầu CSKH") lọc theo trạng thái + modal trả lời. Trước đây
  yêu cầu người dùng gửi chỉ có endpoint user (lọc theo tenant), admin KHÔNG có chỗ xem.
- **Cập nhật 2026-05-31 — Support TÁCH HOÀN TOÀN khỏi `ai_providers`/registry:** trợ lý Hỏi AI
  giờ dùng `SupportAiClient` riêng (Laravel Http gọi thẳng `/v1/chat/completions` + `/v1/embeddings`)
  với **credentials riêng**, KHÔNG còn import `AiAssistantRegistry` hay tạo row `ai_providers`
  (đã xoá `SupportProviderProvisioner`). CHAT và EMBEDDING cấu hình ĐỘC LẬP (mỗi bên base_url +
  api_key + model) → dùng cùng provider hay khác provider tuỳ ý cho chat/embedding. 6 system-setting key
  `help_assistant.{chat_base_url,chat_api_key,chat_model,embedding_base_url,embedding_api_key,embedding_model}`
  (api_key là secret mã hoá), env `HELP_ASSISTANT_*` / `HELP_ASSISTANT_EMBEDDING_*`. Embedding base_url
  trống ⇒ tắt vector, chạy keyword. Trang admin riêng `/admin/ai-support` (`AdminAiSupportPage`,
  menu "AI Trợ giúp") nhập trực tiếp các trường này. KHÔNG cần migration (system_settings key-value).
- **Test AI provider phải kiểm năng lực thực tế**: `/admin/ai-providers` Test giờ kiểm CẢ `embedding`
  (không chỉ chat) — provider chat OK nhưng embedding sai vẫn làm hỏng RAG, trước đây test bỏ sót.
  Trả `results.{chat,embedding}` để super-admin biết provider có dùng được cho Support không.
- **Suy biến mượt (bắt buộc)** — KHÔNG bao giờ 500:
  1. Có provider embedding + Qdrant ⇒ vector search (mode `rag`).
  2. Thiếu provider/Qdrant lỗi ⇒ **keyword search** trên `help_chunks` (mode `keyword`).
  3. Không có provider chat ⇒ trả thẳng nội dung chunk khớp nhất + ghi chú (mode `*_no_llm`).
  4. Chưa index tài liệu ⇒ thông báo gợi ý dùng tab Hỏi CSKH (mode `no_docs`).
- **Chỉ thêm 1 dòng** ở `bootstrap/providers.php` + 1 service `qdrant` ở `docker-compose.yml`.
- **Cập nhật 2026-05-31 — CSKH chuyển sang HỘI THOẠI nhiều tin + đính kèm file/ảnh/video:**
  Thay mô hình `support_requests` (1 câu hỏi – 1 trả lời) bằng **hội thoại + tin nhắn** (chưa chạy live ⇒
  drop bảng cũ). 3 bảng mới: `support_conversations` (status `open|closed`, `last_message_at`, `last_sender`,
  `user_unread_count`, `closed_at/by`), `support_messages` (`sender user|cskh`, `type text|system`, `body`,
  `attachments_count`), `support_message_attachments` (mô phỏng `message_attachments`: `kind/mime/size_bytes/
  storage_path/checksum/filename/status`, đính kèm vào MESSAGE). Attachment hợp lệ ở **cả 2 chiều** (user hỏi +
  CSKH trả lời), tối đa `support.attachments.max_files` (mặc định 5)/tin; giới hạn MIME/size theo `config/support.php`
  (ảnh 25MB, video 100MB, file 25MB) — vi phạm ⇒ 422 `ATTACHMENT_INVALID`. Lưu qua `SupportMediaStorage`
  (`tenants/{tid}/support/{yyyy}/{mm}/{convId}/{uuid}.{ext}`, signed URL TTL 300s, fallback unsigned cho local) +
  `SupportMediaService` — **không** import Services của Messaging (luật module). Domain logic gom vào
  `SupportConversationService` (postUserMessage / postCskhMessage / close / markUserRead) ⇒ controller mỏng.
  - **Đóng/mở lại:** CSKH bấm Đóng ⇒ `status=closed` + chèn message hệ thống ("Hỗ trợ viên đã đóng đoạn hội thoại
    này") + `user_unread_count++` (user được báo). User **gửi tin khi cuộc gần nhất đã đóng ⇒ tự mở cuộc MỚI**;
    đang `open` ⇒ nối vào cuộc hiện tại. Admin **nhắn nhiều tin** trong cuộc open tới khi tự đóng (không reopen).
  - **Thông báo user = server-side unread** (bỏ hack localStorage cũ): `GET /support/unread` (nhẹ, tổng
    `user_unread_count`) cho widget poll 20s ⇒ badge + ping khi tăng; `GET /support/conversations` (đầy đủ thread)
    cho CskhTab poll 8s khi mở. Mở tab CSKH ⇒ `POST /support/conversations/{id}/read` ⇒ badge về 0.
  - **FE:** tách `CskhTab.tsx` + `SupportAttachmentList.tsx` (ảnh thumbnail/video `<video>`/file link). Trang admin
    `support-requests` (giữ route/menu) đổi sang xem thread + nhắn nhiều tin + đính kèm + đóng.

## 3. Thiết kế

### 3.1 Backend — module `Support`
- **Hạ tầng**: service `qdrant` (docker, cổng 6333) + `QdrantClient` (HTTP: ensureCollection/upsert/search,
  an toàn khi tắt). Config `config/support.php` + env `QDRANT_URL`, `QDRANT_API_KEY`,
  `HELP_ASSISTANT_PROVIDER`, `HELP_ASSISTANT_EMBEDDING_MODEL`.
- **Bảng**: `help_chunks` (GLOBAL — không tenant; payload + text cho keyword + ref vector Qdrant),
  `support_conversations` + `support_messages` + `support_message_attachments` (theo tenant — hội thoại CSKH;
  xem khối "Cập nhật 2026-05-31 — CSKH chuyển sang HỘI THOẠI" ở §2). Bảng `support_requests` đã bị bỏ.
- **Index**: artisan `help:index [--fresh]` đọc `docs_user/rag_chunks.jsonl`, embed mỗi chunk
  (idempotent theo ref_key), lưu `help_chunks` + upsert Qdrant. Không có provider embedding ⇒ vẫn
  lưu chunk (keyword chạy được).
- **Services**: `HelpIndexer`, `HelpAssistant` (retrieve vector→keyword fallback, generate qua LLM
  hoặc trả chunk).
- **API** (`/api/v1/support`, auth + verified + tenant; **không** gate gói):
  - `POST /support/assistant/ask {question, history?}` → `{answer, sources[], mode}` (throttle 30/phút).
  - `GET /support/conversations` → hội thoại CSKH của tenant (kèm messages + attachments signed URL).
  - `GET /support/unread` → `{ unread }` (tổng `user_unread_count`) — nguồn nhẹ cho badge widget.
  - `POST /support/messages` *(multipart `body?` + `files[]`)* → gửi tin (tự mở cuộc mới nếu cuộc gần nhất đã đóng; throttle 20/phút).
  - `POST /support/conversations/{id}/read` → xoá unread phía user.
  - Admin (`/api/v1/admin/support-conversations`, guard `admin_web`, xuyên tenant): `GET /` (lọc status/awaiting/tenant/q),
    `GET /{id}` (thread), `POST /{id}/messages` *(multipart)*, `POST /{id}/close`.

### 3.2 Frontend (chỉ app người dùng)
- `HelpChatWidget` mount global ở `AppLayout`: nút tròn nổi **kéo–thả** (nhớ vị trí localStorage),
  bấm mở cửa sổ nhỏ 2 tab. Tab Hỏi AI: chat + nguồn tham khảo. Tab Hỏi CSKH: ô nhập + banner
  "vui lòng chờ" + lịch sử yêu cầu. Hook `lib/support.tsx`.

## 4. Phi mục tiêu (YAGNI)
- Không widget ở app super-admin.
- Không streaming token; trả nguyên câu trả lời.
- Không realtime (Reverb) cho CSKH — dùng polling như §2 (follow-up nếu cần).
- Admin không "mở lại" cuộc đã đóng — user nhắn mới sẽ tự mở cuộc mới.

## 5. Kiểm thử & giới hạn (honest)
- Feature test bằng `Http::fake` (Qdrant + embed + chat): RAG happy-path, keyword fallback, no-docs,
  CSKH persist + tenant isolation + auth. **8 test pass.**
- **Bật mặc định + provider RIÊNG tự seed**: `QDRANT_URL` và `HELP_ASSISTANT_PROVIDER` (=`support`)
  BẬT mặc định trong config/compose. Khi đặt **`HELP_ASSISTANT_API_KEY`** (1 key OpenAI-compatible
  có embedding), `php artisan help:index` tự **provision** row `ai_providers` code `support`
  (adapter openai_compatible, từ env — `SupportProviderProvisioner`, idempotent) rồi index. Không có
  key ⇒ bỏ qua provision, widget chạy **keyword fallback** (không lỗi). Provider này tách hẳn provider
  messaging của tenant. Vẫn có thể tự tạo/đổi ở `/admin/ai-providers` + `/admin/settings` (nhóm AI).
- **Chưa chạy live** ở môi trường dev (chưa có Qdrant container + chưa có key). Code verify bằng
  `Http::fake`; live cần Qdrant chạy + `HELP_ASSISTANT_API_KEY`.
- **Triển khai container**: đã thêm service `qdrant` + env vào **cả** `docker-compose.yml` (dev) và
  `docker-compose.prod.yml` (prod/Portainer). `docs_user/` nằm ngoài context build `./app` nên KHÔNG
  vào image ⇒ ship sẵn `app/resources/help/rag_chunks.jsonl` (copy từ docs_user); `config('support.docs_path')`
  tự chọn: dev dùng `../docs_user`, prod dùng `resources/help` (đặt `HELP_DOCS_PATH` để ghi đè).
  Sau deploy chạy 1 lần: `docker compose -f docker-compose.prod.yml exec app php artisan help:index`.
  ⚠️ Khi regenerate `docs_user/rag_chunks.jsonl`, nhớ copy lại sang `app/resources/help/` cho prod.
