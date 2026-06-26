# SPEC: Ví trả trước của khách hàng (Customer Prepaid Wallet)

- **Trạng thái:** Draft
- **Phase:** Customers + Orders + Accounting
- **Module backend liên quan:** Customers (sở hữu ví), Orders (áp ví lúc tạo đơn manual), Accounting (hạch toán qua 131), Fulfillment (COD đã sẵn theo prepaid_amount)
- **Tác giả / Ngày:** 2026-06-26
- **Liên quan:** `accounting-ux-party-picker-and-endpoint` (PartyPicker/AR), `PostOnOrderShipped`, `CustomerReceiptService`, `ManualOrderService`

## 1. Vấn đề & mục tiêu

Khách có thể **nạp tiền trước** (trả trước) vào một "ví" gắn với hồ sơ khách. Khi tạo **đơn thủ công**, nhập đúng SĐT khách → hệ thống gợi ý **trừ vào số dư ví**; đủ ví ⇒ **COD đẩy ĐVVC = 0đ**, nhưng đơn vẫn được **ghi nhận vào lịch sử mua hàng**, **trừ số dư ví**, và **khớp nghiệp vụ kế toán** (dồn tích qua TK 131).

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Số dư ví + sổ giao dịch ví trên hồ sơ khách (Customers module).
  - Nạp tiền (top-up) trên màn thêm/sửa khách, có hạch toán GL.
  - Áp ví khi tạo đơn manual: thanh toán MỘT PHẦN nếu ví không đủ; **toggle trừ tiền ship vào ví hoặc không**.
  - Trừ ví ngay khi tạo đơn; **hoàn ví khi huỷ/hoàn** đơn.
  - Hạch toán **dồn tích qua 131** (đã chốt): nạp = Cr 131 (advance); doanh thu khi giao qua `PostOnOrderShipped` sẵn có.
  - FE: panel ví ở màn khách + màn tạo đơn.
- **Ngoài (làm sau):**
  - Sửa `prepaid_amount`/đổi tổng tiền đơn SAU khi đã tạo & trừ ví (mục 7 — chặn hoặc tính lại có kiểm soát; mặc định CHẶN đổi phần trả-trước-từ-ví sau tạo).
  - Ví cho đơn sàn (chỉ áp dụng đơn manual — đơn sàn COD do sàn quản lý).
  - Rút tiền mặt khỏi ví (chỉ nạp + tiêu + hoàn; điều chỉnh thủ công qua `adjustment` có kiểm soát).

## 3. Luồng chính

1. **Nạp tiền:** màn sửa khách → "Nạp tiền" (số tiền + phương thức cash/bank/ewallet) → ví +tiền, ghi GL Dr tiền/Cr 131.
2. **Tạo đơn manual:** nhập SĐT → `lookup` trả `prepaid_balance` → FE hiện "Ví khách: X đ — Dùng ví?" + toggle "Trừ cả tiền ship vào ví".
   - `target = toggle ? grand_total : (grand_total − shipping_fee)`; `prepaid_from_wallet = min(balance, target)`.
   - `order.prepaid_amount = prepaid_from_wallet` ⇒ `COD = grand_total − prepaid_amount` (logic sẵn có).
3. **Lưu đơn:** 1 transaction — tạo đơn + trừ ví (lock) + ghi ledger `order_payment`; đơn vào lịch sử mua hàng (lifetime_stats).
4. **Giao thành công:** `PostOnOrderShipped` ghi doanh thu Dr 131/Cr 511 (+VAT) — advance (Cr 131 lúc nạp) tự cấn trừ.
5. **Huỷ/hoàn (trước giao):** hoàn ví (ledger `refund`).

## 4. Hành vi & quy tắc nghiệp vụ

- **Số dư ví** = nguồn sự thật khả dụng (operational), denormalized `customers.prepaid_balance` + sổ `customer_wallet_transactions`.
- **Thanh toán một phần:** `prepaid_amount = min(balance, target)`; phần thiếu = COD.
- **Trừ ship hay không:** chỉ ảnh hưởng cách FE tính `target`/`prepaid_amount`; backend lưu & trừ đúng `prepaid_amount`.
- **Trừ ví:** ngay khi tạo đơn, trong DB transaction, `lockForUpdate` trên customer (chống race); balance không bao giờ âm (`deduct ≤ balance`).
- **Hoàn ví:** đơn có `order_payment` mà chuyển `cancelled`/`returned_refunded` (trước khi ghi doanh thu) → cộng lại ví. Idempotent.
- **Idempotency:** unique `(order_id, type='order_payment')` và `(order_id, type='refund')`.
- **Hạch toán (dồn tích qua 131):**
  - Nạp tiền: **Dr 1111/1121 / Cr 131** (party=customer) — tái dùng `CustomerReceiptService` (advance, không applied_orders). Lưu `journal_entry_id` vào ledger ví.
  - Đơn giao: **Dr 131 / Cr 511 (+VAT)** qua `PostOnOrderShipped` (KHÔNG sửa) — advance tự cấn trừ; COD thu sau → CustomerReceipt Dr tiền/Cr 131.
  - **Không phát sinh GL mới ngoài top-up.** Ví (operational) phục vụ UX + audit; đối soát: 131-credit của khách = `prepaid_balance` + Σ prepaid của đơn chưa giao.
