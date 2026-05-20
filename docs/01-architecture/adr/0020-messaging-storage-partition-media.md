# ADR-0020: Lưu trữ messaging — partition `messages` theo tháng; media relay vào MinIO; raw payload purge 30 ngày

- **Trạng thái:** Proposed
- **Ngày:** 2026-05-19
- **Người quyết định:** Team (chờ duyệt SPEC-0024)
- **Liên quan:** SPEC-0024, ADR-0002 (Postgres partition theo tháng), `08-security-and-privacy.md`, `02-data-model/overview.md` §1

## Bối cảnh

`messages` dự kiến là bảng **lớn nhất** trong hệ thống sau `orders` — ước tính 10× số dòng so với `orders` (mỗi đơn ~3 tin trung bình, cộng với tin không liên quan đơn). Với target 100 nhà bán × 5,000 đơn/tháng = 500k đơn/tháng ⇒ ~5M messages/tháng = ~60M/năm.

Vấn đề:
- Bảng không partition ⇒ query inbox chậm sau 6–12 tháng (full scan).
- Media (ảnh/video/file) buyer gửi: sàn trả URL ngắn hạn (Shopee/TikTok ~24h, Facebook ~vài giờ) — nếu không tải về, mất.
- Raw payload từ sàn chứa PII không cần thiết để vận hành sau khi đã upsert — giữ lâu = rủi ro PII + tốn disk.

**Phương án đã cân nhắc cho partition:**
A. Không partition; hosting trên Postgres + index tốt + read replica. ✗ Sau 12 tháng index bloat, vacuum đau.
B. **Partition RANGE theo tháng trên `created_at`** — giống pattern `orders`/`inventory_movements`/`webhook_events` đã có.
   ✓ Có sẵn helper `MonthlyPartition`/`PartitionRegistry` + scheduler `db:partitions:ensure`. Archive/drop partition cũ qua chính sách lưu trữ.
C. Move sang specialized store (Cassandra/ScyllaDB). ✗ Out of scope (ADR-0003 modular monolith).

**Phương án đã cân nhắc cho media:**
A. Lưu URL gốc từ sàn, không tải về. ✗ URL hết hạn ⇒ NV mở lại không thấy ảnh.
B. **Tải về MinIO ngay khi nhận** (`DownloadInboundMedia` job, queue `messaging-media`). Lưu `tenants/{id}/messaging/{yyyy/mm}/{uuid}.{ext}`. Giữ `external_url` để re-fetch nếu cần.
   ✓ Persistent + scope theo tenant như mọi file khác (`08-security-and-privacy.md` §2).
C. Lazy-load on view. ✗ Khi NV cần xem là URL đã chết — UX xấu, race.

**Phương án đã cân nhắc cho raw payload:**
A. Giữ vĩnh viễn (tiện debug). ✗ Dung lượng + PII rủi ro lâu dài.
B. Không lưu (chỉ DTO chuẩn). ✗ Mất khả năng debug schema lạ từ sàn.
C. **Lưu 30 ngày → prune** bằng job hằng ngày `PruneMessagingPayloads`. Sau prune chỉ giữ DTO + metadata.
   ✓ Đủ thời gian debug; PII window ngắn.

## Quyết định

Chọn **B (partition theo tháng) + B (media relay MinIO) + C (raw payload 30 ngày)**.

