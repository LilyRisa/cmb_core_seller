import { useMemo, useState } from 'react';
import { Empty, Input, Radio, Segmented, Spin, Tabs, Typography } from 'antd';
import { EnvironmentOutlined, SearchOutlined } from '@ant-design/icons';
import { useDistricts, useProvinces, useWards, type AddressFormat } from '@/lib/masterData';
import type { CustomerAddress } from '@/lib/customers';

/**
 * SPEC 0021 — AddressPicker dùng trong tạo/sửa đơn manual.
 *
 * Tab "Địa chỉ mới" / "Địa chỉ cũ" giờ ám chỉ HỆ HÀNH CHÍNH chứ không phải nguồn cache khách:
 *   - "Địa chỉ mới (2 cấp)": Tỉnh → Phường/Xã, dữ liệu từ AddressKit cas.so. Áp dụng sau cải
 *     cách hành chính VN 2025 (sáp nhập tỉnh + bỏ cấp quận/huyện).
 *   - "Địa chỉ cũ (3 cấp)": Tỉnh → Quận/Huyện → Phường/Xã, dữ liệu từ provinces.open-api.vn.
 *     Vẫn cần thiết vì nhiều ĐVVC (GHN, GHTK, J&T...) đang dùng code cũ trong tài liệu API.
 *
 * Tab phụ "Từ khách cũ" nằm ngang để tận dụng `customer.addresses_meta` (từ lookup theo SĐT).
 *
 * Component KHÔNG quản state địa chỉ chính — chỉ phát `onPick(value)`. Cha (CreateOrderPage)
 * giữ object `{format, province, province_code, district?, district_code?, ward, ward_code, address}`.
 */
export interface PickedAddress {
    format?: AddressFormat;
    province?: string;
    province_code?: string;
    district?: string;
    district_code?: string;
    ward?: string;
    ward_code?: string;
    address?: string;
    name?: string;
    phone?: string;
}

export function AddressPicker({ value, onPick, oldAddresses = [] }: {
    value?: PickedAddress;
    onPick: (v: PickedAddress) => void;
    oldAddresses?: CustomerAddress[];
}) {
    const hasOld = oldAddresses.length > 0;
    const [tab, setTab] = useState<'admin' | 'history'>(hasOld ? 'history' : 'admin');

    return (
        <div style={{ width: 520, maxWidth: '92vw' }}>
            <Tabs
                size="small" activeKey={tab} onChange={(k) => setTab(k as 'admin' | 'history')}
                items={[
                    { key: 'admin', label: 'Danh mục hành chính', children: <AdminPicker value={value} onPick={onPick} /> },
                    { key: 'history', label: `Từ khách cũ${hasOld ? ` (${oldAddresses.length})` : ''}`, disabled: !hasOld, children: <CustomerAddressTab addresses={oldAddresses} onPick={onPick} /> },
                ]}
            />
        </div>
    );
}

