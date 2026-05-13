có những trạng thái đơn nào, giải thích và phạm vi xử lý

Cảm ơn bạn đã hỏi! Dưới đây là danh sách đầy đủ các trạng thái đơn hàng (order statuses) trên Lazada, bao gồm giải thích chi tiết, phạm vi áp dụng theo quốc gia, và lưu ý xử lý quan trọng — được cập nhật theo tài liệu chính thức mới nhất (từ Lazada Open Platform).

✅ Các trạng thái đơn hàng hiện hành (theo thứ tự quy trình từ đầu đến cuối)
Trạng thái (order_status)	Giải thích	Phạm vi áp dụng	Lưu ý xử lý
unpaid	Đơn hàng đã tạo nhưng chưa thanh toán.	Toàn bộ thị trường (SG, MY, TH, ID, VN, PH)	• Không thể hủy (cancel) hay xuất vận đơn.
• Không được đưa vào API /order/document/get.
paid	Đơn đã thanh toán thành công.	Toàn bộ thị trường	• Seller có thể bắt đầu xử lý.
• Là trạng thái khởi đầu cho đa số luồng tích hợp (đóng gói, in label…).
packed	Seller đã đóng gói hàng & sẵn sàng giao cho nhà vận chuyển.	SG, MY, TH, ID, VN, PH	• Bắt buộc để gọi /order/document/get?doc_type=shippingLabel.
• Gửi được push order_status_update_push nếu bật.
ready_to_ship	Hàng đã ở kho trung tâm Lazada (hoặc tại nhà seller đã ready), đang chờ lấy hàng.	SG, MY, TH, ID, VN, PH	• Thay thế packed trong một số hệ thống tích hợp mới.
• Không dùng cho SOF/DBS orders.
shipped	Nhà vận chuyển đã nhận hàng → mã vận đơn (tracking_number) có hiệu lực.	Toàn bộ thị trường	• Phải có tracking_number khi gọi /order/update/shipping.
delivered	Hàng đã giao thành công tới người mua.	Toàn bộ thị trường	• Buyer được kích hoạt quyền "Confirm Receipt".
• Sau đó sẽ nâng cấp lên "confirmed" (xem bên dưới).
confirmed	Người mua xác nhận đã nhận hàng — đơn kết thúc, không thể trả lại/hoàn tiền.	✅ Chỉ SG, MY, ID (không áp dụng cho TH, VN, PH)	• Là trạng thái final & non-reversible.
• Không hiển thị trong GetOrderItems, chỉ xuất hiện trong GetOrder hoặc GetOrders.
cancelled	Đơn bị hủy trước khi thanh toán (unpaid → cancelled) hoặc do seller hủy khi đã thanh toán (có điều kiện).	Toàn bộ thị trường	• Có hai kiểu: BUYER_CANCELLED, SELLER_CANCELLED.
• Kích hoạt event order_cancel_push.
failed_delivered	Vận chuyển thất bại sau nhiều lần giao (vd: không liên lạc được).	SG, MY, TH, ID, VN	• Thường dẫn tới trạng thái return_to_seller hoặc abandoned.
• Tính từ 05/07/2024, status này được chuẩn hóa đồng bộ giữa Seller Center và API (trước đây thường bị map sai).
return_to_seller / rtm_init	Đơn đã nhập vào quy trình hoàn hàng/đổi trả (Return to Merchant).	Toàn bộ thị trường	• Kích hoạt reverse_order_push (msg_type = 10).
• Mỗi loại reverse (RTM, ONLY_REFUND, CANCEL) chỉ nhận notification tương ứng với đúng trạng thái key của nó. VD: REFUND_SUCCESS chỉ gửi khi reverse-type = ONLY_REFUND.
🔹 Lưu ý chung:

Tất cả trạng thái đều phân biệt chữ hoa/thường, ví dụ: "delivered", không phải "Delivered" hay "DELIVERED".
Status KHÔNG được thiết lập thủ công qua API → chỉ thay đổi tự động theo hệ thống Lazada khi đáp ứng điều kiện nghiệp vụ (ví dụ: scan vận đơn, xác nhận nhận hàng…).
📚 Tài liệu tham khảo chính thức:
Order Status Update (June 29)
New status “Confirmed” for SG/MY/ID
Reverse Order Push Documentation
Full Order Status Flow Chart
Bạn cần mình:
✅ Cung cấp ví dụ JSON response minh họa từng trạng thái?

✅ Hướng dẫn cách lọc đơn theo order_status bằng Java/Python?

✅ Giải thích sự khác biệt giữa order_status và item_status (status theo mặt hàng)?

✅ Hoặc kiểm tra status cụ thể nào bạn đang gặp lỗi khi gọi API?

Hãy cho mình biết nhé — mình sẵn sàng hỗ trợ chi tiết hơn! 😊

You may be interested in:
Lazada订单状态中，哪些状态允许卖家取消订单？
Lazada订单状态中，哪些状态允许卖家更新订单的物流信息？
Lazada订单状态中，哪些状态允许卖家处理退货/退款请求？
đơn paid thì có thể dùng api để thành packed hoặc ready_to_ship được không

