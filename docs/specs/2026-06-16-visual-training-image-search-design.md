# Visual training & tìm sản phẩm bằng ảnh (Qdrant + CLIP)

- Date: 2026-06-16
- Status: Approved (chốt thiết kế, chưa triển khai)
- Liên quan: `Integrations/Ai`, `AiSuggestionService`, `MediaStorage`, Qdrant (đã có cho Help Assistant — SPEC-0028), `AiCreditService`, Horizon.
- Kế thừa khảo sát: `2026-06-15-ai-vision-inbound-images-design.md` (vision chiều VÀO đã chạy).

## Bối cảnh & mục tiêu

Khách gửi ẢNH trong inbox, AI cần biết **đang hỏi sản phẩm/đối tượng NÀO** để tư vấn đúng. Vision chiều-vào đã chạy (AI nhìn được ảnh), nhưng **chưa biết đối chiếu ảnh với danh mục của shop**. RAG hiện tại là đếm-từ-khóa trên text → ảnh-không-kèm-chữ không khớp được gì.

Mục tiêu: xây **visual search thật** — recall bằng image-embedding, precision bằng vision re-rank — trên hạ tầng Qdrant sẵn có. Đây là **nền tảng lâu dài**, không phải bản vá.

**Quan điểm chốt (không thương lượng):**
- Recall = **visual embedding (CLIP/SigLIP)**. KHÔNG dùng "vision mô tả ảnh → text embedding".
- Precision = **vision LLM re-rank** (bật/tắt được).
- **Tách biệt tuyệt đối**: không sửa `KnowledgeRetriever` (keyword RAG), không đụng collection `omnisell_help` của Help Assistant, không đụng dữ liệu `Sku`/`Product`/`ChannelListing`.
- **Dễ mở rộng**: đổi vendor embedding (CLIP→SigLIP→Voyage) hoặc vector store = thêm 1 connector + 1 dòng register, KHÔNG sửa schema, KHÔNG sửa core.

## Phạm vi

**Trong phạm vi:**
- Catalog **visual training** độc lập do seller **nhập tay** (tạo item + upload ảnh). KHÔNG import Google Sheet/file ở phiên bản này. KHÔNG lấy ảnh từ Sku/Product/ChannelListing.
- Index ảnh → embedding → Qdrant (idempotent).
- Match ảnh → **item** (không phải ảnh) với trạng thái rõ ràng: `matched` / `ambiguous` / `not_found`.
- Tiêu thụ ở 3 nơi: AI auto-reply, AI manual suggest, công cụ "Tìm bằng ảnh" cho seller (API).

**Ngoài phạm vi (phase sau):** import hàng loạt từ Sheet/file; tìm-bằng-chữ cross-modal (CLIP hỗ trợ sẵn, để ngỏ); tự gắn ảnh theo mã/tên file; gợi ý/đề xuất chéo nhiều item.

## Tên trung tính (mở rộng)

Dữ liệu training **không chỉ là sản phẩm** (sau này: logo, bao bì, linh kiện, thiết bị, mẫu nhận diện…). Vì vậy đặt tên trung tính:
- Collection Qdrant: **`visual_training`** (KHÔNG dùng `product_images`).
- Bảng/model: `visual_training_items`, `visual_training_images`, `visual_training_embeddings`.
- Module: `VisualSearch`.

## Kiến trúc tổng thể

```
                    ┌─────────────────────────── Module VisualSearch ───────────────────────────┐
 Seller (UI) ─────► │  TrainingItemService / TrainingImageService  (CRUD item + upload ảnh)       │
                    │            │ store ảnh (MediaStore)        │ dispatch                        │
                    │            ▼                               ▼                                 │
                    │  visual_training_images ──► EmbedTrainingImage (queue: visual-index)         │
                    │                                   │ ImageEmbedder.embedImage()               │
                    │                                   ▼                                          │
                    │                          VectorStore.upsert(visual_training, point)          │
                    │                          + visual_training_embeddings row                    │
                    │                                                                              │
 Ảnh khách ───────► │  VisualMatcher.lookup(tenant, image, opts):                                  │
 (AiSuggestion /    │     1) ImageEmbedder.embedImage(ảnh khách)                                   │
  seller API)       │     2) VectorStore.search(filter tenant) → top-K ẢNH                         │
                    │     3) group by item_id → aggregate score → top-N ITEM                       │
                    │     4) (tùy chọn) VisionReRanker.analyzeImages() → chọn item                 │
                    │     5) VisualMatchResult{matched|ambiguous|not_found}                        │
                    └──────────────────────────────────────────────────────────────────────────┘
                          ▲ ImageEmbedder            ▲ VectorStore           ▲ AiAssistantConnector
              Integrations/Embedding/Image   Integrations/Vector        Integrations/Ai (vision.analyze)
                 (ClipEmbedder → CLIP cont.)  (QdrantStore → Qdrant)     (Claude/OpenAI re-rank)
```

