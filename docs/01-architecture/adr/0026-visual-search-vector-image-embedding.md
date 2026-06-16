# ADR 0026 — Visual search: trục Vector + Image Embedding (vendor-agnostic)

- Date: 2026-06-16
- Status: Accepted
- Liên quan: SPEC `docs/specs/2026-06-16-visual-training-image-search-design.md`, module `VisualSearch`, ADR-0018 (core không biết tên vendor).

## Bối cảnh

Tính năng "tìm sản phẩm bằng ảnh" (khách gửi ảnh → AI nhận đúng item) cần (1) embedding ẢNH và (2) kho vector. Hạ tầng Qdrant đã có (Help Assistant, SPEC-0028) nhưng dùng cho text help-bot, collection `omnisell_help`. Tầng AI (`ai_providers`) chỉ embedding text.

## Quyết định

1. **Hai trục Integration MỚI, vendor-agnostic** (Connector + Registry, ADR-0018):
   - `Integrations/Vector` — `VectorStore` (Qdrant hôm nay). Cấu hình `config/integrations.php['vector']`.
   - `Integrations/Embedding/Image` — `ImageEmbedder` (CLIP sidecar hôm nay). Cấu hình `config/integrations.php['image_embedding']`.
   - Đổi vendor (SigLIP/Cohere/Voyage; Milvus/pgvector) = thêm 1 connector + 1 dòng register, KHÔNG sửa core.
2. **Client Qdrant RIÊNG** (`Vector\Qdrant\QdrantStore`), KHÔNG refactor `Support\QdrantClient` (help-bot). Chấp nhận trùng ~80 dòng để **tách biệt tuyệt đối** — không rủi ro cho luồng Help Assistant đang chạy. Collection `visual_training__{modelKey}` tách hẳn `omnisell_help`.
3. **Capability AI additive `vision.analyze`** trên `AiAssistantConnector` (Claude/OpenAI implement; Custom/Manual ném `UnsupportedOperation`) — re-rank precision. Additive, KHÔNG sửa `generateReply` (luồng reply tối ưu bất biến).
4. **Multitenancy bằng payload + filter** (1 collection, payload `tenant_id` có index, mọi `search` filter tenant) — chuẩn Qdrant, không tạo collection-per-tenant.
5. **Tách lớp embedding** khỏi bảng ảnh (`visual_training_embeddings` model/version/vector_id) ⇒ đổi/chạy song song nhiều model không ALTER schema.

## Hệ quả

- Tích hợp hạ tầng mới: container `clip` (CLIP sidecar) cạnh Qdrant, queue Horizon `visual-index`.
- Core (`Modules/Messaging`) tiêu thụ qua `VisualSearch\Contracts\VisualItemSearch`; tắt/lỗi/không-khớp ⇒ `not_found`, luồng AI reply không đổi (degrade an toàn).
- Re-rank tốn 1 AI credit (qua `AiCreditMeter`), chỉ chạy khi có ảnh + đủ credit + bật.
