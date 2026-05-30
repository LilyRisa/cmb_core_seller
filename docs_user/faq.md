# FAQ — Câu hỏi thường gặp (Knowledge Base)

> 110+ câu hỏi & trả lời chi tiết, nhóm theo chủ đề. Dùng cho khách, support và AI Agent.

## A. Tài khoản & Đăng nhập

**1. Làm sao tạo tài khoản?**
Vào `/register`, nhập Họ tên / Email / Mật khẩu rồi bấm "Đăng ký". Hệ thống tạo tài khoản kèm một workspace (gian hàng) mới và bạn là Chủ sở hữu, đồng thời bắt đầu gói Dùng thử 14 ngày.

**2. Vì sao đăng nhập xong vẫn không dùng được tính năng?**
Vì email chưa xác thực. Mở email, bấm link "Xác thực email". Trước khi xác thực, mọi tính năng bị chặn (trả lỗi `EMAIL_NOT_VERIFIED`).

**3. Không nhận được email xác thực?**
Kiểm tra mục Spam/Quảng cáo. Bấm "Gửi lại email" (chờ 60 giây giữa 2 lần). Link xác thực có hạn 60 phút.

**4. Quên mật khẩu thì làm sao?**
Vào `/forgot-password`, nhập email → nhận link → đặt mật khẩu mới (≥8 ký tự, có chữ hoa/thường/ký tự đặc biệt).

**5. Đổi email/mật khẩu ở đâu?**
`/settings/profile`. Đổi email cần nhập mật khẩu hiện tại để xác nhận.

**6. Một tài khoản có thể có nhiều gian hàng (workspace) không?**
Có. Mỗi workspace là một tenant riêng. Chuyển workspace bằng bộ chọn ở góc header (nhãn = `tên · vai trò`).

**7. "Tenant" là gì?**
Là một tổ chức/workspace sở hữu toàn bộ dữ liệu nghiệp vụ. Mọi bảng dữ liệu đều gắn `tenant_id` nên dữ liệu các workspace tách biệt hoàn toàn.

## B. Vai trò & Phân quyền

**8. Có những vai trò nào?**
Chủ sở hữu (owner), Quản trị (admin), NV xử lý đơn (staff_order), NV kho (staff_warehouse), NV chăm sóc khách (staff_cs), Kế toán (accountant), Chỉ xem (viewer).

**9. Admin khác Owner ở điểm nào?**
Admin làm được toàn bộ nghiệp vụ + quản lý nhân viên, nhưng **không** thanh toán/đổi gói, không xoá/chuyển workspace.

**10. Ai được thanh toán/nâng gói?**
Chỉ Chủ sở hữu (quyền `billing.manage`).

**11. Ai duyệt đơn hoàn/hủy?**
Owner/Admin/NV xử lý đơn (`orders.update`).

**12. Kế toán làm được gì?**
Đối soát, báo cáo, kế toán (định khoản/đóng kỳ/export), xem hoá đơn gói. Không sửa cấu hình hệ thống tài khoản (đó là quyền owner/admin).

**13. Vì sao tôi không thấy nút "Tạo đơn"/"In"?**
Trang ẩn nút khi bạn thiếu quyền tương ứng (`orders.create`, `fulfillment.print`…). Hỏi Owner/Admin cấp quyền.

**14. Super-admin là gì?**
Là nhân viên vận hành CMBcoreSeller, đăng nhập riêng ở `/admin`, làm việc xuyên tenant. Không phải vai trò trong gian hàng của bạn.

## C. Gian hàng & Kết nối sàn

**15. Kết nối gian hàng như thế nào?**
Vào "Gian hàng" (`/channels`), bấm "Kết nối [TikTok/Lazada]", đăng nhập shop và cấp quyền; hệ thống tự tạo gian hàng và kéo 90 ngày đơn gần nhất.

**16. Hỗ trợ sàn nào?**
TikTok Shop và Lazada (đang chạy), `manual` (đơn thủ công, luôn có). Shopee đã có cấu hình nhưng chờ duyệt API.

