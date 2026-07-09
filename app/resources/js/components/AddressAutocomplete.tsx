import { useMemo, useState } from 'react';
import { AutoComplete, Input, Tag } from 'antd';
import { EnvironmentOutlined } from '@ant-design/icons';
import { useDistricts, useProvinces, useWards, type AddressFormat, type District, type Province, type Ward } from '@/lib/masterData';
import { segmentScore, uniqueMatch } from '@/lib/vnAddressMatch';
import type { PickedAddress } from '@/components/AddressPicker';

/**
 * SPEC 0021 — AddressAutocomplete: gợi ý dưới ô "Địa chỉ chi tiết" khi user gõ địa chỉ đầy đủ.
 *
 * Thuật toán (dò theo DANH MỤC hành chính thật, KHÔNG đoán mò bằng cửa sổ token):
 *  1. Tách input theo dấu phẩy. Dò TỈNH từ PHẢI→TRÁI — đoạn nào khớp tỉnh (ở danh mục cũ HOẶC
 *     mới) thì đó là tỉnh; các đoạn bên PHẢI tỉnh coi là thông tin phụ (bỏ), bên TRÁI để khớp
 *     huyện/xã. Nhờ vậy "…, Hải Phòng, Việt Nam, gọi trước" vẫn nhận đúng tỉnh.
 *  2. Không khớp được tỉnh ⇒ KHÔNG gợi ý gì.
 *  3. Khớp được tỉnh ⇒ rà HUYỆN trong tỉnh đó (chỉ chuẩn cũ có huyện):
 *      - Khớp được huyện ⇒ rà XÃ trong huyện đó (địa chỉ CŨ 3 cấp).
 *          · Khớp xã ⇒ gợi ý Tỉnh/Huyện/Xã.
 *          · Không khớp xã ⇒ gợi ý Tỉnh/Huyện (user tự thêm xã).
 *      - Không khớp được huyện ⇒ xem tỉnh có trong danh mục MỚI không:
 *          · Có ⇒ rà XÃ mới trong tỉnh (địa chỉ MỚI 2 cấp); khớp ⇒ Tỉnh/Xã, không ⇒ chỉ Tỉnh.
 *          · Không ⇒ chỉ gợi ý Tỉnh.
 *  4. Khớp CHẶT theo cả cụm ({@see segmentScore}) — cấm token ngắn khớp mờ ⇒ không đẻ gợi ý rác.
 *     Mỗi lần chỉ trả ĐÚNG 1 gợi ý ở cấp sâu nhất khớp được, KHÔNG độn danh sách "gần giống".
 *
 * AntD Form integration: nhận `value`/`onChange` từ Form.Item; user chọn suggestion ⇒ `onPick(s)`
 * để parent set `shipAddress` + `onChange(s.detail)` cập nhật ô địa chỉ chi tiết.
 *
 * Match bỏ dấu: "20 truong chinh, kien an, hai phong" khớp như có dấu. User không cần gõ dấu.
 */

export interface AddressAutoSuggestion {
    label: string;
    detail: string;
    address: PickedAddress;
}