Chi tiết:
- `messages` partition RANGE `created_at` theo tháng. Đăng ký với `PartitionRegistry` ⇒ `db:partitions:ensure` tự tạo tháng kế.
- `conversations` partition RANGE `last_message_at` theo tháng — vì query inbox luôn `ORDER BY last_message_at DESC` + filter window gần đây. Move giữa partitions khi tin mới đến (tự nhiên qua re-insert? — KHÔNG, partition theo `last_message_at` không khả thi vì cột này UPDATE thường xuyên gây di chuyển partition). **Sửa lại:** `conversations` KHÔNG partition — bảng "header" nhỏ hơn (1 row / cặp shop-buyer), index `(tenant_id, last_message_at DESC)` đủ trong vòng 2–3 năm. **Chỉ `messages` partition.**
- `message_attachments`: không partition (1-1 hoặc 1-n với messages, dung lượng metadata nhỏ; file thực ở MinIO).
- `auto_reply_runs` partition RANGE `fired_at` theo tháng, prune > 90 ngày.
- `ai_assistant_runs` partition RANGE `created_at` theo tháng, prune > 365 ngày.
- Media: `DownloadInboundMedia` job tải file → tính sha256 checksum → ghi `message_attachments.storage_path`. Failed > 3 retries ⇒ status `failed` + UI hiện "Không tải được". Outbound media: upload từ FE → MinIO → connector tự fetch hoặc gửi qua API sàn.
- Raw payload: lưu trong `messages.raw_payload jsonb` (compress nhờ TOAST). Job `PruneMessagingPayloads` hằng ngày 03:00 set `raw_payload = NULL` cho rows `created_at < now() - interval '30 days'`. **Không** drop row — DTO + metadata giữ vĩnh viễn (đến khi anonymize policy đẩy đi).

Quy ước MinIO path:
```
tenants/{tenant_id}/messaging/
├── inbound/{yyyy}/{mm}/{conversation_id}/{uuid}.{ext}
├── outbound/{yyyy}/{mm}/{conversation_id}/{uuid}.{ext}
└── templates/{template_id}/{uuid}.{ext}
```

Signed URL TTL **5 phút** cho hiển thị FE (đủ tải xong + render); FE re-fetch khi expire (qua hook `useSignedUrl`).

Validation upload:
- `image/*`: max 25MB, MIME whitelist `image/jpeg|png|webp|gif`.
- `video/*`: max 100MB, MIME `video/mp4|quicktime|webm`.
- `file`: max 25MB, MIME whitelist (pdf/docx/xlsx/zip/csv/txt).
- Tổng kích thước per tenant per ngày: gating qua `plan.limit:messaging_media_mb_daily` (mới — đề xuất, default Pro=500MB, Business=5GB).

## Hệ quả

**Tích cực:**
- Query inbox luôn chạy trên 1–3 partition gần nhất ⇒ nhanh, ổn định.
- Archive partition cũ (vd > 24 tháng) qua job → detach + drop, không phá schema.
- Media bền vững, scope theo tenant + signed URL như mọi file khác (đồng nhất `08-security-and-privacy.md`).
- Raw payload window 30 ngày: đủ debug khi sàn đổi format; PII window ngắn ⇒ giảm rủi ro.

**Tiêu cực / đánh đổi:**
- Storage cost MinIO/S3 tăng đáng kể nếu buyer hay gửi video — cần monitor & cap per tenant qua `plan.limit:messaging_media_mb_daily`.
- Partition + prune job thêm vào scheduler list — tăng thao tác vận hành 1 chút (đã có pattern).
- Re-fetch media từ sàn sau khi URL hết hạn: chỉ Facebook cho re-fetch qua attachment_id; Shopee/TikTok có thể không → mất media nếu DownloadInboundMedia fail vĩnh viễn. Mitigation: ưu tiên queue `messaging-media` cao, alert nếu job fail > N lần.

**Việc phải làm theo sau:**
- Đăng ký 3 bảng (`messages`, `auto_reply_runs`, `ai_assistant_runs`) với `PartitionRegistry`.
- Thêm scheduler entries: `PruneMessagingPayloads` (daily), `PruneAutoReplyRuns` (daily), `PruneAiRuns` (daily), `EnsureMessagingPartitions` (extend `db:partitions:ensure`).
- Cập nhật `02-data-model/overview.md` quy ước §1.9 thêm `messages`, `auto_reply_runs`, `ai_assistant_runs`.
- Cập nhật `07-infra/queues-and-scheduler.md` thêm queue `messaging-media` + 5 scheduler entries.
- Cập nhật `08-security-and-privacy.md` §6: chính sách 30 ngày raw payload, 90 ngày anonymize disconnect, signed URL 5 phút.
- Thêm `plan.limit:messaging_media_mb_daily` vào Billing plan limits (seeder + middleware).
