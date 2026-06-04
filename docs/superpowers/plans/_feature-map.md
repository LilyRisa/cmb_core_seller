# Bản đồ tính năng (nội bộ) — nguồn viết support_doc

> Distilled từ 8 agent đọc source (2026-06-04). Dùng để viết bài; KHÔNG phát hành cho user.
> Khi viết bài: chỉ giữ tên menu/nút tiếng Việt + lời mô tả; bỏ mọi chi tiết kỹ thuật.

## 0. Vai trò & gói (chung)
- **7 vai trò**: Chủ sở hữu (toàn quyền + thanh toán + xoá/chuyển gian hàng) · Quản trị (toàn nghiệp vụ, không billing/xoá tenant) · NV xử lý đơn (đơn + in tem + xem khách + trả tin đơn + xem tồn/kênh) · NV kho (tồn: điều chỉnh/chuyển/kiểm kê, sửa sản phẩm, nhận hàng NCC, ghép SKU + đẩy tồn) · NV chăm sóc khách (hộp thư, trả tin, mẫu tin, xem đơn/khách) · Kế toán (tài chính/đối soát/báo cáo/sổ, không cấu hình) · Chỉ xem. Giao diện tự ẩn nút khi thiếu quyền.
- **Gói**: Dùng thử (14 ngày) / Starter 99k — 2 gian hàng · Pro 199k — 5 gian hàng (mua hàng, giá vốn FIFO, lợi nhuận, đối soát, hộp thư, đề xuất nhập, kế toán cơ bản) · Business 399k — 10 gian hàng (+ đăng bán hàng loạt, kịch bản tự động, AI tự trả lời, kế toán nâng cao, hỗ trợ ưu tiên). Năm = 10 tháng. Hết hạn → 7 ngày grace → về dùng thử miễn phí vĩnh viễn, **dữ liệu không bị khoá**. Vượt số gian hàng quá 2 ngày sau khi hạ gói → tạm khoá thao tác ghi. Chỉ **Chủ sở hữu** thanh toán.

## 1. Bắt đầu (Đăng nhập/đăng ký/gian hàng/nhân sự)
- **Đăng nhập**: Email + Mật khẩu + "Ghi nhớ đăng nhập" + "Quên mật khẩu?". Lỗi: "Email hoặc mật khẩu không đúng".
- **Đăng ký**: Tên + Tên gian hàng (tuỳ chọn) + Email + Mật khẩu (≥8 ký tự, có hoa/thường/số/ký tự đặc biệt) + xác nhận. Tạo gian hàng + tự bật dùng thử 14 ngày + gửi email xác thực.
- **Quên mật khẩu**: nhập email → gửi liên kết đặt lại (60 phút).
- **Chuyển gian hàng**: ô chọn ở đầu trang (tên gian hàng · vai trò) → chọn → trang tải lại.
- **Hồ sơ cá nhân** (Cài đặt): sửa tên/email (đổi email cần nhập mật khẩu hiện tại); đổi mật khẩu (nhập mật khẩu hiện tại + mới). Lỗi: "Mật khẩu hiện tại không đúng".
- **Nhân sự** (Cài đặt → Nhân viên & vai trò): chỉ Chủ sở hữu/Quản trị thêm được; nhập Email + chọn vai trò → "Thêm". Người được thêm phải đã có tài khoản (luồng mời email "sắp có"). Danh sách: Tên/Email/Vai trò.

## 2. Bảng điều khiển
- Bộ lọc 7/30/90 ngày + nút "Làm mới".
- **4 thẻ KPI**: Doanh thu · Số đơn · Lợi nhuận ước tính · GMV trung bình/đơn (mỗi thẻ có % so kỳ trước + biểu đồ mini). Chưa kết nối gian hàng → cảnh báo + link kết nối.
- **Biểu đồ doanh thu/lợi nhuận theo ngày** + **"Việc cần làm"** (6 mục): Đơn chờ xử lý (bấm Chuẩn bị hàng) · Đơn chờ bàn giao ĐVVC · Đơn cần in phiếu · Đơn chưa liên kết SKU · Đơn có vấn đề · Gian hàng cần kết nối lại. Mỗi mục có số + link nhanh.
- **Top sản phẩm bán chạy** + **Doanh thu theo sàn**.
- **Thống kê kế toán** (nếu đã khởi tạo): Tiền & ngân hàng, Phải thu, Phải trả, Doanh thu thuần kỳ, Lợi nhuận gộp kỳ, Lãi/lỗ kỳ.
- **Trạng thái hệ thống**: Gian hàng đã kết nối, Tổng đơn, Lợi nhuận thực (FIFO).

