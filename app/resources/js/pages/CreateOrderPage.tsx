import { Link, useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Switch, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { OrderItemsEditor, type OrderLineInput } from '@/components/OrderItemsEditor';
import { errorMessage } from '@/lib/api';
import { useCreateManualOrder } from '@/lib/inventory';

export function CreateOrderPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const create = useCreateManualOrder();

    const submit = () => form.validateFields().then((v) => {
        const lines: OrderLineInput[] = v.items ?? [];
        if (lines.length === 0) { message.error('Cần ít nhất một dòng hàng.'); return; }
        if (lines.some((l) => l.uploading)) { message.error('Đang tải ảnh — vui lòng đợi.'); return; }
        if (lines.some((l) => !l.sku_id && l.name.trim() === '')) { message.error('Dòng "sản phẩm nhanh" phải có tên.'); return; }
        // SKU lines: name/image filled server-side from the SKU (ManualOrderService::normalizeItems).
        const items = lines.map((l) => ({
            sku_id: l.sku_id,
            name: l.sku_id ? undefined : l.name.trim(),
            image: l.sku_id ? undefined : (l.image || undefined),
            quantity: l.quantity, unit_price: l.unit_price, discount: l.discount,
        }));
        create.mutate({
            sub_source: v.sub_source || undefined,
            status: v.status || undefined,
            buyer: { name: v.buyer_name || undefined, phone: v.buyer_phone || undefined, address: v.buyer_address || undefined, province: v.buyer_province || undefined },
            items, shipping_fee: v.shipping_fee ?? 0, is_cod: !!v.is_cod, cod_amount: v.cod_amount ?? undefined, note: v.note || undefined,
        }, { onSuccess: (o) => { message.success('Đã tạo đơn'); navigate(`/orders/${o.id}`); }, onError: (e) => message.error(errorMessage(e)) });
    }).catch(() => message.error('Vui lòng kiểm tra lại thông tin.'));

    return (
        <div>
            <PageHeader
                title={<Space size="middle"><Link to="/orders"><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>Tạo đơn thủ công</span></Space>}
                subtitle="Đơn nguồn ngoài sàn (website / Facebook / Zalo / hotline) — trừ chung kho, vào cùng luồng xử lý" />
            <Form form={form} layout="vertical" initialValues={{ status: 'processing', items: [], shipping_fee: 0 }}>
                <Row gutter={16}>
                    <Col xs={24} lg={16}>
                        <Card title="Hàng hoá" style={{ marginBottom: 16 }}>
                            <Form.Item name="items" noStyle><OrderItemsEditor /></Form.Item>
                        </Card>
                    </Col>
                    <Col xs={24} lg={8}>
                        <Card title="Khách hàng" size="small" style={{ marginBottom: 16 }}>
                            <Form.Item name="buyer_name" label="Tên"><Input maxLength={255} /></Form.Item>
                            <Form.Item name="buyer_phone" label="SĐT"><Input placeholder="0912xxxxxx" maxLength={32} /></Form.Item>
                            <Form.Item name="buyer_address" label="Địa chỉ"><Input.TextArea rows={2} maxLength={500} /></Form.Item>
                            <Form.Item name="buyer_province" label="Tỉnh/Thành"><Input maxLength={120} /></Form.Item>
                        </Card>
                        <Card title="Thanh toán & vận chuyển" size="small" style={{ marginBottom: 16 }}>
                            <Form.Item name="sub_source" label="Nguồn"><Select allowClear placeholder="manual" options={[{ value: 'website', label: 'Website' }, { value: 'facebook', label: 'Facebook' }, { value: 'zalo', label: 'Zalo' }, { value: 'hotline', label: 'Hotline' }]} /></Form.Item>
                            <Form.Item name="status" label="Trạng thái khởi tạo"><Select options={[{ value: 'processing', label: 'Đang xử lý' }, { value: 'pending', label: 'Chờ xử lý' }]} /></Form.Item>
                            <Form.Item name="shipping_fee" label="Phí vận chuyển (₫)"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                            <Form.Item name="is_cod" label="Thu hộ (COD)" valuePropName="checked"><Switch /></Form.Item>
                            <Form.Item name="cod_amount" label="Số tiền COD (₫) — để trống = tổng đơn"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                            <Form.Item name="note" label="Ghi chú"><Input.TextArea rows={2} maxLength={2000} /></Form.Item>
                        </Card>
                        <Button type="primary" block size="large" loading={create.isPending} onClick={submit}>Tạo đơn</Button>
                        <Typography.Paragraph type="secondary" style={{ marginTop: 8, fontSize: 12 }}>Tạo đơn sẽ giữ tồn (reserve) ngay cho các dòng có SKU và khớp vào sổ khách hàng nếu có SĐT. Dòng "sản phẩm nhanh" không theo dõi tồn kho.</Typography.Paragraph>
                    </Col>
                </Row>
            </Form>
        </div>
    );
}
