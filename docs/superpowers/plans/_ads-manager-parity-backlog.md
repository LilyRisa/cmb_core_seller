# Ads-Manager-parity — Roadmap & Backlog (nội bộ)

> Theo dõi tiến trình đưa module **Quảng cáo (Facebook Ads)** tiệm cận Ads Manager.
> Nguyên tắc bất biến (mọi hạng mục phải tuân): **core không biết tên sàn** (cấu trúc generic ở DTO/node, tên field Graph chỉ trong connector) · `targeting` & spec là **pass-through trong suốt** (FE dựng → node → mapper copy → connector JSON-encode) · tiền = VND integer · mọi bảng có `tenant_id` + `BelongsToTenant` · controller mỏng → service → resource · TDD.
> Mỗi hạng mục lớn = 1 spec trong `docs/superpowers/specs/` + 1 plan trong `docs/superpowers/plans/` trước khi code.

---

## A. Đã hoàn thành (đã merge `main`)

| Mã | Hạng mục | Trạng thái |
|----|----------|-----------|
| Báo cáo AI (v1) | Báo cáo marketing AI async + email Owner/Admin + phân tích creative | ✅ |
| A | Danh sách chiến dịch: tô xanh dòng đang chạy + sắp xếp running-first + sửa lỗi page-size | ✅ |
| B | Cấp ngân sách: CBO (chiến dịch) vs ngân sách nhóm quảng cáo | ✅ |
| C | Cấu trúc cây đa nhóm / đa quảng cáo (multi-adset, multi-ad) | ✅ |
| E | Vị trí đầy đủ (thiết bị/nền tảng/vị trí chi tiết theo nền tảng) | ✅ |
| F | Geo chi tiết (quốc gia/vùng/thành phố) + loại trừ khu vực + **mẫu loại trừ** tái dùng | ✅ |
| D (pt.1) | Khi chọn bài: hiển thị link đã gắn / nút "Gửi tin nhắn" của bài (CTA hiện hữu) | ✅ |
| G | Phân tích AI theo **từng chiến dịch** (số ngày + chỉ số + like/comment tùy chọn) | ✅ |
| H | Clone (Ctrl+C/V/D) nhóm/quảng cáo trong cây nháp | ✅ |
| I | Lịch chạy: thêm **ngày kết thúc** (end_time) | ✅ |
| J | Nhắm mục tiêu **chi tiết** (sở thích/hành vi/nhân khẩu + thu hẹp + loại trừ) | ✅ |
| K | **Advantage+** Audience (toggle) + Advantage+ Placements (nhãn) | ✅ |
| L (v1) | **A/B test** cấp nhóm (clone theo 1 biến số + nhãn thử nghiệm; so sánh ở báo cáo) | ✅ |

Spec từng mục: `docs/superpowers/specs/2026-06-05-*.md`.

---

## B. Follow-up còn lại (chưa làm)

### D (pt.2) — Pixel + sự kiện chuyển đổi
- Pixel + conversion event đầy đủ cho mục tiêu chuyển đổi (ngoài phần hiển thị CTA hiện hữu đã làm ở pt.1).

### G+ — Email per-campaign + lịch sử nhiều bản phân tích
- Hiện G chỉ giữ 1 bản phân tích mới nhất/chiến dịch, không gửi email (xem trực tiếp ở Drawer).

### J+ — Mẫu "đối tượng chi tiết" + nhiều nhóm thu hẹp (>2 flexible_spec)
- Tái dùng cơ chế mẫu như F cho đối tượng chi tiết; hỗ trợ >1 nhóm thu hẹp.

### K+ — Advantage+ Shopping Campaign + Advantage+ creative enhancements

### L+ — A/B test chính thức qua Meta ad_study API
- Tạo study/experiment cells, Meta điều phối ngân sách & tuyên bố winner; A/B cấp **chiến dịch** & **quảng cáo (creative/danh mục)**; bảng so sánh A/B gom theo `experiment.id`.

### H+ — Clone cả chiến dịch + phím tắt cấp quảng cáo

---

## C. Trạng thái
Tất cả hạng mục roadmap chính (Báo cáo AI · A · B · C · D pt.1 · E · F · G · H · I · J · K · L v1) **đã xong & merge `main`**. Phần còn lại ở §B là các follow-up nâng cao (pixel/conversion, ad_study API chính thức, mẫu đối tượng chi tiết, Advantage+ Shopping…), làm khi có nhu cầu — mỗi mục vẫn cần spec + plan trước khi code.
