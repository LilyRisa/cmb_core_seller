import { useMemo, useState } from 'react';
import { Empty, Input, Radio, Spin, Tabs, Typography } from 'antd';
import { EnvironmentOutlined, SearchOutlined } from '@ant-design/icons';
import { useDistricts, useProvinces, useWards } from '@/lib/masterData';
import type { CustomerAddress } from '@/lib/customers';

/**
 * SPEC 0021 — AddressPicker dùng trong tạo/sửa đơn manual (taodon2.png).
 *
 * Hai tab:
 *  - "Địa chỉ mới" (default): cascade Tỉnh → Quận → Phường lấy từ /master-data/* (proxy GHN).
 *    Mỗi level trả `id` (province/district) hoặc `code` (ward) ⇒ map sẵn vào shipping_address.{province_id,
 *    district_id, ward_code} ⇒ GhnConnector::createShipment dùng trực tiếp, không cần resolve thêm.
 *  - "Địa chỉ cũ": lấy từ customer.addresses_meta của khách đã lookup theo SĐT (top 5 địa chỉ gần nhất —
 *    {@see CustomerLinkingService::mergeAddresses}). Chọn 1 ⇒ điền nguyên cụm vào form (kể cả mã GHN nếu có).
 *
 * Component KHÔNG quản state địa chỉ chính — chỉ phát `onPick(value)` khi user xác nhận. Cha (CreateOrderPage)
 * giữ object `{province, province_id, district, district_id, ward, ward_code, address}` rồi gửi vào `recipient`.
 */
export interface PickedAddress {
    province?: string;
    province_id?: number;
    district?: string;
    district_id?: number;
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
    const [tab, setTab] = useState<'new' | 'old'>(hasOld ? 'old' : 'new');

    return (
        <div style={{ width: 480, maxWidth: '90vw' }}>
            <Tabs
                size="small" activeKey={tab} onChange={(k) => setTab(k as 'new' | 'old')}
                items={[
                    { key: 'new', label: 'Địa chỉ mới', children: <NewAddressTab value={value} onPick={onPick} /> },
                    { key: 'old', label: `Địa chỉ cũ${hasOld ? ` (${oldAddresses.length})` : ''}`, disabled: !hasOld, children: <OldAddressTab addresses={oldAddresses} onPick={onPick} /> },
                ]}
            />
        </div>
    );
}

function NewAddressTab({ value, onPick }: { value?: PickedAddress; onPick: (v: PickedAddress) => void }) {
    const [provinceId, setProvinceId] = useState<number | undefined>(value?.province_id ?? undefined);
    const [districtId, setDistrictId] = useState<number | undefined>(value?.district_id ?? undefined);
    const [pQ, setPQ] = useState('');
    const [dQ, setDQ] = useState('');
    const [wQ, setWQ] = useState('');
    const { data: provinces, isFetching: lp } = useProvinces();
    const { data: districts, isFetching: ld } = useDistricts(provinceId);
    const { data: wards, isFetching: lw } = useWards(districtId);

    const fp = useMemo(() => (provinces ?? []).filter((p) => p.name.toLowerCase().includes(pQ.toLowerCase())), [provinces, pQ]);
    const fd = useMemo(() => (districts ?? []).filter((d) => d.name.toLowerCase().includes(dQ.toLowerCase())), [districts, dQ]);
    const fw = useMemo(() => (wards ?? []).filter((w) => w.name.toLowerCase().includes(wQ.toLowerCase())), [wards, wQ]);

    return (
        <Tabs
            size="small" tabPosition="top"
            items={[
                {
                    key: 'province', label: 'Tỉnh thành phố',
                    children: <List
                        loading={lp} items={fp}
                        renderRow={(p) => (
                            <div key={p.id} onClick={() => { setProvinceId(p.id); setDistrictId(undefined); }}
                                style={rowStyle(provinceId === p.id)}>{p.name}</div>
                        )}
                        search={<Input allowClear prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm tỉnh/thành…" onChange={(e) => setPQ(e.target.value)} />}
                        empty="Không có tỉnh/thành phù hợp."
                    />,
                },
                {
                    key: 'district', label: 'Quận huyện', disabled: !provinceId,
                    children: <List
                        loading={ld} items={fd}
                        renderRow={(d) => (
                            <div key={d.id} onClick={() => setDistrictId(d.id)}
                                style={rowStyle(districtId === d.id)}>{d.name}</div>
                        )}
                        search={<Input allowClear prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm quận/huyện…" onChange={(e) => setDQ(e.target.value)} />}
                        empty={provinceId ? 'Không có quận/huyện phù hợp.' : 'Chọn tỉnh/thành trước.'}
                    />,
                },
                {
                    key: 'ward', label: 'Phường xã', disabled: !districtId,
                    children: <List
                        loading={lw} items={fw}
                        renderRow={(w) => {
                            const p = provinces?.find((x) => x.id === provinceId);
                            const d = districts?.find((x) => x.id === districtId);
                            return (
                                <div key={w.code} onClick={() => onPick({
                                    province: p?.name, province_id: p?.id,
                                    district: d?.name, district_id: d?.id,
                                    ward: w.name, ward_code: w.code,
                                    address: value?.address, name: value?.name, phone: value?.phone,
                                })} style={rowStyle(false)}>{w.name}</div>
                            );
                        }}
                        search={<Input allowClear prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm phường/xã…" onChange={(e) => setWQ(e.target.value)} />}
                        empty={districtId ? 'Không có phường/xã phù hợp.' : 'Chọn quận/huyện trước.'}
                    />,
                },
            ]}
        />
    );
}

function OldAddressTab({ addresses, onPick }: { addresses: CustomerAddress[]; onPick: (v: PickedAddress) => void }) {
    if (addresses.length === 0) return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Khách chưa có địa chỉ giao trước đó." />;
    return (
        <Radio.Group style={{ width: '100%' }} onChange={(e) => {
            const a = addresses[e.target.value];
            if (!a) return;
            onPick({
                province: (a.province ?? a.city) as string | undefined,
                province_id: a.province_id ?? undefined,
                district: a.district ?? undefined,
                district_id: a.district_id ?? undefined,
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