/** Cascade picker với toggle "Mới 2 cấp / Cũ 3 cấp". Lưu format vào kết quả để BE biết hệ nào. */
function AdminPicker({ value, onPick }: { value?: PickedAddress; onPick: (v: PickedAddress) => void }) {
    const [format, setFormat] = useState<AddressFormat>((value?.format as AddressFormat) ?? 'new');
    const [provinceCode, setProvinceCode] = useState<string | undefined>(value?.province_code);
    const [districtCode, setDistrictCode] = useState<string | undefined>(value?.district_code);
    const [pQ, setPQ] = useState('');
    const [dQ, setDQ] = useState('');
    const [wQ, setWQ] = useState('');
    const { data: provinces, isFetching: lp } = useProvinces(format);
    const { data: districts, isFetching: ld } = useDistricts(provinceCode, format);
    const wardParent = format === 'new' ? provinceCode : districtCode;
    const { data: wards, isFetching: lw } = useWards(wardParent, format);

    const fp = useMemo(() => (provinces ?? []).filter((p) => p.name.toLowerCase().includes(pQ.toLowerCase())), [provinces, pQ]);
    const fd = useMemo(() => (districts ?? []).filter((d) => d.name.toLowerCase().includes(dQ.toLowerCase())), [districts, dQ]);
    const fw = useMemo(() => (wards ?? []).filter((w) => w.name.toLowerCase().includes(wQ.toLowerCase())), [wards, wQ]);

    const onFormatChange = (v: string | number) => {
        const f = v as AddressFormat;
        setFormat(f);
        setProvinceCode(undefined);
        setDistrictCode(undefined);
    };

    return (
        <>
            <div style={{ marginBottom: 8 }}>
                <Segmented
                    size="small" value={format} onChange={onFormatChange}
                    options={[
                        { value: 'new', label: 'Mới (2 cấp)' },
                        { value: 'old', label: 'Cũ (3 cấp)' },
                    ]}
                />
                <Typography.Text type="secondary" style={{ fontSize: 11, marginInlineStart: 8 }}>
                    {format === 'new' ? 'Sau cải cách 2025 — Tỉnh + Phường/Xã.' : 'Pre-2025 — Tỉnh, Quận/Huyện, Phường/Xã. ĐVVC thường dùng hệ này.'}
                </Typography.Text>
            </div>
            <Tabs
                size="small" tabPosition="top"
                items={[
                    {
                        key: 'province', label: 'Tỉnh / Thành phố',
                        children: <List
                            loading={lp} items={fp}
                            renderRow={(p) => (
                                <div key={p.code} onClick={() => { setProvinceCode(p.code); setDistrictCode(undefined); }}
                                    style={rowStyle(provinceCode === p.code)}>{p.name}</div>
                            )}
                            search={<Input allowClear prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm tỉnh/thành…" onChange={(e) => setPQ(e.target.value)} />}
                            empty="Không có tỉnh/thành phù hợp."
                        />,
                    },
                    ...(format === 'old' ? [{
                        key: 'district', label: 'Quận / Huyện', disabled: !provinceCode,
                        children: <List
                            loading={ld} items={fd}
                            renderRow={(d) => (
                                <div key={d.code} onClick={() => setDistrictCode(d.code)}
                                    style={rowStyle(districtCode === d.code)}>{d.name}</div>
                            )}
                            search={<Input allowClear prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm quận/huyện…" onChange={(e) => setDQ(e.target.value)} />}
                            empty={provinceCode ? 'Không có quận/huyện phù hợp.' : 'Chọn tỉnh/thành trước.'}
                        />,
                    }] : []),
                    {
                        key: 'ward', label: 'Phường / Xã', disabled: !wardParent,
                        children: <List
                            loading={lw} items={fw}
                            renderRow={(w) => {
                                const p = provinces?.find((x) => x.code === provinceCode);
                                const d = districts?.find((x) => x.code === districtCode);
                                return (
                                    <div key={w.code} onClick={() => onPick({
                                        format,
                                        province: p?.name, province_code: p?.code,
                                        district: d?.name, district_code: d?.code,
                                        ward: w.name, ward_code: w.code,
                                        address: value?.address, name: value?.name, phone: value?.phone,
                                    })} style={rowStyle(false)}>{w.name}</div>
                                );
                            }}
                            search={<Input allowClear prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm phường/xã…" onChange={(e) => setWQ(e.target.value)} />}
                            empty={wardParent ? 'Không có phường/xã phù hợp.' : 'Chọn cấp trên trước.'}
                        />,
                    },
                ]}
            />
        </>
    );
}

function CustomerAddressTab({ addresses, onPick }: { addresses: CustomerAddress[]; onPick: (v: PickedAddress) => void }) {
    if (addresses.length === 0) return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Khách chưa có địa chỉ giao trước đó." />;
    return (
        <Radio.Group style={{ width: '100%' }} onChange={(e) => {
            const a = addresses[e.target.value];
            if (!a) return;
            onPick({
                // Địa chỉ cũ của khách thường lưu theo hệ cũ (province/district/ward); thử map format='old'.
                format: 'old',
                province: (a.province ?? a.city) as string | undefined,
                province_code: a.province_id != null ? String(a.province_id) : undefined,
                district: a.district ?? undefined,
                district_code: a.district_id != null ? String(a.district_id) : undefined,
                ward: a.ward ?? undefined,
                ward_code: a.ward_code ?? undefined,
                address: (a.address ?? a.detail) as string | undefined,
                name: a.name ?? undefined,
                phone: a.phone ?? undefined,
            });
        }}>
            <div style={{ maxHeight: 320, overflowY: 'auto' }}>
                {addresses.map((a, i) => {
                    const line = [a.address ?? a.detail, a.ward, a.district, a.province ?? a.city].filter(Boolean).join(', ');
                    return (
                        <Radio key={i} value={i} style={{ display: 'flex', padding: '8px 10px', borderBottom: '1px solid #fafafa' }}>
                            <div>
                                {a.name && <Typography.Text strong>{a.name}</Typography.Text>}
                                {a.phone && <Typography.Text type="secondary" style={{ marginInlineStart: 8 }}>· {a.phone}</Typography.Text>}
                                <div><EnvironmentOutlined style={{ color: '#bfbfbf', marginInlineEnd: 4 }} /><Typography.Text>{line || '(địa chỉ trống)'}</Typography.Text></div>
                            </div>
                        </Radio>
                    );
                })}
            </div>
        </Radio.Group>
    );
}

function List<T>({ items, loading, renderRow, search, empty }: { items: T[]; loading?: boolean; renderRow: (r: T) => React.ReactNode; search?: React.ReactNode; empty: string }) {
    return (
        <div>
            {search ? <div style={{ marginBottom: 8 }}>{search}</div> : null}
            <div style={{ maxHeight: 280, overflowY: 'auto', borderTop: '1px solid #f0f0f0' }}>
                {loading && items.length === 0 ? <div style={{ padding: 24, textAlign: 'center' }}><Spin size="small" /></div>
                    : items.length === 0 ? <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={empty} style={{ margin: 16 }} />
                    : items.map(renderRow)}
            </div>
        </div>
    );
}

const rowStyle = (active: boolean): React.CSSProperties => ({
    padding: '8px 10px', cursor: 'pointer', borderBottom: '1px solid #fafafa',
    background: active ? '#e6f4ff' : undefined,
    color: active ? '#1677ff' : undefined, fontWeight: active ? 600 : undefined,
});