## 3. Gian hàng (kết nối sàn)
- Menu **Gian hàng**. Nút **Kết nối TikTok / Kết nối Lazada** (mở cửa sổ đăng nhập sàn). **Shopee** = nút mờ, "chờ duyệt/Phase 4".
- Trạng thái thẻ: Hoạt động / Token hết hạn (nút **Cấp quyền lại**) / Đã ngắt kết nối / Tạm dừng.
- Nút **Đồng bộ** (đồng bộ lại); kết nối lần đầu tự kéo đơn 90 ngày. Đồng bộ idempotent: "Đơn đã có sẽ được cập nhật trạng thái; không tạo bản sao".
- **Xóa kết nối**: gõ tên gian hàng để xác nhận; xoá đơn + liên kết SKU của gian hàng đó (nhả tồn đang giữ).
- Lỗi hay gặp: Token hết hạn → Cấp quyền lại. Lazada chặn IP (cần whitelist IP máy chủ trên Lazada Open Platform). Lazada chưa Subscribe app. TikTok thiếu scope. "Gian hàng này đã kết nối ở workspace khác".

## 4. Đơn hàng & giao hàng
- Menu **Đơn hàng**. Tab theo trạng thái: Chờ thanh toán → Chờ xử lý → Đang xử lý → Chờ bàn giao → Đang vận chuyển → Đã giao → Hoàn tất; nhánh: Giao thất bại, Đang trả/hoàn, Đã trả/hoàn, Đã huỷ. Thêm chip lọc: Lỗi (đơn có vấn đề), Âm tồn, Sàn, ĐVVC, Gian hàng, Phiếu giao hàng.
- **Chuẩn bị hàng (lấy phiếu)**: chọn đơn → bấm; đơn thủ công sẽ hỏi chọn ĐVVC; đơn sàn tự gọi sàn lấy vận đơn. **Chặn** khi: đã có vận đơn, đơn không ở trạng thái chuẩn bị, có SKU âm tồn, hoặc trộn đơn sàn + đơn thủ công.
- **Nhận phiếu giao hàng**: lấy/đợi tem (sàn cấp hoặc render phiếu cho đơn thủ công); bấm lại nếu lần trước chưa lấy được ("Chưa lấy được phiếu lần này — bấm lại; không cần thao tác trên app sàn").
- **In phiếu giao hàng**: chỉ in chung được khi cùng sàn + cùng ĐVVC; đơn thủ công chọn mẫu phiếu; hỏi số bản + "Đánh dấu đã in". Cảnh báo in lại nếu đã in (tránh trùng).
- **Đánh dấu sẵn sàng bàn giao** (đã đóng gói) → **Bàn giao ĐVVC**. "Đã đóng gói" CHƯA trừ tồn; trừ tồn khi sang **Đang vận chuyển**.
- **Liên kết SKU**: đơn có sản phẩm sàn chưa ghép → "Đơn có vấn đề"; bấm liên kết để ghép.
- **Đơn thủ công** (Tạo đơn): Khách (tên/SĐT) · Người nhận (tên/SĐT/địa chỉ Tỉnh-Quận-Phường) · Sản phẩm (tìm SKU hoặc thêm ngoài danh mục) · Thanh toán (tiền hàng, phí ship, giảm giá, phụ thu, tiền chuyển khoản → tự tính COD) · Ghi chú/đính kèm/tag · chọn ĐVVC. Lưu ở trạng thái Chờ xử lý/Đang xử lý.
- **Sửa đơn / Huỷ đơn**: chỉ đơn thủ công, chưa ở trạng thái cuối. Huỷ → nhả tồn.
- Lỗi: âm tồn không Chuẩn bị hàng được; không in chung nhiều sàn/ĐVVC; sàn không cấp phiếu (vd Lazada DBS) → in phiếu thủ công.

## 5. Hoàn & Hủy
- Menu **Hoàn & Hủy**. Loại: Hủy đơn / Trả hàng / Hoàn tiền. Trạng thái: Chờ xử lý → Đã duyệt/Từ chối → Đang xử lý → Hoàn tất.
- Bộ lọc: Chờ duyệt / Đang mở / Tất cả; theo loại.
- Hành động: **Duyệt** / **Từ chối** (khi Chờ xử lý). Hệ thống tự chuyển tiếp khi sàn xác nhận.
- Tồn: huỷ trước giao → nhả tồn; hoàn sau giao + hàng về → cộng tồn lại.

