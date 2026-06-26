import { useState } from 'react';
import { Anchor, Card, Col, Row, Space, Table, Tag, Typography } from 'antd';
import { CheckOutlined, CopyOutlined } from '@ant-design/icons';

const BASE = 'https://app.cmbcore.com/api/v1';

const METHOD_COLOR: Record<string, string> = { GET: 'blue', POST: 'green', PUT: 'gold', DELETE: 'red' };

/** Khối code có nút copy. */
function Code({ children, lang }: { children: string; lang?: string }) {
    const [done, setDone] = useState(false);
    const copy = () => { navigator.clipboard?.writeText(children).then(() => { setDone(true); setTimeout(() => setDone(false), 1500); }); };
    return (
        <div style={{ position: 'relative', margin: '8px 0' }}>
            {lang && <span style={{ position: 'absolute', top: 6, right: 40, fontSize: 11, color: '#8c8c8c', textTransform: 'uppercase' }}>{lang}</span>}
            <button onClick={copy} title="Sao chép" style={{ position: 'absolute', top: 6, right: 8, border: 'none', background: 'transparent', cursor: 'pointer', color: done ? '#52c41a' : '#8c8c8c' }}>
                {done ? <CheckOutlined /> : <CopyOutlined />}
            </button>
            <pre style={{ margin: 0, padding: '14px 16px', background: '#0d1117', color: '#e6edf3', borderRadius: 8, overflowX: 'auto', fontSize: 13, lineHeight: 1.6 }}>
                <code>{children}</code>
            </pre>
        </div>
    );
}

function Endpoint({ method, path, desc, request, response }: { method: string; path: string; desc: string; request: string; response: string }) {
    return (
        <Card size="small" style={{ marginBottom: 16 }} styles={{ body: { padding: 16 } }}>
            <Space align="center" wrap style={{ marginBottom: 6 }}>
                <Tag color={METHOD_COLOR[method]} style={{ fontWeight: 700, fontFamily: 'monospace' }}>{method}</Tag>
                <Typography.Text strong style={{ fontFamily: 'monospace', fontSize: 14 }}>{path}</Typography.Text>
            </Space>
            <Typography.Paragraph type="secondary" style={{ marginBottom: 8 }}>{desc}</Typography.Paragraph>
            <Typography.Text strong style={{ fontSize: 12, color: '#8c8c8c' }}>VÍ DỤ REQUEST</Typography.Text>
            <Code lang="bash">{request}</Code>
            <Typography.Text strong style={{ fontSize: 12, color: '#8c8c8c' }}>VÍ DỤ RESPONSE</Typography.Text>
            <Code lang="json">{response}</Code>
        </Card>
    );
}

const TOC = [
    { key: 'gioi-thieu', href: '#gioi-thieu', title: '1. Giới thiệu' },
    { key: 'bat-dau', href: '#bat-dau', title: '2. Bắt đầu nhanh' },
    { key: 'xac-thuc', href: '#xac-thuc', title: '3. Xác thực' },
    { key: 'quy-uoc', href: '#quy-uoc', title: '4. Quy ước chung' },
    { key: 'don-hang', href: '#don-hang', title: '5. Đơn hàng' },
    { key: 'san-pham', href: '#san-pham', title: '6. Sản phẩm & tồn kho' },
    { key: 'van-don', href: '#van-don', title: '7. Vận đơn' },
    { key: 'loi', href: '#loi', title: '8. Mã lỗi' },
];

const H = (id: string, text: string) => <Typography.Title id={id} level={2} style={{ scrollMarginTop: 80, marginTop: 8 }}>{text}</Typography.Title>;

