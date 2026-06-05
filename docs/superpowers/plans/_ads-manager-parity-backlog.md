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
| L (v1) | **A/B test** cấp nhóm (clone theo 1 biến số + nhãn thử nghiệm) | ✅ |
| D (pt.2) | **Pixel + sự kiện chuyển đổi** (objective Chuyển đổi, promoted_object pixel) | ✅ |
| H+ | Clone phím tắt **cấp quảng cáo** (Ctrl+C/V/D ở bước Nội dung) | ✅ |
| G+ | **Email** phân tích AI per-campaign cho Owner/Admin | ✅ |
| J+ | **Mẫu đối tượng chi tiết** (lưu/áp include/narrow/exclude) | ✅ |
| K+ | **Advantage+ creative** (standard enhancements) toggle | ✅ |
| L+ | **So sánh A/B** ở báo cáo: gom [A]/[B], tuyên bố người thắng theo chỉ số | ✅ |

Spec từng mục: `docs/superpowers/specs/2026-06-05-*.md`.

---

## B. Follow-up còn lại (subsystem lớn — chưa làm)

Chỉ còn 2 hạng mục thực sự là **hệ con lớn**, cần brainstorming + spec/plan riêng (đa ngày), không gộp vào cadence hiện tại; **không** ship bản nửa vời:

### K++ — Advantage+ Shopping Campaign (ASC)
- Loại **chiến dịch riêng** dựa trên **catalog sản phẩm** (cần tích hợp catalog/feed, objective & cấu trúc khác). Là một module bán hàng theo danh mục, không phải toggle.

### L++ — A/B test chính thức qua Meta **ad_study** API
- Tạo **study/experiment cells**, Meta điều phối chia ngân sách/đối tượng và **tuyên bố winner chính thức** (khác cách lightweight hiện tại = 2 nhóm [A]/[B] + so sánh chỉ số ở báo cáo). Gồm vòng đời study (tạo → chạy → poll kết quả) + A/B cấp **chiến dịch** & **quảng cáo (creative/danh mục)**.

### Mảnh nhỏ còn để ngỏ (đủ dùng ở bản hiện tại)
- J: **>2 nhóm thu hẹp** (danh sách động nhiều `flexible_spec`) — hiện 1 include + 1 narrow đủ cho đa số.
- G: **lịch sử nhiều bản phân tích**/chiến dịch — hiện giữ 1 bản mới nhất.

---

## C. Trạng thái
Toàn bộ roadmap chính + các follow-up tractable **đã xong & merge `main`**: Báo cáo AI · A · B · C · D (pt.1+pt.2) · E · F · G(+) · H(+) · I · J(+) · K(+) · L(v1)(+).
Chỉ còn **2 subsystem lớn** (Advantage+ Shopping Campaign theo catalog; ad_study split-test API chính thức) — mỗi cái cần spec/plan riêng trước khi code.
