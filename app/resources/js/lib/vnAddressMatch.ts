/**
 * SPEC 0021 — Helper match địa chỉ VN bỏ dấu + tách tiền tố cấp hành chính.
 * Dùng chung cho AddressPicker (search trong dropdown) và AddressAutocomplete (parse text).
 *
 * Quy tắc match thông minh:
 *  - "ha noi" (không dấu) khớp "Hà Nội"
 *  - "Hồ Chí Minh" khớp "Thành phố Hồ Chí Minh" (strip tiền tố cấp)
 *  - "Q.1" / "Q1" / "Quận 1" đều khớp "Quận 1"
 *  - Sort: exact match > startsWith > contains
 */

/** Bỏ dấu tiếng Việt + lower + collapse whitespace. KHÔNG strip tiền tố — caller có thể chọn. */
export function vnPlain(s: string): string {
    return s
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/\s+/g, ' ')
        .trim();
}

/** Bỏ tiền tố cấp hành chính (vd "Tỉnh ", "Quận ", "P. ", "TP "). */
export function stripAdminPrefix(plain: string): string {
    return plain
        .replace(
            /^(thanh pho |tp\.? |tinh |t\.? |quan |q\.? |huyen |h\.? |thi xa |tx\.? |phuong |p\.? |xa |x\.? |thi tran |tt\.? |dac khu )+/u,
            '',
        )
        .trim();
}

/** vnPlain + strip prefix — dùng để so sánh "hà nội" với "thành phố hà nội". */
export function vnKey(s: string): string {
    return stripAdminPrefix(vnPlain(s));
}

/** Score độ khớp giữa query (đã normalize) và item.name. Cao = khớp hơn. -1 = không khớp. */
export function matchScore(query: string, itemName: string): number {
    if (query === '') return 0;
    const q = vnKey(query);
    const n = vnKey(itemName);
    const np = vnPlain(itemName);   // không strip prefix — để match khi user gõ kèm "Quận"
    if (q === '') return 0;
    // Exact match (bỏ tiền tố) — score cao nhất
    if (n === q) return 1000;
    // Exact với prefix giữ nguyên — vd "quan 1" === "quan 1" (n đã strip còn "1", np còn "quan 1")
    if (np === q) return 950;
    // Bắt đầu bằng query
    if (n.startsWith(q)) return 800;
    if (np.startsWith(q)) return 780;
    // Chứa query
    if (n.includes(q)) return 500;
    if (np.includes(q)) return 480;
    // Tách từng từ — tất cả từ trong query đều có trong name
    const words = q.split(' ').filter(Boolean);
    if (words.length > 0 && words.every((w) => n.includes(w) || np.includes(w))) {
        return 300 - (n.length - q.length);   // ưu tiên name ngắn (sát query hơn)
    }

    return -1;
}

/** Filter + sort list theo score. Trả mảng đã sort, score cao trước. */
export function smartFilter<T extends { name: string }>(items: T[], query: string, limit = 200): T[] {
    if (!query.trim()) return items.slice(0, limit);
    const scored = items
        .map((item) => ({ item, score: matchScore(query, item.name) }))
        .filter((x) => x.score >= 0);
    scored.sort((a, b) => b.score - a.score || a.item.name.length - b.item.name.length);

    return scored.slice(0, limit).map((x) => x.item);
}

/** Tìm item khớp duy nhất với segment text. Trả null nếu không khớp hoặc khớp nhiều. */
export function uniqueMatch<T extends { name: string }>(items: T[], query: string): T | null {
    const matches = smartFilter(items, query, 5);
    if (matches.length === 0) return null;
    // Nếu top match score cao hơn hẳn 2nd ⇒ chọn nó (vd 1000 vs 500). Nếu sát nhau ⇒ ambiguous.
    if (matches.length === 1) return matches[0];
    const top = matchScore(query, matches[0].name);
    const second = matchScore(query, matches[1].name);

    return top >= 800 && top - second >= 200 ? matches[0] : null;
}