**17. Kết nối Lazada báo lỗi IP?**
Lazada yêu cầu whitelist IP máy chủ. Mở modal "IP máy chủ" trong trang Gian hàng, copy IP và thêm vào cổng Lazada.

**18. Bị giới hạn số gian hàng?**
Đúng: trial/starter = 2, pro = 5, business = 10. Vượt hạn khi kết nối ⇒ báo `PLAN_LIMIT_REACHED`, cần nâng gói.

**19. Ngắt kết nối gian hàng có mất dữ liệu không?**
Có: xoá toàn bộ đơn của shop đó, nhả tồn đã giữ, bỏ mọi liên kết SKU. Phải gõ đúng tên shop để xác nhận.

**20. Đồng bộ lại đơn thủ công?**
Trong gian hàng bấm "Đồng bộ lại", hoặc dùng "kéo đơn chưa xử lý" để lấy đơn cũ chưa giao.

**21. "auto-RTS sau khi in" của Lazada là gì?**
Khi bật, sau khi đánh dấu in, hệ thống tự gọi Lazada `/order/rts` để chuyển đơn sang "Chờ bàn giao" mà không cần bấm tay. Chỉ áp dụng Lazada.

**22. Đơn về bằng cách nào?**
Hai chiều: webhook (tức thời) + polling dự phòng (định kỳ). Webhook luôn được xác minh chữ ký và luôn fetch lại chi tiết trước khi lưu.

## D. Đơn hàng

**23. Đơn có những trạng thái nào?**
Chờ thanh toán → Chờ xử lý → Đang xử lý → Chờ bàn giao → Đang vận chuyển → Đã giao → Hoàn tất; nhánh phụ: Giao thất bại, Đang trả/hoàn, Đã trả/hoàn, Đã huỷ.

**24. "Chờ xử lý" và "Đang xử lý" khác gì?**
"Chờ xử lý" = đã thanh toán/COD, chưa in/sắp tem. "Đang xử lý" = đã tạo/in vận đơn, đang đóng gói.

**25. Vì sao tôi không đổi được trạng thái đơn sàn?**
Trạng thái lõi của đơn sàn do sàn quyết định (nguồn sự thật). Bạn chỉ đổi qua thao tác sàn cho phép (xác nhận đơn, tạo vận đơn) hoặc gắn tag/note.

**26. "Đơn có vấn đề" (has_issue) nghĩa là gì?**
Cờ cảnh báo: SKU chưa ghép, âm kho, hoặc sàn báo lùi trạng thái bất thường (vd từ Hoàn tất về Đang xử lý).

**27. Làm sao lọc đơn theo gian hàng/ĐVVC/thời gian?**
Trong `/orders`, dùng dải chip lọc tầng Sàn → Gian hàng → Vận chuyển, chip Phiếu in và Thời gian (preset hoặc chọn khoảng).

**28. Tìm đơn theo SKU/người mua được không?**
Được. Chọn trường tìm (mã đơn/người mua, SKU, tên SP) rồi nhập từ khoá.

**29. "Quét đơn" để làm gì?**
Quét nhanh mã vận đơn/đơn để tìm và thao tác (gói/bàn giao) tại quầy.

**30. Đơn bị tách kiện (is_split) là sao?**
Một đơn có thể tách thành nhiều kiện ⇒ nhiều vận đơn; cờ `is_split = true`.

## E. Tạo đơn thủ công

**31. Tạo đơn thủ công ở đâu?**
`/orders/new` (kiểu POS). Chọn nguồn, nhập khách, thêm sản phẩm, Lưu đơn.

**32. Thêm sản phẩm chưa có SKU vào đơn được không?**
Được — dùng "Tạo sản phẩm nhanh" (chỉ tên/ảnh/giá/SL). Hàng nhanh không theo dõi tồn và không bị gắn cờ "chưa ghép SKU".

**33. Tạo đơn thủ công có trừ tồn ngay không?**
Có với dòng có SKU: tạo đơn → đặt giữ tồn (`order_reserve`) ngay.

**34. Giới hạn số dòng hàng mỗi đơn?**
1–200 dòng; số lượng 1–99.999; đơn giá/giảm 0–999.999.999 VND.

