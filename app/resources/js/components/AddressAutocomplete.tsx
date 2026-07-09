import { useEffect, useMemo, useState } from 'react';
import { AutoComplete, Input, Tag } from 'antd';
import { EnvironmentOutlined } from '@ant-design/icons';
import { useDistricts, useProvinces, useWards, type AddressFormat, type District, type Province, type Ward } from '@/lib/masterData';
import { matchScore, smartFilter, uniqueMatch, vnKey, vnPlain } from '@/lib/vnAddressMatch';
import type { PickedAddress } from '@/components/AddressPicker';

/**
 * SPEC 0021 — AddressAutocomplete: gợi ý dưới ô "Địa chỉ chi tiết" khi user gõ địa chỉ đầy đủ.
 *
 * Quy tắc parse (quét TỪ PHẢI QUA TRÁI):
 *  - Tách input theo dấu phẩy `,` (hoặc theo từ khi không có phẩy).
 *  - **Đoạn CUỐI cùng** = Tỉnh/TP (bỏ dấu, bỏ tiền tố "Tỉnh "/"Thành phố ").
 *  - Lùi về trái: với địa chỉ CŨ (3 cấp) khớp Quận/Huyện, rồi lùi tiếp khớp Phường/Xã.
 *    Với địa chỉ MỚI (2 cấp) khớp thẳng Phường/Xã.
 *  - Nhận diện tiền tố ("Q.", "P.", "Phường", "Xã"…) để KHÔNG nhầm quận "Q.1" thành phường "P.1".
 *  - Đoạn còn lại (không dùng cho tỉnh/quận/xã) = địa chỉ chi tiết (số nhà, tên đường).
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

    // Parse province match từ TAIL — trả về tỉnh + các đoạn còn lại (rest) để khớp quận/xã.
    const tailParse = useMemo(() => parseTail(safeValue, provinces), [safeValue, provinces]);

    // Khi đã match province ⇒ fetch districts/wards để parse tiếp.
    const provinceCode = tailParse?.province?.code;
    const { data: districts = [] } = useDistricts(provinceCode, format);

    // Địa chỉ CŨ (3 cấp): phải resolve Quận/Huyện TRƯỚC để fetch danh sách Phường/Xã của quận đó.
    const matchedDistrict = useMemo(() => {
        if (format !== 'old' || !tailParse) return null;
        return scanBest(districts, tailParse.rest, 'district');
    }, [format, tailParse, districts]);

    const wardParent = format === 'new' ? provinceCode : matchedDistrict?.item.code;
    const { data: wards = [] } = useWards(wardParent, format);

    // Suggestions tổng hợp — chỉ render khi có ít nhất province match.
    const suggestions = useMemo<AddressAutoSuggestion[]>(() => {
        if (!tailParse) return [];
        return buildSuggestions(tailParse, matchedDistrict, wards, format).slice(0, 5);
    }, [tailParse, matchedDistrict, wards, format]);

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
                suffix={tailParse ? <Tag color="blue" style={{ marginInlineEnd: 0, fontSize: 10 }}>{suggestions.length} gợi ý</Tag> : <EnvironmentOutlined style={{ color: '#bfbfbf' }} />}
            />
        </AutoComplete>
    );
}

// =================== parse helpers ===================

interface TailParse {
    province: Province;
    /** Các đoạn còn lại sau khi bỏ tỉnh (giữ thứ tự gốc trái→phải). Dùng để khớp quận/xã. */
    rest: string[];
}

/** Match đã tìm được kèm vị trí segment gốc (để loại khỏi phần detail sau này). */
interface SegMatch<T> { item: T; segIndex: number; query: string; score: number }

/** Tách input theo dấu phẩy / chấm phẩy, trim, bỏ rỗng. Safe với undefined. */
function splitSegments(text: string | undefined | null): string[] {
    if (!text) return [];
    return String(text).split(/[,;]+/).map((s) => s.trim()).filter(Boolean);
}

/**
 * Đoán cấp hành chính của 1 đoạn theo tiền tố ("Q.1"/"Quận"/"Huyện" ⇒ district; "P.1"/"Phường"/"Xã" ⇒
 * ward). Dùng để KHÔNG khớp nhầm quận thành phường (và ngược lại) khi bỏ dấu/tiền tố làm 2 tên trùng
 * (vd "Q.1" và "P.1" đều rút gọn còn "1"). Không rõ ⇒ 'unknown' (được phép khớp cả 2 cấp).
 */
function adminHint(seg: string): 'district' | 'ward' | 'unknown' {
    const p = vnPlain(seg);
    if (/^(q[.\s\d]|quan\b|huyen\b|h[.\s]|thi xa\b|tx[.\s\d])/.test(p)) return 'district';
    if (/^(p[.\s\d]|phuong\b|xa\b|x[.\s]|thi tran\b|tt[.\s\d])/.test(p)) return 'ward';
    return 'unknown';
}

/**
 * Parse TAIL: tìm province ở đoạn cuối, trả về tỉnh + các đoạn còn lại (rest).
 *
 * 2 chiến lược:
 *  - **Phẩy**: ưu tiên — segment cuối là tỉnh (chuẩn placeholder gợi ý user nhập).
 *  - **Word-window fallback**: input không phẩy ⇒ ghép 1..4 từ cuối thử match tỉnh; phần còn lại
 *    coi như 1 segment để khớp quận/xã theo cửa sổ từ.
 */
