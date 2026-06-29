import { useState } from 'react';
import { Anchor, Col, Row, Table, Tag } from 'antd';
import { CheckOutlined, CopyOutlined } from '@ant-design/icons';

const BASE = 'https://app.cmbcore.com/api/v1';

const METHOD_COLOR: Record<string, string> = { GET: 'blue', POST: 'green', PUT: 'gold', DELETE: 'red' };

/** Inline code chip — light tinted background for prose, reused across sections. */
function Chip({ children, accent }: { children: string; accent?: boolean }) {
    return (
        <code style={{
            fontFamily: 'var(--mono)',
            background: accent ? 'var(--primary-soft)' : 'var(--bg-soft)',
            color: accent ? 'var(--primary)' : 'var(--text)',
            padding: '1px 7px',
            borderRadius: 5,
            fontSize: 13,
            border: accent ? 'none' : '1px solid var(--border-soft)',
        }}>
            {children}
        </code>
    );
}

/** Khối code có nút copy. */
function Code({ children, lang }: { children: string; lang?: string }) {
    const [done, setDone] = useState(false);
    const copy = () => {
        navigator.clipboard?.writeText(children).then(() => {
            setDone(true);
            setTimeout(() => setDone(false), 1500);
        });
    };
    return (
        <div style={{ position: 'relative', margin: '10px 0' }}>
            {lang && (
                <span style={{
                    position: 'absolute', top: 9, right: 44,
                    fontSize: 10.5, color: 'rgba(255,255,255,.38)',
                    textTransform: 'uppercase', fontFamily: 'var(--mono)',
                    letterSpacing: '0.07em', zIndex: 1, pointerEvents: 'none',
                }}>
                    {lang}
                </span>
            )}
            <button
                onClick={copy}
                title="Sao chép"
                style={{
                    position: 'absolute', top: 9, right: 10,
                    border: '1px solid rgba(255,255,255,.14)',
                    background: 'rgba(255,255,255,.07)',
                    cursor: 'pointer',
                    color: done ? 'var(--accent)' : 'rgba(255,255,255,.5)',
                    borderRadius: 6,
                    padding: '3px 7px',
                    fontSize: 13,
                    display: 'flex', alignItems: 'center', gap: 4,
                    transition: 'color .2s, background .2s',
                    zIndex: 1,
                }}
            >
                {done ? <CheckOutlined /> : <CopyOutlined />}
            </button>
            <pre style={{
                margin: 0,
                padding: '12px 16px',
                paddingTop: lang ? '32px' : '12px',
                background: '#0d1117',
                color: '#e6edf3',
                borderRadius: 10,
                overflowX: 'auto',
                fontSize: 13,
                lineHeight: 1.6,
                fontFamily: 'var(--mono)',
                border: '1px solid rgba(255,255,255,.08)',
            }}>
                <code style={{ fontFamily: 'var(--mono)' }}>{children}</code>
            </pre>
        </div>
    );
}