Messaging core tiêu thụ **chỉ qua `Contracts\VisualItemSearch`** (trả DTO). Không match / tắt / lỗi → luồng AI reply chạy **y hệt hôm nay**.

## Lớp Integration mới (vendor-agnostic)

Tuân thủ Connector + Registry; **core không biết tên vendor** (CLIP/Qdrant). Cấu hình ở `config/integrations.php` + `INTEGRATIONS_*` env CSV.

### A. `Integrations/Vector` — kho vector

- `Contracts/VectorStore`:
  ```php
  ensureCollection(string $collection, int $dim, string $distance = 'Cosine'): bool;
  recreateCollection(string $collection, int $dim): bool;
  upsert(string $collection, array $points): bool;   // point: {id:string, vector:list<float>, payload:array}
  search(string $collection, array $vector, int $topK, array $filter = []): array; // [{id,score,payload}]
  deleteByFilter(string $collection, array $filter): bool;
  deleteIds(string $collection, array $ids): bool;
  enabled(): bool;
  ```
- `Qdrant/QdrantStore` implement (HTTP, không SDK) — y triết lý `Support\QdrantClient`: lỗi/chưa cấu hình ⇒ log + trả false/[] (degrade mượt, **không ném**). Hỗ trợ `filter` (Qdrant `must`/`match`/`should`) cho tenant isolation.
- Registry `VectorStoreRegistry::for($code)`; `config/integrations.php['vector']` (driver mặc định `qdrant`, url/api_key/timeout).
- **Lưu ý:** không refactor `Support\QdrantClient` (giữ Help Assistant nguyên). `QdrantStore` là client riêng (cho phép trùng ~80 dòng để tách biệt; ADR ghi rõ lý do).

### B. `Integrations/Embedding/Image` — embedding ảnh

- `Contracts/ImageEmbedder`:
  ```php
  embedImage(string $bytes, string $mime): ImageVectorDTO;  // {vector, dim, model}
  modelKey(): string;   // vd 'jina_clip_v2' — định danh collection & cột model
  dimension(): int;
  enabled(): bool;
  ```
- `Clip/ClipEmbedder` implement: POST tới CLIP sidecar (`/embed`), nhận vector. Config-driven (url/model/dim/timeout).
- Registry `ImageEmbedderRegistry::for($code)`; `config/integrations.php['image_embedding']` (driver mặc định `clip`).
- Thêm SigLIP/Voyage sau = 1 connector mới + 1 dòng register; `modelKey()` khác ⇒ collection khác ⇒ **chạy song song nhiều model không xung đột**.

### C. `Integrations/Ai` — bổ sung ADDITIVE (re-rank)

- Thêm capability `vision.analyze` + method trên `AiAssistantConnector`:
  ```php
  analyzeImages(AiContext $ctx, array $images, string $instruction): string;
  // $images: list<{url|base64, mime}>; trả text thô (JSON do prompt quy định)
  ```
- `ClaudeConnector`/`OpenAiConnector` implement (tái dùng đường vision sẵn có); `CustomHttp`/`Manual` ⇒ `UnsupportedOperation`.
- **Additive** — KHÔNG sửa `generateReply`/`generateText` của luồng tối ưu. Mặc định trait/abstract ném `UnsupportedOperation` để các connector cũ không vỡ.

## Module `VisualSearch`

`app/app/Modules/VisualSearch/` — namespace `CMBcoreSeller\Modules\VisualSearch`. Cấu trúc chuẩn: `Contracts/`, `Database/Migrations/`, `Http/{Controllers,Requests,Resources,routes.php}`, `Jobs/`, `Models/`, `Policies/`, `Services/`, `Console/`, `VisualSearchServiceProvider`.

### Data model (3 bảng — đã tách lớp embedding)

`visual_training_items` (do seller nhập tay):
- `id`, `tenant_id` (BelongsToTenant), `name`, `description` (nullable), `attributes` (json — đặc điểm/ghi chú), `ref_code` (nullable, mã tham chiếu tùy chọn), `status` ('active'|'indexing'|'failed'), `applies_all_pages` (bool, default true), `created_by`, timestamps, softDeletes.
- Pivot `visual_training_item_page` (item_id, channel_account_id, tenant_id) — scoping per-page như SPEC-0035.