## 6. Khách hàng
- Menu **Khách hàng**. Sổ khách (gộp theo SĐT), điểm/đánh giá uy tín (ok/theo dõi/rủi ro/xấu), lịch sử đơn của khách. (Tự liên kết khi tạo đơn có SĐT.)

## 7. Sản phẩm & SKU
- Khu **Tồn kho** có các tab: Tồn theo SKU / Danh mục SKU / Liên kết SKU (sàn) / Phiếu kho. (Menu "Sản phẩm & SKU" dẫn vào danh mục SKU.)
- **Mã sản phẩm (SKU)** = đơn vị tồn, nguồn chuẩn (1 tenant). **Sản phẩm trên sàn (listing)** = cách hiển thị trên từng sàn. **Ghép SKU** = nối listing với SKU (bắt buộc để trừ tồn & đẩy tồn).
- **Thêm SKU**: Mã SKU (bắt buộc, duy nhất, không sửa) · Tên · (SPU/Barcode/đơn vị/giá vốn/giá bán tham khảo/ảnh/tồn ban đầu tuỳ chọn).
- **Ghép SKU** (tab Liên kết SKU): lọc chưa ghép/đã ghép → "Ghép SKU" → chọn SKU có sẵn hoặc "Tạo nhanh SKU mới". Combo/bundle: 1 listing ghép nhiều SKU với số lượng/đơn → tồn combo = số đóng được ít nhất từ thành phần.
- Chưa ghép → đơn "có vấn đề" + không đẩy tồn.

## 8. Tồn kho
- **Tồn khả dụng = Tồn thực − Đang giữ − Tồn an toàn** (số đẩy lên sàn). Cột: Thực có / Đang giữ / An toàn / Khả dụng (xanh >5, vàng ≤5, đỏ ≤0) / Tồn sàn.
- Diễn biến: Chờ xử lý/Đang xử lý → giữ tồn; huỷ trước giao → nhả; Đang vận chuyển → trừ tồn (xuất theo FIFO, chốt giá vốn); hoàn + hàng về → cộng lại.
- **Điều chỉnh** tồn (+nhập/−xuất + ghi chú). **Phiếu nhập/xuất hàng loạt** (chọn loại, kho, ghi chú, nhiều dòng SKU+số lượng).
- **Đẩy tồn lên sàn**: chọn SKU → "Đẩy tồn lên sàn (N)"; trạng thái sàn cập nhật (ok/lỗi). Listing có thể khoá đẩy.
- **Giá vốn FIFO**: lớp giá tạo khi nhận hàng; xuất trừ lớp cũ trước; chốt khi giao (không đổi khi huỷ sau).

## 9. Mua hàng (Pro+)
- **Đề xuất nhập hàng**: dựa tốc độ bán (N ngày) + tồn khả dụng + hàng đang về + tồn an toàn + lead time + đệm an toàn. Mức: Khẩn cấp/Sắp hết/Đủ hàng. Chọn dòng → **Tạo PO nháp** (gom theo NCC).
- **Nhà cung cấp**: Thêm NCC (Tên/Mã tự sinh/SĐT/Email/MST/Địa chỉ/Công nợ/ghi chú). Bảng giá nhập theo SKU (Giá nhập/MOQ/áp dụng từ/đặt mặc định). Không xoá được nếu còn PO mở.
- **Đơn mua hàng (PO)**: Tạo nháp (NCC + Kho nhập + dòng SKU/SL đặt/giá nhập) → **Chốt PO** (khoá giá, không sửa được) → **Nhận hàng** (tạo phiếu nhập, xác nhận → cộng tồn + lớp giá FIFO + tự lên sổ kế toán). Trạng thái: Nháp/Đã chốt/Nhận một phần/Nhận đủ/Đã huỷ. Lỗi: "nhận vượt số còn lại", "chỉ sửa PO ở nháp".

