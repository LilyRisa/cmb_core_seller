# Quy tắc nghiệp vụ & Logic (Business Rules)

> Giải thích cách hệ thống hoạt động bằng ngôn ngữ dễ hiểu. Tiền = **số nguyên đồng**. Nhãn hiển thị bằng tiếng Việt. Khi chỉ đường, dùng tên menu/nút (không dùng đường dẫn URL).

---

## 1. Trạng thái đơn hàng

Mỗi đơn có một trạng thái (nhãn tiếng Việt) và giữ kèm trạng thái gốc của sàn để đối chiếu khi cần. Mỗi lần đổi trạng thái đều được ghi lại lịch sử.

### Các trạng thái

| Nhãn | Ý nghĩa |
|---|---|
| Chờ thanh toán | Đã tạo, chưa thanh toán (đơn online chờ trả tiền) |
| Chờ xử lý | Đã thanh toán / xác nhận COD, **chưa lấy tem**. Bấm **Chuẩn bị hàng** để lấy tem |
| Đang xử lý | **Đã tạo/in vận đơn** — đang đóng gói + quét nội bộ |
| Chờ bàn giao | **Đã gói + quét** — chỉ tới được qua thao tác nội bộ |
| Đang vận chuyển | Đã bàn giao đơn vị vận chuyển / đang đi đường |
| Đã giao | Đã giao tới người nhận |
| Hoàn tất | Qua hạn khiếu nại / đã đối soát. **Kết thúc** |
| Giao thất bại | Giao hỏng, chờ giao lại |
| Đang trả/hoàn | Đang xử lý trả/hoàn |
| Đã trả/hoàn | Đã hoàn tiền và/hoặc nhận lại hàng. **Kết thúc** |
| Đã huỷ | Huỷ trước khi giao. **Kết thúc** |

Cờ phụ: tình trạng thanh toán (chưa trả / đã trả / đã hoàn / hoàn một phần), đơn tách nhiều kiện, và **đơn có vấn đề**.

### Các bước chuyển hợp lệ (do người dùng)

```
Chờ thanh toán   → Chờ xử lý, Đã huỷ
Chờ xử lý        → Đang xử lý, Chờ bàn giao, Đã huỷ
Đang xử lý       → Chờ bàn giao, Đã huỷ
Chờ bàn giao     → Đang vận chuyển, Đã huỷ
Đang vận chuyển  → Đã giao, Giao thất bại, Đang trả/hoàn
Giao thất bại    → Đang vận chuyển, Đang trả/hoàn, Đã huỷ
Đã giao          → Hoàn tất, Đang trả/hoàn
Hoàn tất         → Đang trả/hoàn
Đang trả/hoàn    → Đã trả/hoàn
```

### Quy tắc

- **Dữ liệu sàn là nguồn chuẩn**: cập nhật từ sàn luôn được ghi nhận. Nếu sàn báo lùi trạng thái một cách bất thường (lùi nhiều bậc, hoặc lùi khỏi trạng thái đã kết thúc), đơn được gắn cờ **đơn có vấn đề** để bạn kiểm tra.
- **Thao tác người dùng** phải theo bước hợp lệ. Người dùng **không** tự đổi trạng thái lõi của đơn sàn (chỉ gắn thẻ/ghi chú), trừ các bước sàn cho phép (xác nhận đơn, tạo vận đơn) — các bước này báo sàn trước.
- Đặt lại đúng trạng thái cũ = không làm gì (không ghi lịch sử thừa).
- **Chờ xử lý → Đang xử lý** khi bấm **Chuẩn bị hàng** — **bị chặn nếu có mã hàng âm kho**.
- **Đang vận chuyển** (thời điểm trừ tồn) xảy ra khi bàn giao thực / đơn vị vận chuyển lấy hàng.
- **Hoàn tất** chỉ đặt khi sàn báo hoàn tất — không tự nhảy từ Đã giao.

---

## 2. Tồn kho

