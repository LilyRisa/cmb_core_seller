import { Avatar, Select, Space, Tag, Typography } from 'antd';
import { FacebookFilled } from '@ant-design/icons';
import { type MessagingChannel, useMessagingChannels } from '@/lib/messagingConfig';

/** SPEC 0035 — UI dùng chung cho phạm vi page (chọn + hiển thị) của rule/flow/tài liệu AI. */

const pageName = (c: MessagingChannel) => c.name || c.shop_name || c.external_shop_id;

/**
 * Chọn nhiều page — avatar + tên + ID + tìm kiếm nhanh. Tương thích Form.Item
 * (nhận value/onChange). Dùng `optionRender` cho giao diện, `label` (tên + ID) để search.
 * `provider` lọc theo nền tảng (vd 'facebook_page', 'zalo_oa'); bỏ trống = mọi nền tảng.
 */
export function PageMultiSelect({ value, onChange, disabled, placeholder, provider }: {
    value?: number[];
    onChange?: (v: number[]) => void;
    disabled?: boolean;
    placeholder?: string;
    provider?: string;
}) {
    const channels = useMessagingChannels(provider).data ?? [];

    return (
        <Select
            mode="multiple"
            value={value}
            onChange={onChange}
            disabled={disabled}
            placeholder={placeholder ?? 'Chọn trang áp dụng'}
            showSearch
            optionFilterProp="label"
            maxTagCount="responsive"
            options={channels.map((c) => ({
                value: c.id,
                // label = chuỗi search (tên + ID); optionRender lo phần hiển thị có avatar.
                label: `${pageName(c)} ${c.external_shop_id}`,
                page: c,
            }))}
            optionRender={(opt) => {
                const c = (opt.data as { page: MessagingChannel }).page;
                return (
                    <Space>
                        <Avatar size={22} src={c.avatar_url || undefined} icon={<FacebookFilled />}
                            style={{ background: c.avatar_url ? undefined : '#1877F2' }} />
                        <span>{pageName(c)}</span>
                        <Typography.Text type="secondary" style={{ fontSize: 11 }}>· ID: {c.external_shop_id}</Typography.Text>
                    </Space>
                );
            }}
        />
    );
}

/**
 * Hiển thị phạm vi page trong bảng: "Tất cả trang", "Chưa chọn trang", hoặc avatar+tên
 * các page đã gán (hiện 2, còn lại gộp "+N").
 */
export function PageScopeTags({ appliesAllPages, channelAccountIds }: {
    appliesAllPages: boolean;
    channelAccountIds: number[];
}) {
    const channels = useMessagingChannels().data ?? [];

    if (appliesAllPages) {
        return <Tag color="default">Tất cả trang</Tag>;
    }

    const ids = channelAccountIds ?? [];
    if (ids.length === 0) {
        return <Tag color="warning">Chưa chọn trang</Tag>;
    }

    const byId = new Map(channels.map((c) => [c.id, c]));
    const shown = ids.slice(0, 2);

    return (
        <Space size={4} wrap>
            {shown.map((id) => {
                const c = byId.get(id);
                return (
                    <Tag key={id} style={{ display: 'inline-flex', alignItems: 'center', gap: 4, paddingLeft: 2 }}>
                        <Avatar size={16} src={c?.avatar_url || undefined} icon={<FacebookFilled />}
                            style={{ background: c?.avatar_url ? undefined : '#1877F2' }} />
                        {c ? pageName(c) : `#${id}`}
                    </Tag>
                );
            })}
            {ids.length > shown.length && <Tag>+{ids.length - shown.length}</Tag>}
        </Space>
    );
}
