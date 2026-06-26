# SPEC: Async hóa "Chuẩn bị hàng" + chống orphan php-fpm + scale queue

- **Trạng thái:** Draft
- **Phase:** vận hành / fulfillment hardening
- **Module backend liên quan:** Fulfillment (chính), Orders (status), Channels (connector arrange/document)
- **Tác giả / Ngày:** điều tra prod + thiết kế · 2026-06-26
- **Liên quan:** SPEC 0013/0014 (chuẩn bị hàng, label/AWB sàn), `07-infra/queues-and-scheduler.md`, memory `fulfillment-prepare-sync-blocking-bottleneck`

## 1. Vấn đề & mục tiêu

"Chuẩn bị hàng" hiện **chạy đồng bộ trong request HTTP** (php-fpm): gọi API sàn `arrangeOnChannel()` + `fetchAndStoreChannelLabel()` (Lazada còn `usleep` ~4.5s/đơn). `bulkCreate` lặp tuần tự nhiều đơn trong một request. Điều tra prod (2026-06-26, SSH 2-hop) xác nhận:

- Queue nền **KHÔNG nghẽn** (queue `labels` rỗng, job nền xong trong mili-giây). Vấn đề nằm ở đường đồng bộ.
- Trần timeout end-to-end ≈ **120s** (NPM `location /` `proxy_read_timeout 120s` + web nginx `fastcgi_read_timeout 120s`).
- php-fpm `request_terminate_timeout = 0`, `max_execution_time = 0` ⇒ request >120s bị nginx/NPM trả **504** cho client NHƯNG PHP **chạy tiếp orphan** ⇒ đơn vẫn bị flip status server-side dù client báo lỗi ("status đổi nhưng chưa in tem"); worker bị giữ tới khi xong ⇒ burst làm cạn 64 php-fpm child ⇒ cả site chậm/bất ổn.
- FE đã chia lô **25 đơn/request** nhưng 25 đơn Lazada × ~5-8s vẫn vượt 120s.

**Mục tiêu:** request "chuẩn bị hàng" luôn <1s; bỏ mọi gọi sàn/`usleep` khỏi php-fpm; hết 504 & orphan; throughput xử lý nền scale được; giữ trải nghiệm lỗi tức thì cho các lỗi rẻ (validate).

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Async hóa **cả single** (`POST /orders/{id}/ship`) **và bulk** (`POST /shipments/bulk-create`) qua job `PrepareShipment`.
  - **Hybrid:** validate rẻ đồng bộ (trả lỗi ngay), phần nặng (arrange + tem) chạy job nền.
  - `request_terminate_timeout` ở php-fpm (chống orphan) — fix #2.
  - Bỏ `usleep` đồng bộ khỏi path request — fix #3 (gộp vào async; usleep còn lại nằm trong job là chấp nhận được).
  - Queue riêng `fulfillment` + `supervisor-fulfillment`, scale worker — fix #4.
  - Cập nhật FE: `useShipOrder`, `useBulkCreateShipments`/`useBulkAction` theo contract mới + optimistic + polling (đã có sẵn).
- **Ngoài (làm sau / spec khác):**
  - Async hóa `markPacked`/`pushReadyToShipOnChannel` (cũng gọi sàn đồng bộ, dòng ~754) — không thuộc "chuẩn bị hàng".
  - Thêm cột/migration cho marker "đang chuẩn bị" (cố tình tránh — dùng optimistic FE).

## 3. Luồng chính

1. User bấm "Chuẩn bị hàng" (1 đơn hoặc nhiều đơn đã chọn).
2. Controller **validate rẻ, đồng bộ** từng đơn (DB only, không gọi sàn).
3. Đơn lỗi validate ⇒ trả về client NGAY (single: 422; bulk: trong `errors[]`). Đơn đã có vận đơn open ⇒ `already_prepared` (idempotent, không lỗi).
4. Đơn hợp lệ ⇒ `PrepareShipment::dispatch(orderId, ...)` lên queue `fulfillment`; controller trả ngay (single: **202** `{queued:true, order_id}`; bulk: `{queued:[ids], already_prepared:[ids], errors:[...]}`).
5. FE set optimistic "đang chuẩn bị…" cho các order id queued + bật polling 90s (đã có) → cập nhật shipment/`slip_state`.
6. Job nền chạy phần nặng (= phần còn lại của `createForOrder` hiện tại): arrange sàn → tạo shipment → fetch tem → flip `Pending→Processing`. Lỗi tạm → job retry (backoff). Lỗi vĩnh viễn → `has_issue`.

## 4. Hành vi & quy tắc nghiệp vụ