**Nguyên tắc cốt lõi: mã sản phẩm trong kho (SKU) là nguồn tồn chuẩn duy nhất.** Sản phẩm rao trên sàn chỉ "soi" theo mã đã ghép. Mỗi thay đổi tồn đều được ghi lại đầy đủ. Đẩy tồn lên sàn là hệ quả tự động.

### Khái niệm

- **Mã sản phẩm (SKU)**: đơn vị tồn nhỏ nhất, mã duy nhất trong mỗi nhà bán.
- **Tồn theo kho**: gồm **Tồn thực**, **Đang giữ** và **Tồn an toàn**; **Tồn khả dụng = Tồn thực − Đang giữ − Tồn an toàn** (không nhỏ hơn 0) — đây là số đẩy lên sàn.
- **Ghép SKU**: nối sản phẩm trên sàn với một/nhiều mã trong kho kèm số lượng:
  - **Ghép đơn**: 1 sản phẩm sàn ↔ 1 mã (số lượng thường 1, hoặc N cho "lốc N").
  - **Combo**: 1 sản phẩm sàn ↔ nhiều mã; tồn của sản phẩm sàn = số combo đóng được ít nhất từ các thành phần.
  - Một mã có thể đứng sau nhiều sản phẩm ⇒ đồng bộ tồn chéo các sàn.

### Tự khớp & chưa ghép

- Sản phẩm sàn mới đồng bộ về mà chưa ghép → nhóm **Chưa ghép SKU**.
- **Tự khớp**: nếu mã người bán đặt trên sàn trùng mã trong kho (sau khi chuẩn hoá) ⇒ gợi ý ghép đơn số lượng 1.
- **Sản phẩm chưa ghép KHÔNG đẩy tồn**; đơn của nó bị gắn cờ **đơn có vấn đề** (có sản phẩm chưa ghép).

### Vòng đời biến động tồn

| Sự kiện | Tác động (mỗi dòng đơn có mã sản phẩm) |
|---|---|
| Đơn vào Chờ xử lý / Đang xử lý | Đang giữ tăng |
| Huỷ / hoàn **trước** khi giao | Đang giữ giảm (nhả) |
| Đơn vào Đang vận chuyển | Đang giữ giảm, Tồn thực giảm |
| Hoàn **sau** khi giao, hàng về kho | Tồn thực tăng |
| Nhận hàng từ nhà cung cấp | Tồn thực tăng + tạo lớp giá vốn |
| Chuyển kho | Giảm ở kho đi, tăng ở kho đến |
| Kiểm kê lệch | Điều chỉnh về thực tế |
| Điều chỉnh tay | Theo người dùng |

- **Chống bán âm**: hệ thống khoá khi cập nhật để hai người thao tác cùng lúc không sai số. Nếu sàn lỡ bán quá tay (thiếu hàng), đơn vẫn được giữ (đơn thật), tồn có thể âm tạm thời kèm cảnh báo, và số đẩy lên sàn = 0.
- **Combo**: giữ/xuất ảnh hưởng **mọi** mã thành phần theo số lượng.
- Mỗi biến động đều lưu lại để tra cứu.

### Đẩy tồn lên sàn

Khi tồn đổi, hệ thống gom các thay đổi gần nhau (khoảng 5–15 giây) rồi tính tồn khả dụng và đẩy lên từng sản phẩm trên sàn (ghép đơn = chia theo số lượng; combo = số đóng được ít nhất). Tồn an toàn đã trừ trước khi đẩy. Có thể **ghim tồn** một sản phẩm để không tự đẩy. Định kỳ hệ thống đối chiếu tồn thực trên sàn, cảnh báo và đẩy lại khi lệch nhưng **không ghi đè kho** (kho luôn là chuẩn).

### Giá vốn nhập trước xuất trước (FIFO)

