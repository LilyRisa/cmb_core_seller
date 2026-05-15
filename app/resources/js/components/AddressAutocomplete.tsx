import { useEffect, useMemo, useState } from 'react';
import { AutoComplete, Input, Tag } from 'antd';
import { EnvironmentOutlined } from '@ant-design/icons';
import { useDistricts, useProvinces, useWards, type AddressFormat, type District, type Province, type Ward } from '@/lib/masterData';
import { smartFilter, uniqueMatch, vnKey } from '@/lib/vnAddressMatch';
import type { PickedAddress } from '@/components/AddressPicker';

/**
 * SPEC 0021 — AddressAutocomplete: gợi ý dưới ô "Địa chỉ chi tiết" khi user gõ địa chỉ đầy đủ.
 *
 * Quy tắc parse:
 *  - Tách input theo dấu phẩy `,`.
 *  - **Lấy đoạn CUỐI** match Tỉnh (bỏ dấu, bỏ tiền tố "Tỉnh "/"Thành phố ").
 *  - Sau khi khớp Tỉnh, **bỏ đoạn cuối**, đoạn áp cuối match Quận (format='old') hoặc Phường (format='new').
 *  - Cứ thế khớp dần. Đoạn còn lại đầu chuỗi = địa chỉ chi tiết (số nhà, tên đường).
 *
 * AntD Form integration:
 *  - Component nhận `value` + `onChange` từ Form.Item (chuẩn AntD form-pattern). KHÔNG override.
 *  - User chọn suggestion ⇒ gọi `onPick(s)` để parent set `shipAddress` + form fields liên quan.
 *  - Đồng thời `onChange(s.detail)` được gọi để cập nhật value của field qua Form.Item.
 *
 * Match bỏ dấu: "123 nguyen trai, ha noi" khớp "Thành phố Hà Nội". User không cần gõ dấu.
 */

export interface AddressAutoSuggestion {
    label: string;
    detail: string;
    address: PickedAddress;
}

export function AddressAutocomplete({ value, onChange, format, onPick, placeholder, maxLength = 500, status }: {
    /** Form.Item inject; KHÔNG truyền tay từ ngoài. */
    value?: string;
    /** Form.Item inject. */
    onChange?: (v: string) => void;
    format: AddressFormat;
    onPick: (s: AddressAutoSuggestion) => void;
    placeholder?: string;
    maxLength?: number;
    status?: 'warning' | 'error';
}) {
    // B1 fix — value có thể undefined (initial render trước khi Form.Item inject). Default an toàn để
    // splitSegments không throw TypeError. Component vẫn render input rỗng + không suggest gì.
    const safeValue = value ?? '';
    const [open, setOpen] = useState(false);
    const { data: provinces = [] } = useProvinces(format);

    // Parse province match từ TAIL — chỉ fire khi user đã gõ ít nhất 1 dấu phẩy.
    const tailParse = useMemo(() => parseTail(safeValue, format, provinces), [safeValue, format, provinces]);

    // Khi đã match province ⇒ fetch districts/wards của province đó để parse tiếp.
    const provinceCode = tailParse?.province?.code;
    const { data: districts = [] } = useDistricts(provinceCode, format);
    const wardParent = format === 'new' ? provinceCode : tailParse?.district?.code;
    const { data: wards = [] } = useWards(wardParent, format);

    // Suggestions tổng hợp — chỉ render khi có ít nhất province match.
    const suggestions = useMemo<AddressAutoSuggestion[]>(() => {
        if (!tailParse?.province) return [];
        return buildSuggestions(tailParse, districts, wards, format).slice(0, 5);
    }, [tailParse, districts, wards, format]);

    // Tự đóng dropdown khi không có suggestion.
    useEffect(() => {
        if (suggestions.length === 0) setOpen(false);
    }, [suggestions.length]);

    // B2 fix — option.value KHÔNG dùng index (sẽ ghi "0" vào input khi user click). Dùng `s.detail`
    // (phần địa chỉ chi tiết sau khi parse) làm value ⇒ khi AutoComplete fire onChange với value này,
    // input của Form.Item nhận đúng phần detail. onSelect fire onPick để set state phụ.
    // Nếu nhiều option cùng `s.detail`, append tỉnh để unique nhưng vẫn human-readable.
    const options = useMemo(() => {
        const seen = new Set<string>();
        return suggestions.map((s) => {
            let v = s.detail || s.label;
            if (seen.has(v)) v = `${v} (${s.address.ward ?? s.address.province})`;
            seen.add(v);

            return { value: v, label: <SuggestionRow s={s} />, sug: s };
        });
    }, [suggestions]);

    return (
        <AutoComplete
            value={safeValue}
            options={options}
            popupMatchSelectWidth={false}
            open={open && options.length > 0}
            onDropdownVisibleChange={setOpen}
            onChange={(v) => onChange?.(v)}
            onSelect={(_v, opt) => {
                const sug = (opt as unknown as { sug?: AddressAutoSuggestion }).sug;
                if (sug) {
                    onPick(sug);
                    // Force value to detail (option.value đã set vậy nhưng đảm bảo onChange chạy đúng order).
                    onChange?.(sug.detail);
                }
                setOpen(false);
            }}
            style={{ width: '100%' }}
            popupClassName="address-autocomplete-popup"
        >
            <Input
                placeholder={placeholder ?? 'Vd: 123 Nguyễn Trãi, P. Bến Nghé, Q.1, TP HCM'}
                maxLength={maxLength}
                status={status}
                suffix={tailParse?.province ? <Tag color="blue" style={{ marginInlineEnd: 0, fontSize: 10 }}>{suggestions.length} gợi ý</Tag> : <EnvironmentOutlined style={{ color: '#bfbfbf' }} />}
            />
        </AutoComplete>
    );
}