**35. Sửa đơn thủ công được tới khi nào?**
Khi chưa ở trạng thái "Đang vận chuyển". Gửi lại danh sách hàng sẽ thay toàn bộ và cân bằng lại tồn.

**36. Hủy đơn thủ công có nhả tồn không?**
Có — hủy trước khi ship sẽ nhả tồn đã giữ (`order_release`).

**37. Hệ thống cảnh báo đơn trùng?**
Có — nếu cùng SĐT + cùng SKU trong khoảng thời gian ngắn.

**38. Đơn thủ công giao bằng gì?**
Dùng ĐVVC riêng (GHN…) tạo vận đơn + tem, hoặc tự giao (không tracking).

## F. Xử lý & Giao hàng (Fulfillment)

**39. "Chuẩn bị hàng" làm gì?**
Gọi sàn tạo vận đơn + lấy tem (PDF), đơn chuyển sang "Đang xử lý". Bị chặn nếu có SKU âm kho.

**40. Vì sao tem không in lại được như bản gốc?**
Hệ thống không vẽ lại tem của ĐVVC để giữ nguyên barcode — luôn dùng đúng file PDF gốc của họ.

**41. Phiếu lấy hàng và phiếu đóng gói khác gì?**
Phiếu lấy hàng gom **theo SKU** qua nhiều đơn (để nhặt hàng nhanh); phiếu đóng gói là **1 phiếu/đơn**.

**42. Quét đóng gói hoạt động ra sao?**
Quét barcode vận đơn → hệ thống tìm đơn trong tenant (chống nhầm tenant, chống quét trùng) → (tuỳ chọn) quét từng SKU → đánh dấu đã gói/bàn giao và trừ tồn.

**43. Khi nào tồn bị trừ?**
Mặc định khi đơn sang "Đang vận chuyển" (bàn giao). Đánh dấu "đã gói" chưa trừ tồn (hàng còn trong kho).

**44. Lô lấy hàng (Pickup Batch) là gì?**
Gom các vận đơn theo shop/ĐVVC cho một lần bàn giao; in phiếu bàn giao cho shipper rồi cập nhật hàng loạt.

**45. In lại tem có mất phí/đổi file không?**
Không — file in giữ ~90 ngày, in lại trả về cùng file qua link tạm thời. Từ lần 2 có popup xác nhận.

**46. Khổ tem hỗ trợ?**
A6 (máy in nhiệt), 100×150, 80mm, A5, A4. Cài ở `/settings/print`.

**47. Thiết kế mẫu phiếu riêng?**
Có — `/settings/shipping-labels` với trình thiết kế kéo–thả (canvas, palette field, undo/redo, preview).

## G. Tồn kho & SKU

**48. SKU gốc là gì?**
Đơn vị tồn nhỏ nhất, mã `sku_code` duy nhất trong tenant — là **nguồn sự thật duy nhất của tồn**.

**49. "Tồn khả dụng" tính thế nào?**
`available = max(0, on_hand − reserved − safety_stock)` — đây là số đẩy lên sàn.

**50. Ghép SKU là gì và để làm gì?**
Nối listing trên sàn với SKU gốc để hệ thống biết trừ/đẩy tồn cho đúng. Chưa ghép thì không đẩy tồn và đơn bị "có vấn đề".

**51. Tự khớp SKU hoạt động ra sao?**
Nếu `seller_sku` của listing trùng `sku_code` (chuẩn hoá), hệ thống gợi ý ghép `single ×1`.

**52. Combo/bundle ghép thế nào?**
Một listing nối nhiều SKU gốc với số lượng; tồn listing = `min(floor(available(SKU)/SL))` qua các thành phần.

**53. Đẩy tồn lên sàn tự động không?**
Có — mọi thay đổi tồn kích hoạt đẩy (debounce 5–15s, throttle theo shop). Có thể "ghim tồn" từng listing để không tự đẩy.

