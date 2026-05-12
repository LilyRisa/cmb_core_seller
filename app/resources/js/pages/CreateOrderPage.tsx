import { Link, useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Switch, Typography } from 'antd';
import { ArrowLeftOutlined, DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCreateManualOrder, useSkus } from '@/lib/inventory';

export function CreateOrderPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const create = useCreateManualOrder();
    const skuQuery = useSkus({ per_page: 50 });
    const skuOptions = (skuQuery.data?.data ?? []).map((s) => ({ value: s.id, label: `${s.sku_code} · ${s.name}`, name: s.name }));

    const submit = () => form.validateFields().then((v) => {
        const items = (v.items ?? []).filter((i: { sku_id?: number }) => i?.sku_id).map((i: { sku_id: number; quantity?: number; unit_price?: number; discount?: number }) => ({
            sku_id: i.sku_id,
            name: skuOptions.find((o) => o.value === i.sku_id)?.name ?? 'Hàng',
            quantity: i.quantity ?? 1, unit_price: i.unit_price ?? 0, discount: i.discount ?? 0,
        }));
        if (items.length === 0) { message.error('Cần ít nhất một dòng hàng'); return; }
        create.mutate({
            sub_source: v.sub_source || undefined,
            status: v.status || undefined,
            buyer: { name: v.buyer_name || undefined, phone: v.buyer_phone || undefined, address: v.buyer_address || undefined, province: v.buyer_province || undefined },
            items, shipping_fee: v.shipping_fee ?? 0, is_cod: !!v.is_cod, cod_amount: v.cod_amount ?? undefined, note: v.note || undefined,
        }, { onSuccess: (o) => { message.success('Đã tạo đơn'); navigate(`/orders/${o.id}`); }, onError: (e) => message.error(errorMessage(e)) });
    });

    return (
        <div>
            <PageHeader
                title={<Space size="middle"><Link to="/orders"><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>Tạo đơn thủ công</span></Space>}
                subtitle="Đơn nguồn ngoài sàn (website / Facebook / Zalo / hotline) — trừ chung kho, vào cùng luồng xử lý" />
            <Form form={form} layout="vertical" initialValues={{ status: 'processing', items: [{}], shipping_fee: 0 }}>
                <Row gutter={16}>
                    <Col xs={24} lg={16}>
                        <Card title="Hàng hoá" style={{ marginBottom: 16 }}>
                            <Form.List name="items">
                                {(fields, { add, remove }) => (
                                    <>
                                        {fields.map((field) => (
                                            <Space key={field.key} align="baseline" style={{ display: 'flex', marginBottom: 8 }} wrap>
                                                <Form.Item {...field} name={[field.name, 'sku_id']} rules={[{ required: true, message: 'Chọn SKU' }]} style={{ minWidth: 280, marginBottom: 0 }}>
                                                    <Select showSearch optionFilterProp="label" placeholder="Chọn SKU" options={skuOptions} loading={skuQuery.isLoading} />
                                                </Form.Item>
                                                <Form.Item {...field} name={[field.name, 'quantity']} style={{ marginBottom: 0 }}><InputNumber min={1} placeholder="SL" /></Form.Item>
                                                <Form.Item {...field} name={[field.name, 'unit_price']} style={{ marginBottom: 0 }}><InputNumber min={0} placeholder="Đơn giá ₫" style={{ width: 130 }} /></Form.Item>
                                                <Form.Item {...field} name={[field.name, 'discount']} style={{ marginBottom: 0 }}><InputNumber min={0} placeholder="Giảm ₫" style={{ width: 110 }} /></Form.Item>
                                                <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(field.name)} disabled={fields.length === 1} />
                                            </Space>
                                        ))}
                                        <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({})} block>Thêm dòng hàng</Button>
                                    </>
                                )}
                            </Form.List>
                        </Card>
                    </Col>
                    <Col xs={24} lg={8}>
                        <Card title="Khách hàng" size="small" style={{ marginBottom: 16 }}>
                            <Form.Item name="buyer_name" label="Tên"><Input /></Form.Item>
                            <Form.Item name="buyer_phone" label="SĐT"><Input placeholder="0912xxxxxx" /></Form.Item>
                            <Form.Item name="buyer_address" label="Địa chỉ"><Input.TextArea rows={2} /></Form.Item>
                            <Form.Item name="buyer_province" label="Tỉnh/Thành"><Input /></Form.Item>
                        </Card>
                        <Card title="Thanh toán & vận chuyển" size="small" style={{ marginBottom: 16 }}>
                            <Form.Item name="sub_source" label="Nguồn"><Select allowClear placeholder="manual" options={[{ value: 'website', label: 'Website' }, { value: 'facebook', label: 'Facebook' }, { value: 'zalo', label: 'Zalo' }, { value: 'hotline', label: 'Hotline' }]} /></Form.Item>
                            <Form.Item name="status" label="Trạng thái khởi tạo"><Select options={[{ value: 'processing', label: 'Đang xử lý' }, { value: 'pending', label: 'Chờ xử lý' }]} /></Form.Item>
                            <Form.Item name="shipping_fee" label="Phí vận chuyển (₫)"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                            <Form.Item name="is_cod" label="Thu hộ (COD)" valuePropName="checked"><Switch /></Form.Item>
                            <Form.Item name="cod_amount" label="Số tiền COD (₫) — để trống = tổng đơn"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                            <Form.Item name="note" label="Ghi chú"><Input.TextArea rows={2} /></Form.Item>
                        </Card>
                        <Button type="primary" block size="large" loading={create.isPending} onClick={submit}>Tạo đơn</Button>
                        <Typography.Paragraph type="secondary" style={{ marginTop: 8, fontSize: 12 }}>Tạo đơn sẽ giữ tồn (reserve) ngay và khớp vào sổ khách hàng nếu có SĐT.</Typography.Paragraph>
                    </Col>
                </Row>
            </Form>
        </div>
    );
}