// =================== parse helpers ===================

interface TailParse {
    detail: string;
    province?: Province;
    district?: District;
    ward?: Ward;
    /** Đoạn áp cuối sau khi đã trừ province, dùng để match district/ward (chưa resolved). */
    afterProvinceTail: string;
}

/** Tách input theo dấu phẩy / chấm phẩy, trim, bỏ rỗng. Safe với undefined. */
function splitSegments(text: string | undefined | null): string[] {
    if (!text) return [];
    return String(text).split(/[,;]+/).map((s) => s.trim()).filter(Boolean);
}

/** Parse TAIL: tìm province trong đoạn cuối, trả tail còn lại + detail address ở đầu. */
function parseTail(text: string, _format: AddressFormat, provinces: Province[]): TailParse | null {
    const segs = splitSegments(text);
    if (segs.length < 2 || provinces.length === 0) return null;

    // Match province từ segment cuối; nếu không khớp, thử lấy 2 segment cuối (có khi user gõ "hcm, vn").
    let provIdx = segs.length - 1;
    let prov = uniqueMatch(provinces, segs[provIdx]);
    if (!prov && segs.length >= 2) {
        // Có thể last segment là "Việt Nam" — bỏ qua và thử áp cuối.
        const last = vnKey(segs[provIdx]);
        if (['viet nam', 'vietnam', 'vn'].includes(last)) {
            provIdx = segs.length - 2;
            prov = uniqueMatch(provinces, segs[provIdx]);
        }
    }
    if (!prov) return null;

    // detail = segments[0..provIdx-2] joined; afterProvinceTail = segments[provIdx-1] nếu có.
    const detail = segs.slice(0, Math.max(0, provIdx - 1)).join(', ');
    const afterProvinceTail = provIdx - 1 >= 0 ? segs[provIdx - 1] : '';

    return { detail, province: prov, afterProvinceTail };
}