**54. Bán âm kho thì sao?**
Đơn vẫn được giữ (đơn thật), tồn có thể âm tạm thời + cảnh báo, và tồn đẩy lên sàn = 0 để chặn oversell tiếp.

**55. Một SKU gốc bán nhiều sàn được không?**
Được — một SKU gốc đứng sau nhiều listing, đồng bộ tồn chéo sàn.

**56. Xoá SKU còn tồn?**
Không — trả lỗi `409`. Phải đưa tồn về 0 trước.

**57. Phiếu kho gồm những loại nào?**
Nhập (goods receipt), điều chuyển (transfer), kiểm kê (stocktake). Tạo nháp → xác nhận để ghi vào sổ.

**58. Giá vốn FIFO là gì?**
Khi đơn ship, hệ thống tiêu thụ các lớp giá vốn theo thứ tự nhập trước–xuất trước và ghi COGS bất biến vào đơn.

## H. Khách hàng

**59. Hệ thống nhận diện khách trùng thế nào?**
Theo SĐT chuẩn hoá (băm `phone_hash`). SĐT bị che thì không match được.

**60. Điểm uy tín khách là gì?**
Điểm heuristic 0–100 → nhãn ok/watch/risk/blocked, chỉ mang tính tham khảo.

**61. Ghi chú khách dùng làm gì?**
Lưu lưu ý về khách (info/warning/danger). Có ghi chú thủ công và ghi chú tự động khi vượt ngưỡng.

**62. Chặn khách có tác dụng gì?**
Đánh dấu khách rủi ro; hiển thị cảnh báo khi tạo đơn cho SĐT đó.

**63. Gộp 2 hồ sơ khách?**
Có — chức năng merge (quyền `customers.merge`).

## I. Tin nhắn & AI

**64. Hộp thư hợp nhất là gì?**
Một nơi xem & trả lời tin nhắn (DM) và bình luận từ TikTok/Shopee/Lazada/Facebook.

**65. Cần gói nào để dùng Hộp thư?**
`messaging_inbox` (Pro trở lên). AI auto-reply cần `messaging_ai` (Business).

**66. Chèn mẫu tin nhanh thế nào?**
Gõ `/` trong ô soạn để mở danh sách mẫu, hoặc dùng nút mẫu tin.

**67. "AI gợi ý" hoạt động ra sao?**
AI viết nháp trả lời dựa trên hội thoại + tài liệu RAG bạn tải lên; nhân viên duyệt rồi gửi (chế độ gợi ý mặc định).

**68. Auto-mode AI có an toàn không?**
Có rào chắn: phân loại ý định trước; nếu là khiếu nại/hoàn tiền/khẩn/đe doạ pháp lý/lăng mạ → không tự gửi mà báo người thật. Mọi prompt được che PII (SĐT/email/STK).

**69. Có 4 loại auto-reply nào?**
Theo lịch (giờ vắng), theo trạng thái đơn (vd đã giao → cảm ơn), không phản hồi sau N phút, và tin đầu tiên (lời chào).

**70. Auto-reply có spam khách không?**
Không — có cooldown mỗi hội thoại, không trả lời tin do AI sinh, "tin đầu tiên" chỉ chạy 1 lần, và idempotent theo cửa sổ thời gian.

**71. Cửa sổ 24h của Facebook là gì?**
Sau 24h kể từ tin khách cuối, chỉ tin có message tag hợp lệ (vd HUMAN_AGENT) mới gửi được; vi phạm ⇒ `OUTBOUND_WINDOW_CLOSED`.

**72. Trả lời riêng bình luận Facebook bị lỗi "đã nhắn riêng" (10900)?**
Facebook chỉ cho nhắn riêng **1 lần/comment**. Hệ thống xử lý idempotent — coi như đã nhắn, không báo lỗi đỏ. Muốn nhắn tiếp, chờ khách trả lời trong Messenger rồi nhắn qua hội thoại.

**73. Modal nhắn riêng gửi được ảnh/video/file?**
Có — modal nhắn riêng đầy đủ. Tuy nhiên chỉ phần đầu chắc chắn gửi (private reply); phần sau gửi qua PSID + message tag (best-effort), FE báo "đã gửi X/Y phần".