- Mỗi lần nhận hàng tạo một **lớp giá vốn** (số lượng + giá nhập).
- Khi đơn được giao, hệ thống tiêu thụ các lớp theo nhập trước xuất trước và **chốt** giá vốn vào đơn (cố định, không đổi về sau).
- Thiếu lớp giá vốn ⇒ dùng giá vốn ước tính (bình quân hoặc mới nhất) và đánh dấu là ước tính.
- Lợi nhuận của đơn đã giao dùng giá vốn thật đã chốt; đơn chưa giao dùng ước tính.

---

## 3. Gói thuê bao

### 4 gói (giá VND; năm = 10 tháng)

| Gói | Tháng | Năm | Gian hàng | Tính năng |
|---|---|---|---|---|
| Dùng thử | 0 | 0 | 2 | 14 ngày, như Starter |
| Starter | 99.000 | 990.000 | 2 | cơ bản |
| Pro | 199.000 | 1.990.000 | 5 | + mua hàng, giá vốn FIFO, báo cáo lợi nhuận, đối soát, đề xuất nhập, kế toán cơ bản, hộp thư tin nhắn |
| Business | 399.000 | 3.990.000 | 10 | + đăng bán hàng loạt, kịch bản tự động, kế toán nâng cao, AI tự trả lời, hỗ trợ ưu tiên |

**Không giới hạn số đơn** — gói chỉ khác ở số gian hàng + tính năng nâng cao. Khách cần hơn 10 gian hàng thì liên hệ CMBcoreSeller mở riêng.

### Vòng đời gói

```
Đang dùng thử → Đang hoạt động (đã trả) | Hết hạn (hết dùng thử chưa trả)
Đang hoạt động → Quá hạn thanh toán | Đã huỷ | gia hạn tiếp
Quá hạn        → Đang hoạt động (đã trả) | Hết hạn (sau 7 ngày gia hạn)
Hết hạn        → tự về gói Dùng thử miễn phí vĩnh viễn
```

- Nhà bán mới tự có 14 ngày dùng thử.
- **Gia hạn (grace) = 7 ngày.** Quá hạn quá 7 ngày ⇒ hết hạn + tự về dùng thử miễn phí vĩnh viễn. **Dữ liệu không bao giờ bị khoá** — chỉ mất tính năng nâng cao + gian hàng dư.
- **Huỷ**: gói chạy hết kỳ hiện tại (không hoàn tiền).
- **Nâng cấp giữa kỳ**: tạo hoá đơn mới, gói cũ chạy hết kỳ rồi đổi (chưa chia tiền theo ngày). Không hạ thẳng xuống gói trả phí thấp hơn; muốn xuống thì huỷ để về dùng thử.

### Giới hạn

- **Số gian hàng**: kết nối thêm khi đã đủ → hệ thống nhắc nâng gói.
- **Tính năng nâng cao**: vào tính năng ngoài gói → nhắc nâng cấp.
- **Vượt số gian hàng**: nếu sau khi hạ gói còn nhiều gian hàng hơn mức cho phép, hệ thống cho 2 ngày để gỡ bớt; quá hạn này thì tạm khoá các thao tác ghi (thêm/sửa/xoá) cho tới khi nâng gói hoặc bớt gian hàng — riêng thanh toán, đăng nhập và xoá gian hàng vẫn mở.

### Thanh toán

Cổng: SePay (chuyển khoản qua VietQR), VNPay (chuyển hướng), MoMo (đang phát triển). Hệ thống nhận đúng hoá đơn theo nội dung chuyển khoản; trả thiếu thì hoá đơn chưa kích hoạt cho tới khi đủ; trả dư được ghi nhận, không tự hoàn. Trả đủ ⇒ kích hoạt gói.

---

## 4. Hoàn & Hủy (sau bán)

Yêu cầu sau bán là mục **riêng có trạng thái riêng**, không phải trạng thái đơn.