## 10. Tin nhắn
- Menu **Tin nhắn**: Hộp thư / Kết nối kênh / Mẫu tin / Tự động trả lời / Kịch bản tự động / AI training (+ Cài đặt AI trong Cài đặt).
- **Hộp thư** (hộp thư hợp nhất): tab Sàn / Facebook; với Facebook có tab phụ Tất cả/Tin nhắn/Bình luận. Lọc: đã đọc/chưa đọc, đang mở/đã xong/đã chặn, có SĐT, trang FB, thẻ. Thao tác bình luận: Thích / Nhắn riêng (1 lần/bình luận) / Xoá. Soạn tin: text + đính kèm + chèn mẫu (/phimtat) + emoji + AI gợi ý.
- **Kết nối kênh**: Kết nối Facebook Page (chọn page); Lazada IM (app "IM ERP" riêng); TikTok dùng kết nối Gian hàng. Trạng thái page: Đang hoạt động / Hết hạn token / Comment: cần cấp quyền.
- **Mẫu tin**: Mã/Tên/Phím tắt/Nội dung có biến ({{buyer.name}}, {{order.code}}…).
- **Tự động trả lời** — kiểu: Tin đầu / Theo lịch (vắng mặt) / Theo trạng thái đơn / NV chưa trả lời sau N phút / Từ khoá / Mọi bình luận. Áp cho tin nhắn/bình luận/cả hai. Nội dung: văn bản / mẫu / AI tự soạn. Chống spam: thời gian chờ (cooldown) + ưu tiên.
- **Kịch bản tự động** (Business): trình dựng kéo-thả; trigger Tin đầu/Từ khoá/Mọi tin/Mọi bình luận; trạng thái Nháp/Đang chạy/Tạm dừng/Lưu trữ.
- **AI training**: thêm tài liệu (gõ tay / URL-Google Sheets / tải file PDF-Word-Excel-CSV-TXT ≤25MB). Trạng thái: Đang xử lý/Sẵn sàng/Lỗi. Xem nội dung/Tải lại/Xoá.
- **Cài đặt AI**: chọn nhà cung cấp AI; bật **AI gợi ý** (nhân viên duyệt; cần Business); **AI tự động** (Sàn / Facebook riêng; tự gửi; cần Business). Tin nhạy cảm (khiếu nại/hoàn tiền/gấp/pháp lý/thô tục) → chuyển nhân viên, không tự gửi.
- **Facebook**: chỉ gửi tự do trong 24 giờ kể từ tin cuối của khách; quá hạn cần thẻ tin nhắn (ô soạn tự gắn). Nhắn riêng bình luận 1 lần/bình luận. Thích bình luận cần Trang được cấp quyền tương tác.
- Lỗi: Lazada chat 0 tin = app IM thiếu quyền nhóm IM; "Comment: cần cấp quyền" = kết nối lại page; ngoài cửa sổ 24h.

## 11. Quảng cáo (Facebook Ads — đang phát triển)
- Menu **Quảng cáo**. Chưa kết nối → "Chưa kết nối tài khoản quảng cáo" + nút **Kết nối Facebook Ads**.
- **Tạo quảng cáo** → trình 6 bước: 1) **Mục tiêu** (tên chiến dịch + Tin nhắn/Tương tác/Truy cập web) · 2) **Ngân sách** (hằng ngày, VND, ngày bắt đầu) · 3) **Đối tượng** (quốc gia, độ tuổi, giới tính, sở thích, quy mô ước tính) · 4) **Vị trí** (Tự động/Thủ công: Facebook Feed/Reels/Stories/Instagram). **Bước 5 (Nội dung) & 6 (Xuất bản) CHƯA hoàn thiện — KHÔNG viết.** Tự lưu nháp ("Đã lưu").
- Báo cáo kiểu Ads Manager (chiến dịch/nhóm/quảng cáo), tuỳ chỉnh cột, lọc ngày.
- Chỉ Chủ sở hữu/Admin. Cần kết nối tài khoản Facebook Ads (quyền ads_management). Lỗi: "Tạo quảng cáo chưa được bật cho tài khoản này…".
- ⚠️ Viết bài ở mức: kết nối + tạo nháp 4 bước + xem báo cáo; nêu rõ phần xuất bản đang hoàn thiện.

## 12. Đối soát & lợi nhuận (Pro+)
- Menu **Đối soát sàn**. Kéo đối soát từ sàn (chọn sàn + khoảng ngày) → hệ thống khớp với đơn → trạng thái Chờ đối chiếu/Đã đối chiếu/Lỗi. Phí thật theo 10 nhóm (hoa hồng, phí thanh toán, voucher, phí ship, điều chỉnh, hoàn…).
- **Lợi nhuận = doanh thu − giá vốn − phí − ship − giảm − khác**. Chỉ tính đơn đã ship + không huỷ + đã đối chiếu.

## 13. Báo cáo (Pro cho lợi nhuận)
- Menu **Báo cáo**: Doanh thu · Lợi nhuận · Top sản phẩm bán chạy. Lọc: khoảng ngày, theo ngày/tuần/tháng, theo sàn. **Xuất CSV** (mở đúng tiếng Việt trong Excel). Quyền xem/xuất riêng.

