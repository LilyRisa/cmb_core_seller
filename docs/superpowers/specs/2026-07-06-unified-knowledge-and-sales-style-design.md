# Gộp kho tri thức (text + ảnh) & Phong cách chốt sale — Design Spec

Ngày: 2026-07-06. Trạng thái: chờ duyệt.

## 1. Vấn đề

Hệ thống messaging AI hiện có **hai kho tri thức tách biệt, không liên thông**:

- **Tài liệu chữ (RAG text)** — `AiKnowledgeDocument` + `AiKnowledgeChunk`: text (dán/URL/file) → chunk → **embed vector** vào Qdrant `messaging_kb__*` → truy hồi ngữ nghĩa (`KnowledgeRetriever`) khi AI trả lời.
- **Tài liệu có ảnh (Visual training)** — `VisualTrainingItem` (+ ảnh): tên + mô tả + ảnh (ảnh **không bắt buộc**); ảnh embed CLIP → Qdrant `visual_training__*`; dùng để **gửi ảnh** (khớp tên/mô tả `findByName`) và **khách gửi ảnh → tìm SP** (`lookup`).

**Hệ quả (đau của người dùng):**
1. Mô tả trong "Tài liệu có ảnh" **KHÔNG** được embed vào RAG text → AI không dùng nó để trả lời chữ.
2. Để AI vừa trả lời chữ về sản phẩm vừa gửi ảnh, người bán phải nhập **2 lần** (một ở kho chữ, một ở kho ảnh) → tưởng là 2 dữ liệu khác nhau, gây khó chịu.
3. Khi gửi ảnh chỉ khớp trên kho ảnh (thông tin thường sơ sài) — tách rời khỏi tri thức chữ.

## 2. Mục tiêu & Không mục tiêu

**Mục tiêu**
- **Một loại tri thức duy nhất**: mỗi mục có **text (bắt buộc, luôn embed vector vào RAG như hiện tại)** + **ảnh (tùy chọn)**. Nhập **một lần**, dùng cho **cả** trả lời chữ (RAG) lẫn gửi ảnh + tìm SP bằng ảnh.
- **Bỏ form nhập RAG text riêng** (nguồn gây hiểu nhầm phải tạo 2 lần). Chỉ còn **một** màn nhập.
- Không mất khả năng hiện có: gửi ảnh theo tên/mô tả, khách gửi ảnh → tìm SP, phân trang theo page (SPEC 0035), tách theo provider (facebook/zalo), import file/URL cho tài liệu dài.
- Thêm **Phong cách chốt sale** trong Cài đặt AI: Phase 1 áp cho **toàn bộ page (cả shop)**; chừa sẵn để nâng cấp chọn nhiều page.

**Không mục tiêu (lần này)**
- Không đổi thuật toán khớp ảnh (CLIP/rerank/token-overlap) vừa được làm cứng.
- Không làm per-page/multi-page cho phong cách chốt sale ở Phase 1 (chỉ chừa điểm mở rộng).
- Không xây trình soạn thảo rich-text phức tạp; text thuần + import file/URL là đủ.

## 3. Kiến trúc chọn

**Thực thể hợp nhất xây trên `VisualTrainingItem`** (đổi nhãn UI thành **"Kiến thức"**), vì đây đã là "thẻ sản phẩm/kiến thức" rời rạc có sẵn: tên, mô tả, ref_code, thuộc tính, ảnh (tùy chọn), primary image, phân trang, và **thuật toán khớp ảnh vừa được làm cứng** — giữ nguyên, không phải viết lại.

Bổ sung phần còn thiếu (không phải làm lại): **embed text của mục vào đúng vector RAG hiện tại** + **hút khả năng import file/URL** của kho chữ. Tái dùng tối đa `KnowledgeVectorIndexer` / `KnowledgeRetriever` / `DocumentTextExtractor`.

**Vì sao không xây trên `AiKnowledgeDocument`:** sẽ phải port toàn bộ hệ ảnh (bảng ảnh, embedding CLIP, `findByName`, `lookup`, rerank, primary, dedup) sang document — rủi ro cao hơn và phí công vừa làm cứng. Hướng đã chọn chỉ thêm phần text-RAG (nhẹ, cộng thêm) vào thực thể ảnh.

**Nguyên tắc "ảnh không bắt buộc":** mục chỉ có text → vẫn được embed RAG và trả lời chữ bình thường (không cần ảnh). Mục có thêm ảnh → thêm khả năng gửi ảnh + tìm bằng ảnh.

## 4. Thay đổi dữ liệu

