import { useEffect, useMemo, useState } from 'react';
import { Button, Empty, Input, Radio, Segmented, Space, Spin, Tabs, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, CheckOutlined, EnvironmentOutlined, SearchOutlined } from '@ant-design/icons';
import { useDistricts, useProvinces, useWards, type AddressFormat, type District, type Province, type Ward } from '@/lib/masterData';
import { smartFilter } from '@/lib/vnAddressMatch';
import type { CustomerAddress } from '@/lib/customers';

/**
 * SPEC 0021 — AddressPicker dùng trong tạo/sửa đơn manual.
 *
 * Tab "Danh mục hành chính" — Segmented chuyển 2 chuẩn:
 *   - "Mới (2 cấp)": Tỉnh → Phường/Xã (AddressKit cas.so, sau 2025).
 *   - "Cũ (3 cấp)": Tỉnh → Quận → Phường/Xã (provinces.open-api.vn, pre-2025).
 *
 * Tab "Từ khách cũ" — `customer.addresses_meta` (lookup theo SĐT).
 *
 * Cải thiện so với bản cũ:
 *  1. **Auto-advance**: click Tỉnh ⇒ tự nhảy sang tab Quận/Phường. Click Quận ⇒ sang Phường.
 *  2. **Breadcrumb** trên đầu: thấy được Tỉnh/Quận đã chọn + nút Back/Reset.
 *  3. **Smart match**: bỏ dấu + bỏ tiền tố ("ha noi" khớp "Thành phố Hà Nội", "Q1" khớp "Quận 1").
 *  4. **Sort theo score** — exact match > startsWith > contains.
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
    const [tab, setTab] = useState<'admin' | 'history'>(hasOld && !value?.province ? 'history' : 'admin');

    return (
        <div style={{ width: 540, maxWidth: '94vw' }}>
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

type Level = 'province' | 'district' | 'ward';

/** Cascade picker controlled — auto-advance khi pick xong 1 cấp. */
function AdminPicker({ value, onPick }: { value?: PickedAddress; onPick: (v: PickedAddress) => void }) {
    const [format, setFormat] = useState<AddressFormat>((value?.format as AddressFormat) ?? 'new');
    const [provinceCode, setProvinceCode] = useState<string | undefined>(value?.province_code);
    const [districtCode, setDistrictCode] = useState<string | undefined>(value?.district_code);
    const [level, setLevel] = useState<Level>(value?.province_code ? (format === 'old' && !value?.district_code ? 'district' : 'ward') : 'province');
    const [q, setQ] = useState('');

    const { data: provinces = [], isFetching: lp } = useProvinces(format);
    const { data: districts = [], isFetching: ld } = useDistricts(provinceCode, format);
    const wardParent = format === 'new' ? provinceCode : districtCode;
    const { data: wards = [], isFetching: lw } = useWards(wardParent, format);

    // Smart-filter — bỏ dấu + score-based sort.
    const fp = useMemo(() => smartFilter<Province>(provinces, q), [provinces, q]);
    const fd = useMemo(() => smartFilter<District>(districts, q), [districts, q]);
    const fw = useMemo(() => smartFilter<Ward>(wards, q), [wards, q]);

    // Reset query khi đổi cấp ⇒ user không bị filter cũ làm rỗng list cấp mới.
    useEffect(() => { setQ(''); }, [level, format]);

    const onFormatChange = (v: string | number) => {
        const f = v as AddressFormat;
        setFormat(f);
        setProvinceCode(undefined);
        setDistrictCode(undefined);
        setLevel('province');
    };

    const selectedProvince = provinces.find((p) => p.code === provinceCode);
    const selectedDistrict = districts.find((d) => d.code === districtCode);

    const pickProvince = (p: Province) => {
        setProvinceCode(p.code);
        setDistrictCode(undefined);
        setLevel(format === 'old' ? 'district' : 'ward');
    };
    const pickDistrict = (d: District) => {
        setDistrictCode(d.code);
        setLevel('ward');
    };
    const pickWard = (w: Ward) => {
        onPick({
            format,
            province: selectedProvince?.name, province_code: selectedProvince?.code,
            district: selectedDistrict?.name, district_code: selectedDistrict?.code,
            ward: w.name, ward_code: w.code,
            address: value?.address, name: value?.name, phone: value?.phone,
        });
    };
    const back = () => {
        if (level === 'ward') {
            if (format === 'old') { setLevel('district'); setDistrictCode(undefined); }
            else { setLevel('province'); setProvinceCode(undefined); }
        } else if (level === 'district') {
            setLevel('province'); setProvinceCode(undefined);
        }
    };

    return (
        <div>
            <Space direction="vertical" size={6} style={{ width: '100%', marginBottom: 8 }}>
                <Space wrap size={8}>
                    <Segmented
                        size="small" value={format} onChange={onFormatChange}
                        options={[
                            { value: 'new', label: 'Mới (2 cấp)' },
                            { value: 'old', label: 'Cũ (3 cấp)' },
                        ]}
                    />
                    <Typography.Text type="secondary" style={{ fontSize: 11 }}>
                        {format === 'new' ? 'Sau cải cách 2025 — Tỉnh + Phường/Xã.' : 'Pre-2025 — có cấp Quận/Huyện.'}
                    </Typography.Text>
                </Space>
                {/* Breadcrumb crumbs */}
                <Space size={4} wrap>
                    {level !== 'province' && (
                        <Button type="text" size="small" icon={<ArrowLeftOutlined />} onClick={back} style={{ padding: '0 4px' }} />
                    )}
                    <Tag
                        color={level === 'province' ? 'blue' : 'default'}
                        style={{ cursor: 'pointer', margin: 0 }}
                        onClick={() => setLevel('province')}
                    >
                        Tỉnh: {selectedProvince ? selectedProvince.name : <span style={{ color: '#bfbfbf' }}>—</span>}
                    </Tag>
                    {format === 'old' && (
                        <Tag
                            color={level === 'district' ? 'blue' : 'default'}
                            style={{ cursor: provinceCode ? 'pointer' : 'not-allowed', margin: 0, opacity: provinceCode ? 1 : 0.5 }}
                            onClick={() => provinceCode && setLevel('district')}
                        >
                            Quận: {selectedDistrict ? selectedDistrict.name : <span style={{ color: '#bfbfbf' }}>—</span>}
                        </Tag>
                    )}
                    <Tag
                        color={level === 'ward' ? 'blue' : 'default'}
                        style={{ cursor: wardParent ? 'pointer' : 'not-allowed', margin: 0, opacity: wardParent ? 1 : 0.5 }}
                        onClick={() => wardParent && setLevel('ward')}
                    >
                        Phường/Xã: <span style={{ color: '#bfbfbf' }}>chọn…</span>
                    </Tag>
                </Space>
            </Space>

            {/* Search — apply cho cấp đang hiện */}
            <Input
                allowClear autoFocus key={`${format}-${level}`}
                prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />}
                placeholder={
                    level === 'province' ? 'Tìm tỉnh / thành (vd: "ha noi", "tp hcm"…)' :
                    level === 'district' ? 'Tìm quận / huyện (vd: "binh thanh", "q.1"…)' :
                    'Tìm phường / xã / đặc khu…'
                }
                value={q} onChange={(e) => setQ(e.target.value)}
                style={{ marginBottom: 8 }}
            />

            {/* List of items at current level */}
            {level === 'province' && (
                <List loading={lp} items={fp} empty="Không có tỉnh/thành phù hợp."
                    renderRow={(p) => (
                        <Row key={p.code} active={provinceCode === p.code} onClick={() => pickProvince(p)} suffix={provinceCode === p.code ? <CheckOutlined style={{ color: '#1677ff' }} /> : null}>
                            {p.name}
                            {p.division_type && <span className="muted"> · {p.division_type}</span>}
                        </Row>
                    )}
                />
            )}
            {level === 'district' && format === 'old' && (
                <List loading={ld} items={fd} empty={provinceCode ? 'Không có quận/huyện phù hợp.' : 'Chọn tỉnh/thành trước.'}
                    renderRow={(d) => (
                        <Row key={d.code} active={districtCode === d.code} onClick={() => pickDistrict(d)} suffix={districtCode === d.code ? <CheckOutlined style={{ color: '#1677ff' }} /> : null}>
                            {d.name}
                            {d.division_type && <span className="muted"> · {d.division_type}</span>}
                        </Row>
                    )}
                />
            )}
            {level === 'ward' && (
                <List loading={lw} items={fw} empty={wardParent ? 'Không có phường/xã phù hợp.' : 'Chọn cấp trên trước.'}
                    renderRow={(w) => (
                        <Row key={w.code} onClick={() => pickWard(w)}>
                            {w.name}
                            {w.division_type && <span className="muted"> · {w.division_type}</span>}
                        </Row>
                    )}
                />
            )}
        </div>
    );
}

