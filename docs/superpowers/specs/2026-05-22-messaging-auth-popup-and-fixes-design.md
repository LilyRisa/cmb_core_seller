# Thiết kế: Cấp quyền popup + loạt sửa lỗi Hộp thư Facebook

- Ngày: 2026-05-22
- Phạm vi: luồng cấp quyền (OAuth) toàn bộ kênh + UI/Backend phần tin nhắn Facebook
- Trạng thái: đã chốt qua brainstorming (popup cho **tất cả kênh**; gửi tin quá 24h → **người dùng chọn thẻ**)

## Bối cảnh

App Laravel (`app/app`) + React/Vite (`app/resources/js`, Ant Design v5, dayjs, TanStack Query).
Hộp thư hợp nhất ở `MessagingPage.tsx`; kết nối kênh ở `ChannelsPage.tsx` (marketplace) và
`MessagingChannelsPage.tsx` (Facebook). Connector Facebook: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php`.

Yêu cầu người dùng (7 hạng mục):
1. Nút "cấp quyền" mở **cửa sổ popup**, thao tác trong đó, callback xong **tự đóng** popup và app nhận kết nối.
2. Thread Facebook **hiển thị thời gian** từng tin.
3. Danh sách hội thoại **hiển thị giờ tin cuối**.
4. Icon comment & messenger **to, dễ nhìn**.
5. **Tô màu chip số điện thoại** xuất hiện trong nội dung tin.
6. Còn tin hiển thị **rỗng (không nội dung)** — sửa theo tài liệu Facebook.
7. **Gửi tin không hoạt động do thiếu thẻ** — bổ sung cơ chế thẻ.

## Quyết định đã chốt

- Popup áp dụng cho **mọi kênh** (Facebook + Shopee/TikTok/Lazada), dùng chung một cơ chế.
- Gửi tin ngoài cửa sổ 24h: **hiện bộ chọn thẻ cho người dùng** (không tự gắn ngầm), mặc định `HUMAN_AGENT`.

---

## 1. Cấp quyền bằng popup (tất cả kênh)

### Frontend
- Helper dùng chung `openOAuthPopup(authUrl): Promise<OAuthResult>` (file mới `resources/js/lib/oauthPopup.ts`):
  - `const w = window.open(authUrl, 'cmb_oauth', 'width=600,height=720,left=...,top=...')`.
  - Nếu `w == null` (popup bị chặn) → **fallback**: `window.location.href = authUrl` (giữ luồng cũ).
  - Lắng nghe `message`: chỉ chấp nhận `e.origin === window.location.origin && e.data?.source === 'cmb-oauth'`.
  - Khi nhận `{status, provider, message}` → đóng popup nếu còn, gỡ listener, resolve.
  - Poll `w.closed` mỗi ~500ms; nếu đóng mà chưa có message → resolve `{status: 'cancelled'}`.
- Sửa nơi gọi:
  - `useConnectChannel` (`lib/channels.tsx:64-73`): thay `window.location.href = auth_url` bằng `openOAuthPopup` rồi xử lý kết quả (toast + `resyncPoll.start` như `ChannelsPage` đang làm).
  - Facebook: `MessagingChannelsPage.tsx:55-58` + `useConnectFacebook` (`lib/messagingConfig.tsx:183-188`): tương tự, refetch danh sách kênh khi `connected`.
  - Phần `useEffect` đọc query param `?connected=/?error=` ở 2 trang **giữ lại** để phục vụ luồng fallback (popup bị chặn → redirect toàn trang).

### Backend
- Tạo blade view chung `resources/views/oauth-callback.blade.php` nhận biến `{status, provider, message, redirect}`:
  - Có `window.opener` → `window.opener.postMessage({source:'cmb-oauth', status, provider, message}, window.location.origin)` rồi `window.close()`.
  - Không có opener → `window.location.replace(redirect)` (fallback toàn trang).
  - Hiển thị dòng chữ "Đang hoàn tất kết nối…" để tránh màn hình trắng.
- `OAuthCallbackController::__invoke` (marketplace, `app/app/Modules/Channels/Http/Controllers/OAuthCallbackController.php`):
  thay vì `redirect($result['redirect'])` / `redirect('/channels?...')` → `return response()->view('oauth-callback', [...])`.
- `FacebookOAuthController::callback` (`app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php:57-125`):
  tương tự — mọi nhánh redirect (thành công + các lỗi) đổi sang trả view với `status`/`message`/`redirect` phù hợp.
- Logic trao đổi token, upsert `channel_account`, đăng ký webhook, dispatch backfill **giữ nguyên**.

### Bảo mật
- View `postMessage` đúng `window.location.origin` (callback và SPA cùng domain).
- Listener phía cha kiểm tra `origin` + `data.source`.
- Mở popup **không** dùng `noopener` (cần `window.opener` để postMessage). Đây là cùng-origin nên an toàn.

---

## 2. Thời gian từng tin nhắn (thread)

- `MessagingPage.tsx` vùng render bong bóng (`:693-745`): thêm dòng giờ nhỏ (fontSize ~10, opacity ~0.6) lấy `m.sent_at ?? m.created_at`.
- Định dạng qua helper `fmtMsgTime(iso)` dùng `dayjs`: cùng ngày → `HH:mm`; khác ngày → `DD/MM HH:mm`.
- Tin **outbound**: gộp giờ + nhãn trạng thái (`DELIVERY_STATUS_LABEL`) trên cùng một dòng căn phải (`:720-724`).
- Tin **inbound**: dòng giờ căn trái dưới bong bóng.

## 3. Giờ tin cuối ở danh sách hội thoại

- `MessagingPage.tsx` hàng tên (`:517-521`): thêm nhãn giờ gọn căn phải từ `c.last_message_at`.
- Helper `fmtListTime(iso)` dùng `dayjs`: <60 phút → "x phút"; hôm nay → `HH:mm`; hôm qua → "Hôm qua"; còn lại → `DD/MM`.
- Field `last_message_at` đã có sẵn ở type & resource — chỉ render.

## 4. Icon comment & messenger to hơn

- Badge góc avatar (`:506-507`): `fontSize: 10` → `15`; chỉnh `padding` (~2) và `offset` của `Badge` cho cân với avatar size 40.
- Giữ `CommentOutlined` (comment, xanh dương) và `MessageOutlined` (messenger, xanh lá) từ `@ant-design/icons` — không dùng emoji (theo quy ước UI).

## 5. Tô màu chip số điện thoại trong nội dung tin

- Thay `LinkifiedText` (`:36-58`) bằng renderer mở rộng nhận diện **cả URL lẫn số điện thoại VN** trong body:
  - Regex sđt VN (vd `0xxxxxxxxx`, `+84...`, có/không dấu cách-chấm-gạch). Tận dụng định dạng đang dùng ở backend `PhoneDetector` nếu hợp lý (giữ nhất quán).
  - URL → link như cũ; số điện thoại → `Tag` màu (xanh lá) + `PhoneOutlined`, click để copy (`navigator.clipboard`) + toast "Đã copy".
- Giữ và đồng bộ màu chip sđt sẵn có ở danh sách (`:551`) và header hội thoại (`:599`).
- *Ghi chú diễn giải*: yêu cầu được hiểu là làm nổi số điện thoại nằm **trong nội dung tin** ở thread.

## 6. Tin hiển thị rỗng — sửa theo tài liệu Facebook

### Backend (gốc rễ)
- `FacebookPageConnector::fetchMessages` (`:469-544`): bổ sung field **`shares`** vào Graph request:
  `messages.limit(N){id,message,created_time,from,sticker,shares{link,name,description},attachments{...}}`.
- Khi `message` rỗng:
  - Có `shares` → `body` = `name`/`description` + `link` (linkify được ở FE), `kind = Text`.
  - Giữ logic sticker/share/fallback hiện có.
- Webhook inbound (`:309-387`): xử lý tương tự cho payload `shares`/attachment chưa nhận diện để không tạo tin rỗng.

### Frontend
- `KIND_LABEL` (`:79-86`): đổi placeholder rõ ràng, kèm icon Ant:
  - `image` → "Hình ảnh", `video` → "Video", `file` → "Tệp đính kèm", sticker → "Sticker", share → "Đã chia sẻ liên kết".
  - Chỉ khi thật sự không có gì để hiển thị mới ghi "Tin nhắn không hỗ trợ hiển thị" (thay cho "Tin không có nội dung").

## 7. Gửi tin lỗi "thiếu thẻ" — người dùng chọn thẻ

### Backend
- `FacebookPageConnector::outboundWindow()` (`:716-723`): thêm `HUMAN_AGENT` vào `allowedTags`
  → `['HUMAN_AGENT','CONFIRMED_EVENT_UPDATE','POST_PURCHASE_UPDATE','ACCOUNT_UPDATE']`.
- `MessageIngestionService::updateConversationOnNewMessage` (`:182-216`): dùng `$message->sent_at ?? $message->created_at`
  cho `last_message_at`, `last_inbound_at`, `last_outbound_at` (thay vì luôn dùng `created_at`)
  → cửa sổ 24h tính theo giờ buyer nhắn thật, để FE biết đúng khi nào cần thẻ.
- Map lỗi gửi rõ hơn ở `MessageController::sendText`/`send` (FB code 10/200, subcode 2018278, code 551, và lỗi khác như App chưa duyệt Human Agent) → message tiếng Việt dễ hiểu cho FE.

### Frontend
- Helper `isOutsideWindow(conv)`: `!conv.last_inbound_at || dayjs().diff(conv.last_inbound_at,'hour') >= 24`.
- Khi hội thoại Facebook (`provider==='facebook_page'`, `thread_type==='message'`) **ngoài 24h**:
  composer hiện **`Radio.Group`/`Segmented`** chọn thẻ (mặc định `HUMAN_AGENT`) + dòng chú thích ngắn ("Quá 24h — cần thẻ tin nhắn để gửi").
  Gửi kèm `message_tag` vào `useSendText`.
- Trong 24h: không hiện picker, gửi như cũ (RESPONSE).
- `useSendText`/`useSendMedia` (`lib/messaging.tsx`): cho phép truyền `message_tag` (kiểm tra đã hỗ trợ chưa, bổ sung nếu thiếu).

---

## Đơn vị/biên rõ ràng

- `oauthPopup.ts`: 1 hàm thuần, in/out rõ (`authUrl` → `OAuthResult`), không phụ thuộc UI; test được độc lập.
- `oauth-callback.blade.php`: thuần view, nhận payload chuẩn hoá từ controller.
- Helper format thời gian (`fmtMsgTime`, `fmtListTime`) và `isOutsideWindow`: hàm thuần, tách khỏi component.
- Renderer body tin (URL + sđt): tách thành component nhỏ thay cho `LinkifiedText`.

## Kiểm thử

- FE: `npm run typecheck`, `npm run lint`, `npm run build`.
- BE: `vendor/bin/phpstan`, `vendor/bin/pint --test`, `php artisan test` (đặc biệt `OutboundWindowGuardTest`; thêm test cho ingestion `last_inbound_at` dùng `sent_at`, và parse `shares` ở connector nếu khả thi).
- Hạn chế: luồng OAuth/gửi tin Facebook live cần credentials thật → chỉ kiểm tĩnh + unit test, không E2E.

## Ngoài phạm vi (YAGNI)

- Không thêm realtime (Reverb) — giữ polling hiện có.
- Không refactor các phần không liên quan tới 7 hạng mục.
- Không tự động hoá việc duyệt tính năng Human Agent của App Facebook (chỉ map lỗi rõ ràng).