export function AddressAutocomplete({ value, onChange, onPick, placeholder, maxLength = 500, status }: {
    /** Form.Item inject; KHÔNG truyền tay từ ngoài. */
    value?: string;
    /** Form.Item inject. */
    onChange?: (v: string) => void;
    onPick: (s: AddressAutoSuggestion) => void;
    placeholder?: string;
    maxLength?: number;
    status?: 'warning' | 'error';
}) {
    // value có thể undefined (initial render trước khi Form.Item inject). Default an toàn.
    const safeValue = value ?? '';
    // Mở dropdown theo FOCUS: cứ đang focus mà có gợi ý là hiện. Nhờ vậy sau khi chọn 1 gợi ý (ô
    // text thu còn phần chi tiết) rồi user gõ lại đủ tỉnh/quận, dropdown TỰ hiện lại — không bị kẹt.
    const [focused, setFocused] = useState(false);

    // Dò tỉnh ở CẢ hai danh mục (cũ + mới) — cùng tên nhưng khác mã; cần mã cũ để lấy huyện,
    // mã mới để lấy xã chuẩn mới.
    const { data: provincesOld = [] } = useProvinces('old');
    const { data: provincesNew = [] } = useProvinces('new');

    const tail = useMemo(() => resolveProvince(safeValue, provincesOld, provincesNew), [safeValue, provincesOld, provincesNew]);

    // Huyện chỉ tồn tại ở chuẩn CŨ ⇒ fetch theo mã tỉnh cũ để thử khớp.
    const { data: districts = [] } = useDistricts(tail?.old?.code, 'old');
    const matchedDistrict = useMemo(() => {
        if (!tail?.old) return null;
        return pickSegment(districts, tail.rest);
    }, [tail, districts]);

    // Xã: nếu đã khớp huyện ⇒ xã CŨ trong huyện; ngược lại ⇒ xã MỚI trong tỉnh (nếu tỉnh có ở chuẩn mới).
    const { data: oldWards = [] } = useWards(matchedDistrict?.item.code, 'old');
    const { data: newWards = [] } = useWards(matchedDistrict ? undefined : tail?.new?.code, 'new');

    const suggestions = useMemo<AddressAutoSuggestion[]>(() => {
        if (!tail) return [];
        return buildSuggestions(tail, matchedDistrict, oldWards, newWards);
    }, [tail, matchedDistrict, oldWards, newWards]);

    // option.value = phần địa chỉ chi tiết (số nhà/đường) để khi chọn, ô Form.Item nhận đúng detail.
    const options = useMemo(() => {
        const seen = new Set<string>();
        return suggestions.map((s) => {
            let v = s.detail || s.label;
            if (seen.has(v)) v = `${v} (${s.address.ward ?? s.address.district ?? s.address.province})`;
            seen.add(v);

            return { value: v, label: <SuggestionRow s={s} />, sug: s };
        });
    }, [suggestions]);

    return (
        <AutoComplete
            value={safeValue}
            options={options}
            popupMatchSelectWidth={false}
            open={focused && options.length > 0}
            onFocus={() => setFocused(true)}
            onBlur={() => setFocused(false)}
            onChange={(v) => onChange?.(v)}
            onSelect={(_v, opt) => {
                const sug = (opt as unknown as { sug?: AddressAutoSuggestion }).sug;
                if (sug) {
                    onPick(sug);
                    onChange?.(sug.detail);
                }
                setFocused(false);
            }}
            style={{ width: '100%' }}
            popupClassName="address-autocomplete-popup"
        >
            <Input
                placeholder={placeholder ?? 'Vd: 123 Nguyễn Trãi, P. Bến Nghé, Q.1, TP HCM'}
                maxLength={maxLength}
                status={status}
                suffix={tail ? <Tag color="blue" style={{ marginInlineEnd: 0, fontSize: 10 }}>{suggestions.length} gợi ý</Tag> : <EnvironmentOutlined style={{ color: '#bfbfbf' }} />}
            />
        </AutoComplete>
    );
}

// =================== parse helpers ===================

