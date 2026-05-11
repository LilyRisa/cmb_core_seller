# Roadmap

**Status:** Living document · **Cập nhật:** 2026-05-11

> Quy tắc: mỗi phase chỉ "Done" khi đạt **Exit criteria** của nó. Không nhảy phase. Việc nào không thuộc phase hiện tại → ghi vào backlog của phase sau, **không làm xen**. Ước lượng tính cho team 2–4 dev.

## Tổng quan các phase

| Phase | Tên | Mục tiêu | Ước lượng |
|---|---|---|---|
| 0 | Nền tảng | Skeleton, hạ tầng, multi-tenant, khung connector | 2–3 tuần |
| 1 | TikTok — Đồng bộ đơn | Kết nối shop TikTok, đơn tự về, trạng thái chuẩn | 3–5 tuần |
| 2 | Đơn thủ công + SKU + Tồn kho lõi | Master SKU, ghép SKU, trừ/đẩy tồn | 3–4 tuần |
| 3 | Giao hàng & in ấn (TikTok) | Vận đơn, in hàng loạt, picking/packing, scan-to-pack, ĐVVC đợt 1 | 4–6 tuần |
| 4 | Shopee + Lazada | Slot 2 sàn vào sau khi có API | 6–10 tuần |
| 5 | WMS đầy đủ + Đăng bán đa sàn | Nhập/xuất/chuyển kho, kiểm kê, giá vốn FIFO, mass listing, ĐVVC đợt 2 | 8–12 tuần |
| 6 | Tài chính + Mua hàng + Báo cáo + Billing | Settlement, lợi nhuận, PO/NCC, báo cáo, gói thuê bao | 8–12 tuần |
| 7+ | Hậu mãi & nâng cao | Trả hàng/hoàn, chat hợp nhất, HĐĐT, tối ưu hiệu năng, PWA quét hàng | liên tục |

**Mốc lớn:** dùng được nội bộ ~hết Phase 3 (**~3–4 tháng**) · ra mắt 3 sàn ~hết Phase 5 (**~8–12 tháng**) · tiệm cận full BigSeller ~Phase 7 (**~18–24 tháng**).

---

## Phase 0 — Nền tảng  ◑ (code gần xong; còn việc ngoài-code)
**Việc:** mono repo Laravel 11 + React (Vite) embedded · routing `/api/v1/*` + `/webhook/*` + `/oauth/{provider}/callback` + catch-all → SPA · Docker Compose (app, worker, postgres, redis, minio, gotenberg, horizon, mailhog) · Sanctum SPA auth · multi-tenant + RBAC + sub-account khung · khung module + `ChannelRegistry`/`CarrierRegistry` + interface `ChannelConnector`/`CarrierConnector` + DTO chuẩn · migration nền + cơ chế partition theo tháng · Horizon supervisors · Sentry + logging · SPA shell (layout AntD, router, auth flow, trang Dashboard/Gian hàng rỗng) · CI lint+test · **(song song, không code) nộp hồ sơ Shopee + Lazada Open Platform, đăng ký app TikTok Shop Partner**.
**Exit criteria:** đăng ký/đăng nhập được, tạo tenant + mời thành viên, SPA chạy, queue chạy, CI xanh, Docker `up` là chạy được toàn bộ. Hồ sơ 2 sàn đã nộp.

**Trạng thái (2026-05-11):**
- ✅ Mono repo + routing 4 nhóm (`/api/v1`, `/webhook`, `/oauth/{provider}/callback`, catch-all → SPA).
- ✅ Sanctum SPA auth · multi-tenant + global scope + `BelongsToTenant` · RBAC (Role enum + permission map + `Gate::before`) · sub-account scope cột sẵn · audit log.
- ✅ Khung 11 module (service provider mỗi module) · `ChannelRegistry`/`CarrierRegistry` + interface `ChannelConnector`/`CarrierConnector` + DTO chuẩn + `ManualConnector`.
- ✅ Horizon supervisors (config theo `07-infra/queues-and-scheduler.md`) · scheduler skeleton (`routes/console.php`) · `db:partitions:ensure` + helper `MonthlyPartition`/`PartitionRegistry`.
- ✅ Sentry (web + queue) · log JSON ra stdout + middleware `request_id`/`trace_id` (vào log context, Sentry tag, header `X-Request-Id`, error envelope) · `GET /api/v1/health` (DB/cache/Redis/queue).
- ✅ SPA shell (layout AntD, router, auth flow, Dashboard, Gian hàng, **Cài đặt → Thành viên & phân quyền** với mời/đổi vai trò).
- ✅ CI GitHub Actions: backend (Pint · PHPStan/Larastan level 5 + baseline · migrate · PHPUnit `--coverage --min=60`, hiện ~72%) + frontend (ESLint · typecheck · build); workflow CD staging tối thiểu (`deploy-staging.yml`, manual). Docker Compose stack (`docker compose up`). Script backup/restore Postgres + MinIO (`scripts/backup.sh`, `scripts/restore.sh`) + runbook trong `07-infra/observability-and-backup.md`.
- ✅ ADR-0001…0010 (đã có file đầy đủ trong `01-architecture/adr/`): SPA-in-Laravel, Postgres+partition, modular monolith, connector/registry, VN-only, AntD 5, webhook+polling, master SKU, PK strategy, RBAC tự viết.
- ⏳ **Việc ngoài-code (không thuộc repo):** nộp hồ sơ Shopee + Lazada Open Platform, đăng ký app TikTok Shop Partner · bật branch protection cho `main` (require CI + 1 review) · cấu hình `SENTRY_LARAVEL_DSN` + alert rules + giám sát queue-depth ở môi trường thật · dựng host staging + secrets cho `deploy-staging.yml` · bật WAL/PITR Postgres + versioning/replicate MinIO + đẩy backup off-site + test khôi phục định kỳ.
- ⏳ **Còn lại trong code (nhỏ, không chặn Exit criteria):** contract-test job (thêm khi có connector sàn đầu tiên — Phase 1) · Prettier config FE · trang `/forgot-password` + `/onboarding` (sitemap FE) · backfill seeder/factory cho các bảng module khi schema ra đời.