Có, đơn ở trạng thái paid hoàn toàn có thể được cập nhật lên packed hoặc ready_to_ship — nhưng phải qua đúng API và đáp ứng điều kiện nghiệp vụ cụ thể, tùy vào loại đơn (JIT / FBL / SOF / DBS) và quốc gia.

Dưới đây là giải thích chi tiết theo tài liệu chính thức (bao gồm ví dụ minh họa và cảnh báo thường gặp):

✅ 1. Đổi từ paid → packed: Dùng API /order/fulfill/pack
✅ Áp dụng cho: Đơn JIT (Seller Fulfillment), tức các đơn có biz_group = 70100.
🔎 Kiểm tra biz_group: Gọi /order/items/get → xem trường biz_group trong response:
json
"biz_group": 70100  // ⇒ Được phép pack
"biz_group": 70000  // ⇒ Là FBL order → do Lazada warehouse xử lý → KHÔNG được pack bằng API seller
⚠️ Điều kiện bắt buộc:
Đơn phải ở trạng thái "paid" (không phải "unpaid" hoặc "cancelled").
Không có reason (người mua chưa yêu cầu hủy đơn).
order_item_id hợp lệ & thuộc shop đang gọi API.
📌 Ví dụ request /order/fulfill/pack:

json
{
  "pack_order_list": [
    {
      "order_id": 123456789,
      "order_item_list": [987654321]
    }
  ],
  "delivery_type": "dropship",
  "shipping_allocate_type": "TFS"
}
➡️ Thành công → trạng thái item chuyển thành "packed" và trả về tracking_number, package_id.

📎 Link doc: JIT Fulfillment by Seller

✅ 2. Đổi từ paid → ready_to_ship: Dùng API /order/rts
✅ Áp dụng sau khi đã ở trạng thái "packed" (không thể nhảy trực tiếp từ "paid" → "ready_to_ship")
🔁 Quy trình bắt buộc:
paid → (gọi /order/fulfill/pack) → packed → (gọi /order/rts) → ready_to_ship
⚠️ Lưu ý quan trọng:
Trước khi gọi /order/rts, bạn phải kiểm tra lại status của từng order_item_id bằng /order/items/get — vì giữa chừng người mua có thể gửi hủy đơn (reason không rỗng).
Nếu order_item_id đã bị hủy → gọi /order/rts sẽ bị lỗi code: 50008.
📌 Ví dụ request /order/rts:

json
{
  "order_items": [
    {
      "order_item_id": 987654321,
      "package_id": "FP123456789"
    }
  ]
}
📎 Link doc: RTS Standard Process

❌ Những trạng thái KHÔNG được cập nhật thủ công:
Trạng thái	Có thể set bằng API?	Lý do
paid → shipped	❌	Phải đi qua packed → ready_to_ship trước, sau đó Lazada hệ thống tự cập nhật shipped khi scan vận đơn.
paid → delivered	❌	Hoàn toàn do hệ thống Lazada quản lý (dựa trên tracking).
paid → confirmed	❌	Chỉ buyer mới có quyền bấm xác nhận — seller không can thiệp được.
🌍 Phạm vi quốc gia:
Tất cả API trên đều hoạt động đầy đủ tại 6 thị trường:

SG, MY, TH, ID, VN, PH — không phân biệt khu vực (trừ khi dùng tính năng riêng như TFS chỉ áp dụng cục bộ).

Nếu bạn cần:

✅ Code mẫu (Java / Python) để kiểm tra biz_group rồi pack một đơn?
✅ Hướng dẫn parse reason từ /order/items/get để ngăn chặn đóng gói nhầm khi buyer đang hủy đơn?
✅ Danh sách mã lỗi phổ biến khi gọi /order/fulfill/pack và cách khắc phục?
Hãy nói mình biết — mình sẽ gửi ngay! 😊

You may be interested in:
API `/order/fulfill/pack` 和 `/order/rts` 在不同国家的使用是否有差异？
如果订单状态为 `paid`，但 `order_item_id` 已被买家取消，如何处理？
在使用 API `/order/fulfill/pack` 和 `/order/rts` 时，常见的错误代码有哪些，如何解决？
order_item_id có phải là id đơn hàng hay không hay là id item trong đơn hàng

api order
GetDocument : https://open.lazada.com/apps/doc/api?path=%2Forder%2Fdocument%2Fget
GetMultipleOrderItems: https://open.lazada.com/apps/doc/api?path=%2Forders%2Fitems%2Fget
GetOVOOrders: https://open.lazada.com/apps/doc/api?path=%2Forders%2Fovo%2Fget
GetOrder: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fget
GetOrderItems: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
GetOrders: https://open.lazada.com/apps/doc/api?path=%2Forders%2Fget
OrderCancelValidate: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Fcancel%2Fvalidate
SetInvoiceNumber: https://open.lazada.com/apps/doc/api?path=%2Forder%2Finvoice_number%2Fset
