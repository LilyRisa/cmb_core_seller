# Thanh tìm kiếm hội thoại — Messaging (Facebook + Zalo OA)

- **Ngày:** 2026-07-01
- **Module:** Messaging
- **Trạng thái:** Design approved, chờ implementation plan

## 1. Mục tiêu

Thêm ô tìm kiếm ở đầu cột danh sách hội thoại (inbox), dùng chung cho cả Facebook và Zalo OA (cùng một màn `MessagingPage.tsx`, chỉ khác `provider`). Người dùng tìm được:

- **Tên người nhắn** — `conversations.buyer_name`
- **Số điện thoại trong hội thoại** — `conversations.detected_phone`
- **Nội dung tin nhắn** — toàn bộ lịch sử `messages.body`
- (kèm) **tin nhắn cuối** — `conversations.last_message_preview`

Kết quả:
- Lọc theo đúng board hiện tại (Facebook **hoặc** Zalo OA) — `q` được AND với filter `provider` và các bộ lọc khác đang bật.
- Sắp xếp **mới → cũ** theo `last_message_at desc` (đã là mặc định).
- Ô tìm có **nút X** để xoá; **tự động tìm** sau khi ngừng gõ (debounce); có **icon loading** trong danh sách khi đang tìm.
- Yêu cầu: **hiệu năng & tốc độ**.

## 2. Hiện trạng (đã có sẵn)

- Backend `GET /api/v1/messaging/conversations` (`ConversationController::index`) đã nhận `?q=`, hiện tìm `buyer_name` + `last_message_preview` bằng `LIKE '%..%'`, order `orderByDesc('last_message_at')`, phân trang `meta.pagination`.
- Frontend `useConversations` (`app/resources/js/lib/messaging.tsx`) là `useInfiniteQuery`; query function đã tự forward mọi filter (kể cả `q`) xuống request. Type `ConversationFilters` đã có `q?: string`.
- `Conversation::messages()` (`app/app/Modules/Messaging/Models/Conversation.php:97`) là `hasMany(Message::class)` — dùng được cho `orWhereHas`. `Message` có `BelongsToTenant` (tenant global scope tự áp).
- **Chưa có:** ô nhập UI, tìm theo `detected_phone`, tìm theo `messages.body`, snippet, loading spinner, tô đậm, và index tăng tốc.

## 3. Frontend

File: `app/resources/js/pages/MessagingPage.tsx` (+ helper trong cùng file hoặc `lib/messaging.tsx`).

- Thêm `<Input allowClear prefix={<SearchOutlined/>} placeholder="Tìm tên, SĐT, nội dung tin nhắn…" />` vào header cột trái (phía trên/cạnh nút "Bộ lọc"). `allowClear` cung cấp **nút X** sẵn.
- State `searchInput` (giá trị thô) → **debounce ~350ms** thành `debouncedQ`. Nhấn **Enter** tìm ngay (bỏ chờ debounce).
- Truyền `q: debouncedQ || undefined` vào object filter tại lời gọi `useConversations({...})` (khoảng dòng 337). Không cần sửa tầng data — query function + type đã hỗ trợ.
- **Loading trong danh sách:** bật `loading` của `<List>` (hoặc `<Spin>` mảnh ở đầu list) khi `list.isFetching` do `q` đổi. Ưu tiên không che toàn bộ list khi đang có dữ liệu (dùng spinner nhỏ ở đầu để giữ UX mượt).
- **Xoá từ khoá:** `searchInput=''` → `debouncedQ=''` → `q=undefined` → query key đổi → refetch trang 1, infinite scroll reset về danh sách thường.
- **Tô đậm + snippet:**
  - Helper `highlight(text, q)` bọc đoạn khớp (không phân biệt hoa/thường) bằng `<mark>` / `<Text strong>`.
  - Áp cho: tên người nhắn, `last_message_preview`.
  - Khi khớp trong tin nhắn cũ, render thêm **1 dòng phụ** hiển thị `match_snippet` (trường mới từ API) đã tô đậm, để người dùng thấy vì sao hội thoại xuất hiện.

## 4. Backend — `ConversationController::index`

File: `app/app/Modules/Messaging/Http/Controllers/ConversationController.php`.

Mở rộng khối `q` hiện tại (giữ nguyên cấu trúc, chỉ thêm nhánh):

```php
if ($search = trim((string) $request->query('q', ''))) {
    $q->where(function ($qq) use ($search) {
        $qq->where('buyer_name', 'like', "%{$search}%")
           ->orWhere('last_message_preview', 'like', "%{$search}%")
           ->orWhere('detected_phone', 'like', "%{$search}%");

        // Nội dung tin nhắn (toàn bộ lịch sử) — chỉ khi >= 2 ký tự để chặn quét nặng
        if (mb_strlen($search) >= 2) {
            $qq->orWhereHas('messages', function ($m) use ($search) {
                $m->where('body', 'like', "%{$search}%");
            });
        }
    });
}
```

- Dùng `orWhereHas('messages', …)` để giữ tenant global scope tự động (không dùng `DB::table`).
- Giữ nguyên `orderByDesc('last_message_at')` (mới → cũ) và phân trang hiện có.
- `q` được AND với các filter khác (provider, status, blocked, …) qua cấu trúc `where(function…)` lồng — nên tìm kiếm luôn nằm trong board hiện tại.

