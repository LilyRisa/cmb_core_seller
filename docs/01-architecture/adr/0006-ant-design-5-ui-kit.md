# ADR-0006: UI kit của React SPA = Ant Design 5

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team

## Bối cảnh

App kiểu "BigSeller": rất nhiều **bảng dữ liệu, bộ lọc, form, upload, modal, drawer** (đơn hàng, liên kết SKU, in hàng loạt, đối soát…). Cần một UI kit hoàn chỉnh, có sẵn component nặng (Table có sort/filter/phân trang/selection, Form, Upload), tiếng Việt, để không phải tự dựng.

## Quyết định

- UI kit = **Ant Design 5** (`antd` + `@ant-design/icons`), `ConfigProvider` locale `vi_VN`. State server qua **TanStack Query**; UI/local state qua **Zustand**; routing **React Router v6**; form **React Hook Form + zod**; HTTP **Axios** (`withCredentials`). i18n mặc định `vi`.
- Hiển thị thống nhất bằng component chung bọc AntD: `<DataTable>` (chuẩn hoá phân trang/lọc/sort khớp quy ước API), `<StatusTag>`, `<MoneyText>`, `<DateText>` (timezone VN). Filter phản ánh trong URL.
- `features/*` khớp 1-1 với `app/Modules/*` backend; component "dumb", hook "smart".

## Hệ quả

- Tích cực: dựng màn quản lý đơn–kho rất nhanh; ít CSS thủ công; có sẵn theme + responsive.
- Đánh đổi: bundle lớn (chấp nhận cho app nội bộ; có thể code-split sau); phụ thuộc vào nhịp release của AntD; tránh "phá theme" lung tung — đi qua component chung.
- Liên quan: `tech-stack.md`, `06-frontend/overview.md`.