**74. Nút Thích bình luận báo lỗi quyền?**
Cần quyền `pages_manage_engagement` cho Page. Kết nối lại Page để cấp quyền.

**75. Xoá bình luận gốc khác xoá bình luận con?**
Xoá comment gốc ⇒ hội thoại thành spam; xoá comment con chỉ xoá đúng comment đó, giữ hội thoại.

**76. Tài liệu AI training (RAG) dùng thế nào?**
Tải FAQ/chính sách (text/URL/file) ở `/messaging/knowledge`; hệ thống chia chunk + index để AI trả lời sát ngữ cảnh.

**77. Ai thêm nhà cung cấp AI?**
Super-admin (ở `/admin/ai-providers`). Tenant chỉ chọn 1 provider đang bật ở `/settings/messaging`.

## J. Hoàn & Hủy

**78. Đơn hoàn/hủy quản lý ở đâu?**
`/returns`. Đây là yêu cầu sau bán có trạng thái riêng (requested/approved/rejected/processing/refunded/...), không phải trạng thái đơn.

**79. Duyệt/Từ chối yêu cầu hoàn?**
Trong `/returns`, dùng nút Duyệt/Từ chối (Owner/Admin/NV xử lý đơn); hệ thống gọi sàn rồi fetch lại.

**80. Hoàn hàng có tự trừ tiền/tồn không?**
Trong phiên bản hiện tại: chỉ lưu số tiền hoàn để hiển thị và gắn cờ `has_return`/`has_issue`; không tự đổi trạng thái đơn hay đụng sổ tài chính.

**81. Một đơn hoàn nhiều lần?**
Được — mỗi lần là một bản ghi riêng (theo `external_return_id`).

## K. Mua hàng (Procurement)

**82. Cần gói nào để dùng Mua hàng?**
`procurement` (Pro trở lên).

**83. Quy trình nhập hàng?**
Tạo PO với NCC → nhận hàng (goods receipt) → xác nhận → tồn tăng + tạo lớp giá vốn FIFO.

**84. Đề xuất nhập hàng tính dựa trên gì?**
Tốc độ bán, tồn khả dụng, hàng đang về, số ngày còn hàng → đề xuất số lượng + NCC. Chọn nhiều dòng để tạo PO nháp tách theo NCC.

**85. Bảng giá nhập theo NCC?**
Có — đặt giá nhập theo từng SKU cho mỗi NCC trong trang Nhà cung cấp.

## L. Đối soát & Báo cáo

**86. Đối soát sàn để làm gì?**
Lấy phí thực theo từng đơn từ sao kê của sàn (thay vì % ước tính), để tính lợi nhuận chính xác.

**87. Có những loại phí chuẩn nào?**
revenue, commission, payment_fee, shipping_fee, shipping_subsidy, voucher_seller, voucher_platform, adjustment, refund, other.

**88. Lợi nhuận đơn tính thế nào?**
Doanh thu − COGS (FIFO) − Σ phí đối soát − ship thực người bán chịu − giảm giá − khác.

**89. Báo cáo nào cần gói gì?**
Doanh thu có ở mọi gói; Lợi nhuận và Top sản phẩm cần `profit_reports` (Pro+). Export CSV cần `reports.export`.

**90. Export báo cáo định dạng gì?**
CSV UTF-8 có BOM (mở tốt trên Excel tiếng Việt).

## M. Kế toán

**91. Cần gói nào cho Kế toán?**
`accounting_basic` (Pro+); tính năng nâng cao (sao kê/ngân hàng, VAT, MISA) cần `accounting_advanced` (Business).

**92. Khởi tạo kế toán thế nào?**
Lần đầu vào `/accounting`, bấm "Khởi tạo hệ thống tài khoản theo TT133" — tạo hệ thống TK, các kỳ và quy tắc định khoản mặc định (idempotent).

**93. Sửa/xoá bút toán được không?**
Không — bút toán bất biến (luật lưu 10 năm). Muốn sửa thì **đảo** bút toán rồi ghi lại.

