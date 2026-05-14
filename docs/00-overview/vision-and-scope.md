# Tầm nhìn & Phạm vi

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Một câu mô tả

> Phần mềm SaaS giúp người bán hàng tại Việt Nam **kết nối nhiều gian hàng** trên TikTok Shop / Shopee / Lazada (và tạo đơn thủ công), **đồng bộ đơn hàng về một chỗ với trạng thái chuẩn hoá**, **đồng bộ tồn kho theo SKU**, **xử lý giao hàng & in vận đơn hàng loạt**, và quản lý kho – sản phẩm – tài chính — tương tự BigSeller, tập trung cho thị trường Việt Nam.

## 2. Đối tượng người dùng

- Nhà bán hàng đa sàn (1 người → nhiều gian hàng, nhiều sàn).
- Trong một nhà bán: chủ shop + nhân viên (kho, xử lý đơn, kế toán) với phân quyền khác nhau.
- Quy mô mục tiêu giai đoạn đầu: **~100 nhà bán**, mỗi nhà bán ~**5.000 đơn/tháng** (≈ 500k đơn/tháng toàn hệ thống).

## 3. Trong phạm vi (In scope) — full bộ tính năng kiểu BigSeller

| Nhóm | Tính năng |
|---|---|
| **Đơn hàng** | Đồng bộ đơn đa sàn (webhook + polling); tạo đơn thủ công; trạng thái chuẩn + mapping từng sàn; lọc/tìm; thao tác hàng loạt; gộp/tách đơn; cảnh báo đơn trùng/lỗi |
| **Giao hàng & in ấn** | Sắp xếp vận chuyển qua API sàn → lấy mã vận đơn + label PDF; **in vận đơn hàng loạt**; **picking/packing list** tự render; **template in tùy biến**; **quét mã đóng gói** (scan-to-pack/ship); kết nối ĐVVC riêng (GHN, GHTK, J&T, ViettelPost, SPX, NinjaVan, VNPost, Best, Ahamove/Grab); đối soát phí ship |
| **Kho / WMS** | Nhiều kho; tồn theo (SKU, kho) với reserved/available/safety stock; nhập–xuất–điều chuyển; kiểm kê; sổ cái biến động tồn; cảnh báo hết hàng/tồn lâu/âm kho; giá vốn FIFO; (tùy chọn) batch/HSD, serial |
| **Sản phẩm & đăng bán** | Sản phẩm/SKU master; **ghép SKU** (N→1, 1→N combo); đồng bộ tồn 2 chiều; đồng bộ giá (tùy chọn); **đăng sản phẩm lên nhiều sàn** từ 1 SP gốc; sao chép listing; sửa hàng loạt; đồng bộ cây danh mục/thuộc tính sàn |
| **Mua hàng & NCC** | Quản lý NCC, bảng giá nhập; đề xuất nhập hàng; đơn mua (PO) → nhận hàng → nhập kho → cập nhật giá vốn |
| **Tài chính** | Kéo đối soát/settlement từng sàn (phí sàn, phí TT, phí ship, hoa hồng affiliate, voucher sàn); tính **lợi nhuận theo đơn / SP / gian hàng / thời gian**; đối chiếu tiền sàn trả thực tế |
| **Báo cáo** | Dashboard tổng quan; báo cáo bán hàng, top SP, tỉ lệ huỷ/hoàn, hiệu suất xử lý đơn; export Excel/CSV |
| **Hậu mãi** | Quản lý trả hàng/hoàn tiền từ sàn; nhập kho hàng hoàn |
| **SaaS** | Đa tenant; sub-account & phân quyền chi tiết; audit log; **billing** (gói thuê bao, hạn mức đơn/gian hàng, dùng thử, gia hạn, cổng TT VN); thông báo đa kênh (email/in-app/Zalo/Telegram); quy tắc tự động (auto-confirm, auto-gán kho/ĐVVC) |

## 4. Ngoài phạm vi / Non-goals (giai đoạn này)

- **Chỉ thị trường Việt Nam.** Không đa quốc gia, không đa region cho mỗi sàn, không đa tiền tệ — chỉ VND, địa chỉ VN (tỉnh/huyện/xã), ĐVVC VN. *(Kiến trúc vẫn để chừa đường mở rộng — xem `extensibility-rules.md` — nhưng không xây tính năng đa quốc gia bây giờ.)*
- **Không** tích hợp nguồn hàng quốc tế (1688/Taobao). Chỉ NCC nội địa.
- **Không** làm sàn TMĐT riêng, không làm POS bán tại quầy (giai đoạn này).
- **Không** làm CRM/marketing automation, email marketing.
- **Chat hợp nhất đa sàn**: để rất sau (Phase 7+), không phải mục tiêu chính.
- **Hóa đơn điện tử**: tích hợp ở Phase rất sau, không phải MVP.
- **Mobile app native**: không. Có thể làm PWA cho khâu quét đóng gói ở Phase sau.
- **Không** tự xây cổng thanh toán. Đã tích hợp **SePay** (chuyển khoản qua webhook sao kê — không phí cổng) + **VNPay** (redirect + IPN) cho thuê bao SaaS; **MoMo** skeleton sẵn để bật khi có nhu cầu. Phase 6.4 / SPEC-0018.

## 5. Tiêu chí thành công (mỗi mốc)

- **Hết Phase 3 (~3–4 tháng):** một nhà bán kết nối được shop TikTok thật, đơn tự đồng bộ về với trạng thái chuẩn, tạo được đơn tay, tồn kho trừ chung và tự đẩy lên sàn, tạo được vận đơn → in tem hàng loạt → quét đóng gói → bàn giao ĐVVC. Dùng được nội bộ/beta.
- **Hết Phase 5 (~8–12 tháng):** đủ 3 sàn, WMS cơ bản, đăng bán đa sàn. Ra mắt thương mại.
- **Hết Phase 7 (~18–24 tháng):** tiệm cận full BigSeller (đối soát, mua hàng, báo cáo lợi nhuận, billing, tự động hoá).

## 6. Ràng buộc & giả định

- Đường găng tiến độ = **duyệt app Shopee Open Platform & Lazada Open Platform** (lâu, cần hồ sơ doanh nghiệp) → nộp ngay tuần đầu Phase 0. Trong lúc chờ, hoàn thiện TikTok + WMS + in ấn (không phụ thuộc 2 sàn kia nhờ kiến trúc connector).
- API các sàn thay đổi version liên tục (TikTok có v202309→v202601) → client phải versioned.
- Webhook các sàn không đảm bảo 100% → luôn có polling backup.
- Phải tuân thủ yêu cầu xoá dữ liệu cá nhân buyer của từng sàn (xem `08-security-and-privacy.md`).
