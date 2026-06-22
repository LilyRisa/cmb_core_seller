import { useEffect } from 'react';
import { Alert, Col, Divider, Empty, Input, InputNumber, Radio, Row, Select, Space, Spin, Typography } from 'antd';
import { useAttributes } from './hooks';
import type { ListingAttribute } from './api';

/** Thuộc tính được coi là đã điền: chuỗi/số khác rỗng, hoặc mảng (đa chọn) khác rỗng. */
function isFilled(v: unknown): boolean {
    if (Array.isArray(v)) return v.length > 0;
    if (typeof v === 'number') return true;
    return v !== undefined && v !== null && String(v).trim() !== '';
}

/** Ngưỡng số lựa chọn: <= ngưỡng dùng Radio, vượt thì dùng Select (theo UI rule). */
const RADIO_MAX = 5;

function AttributeField({
    attr,
    value,
    onChange,
}: {
    attr: ListingAttribute;
    value: unknown;
    onChange: (v: unknown) => void;
}) {
    if (attr.input_type === 'number') {
        return (
            <InputNumber
                style={{ width: '100%' }}
                value={value as number | undefined}
                onChange={(v) => onChange(v)}
                placeholder={attr.name}
            />
        );
    }
    if (attr.input_type === 'text') {
        return (
            <Input
                value={(value as string) ?? ''}
                onChange={(e) => onChange(e.target.value)}
                placeholder={attr.name}
            />
        );
    }
    // select / multi_select
    if (attr.input_type === 'multi_select') {
        return (
            <Select
                mode="multiple"
                style={{ width: '100%' }}
                value={(value as string[]) ?? []}
                onChange={(v) => onChange(v)}
                placeholder={`Chọn ${attr.name}`}
                options={attr.values.map((o) => ({ value: o.id, label: o.name }))}
                showSearch
                optionFilterProp="label"
            />
        );
    }
    // single select — Radio cho danh sách nhỏ (gói gọn theo hàng ngang), Select cho danh sách lớn
    if (attr.values.length <= RADIO_MAX) {
        return (
            <Radio.Group value={value} onChange={(e) => onChange(e.target.value)}>
                <Space wrap size={[12, 4]}>
                    {attr.values.map((o) => (
                        <Radio key={o.id} value={o.id}>
                            {o.name}
                        </Radio>
                    ))}
                </Space>
            </Radio.Group>
        );
    }
    return (
        <Select
            style={{ width: '100%' }}
            value={(value as string) ?? undefined}
            onChange={(v) => onChange(v)}
            placeholder={`Chọn ${attr.name}`}
            options={attr.values.map((o) => ({ value: o.id, label: o.name }))}
            showSearch
            optionFilterProp="label"
        />
    );
}

function FieldRow({
    attr,
    value,
    onChange,
}: {
    attr: ListingAttribute;
    value: unknown;
    onChange: (v: unknown) => void;
}) {
    return (
        <div style={{ marginBottom: 12 }}>
            <Typography.Text>
                {attr.required && <span style={{ color: '#EF4444', marginRight: 4 }}>*</span>}
                {attr.name}
            </Typography.Text>
            <div style={{ marginTop: 4 }}>
                <AttributeField attr={attr} value={value} onChange={onChange} />
            </div>
        </div>
    );
}

export function AttributeForm({
    provider,
    channelAccountId,
    categoryId,
    value,
    onChange,
    onMissingRequiredChange,
}: {
    provider: string;
    channelAccountId: number;
    categoryId: string | null;
    value: Record<string, unknown>;
    onChange: (attributes: Record<string, unknown>) => void;
    /** Báo lên các thuộc tính BẮT BUỘC còn trống để trang cha chặn đẩy. */
    onMissingRequiredChange?: (missingNames: string[]) => void;
}) {
    const { data: attributes, isLoading, isError } = useAttributes(provider, channelAccountId, categoryId);

    const missingRequired = (attributes ?? [])
        .filter((a) => a.required && !isFilled(value[a.id]))
        .map((a) => a.name);

    // Đẩy danh sách thiếu lên cha mỗi khi thay đổi (join để so sánh ổn định, tránh loop).
    useEffect(() => {
        onMissingRequiredChange?.(missingRequired);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [missingRequired.join('|'), onMissingRequiredChange]);

    const setOne = (id: string, v: unknown) => {
        onChange({ ...value, [id]: v });
    };

    if (!categoryId) {
        return <Alert type="info" showIcon message="Chọn ngành hàng trước để tải thuộc tính." />;
    }
    if (isLoading) {
        return <Spin />;
    }
    if (isError) {
        return <Alert type="error" showIcon message="Không tải được thuộc tính ngành hàng." />;
    }
    if (!attributes || attributes.length === 0) {
        return <Empty description="Ngành hàng này không có thuộc tính bắt buộc." />;
    }

    const saleProps = attributes.filter((a) => a.is_sale_prop);
    const normalAttrs = attributes.filter((a) => !a.is_sale_prop);

    return (
        <div>
            {missingRequired.length > 0 && (
                <Alert
                    type="warning"
                    showIcon
                    style={{ marginBottom: 12 }}
                    message="Thiếu thuộc tính bắt buộc"
                    description={`Vui lòng điền: ${missingRequired.join(', ')}`}
                />
            )}
            {saleProps.length > 0 && (
                <>
                    <Divider orientation="left" plain style={{ marginTop: 0 }}>
                        Thuộc tính phân loại (biến thể)
                    </Divider>
                    <Row gutter={[16, 0]}>
                        {saleProps.map((attr) => (
                            <Col xs={24} sm={12} lg={8} key={attr.id}>
                                <FieldRow attr={attr} value={value[attr.id]} onChange={(v) => setOne(attr.id, v)} />
                            </Col>
                        ))}
                    </Row>
                </>
            )}
            {normalAttrs.length > 0 && (
                <>
                    <Divider orientation="left" plain>
                        Thông tin sản phẩm
                    </Divider>
                    <Row gutter={[16, 0]}>
                        {normalAttrs.map((attr) => (
                            <Col xs={24} sm={12} lg={8} key={attr.id}>
                                <FieldRow attr={attr} value={value[attr.id]} onChange={(v) => setOne(attr.id, v)} />
                            </Col>
                        ))}
                    </Row>
                </>
            )}
        </div>
    );
}
