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
  `generateReply()` với `systemPromptExtra` + `KnowledgeBase`). Provider help dùng **RIÊNG**
  (system config `HELP_ASSISTANT_PROVIDER`), KHÔNG dùng `messaging_account_meta.ai_provider_code`.
- **Suy biến mượt (bắt buộc)** — KHÔNG bao giờ 500:
  1. Có provider embedding + Qdrant ⇒ vector search (mode `rag`).
  2. Thiếu provider/Qdrant lỗi ⇒ **keyword search** trên `help_chunks` (mode `keyword`).
  3. Không có provider chat ⇒ trả thẳng nội dung chunk khớp nhất + ghi chú (mode `*_no_llm`).
  4. Chưa index tài liệu ⇒ thông báo gợi ý dùng tab Hỏi CSKH (mode `no_docs`).
- **Chỉ thêm 1 dòng** ở `bootstrap/providers.php` + 1 service `qdrant` ở `docker-compose.yml`.

## 3. Thiết kế

### 3.1 Backend — module `Support`
- **Hạ tầng**: service `qdrant` (docker, cổng 6333) + `QdrantClient` (HTTP: ensureCollection/upsert/search,
  an toàn khi tắt). Config `config/support.php` + env `QDRANT_URL`, `QDRANT_API_KEY`,
  `HELP_ASSISTANT_PROVIDER`, `HELP_ASSISTANT_EMBEDDING_MODEL`.
- **Bảng**: `help_chunks` (GLOBAL — không tenant; payload + text cho keyword + ref vector Qdrant),
  `support_requests` (theo tenant — câu hỏi CSKH, status pending|answered|closed).
- **Index**: artisan `help:index [--fresh]` đọc `docs_user/rag_chunks.jsonl`, embed mỗi chunk
  (idempotent theo ref_key), lưu `help_chunks` + upsert Qdrant. Không có provider embedding ⇒ vẫn
  lưu chunk (keyword chạy được).
- **Services**: `HelpIndexer`, `HelpAssistant` (retrieve vector→keyword fallback, generate qua LLM
  hoặc trả chunk).
- **API** (`/api/v1/support`, auth + verified + tenant; **không** gate gói):
  - `POST /support/assistant/ask {question, history?}` → `{answer, sources[], mode}` (throttle 30/phút).
  - `POST /support/requests {question}` → tạo yêu cầu + thông báo chờ (throttle 10/phút).
  - `GET /support/requests` → lịch sử yêu cầu của tenant.

### 3.2 Frontend (chỉ app người dùng)
- `HelpChatWidget` mount global ở `AppLayout`: nút tròn nổi **kéo–thả** (nhớ vị trí localStorage),
  bấm mở cửa sổ nhỏ 2 tab. Tab Hỏi AI: chat + nguồn tham khảo. Tab Hỏi CSKH: ô nhập + banner
  "vui lòng chờ" + lịch sử yêu cầu. Hook `lib/support.tsx`.

## 4. Phi mục tiêu (YAGNI)
- Không trang admin xử lý `support_requests` (chỉ lưu hàng đợi; CSKH xem/trả lời = follow-up).
- Không widget ở app super-admin.
- Không streaming token; trả nguyên câu trả lời.

## 5. Kiểm thử & giới hạn (honest)
- Feature test bằng `Http::fake` (Qdrant + embed + chat): RAG happy-path, keyword fallback, no-docs,
  CSKH persist + tenant isolation + auth. **8 test pass.**
- **Chưa chạy live** ở môi trường dev (chưa có Qdrant container + chưa cấu hình provider embedding).
  Để bật RAG vector thật: chạy `docker compose up qdrant`, super-admin tạo 1 `ai_providers`
  (adapter `openai_compatible`, có embedding), đặt `HELP_ASSISTANT_PROVIDER=<code>`, rồi
  `php artisan help:index`. Khi chưa cấu hình, widget vẫn hoạt động bằng keyword fallback.
- **Triển khai container**: đã thêm service `qdrant` + env vào **cả** `docker-compose.yml` (dev) và
  `docker-compose.prod.yml` (prod/Portainer). `docs_user/` nằm ngoài context build `./app` nên KHÔNG
  vào image ⇒ ship sẵn `app/resources/help/rag_chunks.jsonl` (copy từ docs_user); `config('support.docs_path')`
  tự chọn: dev dùng `../docs_user`, prod dùng `resources/help` (đặt `HELP_DOCS_PATH` để ghi đè).
  Sau deploy chạy 1 lần: `docker compose -f docker-compose.prod.yml exec app php artisan help:index`.
  ⚠️ Khi regenerate `docs_user/rag_chunks.jsonl`, nhớ copy lại sang `app/resources/help/` cho prod.