- **Trạng thái**: chờ duyệt · đã duyệt · từ chối · đang xử lý · đã hoàn tiền · khách rút · đã đóng. Loại: hủy / trả hàng / hoàn tiền.
- **Lấy dữ liệu hai chiều**: kiểm tra định kỳ (khoảng 15 phút, truy ngược 90 ngày) + tín hiệu từ sàn. Vì tín hiệu không đủ ⇒ luôn lấy lại chi tiết trước khi lưu.
- **Không trùng**: mỗi yêu cầu nhận diện duy nhất; bỏ qua nếu bản cập nhật cũ hơn.
- **Liên kết đơn**: nối với đơn gốc; cho phép để trống nếu đơn gốc chưa đồng bộ (nối sau).
- **Hiện tại không tự đụng tồn/tài chính** — chỉ lưu số tiền hoàn để hiển thị và gắn cờ. Mặc định không đổi trạng thái đơn.
- **Quyền**: xem = ai xem được đơn; **Duyệt/Từ chối** = Chủ sở hữu/Quản trị/NV xử lý đơn.

---

## 5. Xử lý & giao hàng

### Trạng thái vận đơn

`chờ tạo → đã tạo (có mã + tem) → đã đóng gói → đã bàn giao → đang vận chuyển → đã giao | thất bại | đã trả | đã huỷ`.

Ảnh hưởng tới đơn: đã tạo/đã đóng gói ⇒ đơn Chờ bàn giao; đã bàn giao/đang vận chuyển ⇒ đơn Đang vận chuyển; đã giao ⇒ đơn Đã giao; thất bại ⇒ Giao thất bại. **"Đã đóng gói" KHÔNG trừ tồn** (hàng còn trong kho) — trừ tồn ở bàn giao. Màn xử lý chia 3 chặng: chuẩn bị → đóng gói → bàn giao.

### Hai cách giao hàng

- **Dùng dịch vụ của sàn**: hệ thống lấy phương án vận chuyển và tem từ sàn. Một đơn có thể tách nhiều kiện.
- **Dùng đơn vị vận chuyển riêng** (GHN, GHTK, J&T...): đơn thủ công dùng cách này.

### In ấn

- **Tem**: không bao giờ vẽ lại tem của đơn vị vận chuyển — dùng đúng file gốc để giữ nguyên mã vạch. In hàng loạt gộp PDF sắp theo đơn vị vận chuyển → đơn. Khổ A6 (nhiệt) / A4 (4 tem/trang)...
- **Phiếu lấy hàng** (gom theo mã sản phẩm qua nhiều đơn) / **Phiếu đóng gói** (mỗi đơn một phiếu): tự tạo file PDF.
- Theo dõi số lần in; từ lần in thứ 2 có hỏi xác nhận. File in giữ ~90 ngày, in lại trả về **cùng file**.

### Quét đóng gói / bàn giao

Quét mã vạch vận đơn → tìm đúng đơn **trong nhà bán của bạn** → (tuỳ chọn) quét từng mã sản phẩm → đánh dấu đã gói/đã bàn giao. Chống nhầm nhà bán + chống quét trùng (quét lần 2 = bỏ qua + thông báo). Tồn bị trừ khi bàn giao (Đang vận chuyển).

### Cấu hình đáng chú ý

- **Lazada — Tự động chuyển chờ bàn giao sau khi in** (mỗi shop, mặc định tắt): khi đánh dấu in, đơn tự sang Chờ bàn giao không cần bấm tay. Chỉ Lazada.

---

## 6. Đối soát & Lợi nhuận

Thay con số phí ước lượng bằng **phí thật theo từng đơn** từ bảng sao kê của sàn.

- **10 nhóm phí chuẩn**: doanh thu, hoa hồng sàn, phí thanh toán, phí vận chuyển, trợ giá vận chuyển, voucher người bán, voucher sàn, điều chỉnh, hoàn tiền, phí khác.
- **Quy ước dấu (góc người bán)**: dương = thu; âm = chi.
- **Kéo dữ liệu**: cần gói có đối soát; nếu chưa bật thì hệ thống báo chưa dùng được. Chi tiết phí được lưu cố định.
- **Đối soát**: hệ thống khớp từng dòng sao kê với đơn tương ứng; kỳ thành **đã đối soát** khi mọi dòng khớp.
- **Lợi nhuận**: doanh thu − giá vốn − tổng phí từ sao kê − ship thực người bán chịu − giảm giá người bán − phí khác. Đã đối soát dùng phí thật; chưa thì ước tính.
- **Quyền**: Chủ sở hữu/Quản trị/Kế toán.

