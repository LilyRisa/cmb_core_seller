import { create } from 'zustand';

/** Một dòng giá theo SKU (chỉ giá sửa được — tồn đẩy theo master SKU). */
export interface MarketplacePriceRow {
    external_sku_id: string;
    seller_sku: string;
    price: number;
}

export interface MarketplaceEditDraft {
    title: string;
    description: string;
    images: string[];
    prices: MarketplacePriceRow[];
}

interface MarketplaceEditState {
    /** channel_listing_id đang sửa (null khi chưa có). */
    id: number | null;
    /** Bản gốc để tính diff (giá trị mới nhất từ sàn / listing seed). */
    baseline: MarketplaceEditDraft | null;
    /** Thay đổi đang dở (gom cục bộ, đẩy theo loạt). */
    draft: MarketplaceEditDraft | null;
    /** Người dùng đã chạm vào form chưa (để biết có nên re-seed khi detail tới). */
    touched: boolean;

    /** Khởi tạo cho 1 listing. Chỉ ghi đè khi đổi id, hoặc khi `force` (re-seed lúc chưa touched). */
    init: (id: number, baseline: MarketplaceEditDraft, force?: boolean) => void;
    patch: (p: Partial<MarketplaceEditDraft>) => void;
    /** Thay 1 ảnh nguồn bằng ảnh mới (sau khi sửa ảnh nâng cao); thêm vào cuối nếu không thấy. */
    replaceImage: (oldUrl: string, newUrl: string) => void;
    clear: () => void;
}

export const useMarketplaceEditStore = create<MarketplaceEditState>((set) => ({
    id: null,
    baseline: null,
    draft: null,
    touched: false,

    init: (id, baseline, force = false) =>
        set((s) => {
            if (!force && s.id === id && s.draft !== null) return s; // giữ thay đổi đang dở
            return {
                id,
                baseline,
                draft: { title: baseline.title, description: baseline.description, images: [...baseline.images], prices: baseline.prices.map((p) => ({ ...p })) },
                touched: false,
            };
        }),

    patch: (p) =>
        set((s) => (s.draft ? { draft: { ...s.draft, ...p }, touched: true } : s)),

    replaceImage: (oldUrl, newUrl) =>
        set((s) => {
            if (!s.draft) return s;
            const images = s.draft.images.includes(oldUrl)
                ? s.draft.images.map((u) => (u === oldUrl ? newUrl : u))
                : [...s.draft.images, newUrl];
            return { draft: { ...s.draft, images }, touched: true };
        }),

    clear: () => set({ id: null, baseline: null, draft: null, touched: false }),
}));