function Endpoint({ method, path, desc, request, response }: {
    method: string; path: string; desc: string; request: string; response: string;
}) {
    return (
        <div style={{
            background: '#fff',
            border: '1px solid var(--border-soft)',
            borderRadius: 'var(--radius-md)',
            padding: '20px 22px',
            marginBottom: 16,
            boxShadow: 'var(--shadow-sm)',
        }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8, flexWrap: 'wrap' }}>
                <Tag
                    color={METHOD_COLOR[method]}
                    style={{ fontWeight: 700, fontFamily: 'var(--mono)', fontSize: 12, margin: 0 }}
                >
                    {method}
                </Tag>
                <code style={{
                    fontFamily: 'var(--mono)', fontSize: 13.5, fontWeight: 600,
                    color: 'var(--text)', background: 'var(--bg-soft)',
                    padding: '2px 9px', borderRadius: 5, border: '1px solid var(--border-soft)',
                }}>
                    {path}
                </code>
            </div>
            <p style={{ fontSize: 14, color: 'var(--text-muted)', margin: '0 0 10px', lineHeight: 1.55 }}>{desc}</p>
            <div style={{ fontSize: 10.5, fontWeight: 700, letterSpacing: '0.07em', color: 'var(--text-soft)', textTransform: 'uppercase', marginBottom: 4 }}>Ví dụ request</div>
            <Code lang="bash">{request}</Code>
            <div style={{ fontSize: 10.5, fontWeight: 700, letterSpacing: '0.07em', color: 'var(--text-soft)', textTransform: 'uppercase', marginBottom: 4, marginTop: 10 }}>Ví dụ response</div>
            <Code lang="json">{response}</Code>
        </div>
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

/** Section heading with ID anchor + bottom border separator. */
function SectionH({ id, children }: { id: string; children: string }) {
    return (
        <h3
            id={id}
            style={{
                fontSize: 20,
                fontWeight: 800,
                letterSpacing: '-0.02em',
                lineHeight: 1.2,
                marginBottom: 16,
                marginTop: 40,
                color: 'var(--text)',
                scrollMarginTop: 88,
                paddingBottom: 10,
                borderBottom: '1px solid var(--border-soft)',
            }}
        >
            {children}
        </h3>
    );
}

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
        <section style={{ background: 'var(--bg)', borderTop: '1px solid var(--border-soft)' }}>
            <div className="container" style={{ paddingTop: 56, paddingBottom: 80 }}>

                {/* Page header */}
                <div style={{ marginBottom: 48 }}>
                    <span className="section-tag">Tài liệu API</span>
                    <h2 style={{ marginTop: 16, marginBottom: 12 }}>Tài liệu REST API</h2>
                    <p className="section-sub" style={{ maxWidth: 720, margin: 0 }}>
                        API REST cho phép hệ thống ngoài (ERP, Zapier, phần mềm tự xây…) đọc và thao tác dữ liệu gian hàng — đồng bộ đơn, sản phẩm, tồn kho, vận đơn —{' '}
                        <strong>như khi thao tác trên web</strong>.
                    </p>
                </div>

                <Row gutter={48}>
                    {/* Sticky sidebar TOC */}
                    <Col xs={0} lg={6}>
                        <div style={{ position: 'sticky', top: 88 }}>
                            <div style={{
                                background: 'var(--bg-soft)',
                                border: '1px solid var(--border-soft)',
                                borderRadius: 'var(--radius-md)',
                                padding: '18px 20px',
                            }}>
                                <div style={{
                                    fontSize: 10.5, fontWeight: 700, letterSpacing: '0.07em',
                                    color: 'var(--text-soft)', textTransform: 'uppercase',
                                    marginBottom: 12,
                                }}>
                                    Mục lục
                                </div>
                                <Anchor affix={false} items={TOC} style={{ fontSize: 13.5 }} />
                            </div>
                        </div>
                    </Col>

                    {/* Main content */}
                    <Col xs={24} lg={18}>

                        <SectionH id="gioi-thieu">1. Giới thiệu</SectionH>
                        <p style={{ fontSize: 15, color: 'var(--text)', lineHeight: 1.7, marginBottom: 0 }}>
                            Mọi endpoint dùng chung tiền tố <Chip accent>{BASE}</Chip>, trả về JSON theo định dạng phong bì chuẩn và xác thực bằng{' '}
                            <strong>API key</strong> (Bearer token). API key gắn cứng gian hàng của bạn nên không cần truyền thêm thông tin gian hàng.
                        </p>

                        <SectionH id="bat-dau">2. Bắt đầu nhanh</SectionH>
                        <ol style={{ paddingLeft: 22, fontSize: 15, color: 'var(--text)', lineHeight: 1.7, marginBottom: 14 }}>
                            <li style={{ marginBottom: 8 }}>
                                Chủ gian hàng vào <strong>Cài đặt → API &amp; Tích hợp → Tạo API key</strong> (chỉ chủ gian hàng tạo/xem/xóa được).
                            </li>
                            <li style={{ marginBottom: 8 }}>
                                Đặt tên + thời hạn → <strong>Tạo</strong>. Token hiện <strong>1 lần duy nhất</strong> — sao chép &amp; lưu nơi an toàn.
                            </li>
                            <li>
                                Gọi API kèm header <Chip accent>Authorization: Bearer &lt;API_KEY&gt;</Chip>.
                            </li>
                        </ol>
                        <Code lang="bash">{`curl -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/orders?per_page=10"`}</Code>

                        <SectionH id="xac-thuc">3. Xác thực</SectionH>
                        <p style={{ fontSize: 15, color: 'var(--text)', lineHeight: 1.7, marginBottom: 12 }}>
                            Gửi API key ở header <Chip accent>Authorization</Chip> với mọi request:
                        </p>
                        <Code>{`Authorization: Bearer <API_KEY>`}</Code>
                        <ul style={{ paddingLeft: 22, fontSize: 15, color: 'var(--text)', lineHeight: 1.7, marginTop: 12 }}>
                            <li style={{ marginBottom: 6 }}>
                                Key đã <strong>gắn cứng gian hàng</strong> — KHÔNG cần gửi header <Chip>X-Tenant-Id</Chip>.
                            </li>
                            <li style={{ marginBottom: 6 }}>
                                Key có <strong>toàn quyền</strong> như tài khoản chủ gian hàng — giữ bí mật như mật khẩu.
                            </li>
                            <li>
                                Có thể đặt <strong>thời hạn</strong> và <strong>thu hồi (xóa)</strong> bất cứ lúc nào; key bị xóa sẽ trả <Chip>401</Chip> ngay.
                            </li>
                        </ul>

                        <SectionH id="quy-uoc">4. Quy ước chung</SectionH>
                        <ul style={{ paddingLeft: 22, fontSize: 15, color: 'var(--text)', lineHeight: 1.7 }}>
                            <li style={{ marginBottom: 6 }}><strong>Base URL:</strong> <Chip accent>{BASE}</Chip></li>
                            <li style={{ marginBottom: 6 }}>
                                <strong>Định dạng:</strong> JSON. Thành công <Chip>{'{ "data": ..., "meta": ... }'}</Chip>; lỗi{' '}
                                <Chip>{'{ "error": { "code", "message", "trace_id", "details" } }'}</Chip>.
                            </li>
                            <li style={{ marginBottom: 6 }}><strong>Tiền tệ:</strong> số nguyên VND (không số thập phân).</li>
                            <li style={{ marginBottom: 6 }}>
                                <strong>Thời gian:</strong> ISO-8601 UTC, vd <Chip>2026-06-26T03:37:21Z</Chip>.
                            </li>
                            <li style={{ marginBottom: 6 }}>
                                <strong>Trạng thái đơn:</strong> trả <Chip>code</Chip> + <Chip>status_label</Chip> + <Chip>raw_status</Chip>.
                            </li>
                            <li style={{ marginBottom: 6 }}>
                                <strong>Rate limit:</strong> 120 request/phút (vượt → <Chip>429</Chip>).
                            </li>
                            <li>
                                <strong>Phân trang:</strong> <Chip>?page=&amp;per_page=</Chip> → <Chip>meta.pagination</Chip>.
                            </li>
                        </ul>

                        <SectionH id="don-hang">5. Đơn hàng</SectionH>
                        <Endpoint
                            method="GET"
                            path="/orders"
                            desc="Danh sách đơn (lọc: status, source, q, placed_from, placed_to, page, per_page)."
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
}`}
                        />
                        <Endpoint
                            method="GET"
                            path="/orders/{id}"
                            desc="Chi tiết một đơn, kèm dòng hàng (?include=items)."
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
}`}
                        />
                        <Endpoint
                            method="POST"
                            path="/orders"
                            desc="Tạo đơn thủ công (source=manual)."
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
}`}
                        />
                        <Endpoint
                            method="POST"
                            path="/orders/{id}/ship"
                            desc="Chuẩn bị hàng: tạo vận đơn / lấy phiếu giao hàng của sàn."
                            request={`curl -X POST -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/orders/9876/ship"`}
                            response={`{ "data": { "queued": true, "order_id": 9876 } }`}
                        />

                        <SectionH id="san-pham">6. Sản phẩm &amp; tồn kho</SectionH>
                        <Endpoint
                            method="GET"
                            path="/products"
                            desc="Danh sách sản phẩm / SKU master."
                            request={`curl -H "Authorization: Bearer <API_KEY>" "${BASE}/products?per_page=20"`}
                            response={`{
  "data": [ { "id": 1, "name": "Áo thun", "skus": [ { "id": 1, "sku_code": "AT-01" } ] } ],
  "meta": { "pagination": { "page": 1, "per_page": 20, "total": 50, "total_pages": 3 } }
}`}
                        />
                        <Endpoint
                            method="GET"
                            path="/inventory"
                            desc="Tồn kho theo SKU (on_hand, reserved, available)."
                            request={`curl -H "Authorization: Bearer <API_KEY>" "${BASE}/inventory"`}
                            response={`{
  "data": [ { "sku_id": 1, "sku_code": "AT-01", "on_hand": 120, "reserved": 8, "available": 112 } ]
}`}
                        />

                        <SectionH id="van-don">7. Vận đơn</SectionH>
                        <Endpoint
                            method="GET"
                            path="/shipments"
                            desc="Danh sách vận đơn (lọc: status, carrier, order_id, q)."
                            request={`curl -H "Authorization: Bearer <API_KEY>" "${BASE}/shipments?status=created"`}
                            response={`{
  "data": [ { "id": 555, "order_id": 9876, "carrier": "J&T VN", "tracking_no": "JNTMP00413467", "status": "created" } ]
}`}
                        />

                        <SectionH id="loi">8. Mã lỗi</SectionH>
                        <div style={{
                            background: '#fff',
                            border: '1px solid var(--border-soft)',
                            borderRadius: 'var(--radius-md)',
                            overflow: 'hidden',
                            boxShadow: 'var(--shadow-sm)',
                            marginBottom: 20,
                        }}>
                            <Table
                                size="small"
                                pagination={false}
                                rowKey="code"
                                columns={[
                                    {
                                        title: 'HTTP',
                                        dataIndex: 'code',
                                        width: 130,
                                        render: (v: string) => (
                                            <code style={{
                                                fontFamily: 'var(--mono)',
                                                background: 'var(--bg-soft)',
                                                padding: '2px 8px',
                                                borderRadius: 5,
                                                fontSize: 13,
                                                border: '1px solid var(--border-soft)',
                                            }}>
                                                {v}
                                            </code>
                                        ),
                                    },
                                    { title: 'Ý nghĩa', dataIndex: 'mean' },
                                ]}
                                dataSource={errorRows}
                            />
                        </div>
                        <p style={{ fontSize: 14, color: 'var(--text-muted)', lineHeight: 1.65, marginBottom: 0 }}>
                            Danh sách endpoint liên tục được mở rộng. Cần endpoint chưa có trong tài liệu? Liên hệ hỗ trợ.
                        </p>

                    </Col>
                </Row>
            </div>
        </section>
    );
}