---

## 7. Kế toán (Thông tư 133)

Sổ kép, hệ thống tài khoản Việt Nam (doanh nghiệp nhỏ & vừa), **chỉ VND**, năm tài chính = năm dương lịch.

- **Sổ kép**: mỗi bút toán có tổng Nợ = tổng Có, ít nhất 2 dòng. Bút toán **cố định** (không sửa/xoá — luật kế toán lưu 10 năm). Sửa = ghi đảo + ghi lại.
- **Hệ thống tài khoản**: tạo sẵn theo Thông tư 133 (khoảng 80 tài khoản: tiền mặt/ngân hàng, phải thu, hàng hoá, phải trả, thuế, doanh thu, giá vốn, chi phí quản lý...). Bạn được đổi tên/sắp xếp/bật-tắt, thêm tài khoản con; **không xoá** tài khoản đã phát sinh số liệu.
- **Khoá kỳ**: Đang mở → Đã đóng (ghi vào sẽ bị từ chối; bút toán đảo của kỳ đã đóng rơi sang kỳ mở kế tiếp) → Đã khoá (đã nộp/ký — không mở lại). Đóng kỳ chốt số dư (cuối kỳ → đầu kỳ sau).
- **Tự lên sổ**: nhận hàng → Nợ hàng hoá/Có phải trả người bán; chuyển kho → giữa hai tài khoản hàng hoá; kiểm kê thừa/thiếu → với tài khoản thu nhập/chi phí khác. Quy tắc sửa được ở **Cài đặt → Quy tắc hạch toán**; đổi không ảnh hưởng bút toán đã ghi.
- **Quyền & gói**: cần gói kế toán cơ bản (Pro/Business).

---

## 8. Tin nhắn

Hộp thư hợp nhất gom tin khách từ TikTok/Shopee/Lazada/Facebook. Tin về qua tín hiệu tức thời + kiểm tra định kỳ dự phòng.

### Tự động trả lời (4 kiểu)

- **Theo lịch** (vd 22:00–08:00, giờ Việt Nam), **theo trạng thái đơn** (vd Đã giao → cảm ơn, có độ trễ), **chưa trả lời sau N phút**, **tin đầu tiên** (lời chào, 1 lần/hội thoại).
- **Chống spam**: thời gian nghỉ giữa hai lần trả lời cho cùng hội thoại (mặc định 1 giờ); không tự trả lời lên tin do AI sinh; lời chào chỉ chạy đúng lần đầu; "chưa trả lời sau N phút" bỏ qua nếu quy tắc theo lịch đã trả lời. Không gửi trùng trong cùng khung thời gian.

### AI — an toàn & rào chắn

- Mặc định **chế độ gợi ý** (nhân viên duyệt nháp); **chế độ tự gửi** là tuỳ chọn theo nhà bán.
- Chế độ tự gửi: trước mỗi tin, AI phân loại ý định; nếu là khiếu nại / hoàn tiền / việc gấp / đe doạ pháp lý / lời lẽ thô tục ⇒ **không tự gửi**, chuyển người thật.
- AI **không bao giờ** gửi theo đường vòng (luôn qua quy trình gửi chuẩn có nhật ký + kiểm tra cửa sổ gửi). Mọi nội dung gửi AI đều che thông tin nhạy cảm (số điện thoại/email/số tài khoản).
- **Gói**: hộp thư (Pro+), AI tự trả lời (Business). Nhà cung cấp AI do đội vận hành thêm; nhà bán chọn 1.

### Facebook — cửa sổ 24 giờ & thẻ tin nhắn