**94. Định khoản tự động cho nghiệp vụ nào?**
Nhận hàng → Nợ 156/Có 331; chuyển kho → 156↔156; kiểm kê thừa/thiếu → 156↔711/811. Quy tắc sửa được ở `/settings/accounting/post-rules`.

**95. Đóng kỳ rồi còn ghi được không?**
Không — ghi vào kỳ đã đóng ⇒ `PERIOD_CLOSED`. Đảo bút toán kỳ đã đóng sẽ nhảy sang kỳ mở kế.

**96. Xoá tài khoản kế toán đã có phát sinh?**
Không — `ACCOUNTING_ACCOUNT_IN_USE`.

## N. Gói & Thanh toán

**97. Có mấy gói và giá?**
Trial (0đ, 14 ngày), Starter (99k/tháng), Pro (199k/tháng), Business (399k/tháng). Năm = 10× tháng (tặng 2 tháng).

**98. Có giới hạn số đơn không?**
Không — gói chỉ khác ở số gian hàng + tính năng nâng cao.

**99. Cổng thanh toán nào?**
SePay (chuyển khoản VietQR), VNPay (redirect). MoMo đang phát triển.

**100. SePay thanh toán thế nào?**
Hiện VietQR + nội dung chuyển khoản; bạn chuyển khoản rồi bấm "Tôi đã chuyển"; hệ thống tự xác nhận khi nhận được tiền (poll mỗi 5s).

**101. Hết hạn gói có bị khoá dữ liệu không?**
Không — sau 7 ngày grace sẽ rơi về trial miễn phí vĩnh viễn. Dữ liệu của bạn vẫn còn; chỉ mất tính năng nâng cao + gian hàng dư.

**102. Nâng cấp giữa kỳ có hoàn tiền phần cũ không?**
Không (v1 không proration) — tạo hoá đơn full mới; gói cũ chạy hết kỳ rồi đổi.

**103. Hạ gói được không?**
Không hạ thẳng xuống gói trả phí thấp hơn (`DOWNGRADE_NOT_ALLOWED`). Có thể huỷ để chạy hết kỳ rồi về trial.

**104. "Over-quota lock" là gì?**
Nếu sau khi hạ gói bạn còn nhiều gian hàng hơn hạn mức, sau 2 ngày hệ thống chặn các thao tác ghi cho tới khi nâng gói hoặc gỡ bớt gian hàng (dữ liệu không bị khoá).

**105. Voucher dùng ở đâu?**
Khi checkout có thể nhập mã voucher; super-admin cấp/quản lý voucher.

## O. Đồng bộ & Hệ thống

**106. Nhật ký đồng bộ xem ở đâu?**
`/sync-logs` — tab Lần đồng bộ và Webhook; có nút redrive/retry khi lỗi.

**107. Webhook có đáng tin không, có cần polling?**
Webhook được xác minh chữ ký nhưng vẫn luôn có polling dự phòng; webhook chỉ là tín hiệu, hệ thống luôn fetch lại chi tiết trước khi lưu.

**108. Tiền hiển thị có lẻ không?**
Không — tiền là số nguyên VND, không dùng số thực, tránh sai số làm tròn.

**109. Múi giờ hiển thị?**
API trả UTC; giao diện hiển thị theo Asia/Ho_Chi_Minh.

**110. Dữ liệu giữa các gian hàng có lẫn nhau không?**
Không — mọi truy vấn tự lọc theo tenant; không có truy vấn xuyên tenant.

**111. Thêm sàn/ĐVVC/cổng thanh toán mới có phải sửa lõi không?**
Không — kiến trúc Connector + Registry: thêm 1 connector + 1 dòng đăng ký + 1 khối cấu hình; lõi không biết tên sàn cụ thể.

**112. AI có gửi dữ liệu nhạy cảm ra ngoài không?**
Không trực tiếp — SĐT/email/số tài khoản được che (PII redaction) trước khi gửi tới mô hình ngôn ngữ; chi phí token được ghi log.

> Cần xử lý lỗi cụ thể? Xem [troubleshooting.md](troubleshooting.md).