function parseTail(text: string, provinces: Province[]): TailParse | null {
    if (provinces.length === 0) return null;
    const segs = splitSegments(text);

    // ---- Strategy 1: comma-separated ----
    if (segs.length >= 2) {
        let provIdx = segs.length - 1;
        let prov = uniqueMatch(provinces, segs[provIdx]);
        if (!prov) {
            // Có thể last segment là "Việt Nam" — bỏ qua và thử áp cuối.
            const last = vnKey(segs[provIdx]);
            if (['viet nam', 'vietnam', 'vn'].includes(last) && segs.length >= 3) {
                provIdx = segs.length - 2;
                prov = uniqueMatch(provinces, segs[provIdx]);
            }
        }
        if (prov) return { province: prov, rest: segs.slice(0, provIdx) };
    }

    // ---- Strategy 2: word-window từ cuối (cho input không phẩy) ----
    const words = text.trim().split(/\s+/).filter(Boolean);
    if (words.length < 2) return null;
    for (let n = Math.min(4, words.length - 1); n >= 1; n--) {
        const candidate = words.slice(-n).join(' ');
        const prov = uniqueMatch(provinces, candidate);
        if (prov) {
            const remain = words.slice(0, words.length - n).join(' ');
            return { province: prov, rest: remain ? [remain] : [] };
        }
    }

    return null;
}

/**
 * Quét các segment (phải→trái) tìm item khớp tốt nhất với `want` (district|ward).
 * Mỗi segment thử cả nguyên đoạn lẫn các cửa sổ từ đầu/cuối (1..4 từ) — bắt được cả khi tên nằm
 * lẫn số nhà ("123 NTrai P Ben Nghe"). Bỏ qua segment có tiền tố trái cấp (quận≠phường).
 * Trả match điểm cao nhất (>= ngưỡng all-words) kèm vị trí segment.
 */
function scanBest<T extends { name: string }>(items: T[], segments: string[], want: 'district' | 'ward'): SegMatch<T> | null {
    if (items.length === 0) return null;
    let best: SegMatch<T> | null = null;
    // Quét từ phải qua trái để ưu tiên đoạn gần tỉnh nhất khi điểm bằng nhau.
    for (let i = segments.length - 1; i >= 0; i--) {
        const seg = (segments[i] ?? '').trim();
        if (!seg) continue;
        const hint = adminHint(seg);
        if ((want === 'ward' && hint === 'district') || (want === 'district' && hint === 'ward')) continue;

        const words = seg.split(/\s+/).filter(Boolean);
        const candidates = new Set<string>([seg]);
        for (let n = 1; n <= Math.min(4, words.length); n++) {
            candidates.add(words.slice(-n).join(' '));   // cửa sổ cuối
            candidates.add(words.slice(0, n).join(' '));  // cửa sổ đầu (vd "Q.1 …")
        }
        for (const c of candidates) {
            const top = smartFilter(items, c, 1)[0];
            if (!top) continue;
            const score = matchScore(c, top.name);
            if (score >= 300 && (!best || score > best.score)) {
                best = { item: top, segIndex: i, query: c, score };
            }
        }
    }
    return best;
}

/**
 * Sinh suggestions từ tailParse + district/ward đã fetch.
 * Format 'old': dùng district đã resolve (matchedDistrict) rồi khớp ward trong quận đó.
 * Format 'new': khớp ward thẳng theo tỉnh. Không khớp được cấp con ⇒ trả 1 suggestion mức tỉnh
 * (kèm quận nếu có) để user bổ sung sau.
 */
function buildSuggestions(tp: TailParse, matchedDistrict: SegMatch<District> | null, wards: Ward[], format: AddressFormat): AddressAutoSuggestion[] {
    const rest = tp.rest;
    const usedIdx = new Set<number>();
    const district = format === 'old' ? matchedDistrict?.item : undefined;
    if (format === 'old' && matchedDistrict) usedIdx.add(matchedDistrict.segIndex);

    // Khớp ward trên các segment CHƯA dùng (loại segment đã là quận).
    const wardSegs = rest.map((s, i) => (usedIdx.has(i) ? '' : s));
    const wardMatch = scanBest(wards, wardSegs, 'ward');

    const out: AddressAutoSuggestion[] = [];
    if (wardMatch) {
        usedIdx.add(wardMatch.segIndex);
        const detail = rest.filter((_, i) => !usedIdx.has(i)).join(', ');
        const alts = smartFilter(wards, wardMatch.query, 3);
        const seen = new Set<string>();
        for (const w of alts.length ? alts : [wardMatch.item]) {
            if (seen.has(w.code)) continue;
            seen.add(w.code);
            out.push(makeSuggestion(tp.province, district, w, format, detail));
        }
    }

    if (out.length === 0) {
        // Chỉ tỉnh (+ quận nếu đã khớp) — các đoạn còn lại (trừ quận) làm detail.
        const detail = rest.filter((_, i) => !usedIdx.has(i)).join(', ');
        out.push(makeSuggestion(tp.province, district, undefined, format, detail));
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
