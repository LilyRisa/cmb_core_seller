# ADR-0005: Chỉ thị trường Việt Nam ở giai đoạn này (VND, ĐVVC VN, sàn VN); kiến trúc chừa đường mở rộng

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Chủ dự án + team

## Bối cảnh

Đa quốc gia (đa tiền tệ, đa thuế, ĐVVC quốc tế, sàn nước ngoài) làm phình mọi tầng (tiền, địa chỉ, thuế, vận chuyển, đối soát). Khách hàng mục tiêu giai đoạn đầu là nhà bán **Việt Nam** bán trên TikTok Shop/Shopee/Lazada VN, giao bằng ĐVVC trong nước.

## Quyết định

- **Phạm vi: chỉ Việt Nam.** Tiền tệ **chỉ VND**, lưu `bigint` đơn vị **đồng** (không float); vẫn có cột `currency = 'VND'` để chừa đường. Địa chỉ theo cấu trúc VN (tỉnh/huyện/xã). ĐVVC VN; sàn VN.
- Vẫn **chừa đường mở rộng** (rẻ): `currency` cột sẵn; helper money/address VN gói trong `app/Support` (thêm quốc gia sau = mở rộng support layer, không rải khắp nơi); `AuthContext.region = 'VN'` để multi-region là extension, không phải rewrite; DTO chuẩn không hard-code giả định "chỉ VN" ở chỗ không cần thiết.
- **Không** làm: i18n nhiều ngôn ngữ ngoài tiếng Việt (kết cấu i18n có sẵn nhưng chỉ `vi`), đa tiền tệ, ĐVVC/sàn nước ngoài, thuế nước ngoài — cho tới khi có ADR mới.

## Hệ quả

- Tích cực: ít phức tạp, ra mắt nhanh; quyết định nghiệp vụ (phí, đối soát, thuế) khu trú vào VN.
- Đánh đổi: muốn mở rộng quốc gia sau cần một đợt rà soát money/address/tax/shipping/settlement; chấp nhận.
- Liên quan: `00-overview/vision-and-scope.md`, `02-data-model/overview.md` §1 (quy ước tiền/thời gian), `app/Support/*`.