`visual_training_images` (chỉ metadata lưu trữ — KHÔNG dính embedding):
- `id`, `tenant_id`, `item_id` (FK cascade), `storage_disk`, `storage_path`, `image_hash` (sha256 — dedupe), `mime_type`, `width`, `height`, `size_bytes`, `sort_order`, timestamps.
- Unique `(tenant_id, item_id, image_hash)` — chống upload trùng.

`visual_training_embeddings` (lớp embedding tách riêng):
- `id`, `tenant_id`, `image_id` (FK cascade), `model` (= `ImageEmbedder::modelKey()`), `version` (smallint), `collection` (tên collection Qdrant thực tế), `vector_id` (string — point id trong Qdrant), `dim`, `status` ('pending'|'indexed'|'failed'), `error` (nullable), `indexed_at` (nullable), timestamps.
- Unique `(image_id, model, version)` — 1 ảnh có thể có **nhiều embedding song song** (CLIP + SigLIP) để migrate model không phá schema.

> **Đổi model về sau:** index model mới vào `visual_training_embeddings` (model/version mới) + collection mới, search vẫn chạy model cũ; khi sẵn sàng → chuyển `active model` qua config; dọn embedding cũ bằng command. Không ALTER bảng.

### Collection & multitenancy Qdrant

- Tên collection vật lý = `{prefix}__{modelKey}`, prefix=`visual_training` (config). Vd `visual_training__jina_clip_v2`.
- **1 collection cho tất cả tenant**, cô lập bằng **payload + filter** (chuẩn multitenancy Qdrant): payload mỗi point = `{tenant_id, item_id, image_id, image_hash}`. Tạo payload index trên `tenant_id`. **Mọi `search` BẮT BUỘC filter `tenant_id`** (cô lập cứng).
- `vector_id` (point id) = uuid (lưu ở `visual_training_embeddings.vector_id`). Idempotent: re-embed cùng ảnh+model ⇒ tái dùng `vector_id` → upsert đè.
- Per-page scope KHÔNG đưa vào filter Qdrant (tránh đồng bộ payload khi seller sửa scope). Recall theo tenant rồi **lọc per-page ở PHP** sau khi group (N nhỏ).

### Services

- `TrainingItemService` — CRUD item + scope per-page.
- `TrainingImageService` — nhận upload, validate (mime/size theo `config`), tính `image_hash`/`width`/`height`/`mime`, lưu qua disk (`config` `visual_search.media_disk`), tạo `visual_training_images`, dispatch `EmbedTrainingImage`. Xóa ảnh ⇒ dispatch xóa vector.
- `VisualIndexer` — `EmbedTrainingImage` gọi: `ImageEmbedder.embedImage()` → `VectorStore.upsert(activeCollection, point)` → ghi/cập nhật `visual_training_embeddings` (status). Idempotent theo `(image_id, model, version)`.
- `VisualMatcher` — **lookup** (xem flow dưới).
- `VisionReRanker` — build prompt, gọi `AiAssistantConnector::analyzeImages` (capability `vision.analyze`), parse JSON kết quả; tính 1 credit qua `AiCreditService`.

### Contracts (Messaging/API tiêu thụ)

- `Contracts/VisualItemSearch`:
  ```php
  lookup(int $tenantId, VisualImageInput $image, VisualLookupOptions $opts): VisualMatchResult;
  ```
  - `VisualImageInput`: bytes | (disk+path) | dataUrl + mime (caller tự resolve, KHÔNG truyền model Messaging vào module).
  - `VisualLookupOptions`: `channelAccountId?` (lọc per-page), `rerank` (bool), `providerCode?` + `aiContext?` (cho re-rank), `topKImages`, `topNItems`.

### DTO kết quả (tri-state — không auto chọn khi sát điểm)

```php
final class VisualMatchResult {
    public string $status;            // 'matched' | 'ambiguous' | 'not_found'
    public ?VisualItemCandidate $item;        // chỉ khi 'matched'
    public array $candidates;         // VisualItemCandidate[] khi 'ambiguous'
    public string $stage;             // 'recall' | 'rerank'  (debug)
}
final class VisualItemCandidate {
    public int $itemId; public string $name; public ?string $description;
    public array $attributes; public float $confidence;
}
```

## Flow MATCH (group-by-item, không trả ảnh)

Một item có nhiều ảnh (`front/back/side/box`). Mục tiêu là **item đúng**, không phải ảnh đúng.