function Row({ children, active, onClick, suffix }: { children: React.ReactNode; active?: boolean; onClick: () => void; suffix?: React.ReactNode }) {
    return (
        <div
            onClick={onClick}
            style={{
                padding: '8px 10px', cursor: 'pointer', borderBottom: '1px solid #fafafa',
                background: active ? '#e6f4ff' : undefined,
                color: active ? '#1677ff' : undefined, fontWeight: active ? 600 : undefined,
                display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8,
            }}
            onMouseEnter={(e) => { if (!active) e.currentTarget.style.background = '#fafafa'; }}
            onMouseLeave={(e) => { if (!active) e.currentTarget.style.background = ''; }}
        >
            <div style={{ flex: 1, minWidth: 0 }}>{children}</div>
            {suffix}
        </div>
    );
}

function CustomerAddressTab({ addresses, onPick }: { addresses: CustomerAddress[]; onPick: (v: PickedAddress) => void }) {
    if (addresses.length === 0) return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Khách chưa có địa chỉ giao trước đó." />;
    return (
        <Radio.Group style={{ width: '100%' }} onChange={(e) => {
            const a = addresses[e.target.value];
            if (!a) return;
            onPick({
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

function List<T>({ items, loading, renderRow, empty }: { items: T[]; loading?: boolean; renderRow: (r: T) => React.ReactNode; empty: string }) {
    return (
        <div style={{ maxHeight: 320, overflowY: 'auto', border: '1px solid #f0f0f0', borderRadius: 6 }}>
            {loading && items.length === 0
                ? <div style={{ padding: 24, textAlign: 'center' }}><Spin size="small" /></div>
                : items.length === 0
                    ? <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={empty} style={{ margin: 16 }} />
                    : items.map(renderRow)}
        </div>
    );
}