## 14. Kế toán (TT133, Pro cơ bản / Business nâng cao)
- Lần đầu: banner "Module Kế toán chưa được khởi tạo" → **Khởi tạo TT133** (tạo hệ thống tài khoản + kỳ + quy tắc hạch toán; an toàn gọi lại).
- **Sổ nhật ký**: danh sách bút toán (tự động + tay); Tạo bút toán tay (ngày, diễn giải, các dòng TK Nợ/Có cân bằng); Đảo bút toán (sửa = ghi đảo). Lọc theo kỳ/nguồn/từ khoá.
- **Hệ thống TK**: cây tài khoản TT133; thêm/sửa/ẩn TK (quyền cấu hình).
- **Cân đối phát sinh**: dư đầu + phát sinh Nợ/Có + dư cuối theo kỳ; "Tính lại số dư".
- **Công nợ phải thu**: tuổi nợ theo khách; **Phiếu thu** (tạo → xác nhận ghi sổ).
- **Công nợ phải trả**: tuổi nợ theo NCC; **Hóa đơn NCC** + **Phiếu chi**.
- **Quỹ & Ngân hàng**: tạo quỹ/TK ngân hàng; **Import sao kê** (CSV) → khớp dòng.
- **Báo cáo tài chính**: Cân đối kế toán (B01), Kết quả kinh doanh (B02), Sổ chi tiết TK; Export MISA.
- **Kỳ kế toán**: Mở → Đóng → Khoá. Ghi vào kỳ đã đóng bị từ chối ("Kỳ kế toán đã đóng nên không ghi thêm được"); đảo bút toán của kỳ đóng post vào kỳ mở kế tiếp; khoá = vĩnh viễn. Tạo kỳ cho năm khác.
- Tự lên sổ khi: nhận hàng, chuyển kho, kiểm kê, ship, đối soát.

## 15. Gói thuê bao & thanh toán
- Khu Cài đặt → **Gói & nâng cấp**. Thẻ gói hiện tại (trạng thái + ngày còn lại + tiến độ gian hàng X/Y). Bảng so sánh 4 gói. **Chọn gói này** → modal: chu kỳ Tháng/Năm (giảm 2 tháng), tổng tiền, phương thức **SePay (chuyển khoản QR)** / **VNPay** / MoMo (sắp có) → "Tạo hoá đơn & thanh toán". Hoá đơn gần đây (Chờ thanh toán/Đã thanh toán). **Huỷ gói** (chỉ Chủ sở hữu). Cảnh báo vượt hạn mức (OverQuotaBanner) + khoá ghi khi quá grace.

## 16. Nhật ký đồng bộ
- Menu **Nhật ký đồng bộ**. Tab **Lần đồng bộ** (lọc gian hàng/loại: Định kỳ-Lấy lại lịch sử-Quét đơn tồn đọng-Webhook / trạng thái; cột Gian hàng/Loại/Trạng thái/Kết quả nhận-mới-cập nhật-bỏ qua-lỗi/Thời lượng; **Chạy lại**). Tab **Webhook** (sự kiện sàn, chữ ký Hợp lệ/Sai, trạng thái Chờ/Đã xử lý/Bỏ qua/Thất bại; **Xử lý lại**). Tự làm mới.

## 17. Cài đặt
- Khu **Cài đặt** (các nhóm): Tài khoản (Hồ sơ, Thông tin gian hàng, Gói & nâng cấp) · Nhân sự & phân quyền (Nhân viên & vai trò) · Kết nối (Đơn vị vận chuyển, Gian hàng & module phụ trợ) · Vận hành (Cài đặt đơn, Mẫu in, Mẫu phiếu giao hàng, Quy tắc hạch toán, Nhật ký thao tác — một số "sắp có"). Một số mục chỉ Chủ sở hữu/Quản trị sửa.

## 18. Trợ giúp & CSKH
- Nút **Trợ giúp** nổi (kéo-thả, nhớ vị trí), badge đỏ + âm thanh khi có tin CSKH mới. Mở ra 2 tab:
  - **Hỏi AI**: hỏi cách dùng; trả lời kèm thẻ nguồn. VD "Làm sao kết nối gian hàng?", "Cách in tem hàng loạt?". Ngoài phạm vi → gợi sang Hỏi CSKH.
  - **Hỏi CSKH**: chat với nhân viên; đính kèm ảnh/video/PDF/Office/TXT (tối đa 5 tệp/tin); cuộc đã đóng → gửi tin mở cuộc mới; mở tab → đánh dấu đã đọc.
- Dùng được cho mọi gói.