- **Tách `createForOrder`:**
  - `assertPreparable(Order): void` (throw `RuntimeException` message VN) — gom check rẻ HIỆN CÓ, KHÔNG gọi sàn: trạng thái terminal/returning; hết hàng âm tồn (`isOutOfStock`); đơn sàn raw_status chưa cho fulfill (`assertChannelOrderFulfillable`). Gọi từ controller (feedback sớm) VÀ đầu `createForOrder` (DRY, bắt lại trong job).
  - Vận đơn `open()` đã tồn tại ⇒ trả về (idempotent), không phải lỗi.
  - Phần còn lại của `createForOrder` giữ nguyên logic, chỉ chạy trong job.
- **Status:** flip `Pending→Processing` GIỮ NGUYÊN vị trí (bên trong job, qua `OrderStatusSync::apply`). Trước khi job chạy đơn vẫn Pending — FE che bằng optimistic.
- **Idempotency (3 lớp):** `ShouldBeUnique` theo `orderId` (TTL 60s, dedup lúc enqueue) + `WithoutOverlapping($orderId)` (không chạy song song cùng đơn) + `Cache::lock("prepare:{orderId}")` quanh khối tạo shipment + check `open()` sẵn có. arrange sàn vốn idempotent.
- **Rate-limit sàn:** queue riêng `fulfillment` số worker giới hạn + `WithoutOverlapping` theo channel account (serial cùng shop) + backoff retry.
- **Phân quyền:** giữ nguyên `fulfillment.ship` ở controller (validate quyền TRƯỚC khi dispatch).

## 5. Dữ liệu

- **Không bảng/cột mới, không migration.**
- Domain event: giữ `ShipmentCreated` (job phát sau khi tạo shipment); listener hiện có không đổi.
- Job mới đọc Order/Shipment qua id (tuần theo `BelongsToTenant`; job phải set tenant context như các job Fulfillment hiện có — kiểm tra mẫu `FetchChannelLabel`).

## 6. API & UI

- **`POST /orders/{id}/ship`** (đổi): trả **202** `{data:{queued:true, order_id}}` (thay vì 201 + Shipment). Lỗi validate → 422 (envelope chuẩn).
- **`POST /shipments/bulk-create`** (đổi response): `{data:{queued:[int], already_prepared:[int], errors:[{order_id,message}]}}` (thay vì `{created:[Shipment], errors}`).
- Cập nhật `05-api/endpoints.md`.
- **Job mới `PrepareShipment`** trên queue `fulfillment`, tries=5, backoff [10,30,60,120,300]s, `WithoutOverlapping`+`ShouldBeUnique`. Cập nhật `07-infra/queues-and-scheduler.md`.
- **Horizon** (`config/horizon.php`, MỌI env): thêm `supervisor-fulfillment` (prod ~6 process, balance auto, timeout 120s). `supervisor-labels` prod 4→6.
- **php-fpm** (`app/docker/entrypoint.sh`, khối sinh `zz-pool.conf`): thêm `request_terminate_timeout = 115s` (dưới trần nginx/NPM 120s).
- **FE:**
  - `useShipOrder`: nhận 202 → optimistic "đang chuẩn bị…" (local set order id) + `syncPoll.start()` → polling cập nhật rồi clear.
  - `useBulkCreateShipments`/`useBulkAction`/progress modal: map `queued[]`→trạng thái "đang xử lý" (xanh dương, không phải "ok"), `already_prepared[]`→"skipped", `errors[]`→"error"; rồi `syncPoll.start()`.
  - **Polling phải cập nhật & render ĐẦY ĐỦ (yêu cầu rõ):** mỗi vòng poll phải invalidate/refetch TẤT CẢ query ảnh hưởng để UI khớp ngay khi job nền xong, KHÔNG chỉ list đơn:
    - **Số lượng đơn trong các chip trạng thái** (badge counts): query đếm theo trạng thái đơn + theo stage (`/fulfillment/processing/counts`: prepare/pack/handover) + đếm sub-tab phiếu ("Có thể in"/"Đang tải lại"/"Nhận phiếu"). Đơn chuyển Pending→Processing và shipment xuất hiện ⇒ các con số này phải đổi đúng.
    - **Tình trạng in (`slip_state`)**: printable/loading/failed phải re-render đúng trên từng dòng đơn (spinner "Đang lấy phiếu…" → nút in được / nút "Nhận phiếu" khi fail) khi job kéo tem xong hoặc thất bại.
    - Rà soát `useSyncPolling`/`syncPoll`: đảm bảo danh sách `queryKey` được invalidate gồm cả counts/stats/tab-stats/processing-counts, không sót, tránh chip lệch số sau khi async hoàn tất.
  - Không thêm timeout axios; reload giữa chừng mất optimistic ⇒ re-click an toàn (idempotent).