### 4.1 Mở rộng `visual_training_items`
Thêm cột để hút khả năng "tài liệu" (đa số nullable, có default an toàn):
- `title` — dùng `name` sẵn có làm tiêu đề (không thêm cột; với tài liệu dài, `name` = tiêu đề).
- `source` (string, default `inline`): `inline | url | upload`.
- `url` (string, nullable), `storage_path` (string, nullable) — cho nguồn URL/file.
- `content_text` (text, nullable) — text dài đã trích xuất (file/URL) hoặc nội dung thuần dài; `description` giữ cho mô tả ngắn của SP.
- `provider` (string, default `facebook_page`) — tách theo nền tảng, đồng bộ với `AiKnowledgeDocument.provider`.
- `kb_status` (string, default `pending`): trạng thái index text-RAG (`pending|ready|failed`) — tách khỏi `status` (trạng thái index ảnh).
- `chunk_count`, `embedding_provider_code`, `embedding_model`, `embedding_version`, `kb_indexed_at` — metadata index text (gương của `AiKnowledgeDocument`).

### 4.2 Tổng quát hoá `ai_knowledge_chunks`
Chunk thuộc **document (cũ) HOẶC item (mới)**:
- Thêm `visual_item_id` (unsignedBigInt, nullable, index).
- `document_id` → cho phép nullable.
- (Tuỳ chọn) `source_type` (`document|item`) để rõ ràng.
- Ràng buộc: đúng một trong hai id khác null.

Giữ nguyên collection Qdrant `messaging_kb__<model>` (payload thêm `item_id`), để truy hồi ngữ nghĩa dùng chung không đổi.

### 4.3 Không đổi
- `visual_training_images`, `visual_training_embeddings`, pivot `visual_training_item_page`, hệ CLIP/lookup/rerank — **giữ nguyên**.

## 5. Index & Truy hồi

### 5.1 Index text (RAG) cho mục — tái dùng `KnowledgeVectorIndexer`
- Thêm job `IndexKnowledgeItem` (queue `messaging-ai`): khi tạo/sửa mục → dựng text nguồn = `name` + `description` + `attributes` + `content_text` (nếu `source=url/upload` thì trích xuất qua `DocumentTextExtractor`) → chunk (free-text 800 ký tự; tabular = 1 dòng/chunk) → ghi `ai_knowledge_chunks` (`visual_item_id`) → `KnowledgeVectorIndexer` embed + upsert Qdrant (payload `{tenant_id, item_id}`) → set `kb_status=ready`.
- Xoá/xoá mềm mục → `forget()` vector + xoá chunk.
- Đổi model embedding → lệnh `messaging:kb-reindex` bao gồm cả item.

### 5.2 Truy hồi — mở rộng `KnowledgeRetriever`
- `readyDocumentTitles()` → thêm nguồn item: mục `kb_status=ready`, cùng `provider`, đúng phân trang (item `applies_all_pages` hoặc pivot `visual_training_item_page`).
- Truy hồi vector: nạp chunk theo cả `document_id` (cũ) lẫn `visual_item_id` (mới) trong phạm vi hợp lệ; xếp theo điểm; lấy topK. Fallback keyword tương tự.
- `KnowledgeBase` chunk thêm `item_id?` để trace nguồn. Prompt block "# Tài liệu tham khảo" giữ nguyên.

### 5.3 Gửi ảnh & tìm bằng ảnh — không đổi
`findByName` / `imagesForItem` / `lookup` vẫn chạy trên item; nay item đã có đủ text nên khớp tốt hơn. Suy sản phẩm từ ngữ cảnh (đã có) giữ nguyên.

## 6. Giao diện (bỏ form RAG riêng)

- **Hợp nhất 2 panel thành một**: màn **"Kiến thức"** duy nhất. Form nhập: **Tiêu đề/Tên** (bắt buộc), **Nội dung text** (bắt buộc — vùng nhập dài) hoặc **Nhập từ file/URL** (tùy chọn), **Mã SP/ref_code** (tùy chọn), **Ảnh** (tùy chọn, khu vực upload như hiện tại), phạm vi trang (`applies_all_pages` + chọn page), provider.
- **Gỡ** panel/form tạo "Tài liệu (chữ)" cũ (`AiKnowledgeDocument`) khỏi luồng tạo mới. Tài liệu chữ cũ hiển thị dạng **legacy** (xem/sửa/xoá) cho tới khi migrate, KHÔNG cho tạo mới.
- Nhãn rõ: "Ảnh (tùy chọn) — thêm nếu muốn AI gửi ảnh cho khách". Không có ảnh vẫn lưu & trả lời chữ được.

## 7. Migrate dữ liệu (phân kỳ, giảm rủi ro)

