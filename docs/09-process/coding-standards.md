# Chuẩn code

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Chung
- **Tiếng Anh** cho tên biến/hàm/class/bảng/cột/route; **tiếng Việt** cho chuỗi hiển thị người dùng (qua i18n), comment có thể tiếng Việt.
- Đặt tên theo **từ điển** (`00-overview/glossary.md`) — gọi cùng một thứ bằng cùng một tên ở mọi nơi (code, DB, API, UI).
- Tự động format trong CI (Pint cho PHP, Prettier cho TS) — không tranh luận style thủ công.
- Không để code chết, không `dd()`/`console.log` lẫn vào, không `// TODO` mồ côi (gắn issue hoặc bỏ).
- Số "ma thuật" ⇒ hằng số/enum có tên. Tiền ⇒ `bigint` đồng, không float. Thời gian ⇒ UTC, dùng Carbon/`timestamptz`.

## 2. PHP / Laravel
- **PSR-12** + **Laravel Pint**. **PHPStan/Larastan** level cố định, tăng dần (đặt mức khởi điểm ở `ci-cd-pipeline.md`).
- **Module hoá**: code nghiệp vụ ở `app/Modules/<X>/...`; service provider mỗi module bind interface→impl, đăng ký route/migration/policy của module. Module gọi nhau **chỉ qua `Contracts/`** hoặc domain event (xem `01-architecture/modules.md`).
- **Controller mỏng**: `FormRequest` validate → gọi **một** Service → trả **API Resource**. Không nghiệp vụ trong controller, không query Eloquent phức tạp trong controller.
- **Service**: chứa nghiệp vụ; nhận/trả DTO hoặc model; transaction rõ ràng; phát domain event `afterCommit`.
- **Eloquent**: model nghiệp vụ dùng trait `BelongsToTenant` (global scope + auto set `tenant_id`); quan hệ khai báo rõ; tránh N+1 (eager load có chủ đích); không "fat model" — logic nặng để ở Service.
- **DTO**: dùng class readonly (hoặc `spatie/laravel-data`) cho dữ liệu vào/ra connector & ranh giới module. Không truyền array tự do qua nhiều tầng.
- **Jobs**: `implements ShouldQueue`; idempotent; `tries`/`backoff`/`retryUntil` hợp lý; `ShouldBeUnique` khi cần; chỉ định `queue` đúng (xem `07-infra/queues-and-scheduler.md`); không gọi API ngoài trong web request — luôn dispatch job.
- **Integration layer** (`app/Integrations/*`): chỉ HTTP/ký/version + mapping → DTO chuẩn; **không** import `app/Modules/*` (ngoài DTO/interface chuẩn). Client API ngoài: timeout, retry giới hạn, log có kiểm soát (không log token), versioned theo version API của bên kia.
- **Migration**: một thay đổi schema = một migration; có `down()`; đặt index cho `tenant_id` + cột lọc thường dùng + unique chống trùng dữ liệu ngoài; bảng lớn → partition theo tháng (xem `02-data-model/overview.md`).
- **Validation**: dùng `FormRequest`; thông báo lỗi tiếng Việt, rõ ràng; trả theo envelope chuẩn (`05-api/conventions.md`).
- **Quyền**: mọi action qua Policy/Gate; kiểm `tenant_id` ngay cả khi đã có global scope.
- **Cấu hình**: dùng `config()` (không `env()` ngoài file config); thêm key vào `config/integrations.php` cho bật/tắt sàn/ĐVVC/throttle.
- **Không** dùng facade lung tung trong domain code khi DI rõ ràng hơn; **không** static state.

## 3. TypeScript / React (xem `06-frontend/overview.md` để biết cấu trúc)
- **ESLint + Prettier**; `tsc --noEmit` trong CI; `strict` mode bật.
- `features/*` khớp 1-1 module backend. Component "dumb", hook "smart" (logic trong hook).
- **Server state luôn qua TanStack Query**; UI state qua Zustand; không gọi axios rải rác.
- Gọi API qua `lib/api.ts` (axios instance + interceptor lỗi theo envelope chuẩn).
- Form: React Hook Form + zod schema cạnh form.
- Hiển thị thống nhất qua component chung (`StatusTag`, `MoneyText`, `DateText`, `DataTable`...). Filter phản ánh trong URL.
- Type cho dữ liệu API: ưu tiên đồng bộ/generate từ backend Resource (tránh lệch).
- Không hard-code chuỗi tiếng Việt rải rác (gom qua i18n; cho phép giai đoạn đầu nhưng dọn dần).
- Phân quyền UI bằng `useCan(permission)` để ẩn/disable — nhưng không tin client.

## 4. Tài liệu & comment
- Comment giải thích **"vì sao"**, không lặp lại "cái gì" mà code đã nói. Quy tắc nghiệp vụ phức tạp ⇒ link tới doc tương ứng trong `docs/`.
- Public method của Service/Connector: docblock ngắn nói input/output/side-effect/lỗi có thể ném.
- Cập nhật doc cùng PR (xem Definition of Done ở `ways-of-working.md`).

## 5. Bảo mật khi viết code
- Không log token/secret/PII đầy đủ (mask). Không nội suy biến vào SQL thô (dùng query builder/binding). Validate & whitelist mọi input từ ngoài (kể cả từ sàn). Không fetch URL người dùng nhập (SSRF). Token/credential lưu `encrypted`.
