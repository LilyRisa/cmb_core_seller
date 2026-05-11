# ADR-0003: Modular monolith (domain modules trong `app/Modules/*`), không microservice

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team

## Bối cảnh

Phạm vi rộng (đơn, tồn, sản phẩm/listing, giao hàng/in, mua hàng, tài chính, báo cáo, billing, settings) cho ~100 nhà bán. Cần dễ hiểu, dễ vận hành, dễ mở rộng theo chiều "thêm sàn / thêm ĐVVC / thêm tính năng". Microservice ở quy mô này = chi phí vận hành/đồng bộ không xứng.

## Quyết định

- **Modular monolith**: một ứng dụng Laravel, code nghiệp vụ chia theo **domain module** trong `app/Modules/<Name>/` (Tenancy, Channels, Orders, Inventory, Products, Fulfillment, Procurement, Finance, Reports, Billing, Settings). Mỗi module có service provider riêng (bind interface→impl, đăng ký route/migration/policy của module).
- **Luật phụ thuộc** (bắt buộc, xem `modules.md`): Tenancy là nền; module khác chỉ gọi nhau qua `Contracts/` (interface) hoặc domain event (listener idempotent); cấm import "ruột" module khác; cấm phụ thuộc vòng; `Reports` chỉ đọc; integration layer (`app/Integrations/*`) không import `app/Modules/*` ngoài DTO/interface chuẩn.
- Tách "service" thật chỉ khi có lý do vận hành rõ ràng (sau, có ADR riêng).

## Hệ quả

- Tích cực: một deploy, một DB, transaction xuyên domain dễ; ranh giới module vẫn rõ qua interface/event ⇒ có thể tách sau nếu cần.
- Đánh đổi: kỷ luật phụ thuộc phải được enforce ở review (vi phạm = từ chối PR); scale theo chiều ngang là scale cả monolith (worker thì tách container riêng).
- Liên quan: `01-architecture/overview.md`, `modules.md`, `tech-stack.md`.