- **Phase 1 — không migrate ngay, chạy song song:** `KnowledgeRetriever` truy hồi **cả** doc cũ lẫn item mới. Tạo mới chỉ qua item hợp nhất. Doc cũ vẫn được AI dùng (không mất tri thức), chỉ ẩn nút tạo mới. Ship nhanh, an toàn.
- **Phase 2 — migrate & dọn:** command chuyển `AiKnowledgeDocument` → `visual_training_items` (name=title, source/url/storage_path, content_text=inline/đã trích, provider, pivot page), chunk `document_id`→`visual_item_id`. Sau khi đối chiếu số lượng, gỡ UI legacy; giữ bảng cũ một thời gian để rollback.

## 8. Phong cách chốt sale (tính năng 2)

### 8.1 Lưu trữ (Phase 1: toàn shop)
- Lưu trong `messaging_settings.settings` (JSON bag sẵn có), khóa `sales_closing_style` = một trong các preset + `sales_closing_note` (ghi chú tùy chỉnh, tùy chọn). **Áp cho toàn bộ page của tenant.**
- Chừa mở rộng: sau này thêm khóa cùng tên trong `messaging_account_meta.business_info` (per-page) để **page ghi đè** shop; resolver ưu tiên page → shop → mặc định.

### 8.2 Preset đề xuất (chọn 1)
- `consultative` — Tư vấn nhẹ nhàng, ưu tiên giải đáp, không hối thúc.
- `fast_close` — Thúc đẩy chốt nhanh: chủ động mời đặt, xin thông tin giao hàng sớm.
- `scarcity` — Nhấn ưu đãi/khan hiếm có thời hạn để tạo quyết định.
- `attentive` — Chăm sóc kỹ, hỏi nhu cầu, gợi ý combo/upsell.
- (mặc định) `default` — theo QUY TẮC CHỐT ĐƠN gốc trong persona.

### 8.3 Nối vào prompt
- Thêm `withClosingStyle($extra, $conv)` vào **cả** `draftAutoReply()` và `suggest()` trong chuỗi ghép `$extra` (sau `withBusinessInfo`). Đọc style của tenant (Phase 1), map preset → chỉ dẫn tiếng Việt ngắn, nối vào `$extra` (đứng sau persona nên định hướng được "QUY TẮC CHỐT ĐƠN").
- Không áp cho bước phân loại intent.

### 8.4 UI + API
- `MessagingSettingsPage`: thêm mục "Phong cách chốt sale" (Radio các preset — theo quy ước UI dùng Radio/Segmented, không Select) + ô ghi chú.
- `MessagingSettingsController::update()` + `show()`: nhận/validate `sales_closing_style` (Rule::in presets) + `sales_closing_note` (nullable, max ~500), lưu vào `settings`.

## 9. Kiểm thử

- **Text-RAG cho item**: tạo item chỉ text (không ảnh) → chunk + embed → `KnowledgeRetriever` trả về item cho câu hỏi ngữ nghĩa; per-page + provider scope đúng.
- **Ảnh tùy chọn**: item không ảnh vẫn `kb_status=ready` và không gửi ảnh; item có ảnh vẫn gửi ảnh (findByName/lookup không đổi).
- **Truy hồi song song**: doc cũ + item mới cùng ra kết quả (Phase 1).
- **Migrate** (Phase 2): số item sau = số doc trước; chunk repoint đúng; retrieval tương đương.
- **Phong cách chốt sale**: mỗi preset chèn đúng chỉ dẫn vào `$extra` (cả auto + suggest); không rò vào prompt phân loại; ghi chú tùy chỉnh áp dụng.

## 10. Phân kỳ triển khai

- **Phase 1 (lõi):** cột mới cho item + `visual_item_id` cho chunk; `IndexKnowledgeItem`; retriever gồm item; UI hợp nhất + gỡ form tạo tài liệu chữ; retriever chạy song song doc cũ; **Phong cách chốt sale toàn shop**. → Giải quyết trọn "nhập 1 lần" + "text vào RAG" + tính năng chốt sale.
- **Phase 2 (dọn):** import file/URL trong form hợp nhất (nếu chưa làm ở P1); migrate doc→item; gỡ UI legacy.
- **Phase 3 (nâng cấp):** phong cách chốt sale per-page/multi-page (ghi đè), theo điểm mở rộng đã chừa.

## 11. Rủi ro & giảm thiểu
- Migrate/chạy-song-song: Phase 1 KHÔNG migrate → không rủi ro mất dữ liệu; doc cũ vẫn phục vụ.
- Chi phí embedding tăng (item text): chỉ khi tạo/sửa; tái dùng infra sẵn.
- Prod baked image + `RUN_MIGRATIONS=false`: **CẦN migrate** cột mới sau deploy (Phase 1) — không backfill.
