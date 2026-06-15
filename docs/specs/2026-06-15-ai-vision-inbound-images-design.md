# AI phân tích hình ảnh khách gửi (vision, qua link)

- Date: 2026-06-15
- Status: Approved (đang triển khai) — Phase 3 của khảo sát AI vision.
- Liên quan: `AiSuggestionService`, `ConversationSnapshot`, `ClaudeConnector`,
  `OpenAiConnector`, `ReplyPersona`, `MediaStorage`, `config/ai.php`.

## Bối cảnh

Tầng AI hiện text-in/text-out. Khi khách gửi ảnh, ảnh ĐÃ được relay lưu ở
`message_attachments.storage_path` (MinIO/R2) nhưng luồng AI auto-reply/suggestion chỉ
thấy placeholder `[media]` — không phân tích được. Yêu cầu: **gửi kèm LINK ảnh (nếu có)
lên AI và yêu cầu AI phân tích**, đối chiếu hội thoại + tài liệu để trả lời.

## Phạm vi

CHỈ chiều **vào**: khách gửi ảnh → AI nhìn & phân tích. KHÔNG làm: ảnh trong tài liệu
training, AI tự sinh/gửi lại ảnh (tách phase sau).

## Cơ chế

1. **Lấy link ảnh:** `AiSuggestionService::buildSnapshot` — với mỗi tin có attachment ảnh
   (`kind=image`, `status=downloaded`), lấy **signed URL tạm** (`MediaStorage::temporaryUrl`,
   bản đã relay — KHÔNG dùng `external_url` TTL ngắn của sàn). Gắn vào
   `recentMessages[].image_urls` (giới hạn `ai.vision.max_images_per_message`, mặc định 3).
2. **DTO:** `ConversationSnapshot.recentMessages[]` thêm khóa tùy chọn `image_urls: list<string>`.
3. **Adapter gửi ảnh (chỉ khi model có vision):**
   - Claude: `content: [{type:text}, {type:image, source:{type:url|base64}}]`.
   - OpenAI: `content: [{type:text}, {type:image_url, image_url:{url}}]`.
   - URL `https://…` → gửi dạng link; data-URI `data:…;base64,…` (chế độ inline) → Claude tách
     thành `source.base64`, OpenAI nhận data-URI trực tiếp.
   - Model KHÔNG vision ⇒ giữ placeholder `[hình ảnh]` như cũ (không gửi ảnh → tránh lỗi API).
4. **Gating vision:** `config/ai.php` thêm khối `vision` — `enabled` (bool), `models`
   (danh sách substring model có vision), `max_images_per_message`, `inline_base64`
   (mặc định false = dùng link; true = nhúng base64 cho môi trường storage không ra Internet).
   Adapter bật ảnh khi `enabled` && model khớp `models`.
5. **Chỉ dẫn prompt:** `ReplyPersona::instructions` thêm quy tắc: nếu tin khách có hình ảnh,
   xem & phân tích nội dung ảnh (sản phẩm/mã đơn/ảnh lỗi…) rồi đối chiếu hội thoại + tài liệu.

## Lưu ý

- Link phải truy cập được từ máy chủ provider: prod (R2/S3 public) OK; dev `local` không ra
  Internet ⇒ đặt `AI_VISION_INLINE_BASE64=true` để nhúng base64 (giới hạn dung lượng).
- Chi phí/độ trễ vision cao hơn ⇒ giới hạn số ảnh/tin; chỉ áp dụng trong luồng AI reply.
- Khách CHỈ gửi ảnh không kèm chữ: truy hồi-từ-khóa không khớp gì (tài liệu vẫn nạp theo
  cơ chế text hiện tại); muốn so khớp sâu theo nội dung ảnh → phase "AI mô tả ảnh rồi truy hồi".

## Nghiệm thu

- Snapshot tin có ảnh → connector Claude/OpenAI gửi image block khi model vision; model
  thường giữ `[hình ảnh]`.
- `config('ai.vision.enabled')=false` ⇒ không gửi ảnh.
- pint/phpstan/test xanh; test connector kiểm tra payload có/không image block theo model.
