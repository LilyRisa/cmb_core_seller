# Chiến lược kiểm thử

**Status:** Stable · **Cập nhật:** 2026-05-11

> Mục tiêu: thay đổi an toàn, mở rộng không vỡ. Trọng tâm là **feature test luồng nghiệp vụ** + **contract test cho connector** — đây là nơi rủi ro cao nhất (đồng bộ đơn, tồn kho, in ấn, tích hợp sàn).

## 1. Các loại test (kim tự tháp, nghiêng về feature test cho domain logic)

| Loại | Công cụ | Dùng cho | Chạy |
|---|---|---|---|
| **Unit** | Pest | Logic thuần: state machine chuyển trạng thái, tính `available`, tính tồn combo, tính lợi nhuận, mapping status/fee, helpers (money/address VN) | nhanh, mỗi push |
| **Feature** | Pest + DB testing (SQLite/Postgres test) | Luồng end-to-end phía backend: webhook → upsert đơn → tác động tồn → push tồn; tạo đơn tay; tạo vận đơn → in label; ghép SKU; phân quyền/tenant isolation; API endpoint trả đúng envelope | mỗi push |
| **Contract** (★) | Pest + **fixtures** (response mẫu lưu trong repo) | Mỗi `ChannelConnector`/`CarrierConnector`: cho ăn payload mẫu của sàn → khẳng định trả đúng DTO chuẩn; verify chữ ký webhook đúng/sai; map status/fee đúng | mỗi push, job CI riêng |
| **Frontend** | Vitest + React Testing Library | Hook/logic FE quan trọng (filter→query, xử lý lỗi envelope, form validate); một số component then chốt | mỗi push |
| **E2E (tuỳ chọn, Phase sau)** | Playwright | Vài luồng quan trọng nhất qua UI (đăng nhập → xem đơn → đổi trạng thái; tạo đơn tay; in label) | nightly / trước release |
| **Load (tuỳ chọn, trước ra mắt)** | k6/Artillery | Webhook burst, polling N shop, sinh bulk label — kiểm chịu tải ~17k đơn/ngày + đỉnh | thủ công theo mốc |

## 2. Quy tắc (RULES)
1. **Logic mới ⇒ có test.** Bug fix ⇒ có test tái hiện bug trước khi sửa.
2. **Connector mới ⇒ có contract test** với fixtures (xem `01-architecture/extensibility-rules.md` §5/§6). Không gọi mạng thật trong test — luôn mock HTTP (Laravel `Http::fake()` / fixtures).
3. **Test tính idempotent**: chạy lại `ProcessWebhookEvent`/`OrderUpsertService`/`PushStockForSku` 2 lần ⇒ kết quả như 1 lần (không tạo dòng thừa, không trừ tồn 2 lần).
4. **Test tenant isolation**: dữ liệu tenant A không bao giờ lộ cho user của tenant B (kiểm ở mức query/policy/endpoint).
5. **Test state machine**: mọi cạnh hợp lệ pass; cạnh không hợp lệ bị từ chối; dữ liệu sàn "lùi trạng thái" được ghi nhận + bật `has_issue`.
6. **Test tồn kho**: reserve/release/ship đúng theo bảng ở `inventory-and-sku-mapping.md` §3; combo tác động đủ thành phần; oversell ⇒ cảnh báo + đẩy 0 lên sàn; mỗi thay đổi có dòng `inventory_movements` với `balance_after` đúng.
7. **Ngưỡng coverage tối thiểu** trong CI (đặt khởi điểm ở `ci-cd-pipeline.md`, tăng dần) — **không hạ ngưỡng để cho qua**.
8. **Test phải nhanh & ổn định** (không flaky). Test phụ thuộc thời gian ⇒ dùng `Carbon::setTestNow()`. Test queue ⇒ `Queue::fake()` hoặc chạy sync có kiểm soát.
9. **Fixtures sàn** lưu ở `tests/Fixtures/Channels/<provider>/...` (lấy từ docs/SDK, đã loại bỏ dữ liệu nhạy cảm). Cập nhật khi API sàn đổi.

## 3. Dữ liệu test
- Factory cho mọi model nghiệp vụ. Seeder "demo tenant" để dev (`migrate --seed`).
- Test DB riêng (không đụng dev DB). Mỗi test tự dọn (transaction rollback / refresh DB).

## 4. Theo phase — trọng tâm test
- **Phase 1**: contract test TikTokConnector (orders/webhook/status map); feature test webhook→upsert→history; polling→upsert; refresh token; tenant isolation; API orders.
- **Phase 2**: feature test tạo đơn tay→reserve; ghép SKU & auto-match; PushStockForSku/ToListing (debounce, lock, combo); sổ cái movements.
- **Phase 3**: feature test arrangeShipment→getShippingDocument→lưu file; bulk label ghép PDF (mock Gotenberg); scan-to-pack→ship→trừ tồn; contract test GHN/GHTK/J&T CarrierConnector.
- **Phase 4+**: contract test Shopee/Lazada; feature test 3 sàn chạy chung luồng; (Phase 6) tính lợi nhuận & đối chiếu settlement.

## 5. CI
Pipeline (xem `07-infra/ci-cd-pipeline.md`) chạy: Pint, PHPStan, migrate, Pest (unit+feature+contract, có coverage), ESLint, tsc, build FE, (FE test nếu có). Tất cả phải xanh để merge.
