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

---

## B. Đang chờ (đã có trong roadmap, chưa code)

### D — Pixel + chuyển đổi (CTA/link) — *mở rộng theo yêu cầu mới*
- Pixel + sự kiện chuyển đổi; CTA/đích đến cho quảng cáo (link / messenger).
- **[MỚI] Khi chọn bài viết (bước Nội dung): hiển thị ngay "đích" mà bài viết đó đang gắn** — tức là **đường link đã gắn sẵn trong bài** *hoặc* **nút "Gửi tin nhắn"** (nếu bài là dạng click-to-Messenger). Người dùng phải nhìn thấy CTA hiện hữu của bài trước khi xuất bản (không sửa mù).
  - Nguồn: đọc `call_to_action` / `link`/`attachments` của post qua connector (Graph `/{post_id}?fields=...`), map về DTO generic `{ cta_type, link_url? }`; FE hiển thị badge "Đường dẫn: …" hoặc "Hành động: Gửi tin nhắn".
  - Chấp nhận: chọn post → thấy đúng link/nút hiện có; objective `messages` ⇒ hiện "Gửi tin nhắn", objective `traffic` ⇒ hiện link đích.

### G — Phân tích AI theo từng chiến dịch cụ thể — *mở rộng theo yêu cầu mới*
- **Phân tích AI cho TỪNG chiến dịch cụ thể** (không chỉ báo cáo tổng), với:
  - **Số ngày tùy chọn** (vd 7/14/30 ngày hoặc tự nhập).
  - **Chỉ số tùy chọn** (chọn các thông số đưa vào phân tích: chi tiêu, CTR, CPM, CPC, CPR, lượt tiếp cận, kết quả, …).
  - **Dữ liệu bài viết kèm tương tác**: bài viết của quảng cáo + **lượt like** + **lượt comment** (và share/lưu nếu có) đưa vào ngữ cảnh để AI nhận xét creative.
  - Đầu ra: nhận xét + khuyến nghị hành động cho riêng chiến dịch đó.
  - Kiến trúc: tái dùng AI axis hiện có; connector lấy insights theo `date_preset`/`time_range` tùy chọn + engagement của post; service tổng hợp → prompt; chạy async (Horizon) như báo cáo AI v1.

### H — Clone (Ctrl+C / Ctrl+V)
- Nhân bản chiến dịch / nhóm quảng cáo / quảng cáo bằng phím tắt (sao chép node trong cây nháp).

---

## C. Hạng mục mới bổ sung (chưa có spec/plan — cần làm sau D/G/H)

### I — Lịch chạy: thêm **ngày kết thúc**
- Bước Ngân sách hiện có: ngân sách hằng ngày + **ngày bắt đầu**. Bổ sung **ngày kết thúc** (end_time) tùy chọn — như trên Ads Manager.
- Áp ở cấp nhóm quảng cáo (`AdSetSpecDTO` đã có `startTime`; thêm `?string $endTime`), generic; connector map sang `end_time`. FE: thêm DatePicker "Ngày kết thúc" (tùy chọn, phải sau ngày bắt đầu); rỗng = chạy liên tục.
- Lưu ý: với CBO/ngân sách trọn đời (lifetime) thì end_time bắt buộc — ghi chú khi mở rộng sang lifetime budget.

### J — Nhắm mục tiêu **chi tiết** (detailed targeting)
- Đối tượng đã có: vị trí (F), độ tuổi, giới tính, sở thích (cơ bản). Bổ sung **nhắm mục tiêu chi tiết đầy đủ**:
  - Tìm & thêm **sở thích / hành vi / nhân khẩu học** (Graph `adinterest`/`adbehavior`/`addemographic`) — gộp vào `flexible_spec`.
  - **Thu hẹp đối tượng** (narrow / "AND" giữa nhiều nhóm `flexible_spec`) và **loại trừ** (`exclusions`).
  - Có thể tái dùng cơ chế **mẫu** như F (mẫu đối tượng chi tiết) — *cân nhắc, không bắt buộc v1*.
  - Pass-through: FE dựng `flexible_spec` + `exclusions` vào `targeting`; lưu metadata FE (name) để render lại như `node.geo`.

### K — **Advantage+** (Meta Advantage+)
- Bật các tùy chọn Advantage+ của Meta:
  - **Advantage+ Audience** (Meta tự mở rộng đối tượng từ gợi ý) — toggle ở bước Đối tượng.
  - **Advantage+ Placements** (đã gần với "Vị trí tự động" của E — ánh xạ rõ ràng).
  - (Tùy chọn xa) **Advantage+ Shopping Campaign** — campaign type riêng.
- Generic: thêm cờ `advantage_audience` / dùng `targeting_automation` trong spec; connector map sang field Graph tương ứng. Khi bật ⇒ làm mờ/ghi đè phần nhắm thủ công tương ứng.

### L — Thử nghiệm **A/B test**
- A/B test cho **chiến dịch / nhóm quảng cáo / quảng cáo** (và biến thể creative — "nguồn quảng cáo / danh mục").
- Cho chọn **biến số thử nghiệm** (creative, đối tượng, vị trí, …), chia ngân sách/đối tượng, theo dõi & tuyên bố "người thắng".
- Kiến trúc: Graph có **A/B test (split test) API** — connector tạo study/experiment generic; hoặc v1 đơn giản = nhân bản node với 1 biến số khác nhau + gắn nhãn nhóm thử nghiệm; service so sánh chỉ số. Cần spec riêng (phức tạp) — ưu tiên sau J/K.

---

## D. Thứ tự đề xuất
Đã xong: Báo cáo AI · A · B · C · E · F.
Tiếp theo: **D** (pixel/conversion + hiển thị link/nút Gửi tin nhắn của bài) → **G** (AI theo từng chiến dịch, số ngày + chỉ số + like/comment tùy chọn) → **H** (clone) → **I** (ngày kết thúc) → **J** (nhắm mục tiêu chi tiết) → **K** (Advantage+) → **L** (A/B test).
(Thứ tự có thể đổi theo ưu tiên kinh doanh; mỗi mục vẫn cần spec + plan trước khi code.)