1. `embed = ImageEmbedder.embedImage(ảnh khách)`.
2. `hits = VectorStore.search(activeCollection, embed, topKImages, filter={tenant_id})` → danh sách **ẢNH** + score (cosine).
3. **Group theo `payload.item_id`**, bỏ ẢNH dưới `recall_floor`.
4. **Aggregate score/item** (config `aggregate`): mặc định `max` (điểm cao nhất trong các ảnh của item); tùy chọn `mean`/`max_plus` (max + thưởng nhỏ theo số ảnh khớp).
5. Lọc **per-page** (nếu có `channelAccountId`): bỏ item không `applies_all_pages` và không có pivot cho page đó.
6. Lấy **top-N item**.
7. Quyết định trạng thái:
   - **Không re-rank** (hoặc tắt):
     - `top1 ≥ match_min` và `top1 − top2 ≥ ambiguous_delta` ⇒ `matched`.
     - nhiều item trong khoảng `ambiguous_delta` (vd 0.93/0.92/0.91) ⇒ `ambiguous(candidates)`.
     - không item nào `≥ recall_floor` ⇒ `not_found`.
   - **Có re-rank** (`opts.rerank` && đủ credit && có candidate): `VisionReRanker.analyzeImages(ảnh khách + ảnh đại diện top-N item + name/desc)` → chọn `item_id` hoặc `"none"` + confidence:
     - chọn rõ ⇒ `matched`; `"none"` ⇒ `not_found`; phân vân/nhiều ⇒ `ambiguous`.
8. Trả `VisualMatchResult`.

Ngưỡng `recall_floor`/`match_min`/`ambiguous_delta`/`topKImages`/`topNItems`/`aggregate` đều **config-driven** (`config/visual_search.php`).

## Tiêu thụ

### AI auto-reply + manual suggest (`AiSuggestionService`)
- Chèn **giữa `buildSnapshot()` và `retriever->retrieve()`** (đúng điểm đã khảo sát). Khi tin inbound mới nhất có ảnh:
  - gọi `VisualItemSearch.lookup(...)` (rerank theo `config` + còn credit).
  - `matched` ⇒ bơm `name/description/attributes` item vào prompt (khối context riêng "Sản phẩm khách đang hỏi (nhận diện từ ảnh)") + ưu tiên dùng item đó.
  - `ambiguous` ⇒ bơm danh sách ứng viên + chỉ dẫn AI **hỏi lại khách để xác nhận** (không tự chốt).
  - `not_found`/tắt/lỗi ⇒ **không thêm gì, snapshot/prompt bất biến** (đúng "không đụng luồng tối ưu").
- Persona thêm chỉ dẫn dùng khối nhận-diện này nếu có.

### Seller "Tìm bằng ảnh"
- `POST /api/v1/visual-search/lookup` (multipart ảnh, `rerank` tùy chọn) → trả `VisualMatchResult` (matched/ambiguous/not_found) + candidates để UI hiển thị.

### CRUD catalog (nhập tay)
- `GET/POST/PATCH/DELETE /api/v1/visual-search/items` (+ scope per-page).
- `POST /api/v1/visual-search/items/{id}/images` (upload nhiều ảnh), `DELETE .../images/{imageId}`.
- UI: mục **"Sản phẩm AI training"** đặt cạnh khu AI knowledge/training của Messaging; danh sách item → tạo/sửa → kéo-thả upload ảnh; hiển thị trạng thái index mỗi ảnh.

## Hạ tầng

- **Container `clip`** (sidecar) cạnh Qdrant trong `docker-compose.yml` + `docker-compose.prod.yml`: FastAPI bọc Jina CLIP v2 / SigLIP, expose `POST /embed` (ảnh; trả vector + dim), healthcheck, cùng network. Env: `IMAGE_EMBEDDING_URL`, `IMAGE_EMBEDDING_MODEL`, `IMAGE_EMBEDDING_DIM`.
- **Qdrant**: tái dùng service đang chạy; collection `visual_training__*` (tách hẳn `omnisell_help`).
- **Queue `visual-index`** (mới): PHẢI thêm vào 1 supervisor trong `config/horizon.php` (gotcha đã ghi — job không nằm trong supervisor sẽ kẹt im lặng). Cập nhật `docs/07-infra/queues-and-scheduler.md`.
- **Command** `php artisan visualsearch:reindex [--tenant=] [--model=] [--fresh]` — backfill/re-index; `--model` index model mới song song; `--fresh` recreate collection.