## Phase 1 — TikTok Shop: Đồng bộ đơn  ☐
**Việc:** OAuth connect/disconnect gian hàng TikTok · HTTP client PHP cho TikTok (auth + ký HMAC, refresh token, orders, order detail) versioned · webhook receiver `/webhook/tiktok` + verify chữ ký + `ProcessWebhookEvent` job · polling `SyncOrdersForShop` (5–15') + backfill 90 ngày · trạng thái chuẩn + mapping TikTok + `order_status_history` · màn Đơn hàng (list/filter/detail/đổi trạng thái/tag/note) + Dashboard cơ bản · auto refresh token + cảnh báo re-connect.
**Exit criteria:** kết nối shop TikTok thật → đơn mới tự xuất hiện trong vòng vài phút (qua webhook hoặc polling), xem/lọc/đổi trạng thái được; mất webhook vẫn không mất đơn nhờ polling.

## Phase 2 — Đơn thủ công + SKU + Tồn kho lõi  ☐
**Việc:** sản phẩm/SKU master · kho (warehouses) + `inventory_levels` + `inventory_movements` (sổ cái) · tạo đơn thủ công (reserve tồn) · màn Liên kết SKU (manual + auto-match `seller_sku == sku_code`, hỗ trợ combo 1→N) cho listing TikTok · `PushStockToChannel` (debounce + distributed lock + safety stock) đẩy tồn lên TikTok · cảnh báo hết hàng/âm kho.
**Exit criteria:** bán TikTok + tạo đơn tay đều trừ chung 1 kho; thay đổi tồn → tự đẩy lên listing TikTok liên kết; mọi thay đổi tồn có dòng trong sổ cái.

## Phase 3 — Giao hàng & in ấn (TikTok)  ☐
**Việc:** luồng "sắp xếp vận chuyển" TikTok → lấy tracking + label PDF → lưu MinIO · **in vận đơn hàng loạt** (ghép PDF, sắp theo ĐVVC) · **picking/packing list** render bằng Gotenberg + **template tùy biến** · **quét mã đóng gói** → xác nhận đóng/bàn giao → trừ tồn → trạng thái shipped · kết nối ĐVVC riêng đợt 1: **GHN + GHTK + J&T** (quote/createShipment/getLabel/track/cancel) cho đơn manual & đơn tự xử lý · lô lấy hàng (pickup batch).
**Exit criteria:** từ list đơn → tạo vận đơn hàng loạt → in tem 1 file → quét từng kiện để xác nhận đóng gói → bàn giao ĐVVC → trạng thái & tồn cập nhật đúng.

## Phase 4 — Shopee + Lazada  ☐  *(bắt đầu sau khi có API)*
**Việc:** connector + OAuth + client + status map + sync đơn (push+pull) + listing + ghép SKU + push tồn + luồng in/label cho **Shopee** và **Lazada** · xử lý khác biệt (đơn nhiều kiện, COD, cấu trúc phí, document API).
**Exit criteria:** 3 sàn + đơn tay chạy chung một luồng; tồn đồng bộ chéo sàn; in tem cho cả 3 sàn.

## Phase 5 — WMS đầy đủ + Đăng bán đa sàn  ☐
**Việc:** nhập/xuất/điều chuyển kho + kiểm kê (có phiếu, chênh lệch) + giá vốn FIFO/bình quân · đăng sản phẩm lên nhiều sàn từ 1 SP gốc + sao chép listing + sửa hàng loạt + đồng bộ category/attribute sàn · ĐVVC đợt 2 (ViettelPost, NinjaVan, SPX, VNPost, Best, Ahamove/Grab).
**Exit criteria:** quản lý kho khép kín (nhập→bán→kiểm kê→báo cáo tồn) + đăng/sửa listing đa sàn từ một nơi.

## Phase 6 — Tài chính + Mua hàng + Báo cáo + Billing  ☐
**Việc:** kéo settlement từng sàn → `settlement_lines` → đối chiếu + tính lợi nhuận theo đơn/SP/gian hàng/thời gian · NCC + bảng giá nhập + PO + nhận hàng → giá vốn · báo cáo bán hàng/lợi nhuận + export · billing SaaS (gói, hạn mức `usage_counters`, dùng thử, VNPay/MoMo) · rules engine tự động hoá · thông báo đa kênh.
**Exit criteria:** biết được lãi/lỗ từng đơn; bán được gói thuê bao; tự động hoá được các thao tác lặp.

## Phase 7+ — Hậu mãi & nâng cao  ☐
Trả hàng/hoàn tiền; chat hợp nhất đa sàn; HĐĐT; tối ưu hiệu năng (read replica, search engine, archive partition); realtime UI (Reverb); PWA quét đóng gói.

---

## Backlog (chưa xếp phase — đừng làm cho tới khi được xếp)
- Tích hợp nguồn hàng 1688/Taobao · POS bán tại quầy · CRM/marketing · đa quốc gia · API public cho bên thứ ba · marketplace plugin.
