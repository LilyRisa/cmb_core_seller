import { Modal } from 'antd';

const SHOPEE_DOC_SETTINGS_URL = 'https://banhang.shopee.vn/portal/all-settings/shipping/shipping-document';

/**
 * Nhắc bật chế độ in nhiệt (Thermal) trước khi in tem đơn Shopee.
 *
 * Khổ tem Shopee (A4 thường vs A6 nhiệt) do **cài đặt in vận đơn của TỪNG gian hàng trên Shopee Seller
 * Center** quyết định — app chỉ in lại đúng tệp sàn cấp, KHÔNG ép được khổ qua API (đã xác minh). Nếu shop
 * để A4 thì tem thừa nhiều khoảng trắng. Hiển thị thông báo trước khi mở tab in cho đơn Shopee.
 *
 * `hasShopee=false` ⇒ chạy `proceed` ngay (không có đơn Shopee thì khỏi nhắc).
 */
export function withShopeePrintNotice(hasShopee: boolean, proceed: () => void): void {
    if (!hasShopee) {
        proceed();
        return;
    }
    Modal.confirm({
        title: 'Tem Shopee — bật chế độ in nhiệt để đúng khổ',
        width: 560,
        content: (
            <div>
                <p style={{ marginTop: 0 }}>
                    Khổ tem Shopee (A4 thường hay A6 nhiệt) do <b>cài đặt in vận đơn của từng gian hàng trên
                    Shopee Seller Center</b> quyết định — app chỉ in lại đúng tệp sàn cấp. Nếu tem ra khổ A4 thừa
                    nhiều khoảng trắng, hãy bật <b>chế độ in nhiệt (Thermal / khổ A6)</b> trong Shopee:
                </p>
                <p style={{ marginBottom: 0 }}>
                    <a href={SHOPEE_DOC_SETTINGS_URL} target="_blank" rel="noreferrer">
                        Mở cài đặt khổ in vận đơn Shopee → chọn Khổ nhiệt (A6)
                    </a>
                </p>
            </div>
        ),
        okText: 'Tiếp tục in',
        cancelText: 'Huỷ',
        onOk: proceed,
    });
}