- Nếu tin khách cuối đã quá 24 giờ, chỉ tin có **thẻ tin nhắn** hợp lệ (Xác nhận sự kiện / Sau mua hàng / Cập nhật tài khoản / Nhân viên) được gửi; cố gửi tin thường sẽ báo **quá hạn cửa sổ gửi**.

### Facebook — nhắn riêng bình luận 1 lần

- Facebook chỉ cho **nhắn riêng 1 lần/bình luận**. Báo "đã nhắn rồi" được xử lý nhẹ nhàng (coi như đã nhắn, không báo lỗi đỏ). Các trường hợp cửa sổ đóng/bị chặn cũng xử lý nhẹ; chỉ lỗi thật (khoá kết nối, quá tần suất) mới báo.
- Một lần gửi chỉ kèm chữ **hoặc** một tệp. Cửa sổ nhắn riêng nhiều phần: phần đầu chắc chắn gửi (nhắn riêng), phần sau cố gắng hết sức (báo "đã gửi X/Y phần").
- Thao tác trên từng bình luận (chỉ Facebook): **Thích** (cần Trang được cấp quyền tương tác), **Nhắn riêng**, **Xoá**.
- Xoá bình luận **gốc** ⇒ hội thoại thành thư rác; xoá bình luận **con** chỉ xoá bình luận đó.

### Trạng thái hội thoại

- Hội thoại: mở → tạm ẩn → mở; mở → đã xử lý (tin mới mở lại); mở → thư rác. Tin: chờ gửi → đã gửi → đã nhận → đã xem, hoặc gửi lỗi.
- Tin vào được nối với khách (theo số điện thoại) và gợi ý đơn gần đây. Không ghi đè khách đã có.

---

## 9. Nguyên tắc chung & Điều kiện nhập liệu

### Nguyên tắc chung
- **Tách biệt dữ liệu**: mỗi nhà bán độc lập, không truy cập chéo.
- **Tiền = số nguyên đồng**; phần lẻ khi chia % dồn vào dòng cuối.
- **Giờ** hiển thị theo Việt Nam.
- **Trạng thái** hiển thị nhãn tiếng Việt + trạng thái gốc của sàn để đối chiếu.
- **Dữ liệu sàn**: xác minh tín hiệu (sai thì bỏ) + luôn có kiểm tra định kỳ; luôn lấy lại chi tiết trước khi lưu.
- **An toàn khi chạy lại**: đồng bộ lại không nhân đôi dữ liệu.

### Điều kiện nhập liệu đáng chú ý
- **Tạo đơn thủ công**: trạng thái khởi tạo chỉ Chờ xử lý hoặc Đang xử lý; 1–200 dòng; mỗi dòng phải chọn mã sản phẩm **hoặc** nhập tên hàng nhanh; số lượng 1–99.999; đơn giá/giảm 0–gần 1 tỷ. Chỉ tạo hồ sơ khách khi có cả tên + số điện thoại. Hàng nhanh (không có mã) không theo dõi tồn và **không** bị cờ chưa ghép SKU.
- **Sửa đơn thủ công**: sửa mọi thứ khi chưa Đang vận chuyển; gửi lại danh sách hàng thay toàn bộ dòng → tồn cân bằng lại.
- **Chống đơn trùng**: cảnh báo nếu cùng số điện thoại + cùng sản phẩm trong thời gian ngắn.
- **Mã sản phẩm**: duy nhất trong nhà bán; xoá mã còn tồn thì bị từ chối (đưa tồn về 0 trước).
- **Bút toán tay**: tổng Nợ = tổng Có, ít nhất 2 dòng, tài khoản ghi sổ được, kỳ đang mở.
- **Đính kèm tin nhắn**: ảnh ≤ 25MB, video ≤ 100MB, file ≤ 25MB, đúng định dạng cho phép.

> Xem cách hiểu các thông báo lỗi trong [troubleshooting.md](troubleshooting.md). Danh mục năng lực trong [what-the-system-does.md](what-the-system-does.md).
