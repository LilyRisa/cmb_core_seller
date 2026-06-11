import { Alert, Divider, Empty, Input, InputNumber, Radio, Select, Space, Spin, Typography } from 'antd';
import { useAttributes } from './hooks';
import type { ListingAttribute } from './api';

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
    // single select — Radio cho danh sách nhỏ, Select cho danh sách lớn
    if (attr.values.length <= RADIO_MAX) {
        return (
            <Radio.Group value={value} onChange={(e) => onChange(e.target.value)}>
                <Space direction="vertical">
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
}: {
    provider: string;
    channelAccountId: number;
    categoryId: string | null;
    value: Record<string, unknown>;
    onChange: (attributes: Record<string, unknown>) => void;
}) {
    const { data: attributes, isLoading, isError } = useAttributes(provider, channelAccountId, categoryId);

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
            {saleProps.length > 0 && (
                <>
                    <Divider orientation="left" plain style={{ marginTop: 0 }}>
                        Thuộc tính phân loại (biến thể)
                    </Divider>
                    {saleProps.map((attr) => (
                        <FieldRow key={attr.id} attr={attr} value={value[attr.id]} onChange={(v) => setOne(attr.id, v)} />
                    ))}
                </>
            )}
            {normalAttrs.length > 0 && (
                <>
                    <Divider orientation="left" plain>
                        Thông tin sản phẩm
                    </Divider>
                    {normalAttrs.map((attr) => (
                        <FieldRow key={attr.id} attr={attr} value={value[attr.id]} onChange={(v) => setOne(attr.id, v)} />
                    ))}
                </>
            )}
        </div>
    );
}