- **Connector:** không thêm logic theo tên sàn ở core; job dùng đúng method `ChannelConnector`/`CarrierConnector` như hiện tại.

## 7. Edge case & lỗi

- **Double-click / job retry / 2 job song song cùng đơn:** 3 lớp idempotency (mục 4) ⇒ không tạo trùng vận đơn.
- **Job fail vĩnh viễn** (sàn từ chối lạ, lỗi kéo dài hết tries): `failed()` → `has_issue=true`+`issue_reason`+log+Sentry ⇒ FE hiện cờ + nút "Nhận phiếu giao hàng" (đã có).
- **Burst dispatch (tới 500 đơn):** queue riêng + worker giới hạn + serial theo account ⇒ không spam rate-limit sàn; backlog xử lý dần, không ảnh hưởng web/fpm.
- **Tồn kho/raw_status đổi giữa validate và job:** job chạy `assertPreparable` lại ⇒ bắt lại; set `has_issue` nếu hết hàng.
- **`request_terminate_timeout=115s`:** chỉ giết request vốn ĐÃ bị nginx/NPM 504 (>120s) ⇒ không cắt request hợp lệ. **Phải verify** không có endpoint export/report nào dựa vào >115s (nếu có, nó vốn đã 504 — xử lý riêng).
- **Gotcha Horizon (memory):** queue `fulfillment` BẮT BUỘC có supervisor xử lý, nếu không job kẹt im lặng ⇒ verify `horizon:list`/log sau deploy.
- **Deploy ordering:** worker (có supervisor mới) phải lên cùng/đẩy trước web; Horizon config cached ⇒ `horizon:terminate` để nạp lại (chạy ở worker-1 theo memory).
- **In-flight lúc deploy:** request sync cũ đang chạy vẫn xong; code mới dispatch job cần queue/supervisor đã sẵn.

## 8. Bảo mật & dữ liệu cá nhân

Không thay đổi xử lý PII. Job thao tác trên đơn/vận đơn theo tenant scope như hiện tại. Quyền `fulfillment.ship` kiểm ở controller trước dispatch.

## 9. Kiểm thử

- **Feature (Queue::fake):** controller dispatch đúng `PrepareShipment` cho đơn hợp lệ; trả lỗi đồng bộ cho đơn fail validate (hết hàng / trạng thái cuối / sàn chưa cho fulfill); đơn có vận đơn open → `already_prepared`, không dispatch.
- **Unit/job:** `PrepareShipment` gọi service tạo shipment + flip status; idempotency (gọi 2 lần không tạo trùng — lock/unique); `failed()` set `has_issue`.
- **FE:** map `queued/already_prepared/errors` trong progress modal; optimistic state cho single; (không có JS test runner — kiểm thủ công theo memory `test-verify-baseline`).
- Theo memory: BE chưa green toàn cục ⇒ chỉ chạy test liên quan Fulfillment.

## 10. Tiêu chí hoàn thành

- [ ] `assertPreparable` tách ra, dùng chung controller + service.
- [ ] `PrepareShipment` job (queue `fulfillment`, idempotent 3 lớp, retry, `failed()`).
- [ ] Controller single trả 202; bulk trả `{queued, already_prepared, errors}`.
- [ ] `entrypoint.sh` thêm `request_terminate_timeout=115s`.
- [ ] `config/horizon.php` thêm `supervisor-fulfillment` + bump labels.
- [ ] FE: `useShipOrder` + `useBulkCreateShipments`/`useBulkAction`/progress modal theo contract mới + optimistic + polling.
- [ ] FE: polling cập nhật & render đầy đủ số lượng chip trạng thái (status + stage + sub-tab phiếu) và `slip_state` (tình trạng in) — không sót queryKey, chip không lệch số.
- [ ] Test Fulfillment liên quan pass; `pint --test`, `phpstan`, `npm run lint/typecheck/build` xanh.
- [ ] Tài liệu cập nhật: `05-api/endpoints.md`, `07-infra/queues-and-scheduler.md`.
- [ ] Verify prod: dispatch 1 đơn thật, job chạy trên queue `fulfillment`, request <1s, không 504.

## 11. Câu hỏi mở

- Số worker prod cho `supervisor-fulfillment` (đề xuất 6) — tinh chỉnh theo lượng đơn thực tế sau khi quan sát.
- Có cần ô "đang chuẩn bị nền" hiển thị tổng (badge) trên board không, hay optimistic + polling đã đủ (đề xuất: đủ, YAGNI).