interface TailParse {
    /** Tỉnh ở danh mục CŨ (nếu tồn tại) — dùng để lấy huyện. */
    old?: Province;
    /** Tỉnh ở danh mục MỚI (nếu tồn tại) — dùng để lấy xã chuẩn mới. */
    new?: Province;
    /** Các đoạn bên TRÁI tỉnh (giữ thứ tự gốc). Dùng để khớp huyện/xã + phần còn lại là detail. */
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
 * Dò TỈNH từ PHẢI→TRÁI ở cả 2 danh mục (cũ + mới).
 *
 * Vì user đôi khi thêm thông tin phụ sau địa chỉ ("…, Hải Phòng, Việt Nam" / "…, TP HCM, giao giờ
 * hành chính"), đoạn CUỐI chưa chắc là tỉnh ⇒ lùi dần sang trái tới đoạn đầu tiên khớp tỉnh. Mọi
 * đoạn bên PHẢI tỉnh coi là phụ (bỏ), bên TRÁI giữ lại (rest) để khớp huyện/xã.
 */
function resolveProvince(text: string, provOld: Province[], provNew: Province[]): TailParse | null {
    if (provOld.length === 0 && provNew.length === 0) return null;

    // ---- Có dấu phẩy: quét từng segment phải→trái ----
    const segs = splitSegments(text);
    for (let idx = segs.length - 1; idx >= 0; idx--) {
        const oldP = uniqueMatch(provOld, segs[idx]);
        const newP = uniqueMatch(provNew, segs[idx]);
        if (oldP || newP) {
            return { old: oldP ?? undefined, new: newP ?? undefined, rest: segs.slice(0, idx) };
        }
    }

    // ---- Không phẩy: cửa sổ 1..4 từ, dịch dần từ phải sang trái ----
    const words = text.trim().split(/\s+/).filter(Boolean);
    if (words.length < 2) return null;
    for (let end = words.length; end >= 2; end--) {
        for (let n = Math.min(4, end); n >= 1; n--) {
            const candidate = words.slice(end - n, end).join(' ');
            const oldP = uniqueMatch(provOld, candidate);
            const newP = uniqueMatch(provNew, candidate);
            if (oldP || newP) {
                const remain = words.slice(0, end - n).join(' ');
                return { old: oldP ?? undefined, new: newP ?? undefined, rest: remain ? [remain] : [] };
            }
        }
    }

    return null;
}

/** Các cửa sổ con của 1 đoạn để khớp (đề phòng số nhà lẫn tên đơn vị khi thiếu phẩy). KHÔNG lấy
 *  cửa sổ 1 từ — token đơn ("hà") là nguồn gốc gợi ý rác; đã có khớp trọn đoạn lo ca 1-âm-tiết. */
function segmentWindows(seg: string): string[] {
    const words = seg.split(/\s+/).filter(Boolean);
    const out = new Set<string>([seg]);
    for (let n = 2; n <= Math.min(4, words.length); n++) {
        out.add(words.slice(-n).join(' '));    // đuôi
        out.add(words.slice(0, n).join(' '));  // đầu (vd "Q.1 …")
    }
    return [...out];
}

/**
 * Quét các segment (phải→trái) tìm item khớp CHẶT nhất ({@see segmentScore} ≥ 650). `items` đã
 * được lọc theo cấp cha (huyện của tỉnh / xã của huyện) nên không bao giờ vượt phạm vi.
 */
function pickSegment<T extends { name: string }>(items: T[], segments: string[]): SegMatch<T> | null {
    if (items.length === 0) return null;
    let best: SegMatch<T> | null = null;
    for (let i = segments.length - 1; i >= 0; i--) {
        const seg = (segments[i] ?? '').trim();
        if (!seg) continue;
        for (const cand of segmentWindows(seg)) {
            for (const it of items) {
                const score = segmentScore(cand, it.name);
                if (score >= 650 && (!best || score > best.score)) {
                    best = { item: it, segIndex: i, query: cand, score };
                }
            }
        }
    }
    return best;
}

/**
 * Sinh ĐÚNG 1 suggestion ở cấp sâu nhất khớp được (không độn danh sách gần giống):
 *  - Khớp huyện ⇒ Tỉnh/Huyện(/Xã nếu khớp) theo chuẩn CŨ.
 *  - Không khớp huyện nhưng tỉnh có ở chuẩn MỚI ⇒ Tỉnh(/Xã mới nếu khớp).
 *  - Còn lại ⇒ chỉ Tỉnh.
 */
function buildSuggestions(tp: TailParse, matchedDistrict: SegMatch<District> | null, oldWards: Ward[], newWards: Ward[]): AddressAutoSuggestion[] {
    let segs = tp.rest.slice();

    if (matchedDistrict && tp.old) {
        // Bỏ ĐÚNG cụm huyện đã khớp khỏi đoạn (không xoá cả đoạn) — để xã nằm CHUNG đoạn (địa chỉ gõ
        // liền không phẩy) vẫn tách ra khớp được.
        segs = stripWindow(segs, matchedDistrict.segIndex, matchedDistrict.query);
        const ward = pickSegment(oldWards, segs);
        if (ward) segs = stripWindow(segs, ward.segIndex, ward.query);
        return [makeSuggestion(tp.old, matchedDistrict.item, ward?.item, 'old', joinDetail(segs))];
    }

    if (tp.new) {
        const ward = pickSegment(newWards, segs);
        if (ward) segs = stripWindow(segs, ward.segIndex, ward.query);
        return [makeSuggestion(tp.new, undefined, ward?.item, 'new', joinDetail(segs))];
    }

    if (tp.old) {
        return [makeSuggestion(tp.old, undefined, undefined, 'old', joinDetail(segs))];
    }

    return [];
}

/** Bỏ cụm từ ĐÃ khớp (huyện/xã) khỏi đúng đoạn chứa nó — phần dư còn lại làm địa chỉ chi tiết. */
function stripWindow(segs: string[], idx: number, query: string): string[] {
    return segs.map((s, i) => {
        if (i !== idx) return s;
        const sw = s.split(/\s+/).filter(Boolean);
        const qw = query.split(/\s+/).filter(Boolean);
        for (let start = 0; start + qw.length <= sw.length; start++) {
            let ok = true;
            for (let k = 0; k < qw.length; k++) {
                if (sw[start + k].toLowerCase() !== qw[k].toLowerCase()) { ok = false; break; }
            }
            if (ok) { sw.splice(start, qw.length); return sw.join(' '); }
        }
        return s;
    });
}

function joinDetail(segs: string[]): string {
    return segs.map((s) => s.trim()).filter(Boolean).join(', ');
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
                {a.ward && <span>{a.ward}, </span>}
                {a.district && <span>{a.district}, </span>}
                <span>{a.province}</span>
            </div>
            <div style={{ fontSize: 11, color: '#8c8c8c', marginTop: 2 }}>
                {a.ward
                    ? 'Khớp đến phường/xã ✓'
                    : a.district
                        ? '⚠ Chưa khớp phường/xã — chọn để điền Tỉnh/Huyện, tự thêm xã sau'
                        : '⚠ Chưa khớp quận/phường — chọn để chỉ điền tỉnh, bổ sung sau'}
            </div>
        </div>
    );
}