export function ApiDocsPage() {
    const errorRows = [
        { code: '200 / 201', mean: 'Thành công.' },
        { code: '401', mean: 'Thiếu / sai / hết hạn API key, hoặc key đã bị thu hồi.' },
        { code: '403', mean: 'Không đủ quyền (hiếm — API key có toàn quyền của chủ gian hàng).' },
        { code: '404', mean: 'Không tìm thấy tài nguyên trong gian hàng.' },
        { code: '422', mean: 'Dữ liệu gửi lên không hợp lệ — xem chi tiết ở error.details.' },
        { code: '429', mean: 'Vượt giới hạn 120 request/phút — thử lại sau.' },
    ];

    return (
        <div style={{ maxWidth: 1180, margin: '0 auto', padding: '40px 24px' }}>
            <Typography.Title level={1}>Tài liệu API</Typography.Title>
            <Typography.Paragraph style={{ fontSize: 16, color: '#595959', maxWidth: 760 }}>
                API REST cho phép hệ thống ngoài (ERP, Zapier, phần mềm tự xây…) đọc và thao tác dữ liệu gian hàng — đồng bộ đơn, sản phẩm, tồn kho, vận đơn — <b>như khi thao tác trên web</b>.
            </Typography.Paragraph>

            <Row gutter={32}>
                <Col xs={0} lg={6}>
                    <div style={{ position: 'sticky', top: 88 }}>
                        <Typography.Text strong style={{ display: 'block', marginBottom: 8, color: '#8c8c8c', fontSize: 12 }}>MỤC LỤC</Typography.Text>
                        <Anchor affix={false} items={TOC} />
                    </div>
                </Col>
                <Col xs={24} lg={18}>
                    {H('gioi-thieu', '1. Giới thiệu')}
                    <Typography.Paragraph>
                        Mọi endpoint dùng chung tiền tố <Typography.Text code>{BASE}</Typography.Text>, trả về JSON theo định dạng phong bì chuẩn và xác thực bằng <b>API key</b> (Bearer token). API key gắn cứng gian hàng của bạn nên không cần truyền thêm thông tin gian hàng.
                    </Typography.Paragraph>

                    {H('bat-dau', '2. Bắt đầu nhanh')}
                    <Typography.Paragraph>
                        <ol style={{ paddingLeft: 20 }}>
                            <li>Chủ gian hàng vào <b>Cài đặt → API &amp; Tích hợp → Tạo API key</b> (chỉ chủ gian hàng tạo/xem/xóa được).</li>
                            <li>Đặt tên + thời hạn → <b>Tạo</b>. Token hiện <b>1 lần duy nhất</b> — sao chép &amp; lưu nơi an toàn.</li>
                            <li>Gọi API kèm header <Typography.Text code>Authorization: Bearer &lt;API_KEY&gt;</Typography.Text>.</li>
                        </ol>
                    </Typography.Paragraph>
                    <Code lang="bash">{`curl -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/orders?per_page=10"`}</Code>

                    {H('xac-thuc', '3. Xác thực')}
                    <Typography.Paragraph>
                        Gửi API key ở header <Typography.Text code>Authorization</Typography.Text> với mọi request:
                    </Typography.Paragraph>
                    <Code>{`Authorization: Bearer <API_KEY>`}</Code>
                    <ul style={{ paddingLeft: 20 }}>
                        <li>Key đã <b>gắn cứng gian hàng</b> — KHÔNG cần gửi header <Typography.Text code>X-Tenant-Id</Typography.Text>.</li>
                        <li>Key có <b>toàn quyền</b> như tài khoản chủ gian hàng — giữ bí mật như mật khẩu.</li>
                        <li>Có thể đặt <b>thời hạn</b> và <b>thu hồi (xóa)</b> bất cứ lúc nào; key bị xóa sẽ trả <Typography.Text code>401</Typography.Text> ngay.</li>
                    </ul>

                    {H('quy-uoc', '4. Quy ước chung')}
                    <ul style={{ paddingLeft: 20 }}>
                        <li><b>Base URL:</b> <Typography.Text code>{BASE}</Typography.Text></li>
                        <li><b>Định dạng:</b> JSON. Thành công <Typography.Text code>{'{ "data": ..., "meta": ... }'}</Typography.Text>; lỗi <Typography.Text code>{'{ "error": { "code", "message", "trace_id", "details" } }'}</Typography.Text>.</li>
                        <li><b>Tiền tệ:</b> số nguyên VND (không số thập phân).</li>
                        <li><b>Thời gian:</b> ISO-8601 UTC, vd <Typography.Text code>2026-06-26T03:37:21Z</Typography.Text>.</li>
                        <li><b>Trạng thái đơn:</b> trả <Typography.Text code>code</Typography.Text> + <Typography.Text code>status_label</Typography.Text> + <Typography.Text code>raw_status</Typography.Text>.</li>
                        <li><b>Rate limit:</b> 120 request/phút (vượt → <Typography.Text code>429</Typography.Text>).</li>
                        <li><b>Phân trang:</b> <Typography.Text code>?page=&amp;per_page=</Typography.Text> → <Typography.Text code>meta.pagination</Typography.Text>.</li>
                    </ul>

                    {H('don-hang', '5. Đơn hàng')}
                    <Endpoint method="GET" path="/orders" desc="Danh sách đơn (lọc: status, source, q, placed_from, placed_to, page, per_page)."
                        request={`curl -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/orders?status=processing&per_page=50"`}
                        response={`{
  "data": [
    {
      "id": 9876,
      "order_number": "CMB-000123",
      "source": "shopee",
      "status": { "code": "processing", "status_label": "Đang xử lý", "raw_status": "PROCESSED" },
      "grand_total": 320000,
      "cod_amount": 0,
      "currency": "VND",
      "placed_at": "2026-06-26T03:37:21Z"
    }
  ],
  "meta": { "pagination": { "page": 1, "per_page": 50, "total": 128, "total_pages": 3 } }
}`} />
                    <Endpoint method="GET" path="/orders/{id}" desc="Chi tiết một đơn, kèm dòng hàng (?include=items)."
                        request={`curl -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/orders/9876?include=items"`}
                        response={`{
  "data": {
    "id": 9876,
    "order_number": "CMB-000123",
    "status": { "code": "processing", "status_label": "Đang xử lý" },
    "grand_total": 320000,
    "items": [
      { "id": 1, "name": "Áo thun", "seller_sku": "AT-01", "quantity": 2, "unit_price": 150000 }
    ]
  }
}`} />
                    <Endpoint method="POST" path="/orders" desc="Tạo đơn thủ công (source=manual)."
                        request={`curl -X POST -H "Authorization: Bearer <API_KEY>" \\
  -H "Content-Type: application/json" \\
  -d '{
    "buyer": { "name": "Nguyễn A", "phone": "0912345678", "address": "Số 5", "province": "Hà Nội" },
    "items": [ { "sku_id": 1, "quantity": 2, "unit_price": 150000 } ],
    "shipping_fee": 20000
  }' \\
  "${BASE}/orders"`}
                        response={`{
  "data": {
    "id": 9999,
    "order_number": "CMB-000200",
    "status": { "code": "pending", "status_label": "Chờ xử lý" },
    "cod_amount": 320000,
    "grand_total": 320000
  }
}`} />
                    <Endpoint method="POST" path="/orders/{id}/ship" desc="Chuẩn bị hàng: tạo vận đơn / lấy phiếu giao hàng của sàn."
                        request={`curl -X POST -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/orders/9876/ship"`}
                        response={`{ "data": { "queued": true, "order_id": 9876 } }`} />

                    {H('san-pham', '6. Sản phẩm & tồn kho')}
                    <Endpoint method="GET" path="/products" desc="Danh sách sản phẩm / SKU master."
                        request={`curl -H "Authorization: Bearer <API_KEY>" "${BASE}/products?per_page=20"`}
                        response={`{
  "data": [ { "id": 1, "name": "Áo thun", "skus": [ { "id": 1, "sku_code": "AT-01" } ] } ],
  "meta": { "pagination": { "page": 1, "per_page": 20, "total": 50, "total_pages": 3 } }
}`} />
                    <Endpoint method="GET" path="/inventory" desc="Tồn kho theo SKU (on_hand, reserved, available)."
                        request={`curl -H "Authorization: Bearer <API_KEY>" "${BASE}/inventory"`}
                        response={`{
  "data": [ { "sku_id": 1, "sku_code": "AT-01", "on_hand": 120, "reserved": 8, "available": 112 } ]
}`} />

                    {H('van-don', '7. Vận đơn')}
                    <Endpoint method="GET" path="/shipments" desc="Danh sách vận đơn (lọc: status, carrier, order_id, q)."
                        request={`curl -H "Authorization: Bearer <API_KEY>" "${BASE}/shipments?status=created"`}
                        response={`{
  "data": [ { "id": 555, "order_id": 9876, "carrier": "J&T VN", "tracking_no": "JNTMP00413467", "status": "created" } ]
}`} />

                    {H('loi', '8. Mã lỗi')}
                    <Table size="small" pagination={false} rowKey="code"
                        columns={[{ title: 'HTTP', dataIndex: 'code', width: 110, render: (v: string) => <Typography.Text code>{v}</Typography.Text> }, { title: 'Ý nghĩa', dataIndex: 'mean' }]}
                        dataSource={errorRows} />
                    <Typography.Paragraph type="secondary" style={{ marginTop: 16 }}>
                        Danh sách endpoint liên tục được mở rộng. Cần endpoint chưa có trong tài liệu? Liên hệ hỗ trợ.
                    </Typography.Paragraph>
                </Col>
            </Row>
        </div>
    );
}