- **Phân quyền:** nạp tiền/điều chỉnh ví cần quyền kế toán (`accounting.manage` hoặc tương đương — chốt ở plan); áp ví khi tạo đơn theo `orders.create`.

## 5. Dữ liệu

- `customers.prepaid_balance` BIGINT NOT NULL DEFAULT 0 (VND). Migration reversible.
- Bảng `customer_wallet_transactions`:
  - `id, tenant_id, customer_id, order_id NULL, type ENUM(topup,order_payment,refund,adjustment), amount BIGINT(±), balance_after BIGINT, payment_method NULL, journal_entry_id NULL, note NULL, created_by NULL, created_at`.
  - Index `(tenant_id, customer_id, created_at)`; partial unique `(order_id, type)` cho order_payment/refund. `BelongsToTenant`.
- Domain event: `CustomerWalletToppedUp`, `CustomerWalletDeducted`, `CustomerWalletRefunded` (Customers phát; Accounting/Reports nghe nếu cần — top-up GL đi qua CustomerReceiptService trực tiếp).

## 6. API & UI

- **`GET /api/v1/customers/lookup`** (sửa): thêm `prepaid_balance` vào response.
- **`GET /api/v1/customers/{id}`** (sửa): thêm `prepaid_balance` + (tuỳ) `wallet_transactions` gần đây vào CustomerResource.
- **`POST /api/v1/customers/{id}/wallet/topup`** (mới, `accounting`-gated): `{ amount, payment_method, note? }` → `{ data: { balance, transaction } }`.
- **`GET /api/v1/customers/{id}/wallet/transactions`** (mới): phân trang sổ ví.
- **`POST /api/v1/orders`** (manual, sửa nhẹ): nhận `prepaid_amount` (sẵn có) — service trừ ví nếu khách có ví & `prepaid_amount>0` (validate `prepaid_amount ≤ balance`). Trả lỗi rõ nếu vượt số dư.
- Cập nhật `05-api/endpoints.md`.
- **FE:** màn khách (số dư + lịch sử ví + nút Nạp tiền); màn tạo đơn (panel ví: số dư, dùng ví, toggle trừ ship, COD còn lại realtime). Icon @ant-design/icons; tránh Select (dùng Radio/Segmented cho phương thức).

## 7. Edge case & lỗi

- Ví không đủ → thanh toán một phần (ví + COD), không lỗi.
- Race 2 đơn cùng rút ví → `lockForUpdate`; vượt số dư → 422 "Số dư ví không đủ".
- Đơn manual chưa có customer (chỉ tên+SĐT) → không hiện ví; tạo đơn bình thường.
- Sửa đơn sau tạo (đổi tổng/ship) khi đã trừ ví: **CHẶN đổi prepaid-từ-ví sau tạo** (lần này); muốn đổi → huỷ tạo lại. Ghi nhận để mở rộng.
- Huỷ sau khi ĐÃ giao (đã ghi doanh thu) → đi luồng hoàn trả `reverseIfPosted` sẵn có; ví chỉ hoàn nếu chưa ghi doanh thu.
- Top-up trùng (double click) → idempotency key trên receipt + ledger.
- Hoàn ví trùng → unique `(order_id,'refund')`.

## 8. Bảo mật & dữ liệu cá nhân

Ví gắn customer (đã có phone mã hoá). Không lộ thêm PII. Nạp tiền/điều chỉnh ghi `created_by` để truy vết. Tất cả thao tác theo tenant scope.

## 9. Kiểm thử

- **Unit/Feature:** topup post GL đúng (Dr tiền/Cr 131) + tăng balance + ledger; tạo đơn trừ ví đúng + COD đúng cho 3 ca (đủ ví/COD0; thiếu ví/partial; toggle trừ ship on/off); idempotent (không trừ/hoàn trùng); race `lockForUpdate`; huỷ đơn hoàn ví; `lookup` trả balance; vượt số dư → 422.
- **Accounting:** đối soát 131 sau topup + ship khớp; reversal khi huỷ-sau-giao.
- Theo memory `test-verify-baseline`: chạy test liên quan Customers/Orders/Accounting (BE chưa green toàn cục).

## 10. Tiêu chí hoàn thành

- [ ] Migration `prepaid_balance` + `customer_wallet_transactions` (reversible, index, partial unique).
- [ ] `CustomerWalletService` (topup/deduct/refund, lock, idempotent) + tái dùng `CustomerReceiptService` cho GL top-up.
- [ ] Endpoints topup/transactions + `lookup`/CustomerResource trả balance.
- [ ] `ManualOrderService` trừ ví khi tạo đơn (transaction, lock, validate ≤ balance).
- [ ] Hoàn ví khi huỷ/hoàn (listener OrderStatusChanged, trước-giao).
- [ ] FE màn khách + màn tạo đơn (panel ví, toggle ship, COD realtime).
- [ ] Test Customers/Orders/Accounting liên quan pass; pint/phpstan/lint/typecheck/build xanh.
- [ ] Docs: `05-api/endpoints.md` (+ `03-domain` nếu cần).

## 11. Câu hỏi mở

- Key quyền chính xác cho nạp tiền (đề xuất `accounting.manage`; nếu chưa có thì `orders.update` + role owner/admin) — chốt ở plan.
- Có cần trần nạp/đơn vị làm tròn nghìn đồng không (đề xuất: không, integer VND tự do).