## Cấu hình & plan-gate

- `config/integrations.php`: khối `vector` (qdrant) + `image_embedding` (clip), bật/tắt qua `INTEGRATIONS_VECTOR`/`INTEGRATIONS_IMAGE_EMBEDDING`.
- `config/visual_search.php`: ngưỡng match, topK/topN, aggregate, media_disk, giới hạn ảnh, active embedder code, collection prefix, rerank default.
- **Feature-key plan-gate** `messaging_visual_search` (khai đủ 4 nơi như chuẩn plan-gate). Tenant không có feature ⇒ ẩn UI + API 403 + lookup trong AiSuggestion bỏ qua.

## Tách biệt & degrade an toàn (lỗi)

- Tất cả sau cờ integration + feature-key. Tắt bất kỳ lớp nào (CLIP/Qdrant/feature) ⇒ `lookup` trả `not_found` (không ném) ⇒ luồng AI reply gốc **không đổi**.
- `VectorStore`/`ImageEmbedder` lỗi mạng/timeout ⇒ log + trả rỗng (không ném).
- Re-rank: chỉ chạy khi bật + có ảnh + đủ credit; tính **1 credit/lượt** qua `AiCreditService` (ghi nhận SAU khi provider thành công — đồng bộ cơ chế credit hiện tại).
- KHÔNG sửa `KnowledgeRetriever`, `Support\QdrantClient`, `Sku/Product/ChannelListing`.

## Tenancy & bảo mật

- Mọi bảng `tenant_id` + `BelongsToTenant`. Mọi `search` Qdrant filter `tenant_id` (cô lập cứng — payload index).
- Ảnh lưu theo disk cấu hình, path tenant-scoped; upload validate mime/size; chỉ owner tenant truy cập (Policy + signed URL nếu cần hiển thị).
- Job chạy ngoài tenant context ⇒ truy vấn pivot per-page bằng `whereExists`/`DB::table` trực tiếp (gotcha SPEC-0035), không dùng `$model->pages()` dính TenantScope.

## Module dependency (PR-blocking) — kiểm

- `VisualSearch` → dùng `Integrations/*` (cho phép), `Tenancy` (base), `AiCreditService` qua Contract. KHÔNG `use` nội bộ Services module khác.
- `Messaging` → `VisualSearch\Contracts\VisualItemSearch` (giao tiếp qua Contracts — hợp lệ). KHÔNG cyclic.
- `Integrations/*` KHÔNG import `Modules/*` (chỉ DTO/interface chuẩn).

## Nghiệm thu

- Tạo item + upload ảnh → `EmbedTrainingImage` chạy → `visual_training_embeddings.status='indexed'` + point trong Qdrant (`vector_id` khớp).
- `lookup(ảnh trùng 1 view của item)` ⇒ `matched(item_id)` đúng; nhiều ảnh cùng item KHÔNG làm trả "ảnh", chỉ trả item (group hoạt động).
- 3 item điểm sát nhau ⇒ `ambiguous(candidates)` (không auto chọn).
- Ảnh lạ ⇒ `not_found`.
- Tắt feature/CLIP/Qdrant ⇒ AiSuggestion snapshot/prompt **bất biến** (so khớp test trước/sau).
- Đổi `active embedder` (model mới) ⇒ index song song không phá dữ liệu cũ.
- `pint --test`, `phpstan` (level 5 + baseline), `php artisan test` (test mới cho indexer/matcher/tri-state/degrade) xanh. Lưu ý baseline: repo chưa green toàn cục, không có JS test runner.

## Tài liệu phải cập nhật (đổi docs trước code)

- Spec này.
- ADR: 2 trục Integration mới (`Vector`, `Image Embedding`) + lý do client Qdrant riêng (không refactor Support).
- `docs/01-architecture/modules.md` (thêm module `VisualSearch` + quan hệ contract với Messaging).
- `docs/01-architecture/extensibility-rules.md` (thêm axis Vector/Image-Embedding vào checklist).
- `docs/05-api/endpoints.md` (endpoints mới).
- `docs/07-infra/` (container `clip`, queue `visual-index`, command reindex).

## Mở (quyết khi triển khai)

- Model CLIP cụ thể & dimension (Jina CLIP v2 vs SigLIP) — chốt ở bước infra, config-driven nên không khóa schema.
- Số ảnh đại diện/item gửi cho vision re-rank (mặc định 1–2, config).
- Ngưỡng mặc định `recall_floor/match_min/ambiguous_delta` — calibrate bằng dữ liệu thật sau khi có catalog mẫu.