### 4.1 Snippet (`match_snippet`)

- Sau khi lấy trang (per_page ≤ 20), **nếu có `q` và `mb_strlen($search) >= 2`**: chạy **1 truy vấn phụ** lấy tin nhắn khớp **mới nhất** cho tập `conversation_id` của trang:
  - `Message::whereIn('conversation_id', $ids)->where('body','like',"%{$search}%")->orderByDesc('created_at')` rồi gom lấy bản mới nhất mỗi `conversation_id` trong PHP (hoặc dùng subquery). Bounded ≤ 20 → không N+1.
- Cắt snippet ~120 ký tự quanh vị trí khớp trong PHP (thêm `…` hai đầu nếu bị cắt).
- Gắn vào `ConversationResource` dưới key `match_snippet` (nullable) — **chỉ xuất hiện khi đang tìm và có khớp trong body**. Khi không tìm hoặc khớp ở tên/preview/phone → `match_snippet = null`.
- `ConversationResource` nhận snippet qua một thuộc tính tạm được set trên model (ví dụ `$conversation->setAttribute('match_snippet', …)` trước khi trả resource), hoặc truyền qua map. Chốt cách cụ thể ở bước plan.

## 5. Migration — hiệu năng (Postgres prod)

Migration mới trong `app/app/Modules/Messaging/Database/Migrations/`, guard theo driver, `public $withinTransaction = false;` (vì `CREATE INDEX CONCURRENTLY` không chạy trong transaction):

```php
if (DB::getDriverName() === 'pgsql') {
    DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_messages_body_trgm
                   ON messages USING gin (body gin_trgm_ops)');
    DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_conversations_buyer_name_trgm
                   ON conversations USING gin (buyer_name gin_trgm_ops)');
    DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_conversations_preview_trgm
                   ON conversations USING gin (last_message_preview gin_trgm_ops)');
    DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_conversations_phone_trgm
                   ON conversations USING gin (detected_phone gin_trgm_ops)');
}
```

- Trigram GIN cho phép `LIKE '%..%'` chạy nhanh trên Postgres. `down()` `DROP INDEX ... IF EXISTS` tương ứng.
- Dev SQLite: khối này bỏ qua → fallback `LIKE` thường (dữ liệu nhỏ, chấp nhận được).
- ⚠️ **Verify khi deploy:** nếu bảng `messages` đã bật partition theo tháng, `CREATE INDEX CONCURRENTLY` **không** chạy trên bảng cha partitioned. Khi đó tạo index theo từng partition (hoặc trên bảng cha, chịu lock ngắn khi bảo trì). Prod đặt `RUN_MIGRATIONS=false` nên migration này chạy tay — kiểm tra trạng thái partition trước khi chạy. Ghi rõ trong plan/PR.

## 6. Kiểm thử

- **Feature test** (`tests/Feature/Messaging/…`, mở rộng `ConversationControllerTest` nếu có):
  - Tìm theo `buyer_name` → trả đúng hội thoại.
  - Tìm theo `detected_phone` → trả đúng.
  - Tìm theo `messages.body` (tạo tin cũ chứa từ khoá, tin cuối không chứa) → hội thoại xuất hiện và `match_snippet` có giá trị chứa từ khoá.
  - `q` 1 ký tự → **không** kích hoạt nhánh `orWhereHas('messages')` (chỉ tên/phone/preview).
  - Thứ tự trả về `last_message_at desc`.
  - `q` được AND với `provider` (kết quả không lẫn provider khác).
- **Frontend:** không có test runner JS trong dự án → verify tay: gõ tìm (debounce), nút X xoá, spinner khi tìm, tô đậm tên/preview/snippet, phân trang khi đang lọc.

## 7. Edge cases

- `q` rỗng / chỉ khoảng trắng → `trim()` → không áp filter (giữ hành vi cũ).
- Tìm không phân biệt hoa/thường: SQLite `LIKE` mặc định case-insensitive ASCII; Postgres `LIKE` phân biệt hoa/thường — cân nhắc `ILIKE` (hoặc trigram vẫn cần `ILIKE` để không phân biệt hoa/thường). **Chốt dùng `ILIKE` trên pgsql / `LIKE` trên sqlite** (hoặc helper theo driver) để hành vi nhất quán. Ghi rõ ở plan.
- Xoá từ khoá phải reset infinite scroll (đảm bảo query key gồm `q`).
- SĐT người dùng gõ có thể có khoảng trắng/dấu — phạm vi hiện tại chỉ `LIKE` thẳng trên `detected_phone`; chuẩn hoá số (bỏ ký tự không phải chữ số) là cải tiến sau, không nằm trong spec này.

## 8. Ngoài phạm vi (YAGNI)

- Full-text search có xếp hạng (ts_rank), gợi ý/autocomplete, lịch sử tìm kiếm.
- Chuẩn hoá & khớp SĐT theo định dạng.
- Nhảy tới đúng tin nhắn khớp trong khung chat (chỉ hiển thị snippet trong list, chưa scroll-to-message).