/**
 * Sinh suggestions dựa trên tailParse + districts/wards đã fetch.
 * Format 'old': cố resolve district trước, rồi ward. 'new': resolve ward thẳng.
 * Nếu không resolve được cấp con ⇒ vẫn trả 1 suggestion ở mức tỉnh để user điền thêm.
 */
function buildSuggestions(tp: TailParse, districts: District[], wards: Ward[], format: AddressFormat): AddressAutoSuggestion[] {
    if (!tp.province) return [];
    const out: AddressAutoSuggestion[] = [];
    if (format === 'old') {
        // Try match district from `afterProvinceTail`.
        if (tp.afterProvinceTail) {
            const candDistricts = smartFilter(districts, tp.afterProvinceTail, 3);
            for (const d of candDistricts) {
                // Match ward from detail tail (last comma in `detail` string).
                const detailSegs = splitSegments(tp.detail);
                const wardSeg = detailSegs.length > 0 ? detailSegs[detailSegs.length - 1] : '';
                const wardsOfD = wards.filter((w) => w.district_code === d.code);
                const candWards = wardSeg ? smartFilter(wardsOfD, wardSeg, 3) : [];
                if (candWards.length > 0) {
                    for (const w of candWards) {
                        const remainDetail = detailSegs.slice(0, -1).join(', ');
                        out.push(makeSuggestion(tp.province, d, w, format, remainDetail));
                    }
                } else {
                    out.push(makeSuggestion(tp.province, d, undefined, format, tp.detail));
                }
            }
        }
        if (out.length === 0) {
            out.push(makeSuggestion(tp.province, undefined, undefined, format, [tp.afterProvinceTail, tp.detail].filter(Boolean).join(', ')));
        }
    } else {
        // 2-cấp: match ward from afterProvinceTail (hoặc detail tail).
        const wardSeg = tp.afterProvinceTail || (() => {
            const ds = splitSegments(tp.detail);
            return ds.length ? ds[ds.length - 1] : '';
        })();
        const candWards = wardSeg ? smartFilter(wards, wardSeg, 5) : [];
        if (candWards.length > 0) {
            for (const w of candWards) {
                // Loại bỏ ward segment khỏi detail nếu nó nằm ở cuối.
                let remainDetail = tp.detail;
                if (!tp.afterProvinceTail) {
                    const ds = splitSegments(tp.detail);
                    remainDetail = ds.slice(0, -1).join(', ');
                }
                out.push(makeSuggestion(tp.province, undefined, w, format, remainDetail));
            }
        } else {
            out.push(makeSuggestion(tp.province, undefined, undefined, format, [tp.afterProvinceTail, tp.detail].filter(Boolean).join(', ')));
        }
    }

    return out;
}

function makeSuggestion(p: Province, d: District | undefined, w: Ward | undefined, format: AddressFormat, detail: string): AddressAutoSuggestion {
    return {
        label: [w?.name, d?.name, p.name].filter(Boolean).join(', '),
        detail,
        address: {
            format,
            province: p.name, province_code: p.code,
            district: d?.name, district_code: d?.code,
            ward: w?.name, ward_code: w?.code,
            address: detail,
        },
    };
}

function SuggestionRow({ s }: { s: AddressAutoSuggestion }) {
    const a = s.address;
    return (
        <div style={{ padding: '4px 0', maxWidth: 480 }}>
            <div style={{ fontSize: 13, fontWeight: 500 }}>
                <EnvironmentOutlined style={{ color: '#1677ff', marginInlineEnd: 6 }} />
                {s.detail && <span>{s.detail}, </span>}
                <span>{a.ward}</span>
                {a.district && <span>, {a.district}</span>}
                <span>, {a.province}</span>
            </div>
            <div style={{ fontSize: 11, color: '#8c8c8c', marginTop: 2 }}>
                {!a.ward ? '⚠ Chưa khớp phường/xã — chọn để chỉ điền tỉnh, bổ sung sau' : 'Khớp đến phường/xã ✓'}
            </div>
        </div>
    );
}
