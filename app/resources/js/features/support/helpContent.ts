// Nội dung Trung tâm trợ giúp — nạp từ các file markdown trong app/resources/help-center/
// (đồng bộ từ support_doc/ ở repo root qua scripts/sync-help-center.mjs).
//
// Mỗi file có frontmatter đơn giản (title, slug, menu, plan?, roles?) + thân markdown.
// Ảnh trong bài viết dùng đường dẫn images/<tên>.png và được đổi sang CDN khi hiển thị
// (xem HELP_IMAGE_BASE). Không cần backend — nội dung đóng gói vào bundle FE.

export const HELP_IMAGE_BASE = 'https://static.cmbcore.com/static_root';

export interface HelpArticle {
    /** slug = tên file không đuôi, ví dụ "01-bat-dau" */
    slug: string;
    title: string;
    /** đường dẫn menu tiếng Việt, ví dụ "Bán hàng → Đơn hàng" */
    menu: string;
    /** gói tối thiểu nếu có (Miễn phí | Pro | Business) */
    plan?: string;
    /** vai trò cần có nếu có */
    roles: string[];
    /** thân markdown (đã bỏ frontmatter) */
    body: string;
    /** text thuần để tìm kiếm (title + body, bỏ ký hiệu markdown) */
    searchText: string;
    /** số thứ tự suy ra từ tiền tố tên file để sắp xếp */
    order: number;
}

/** Tách frontmatter `---\n...\n---` ở đầu file; trả [meta, body]. */
function splitFrontmatter(raw: string): [Record<string, string>, string] {
    const m = /^---\s*\n([\s\S]*?)\n---\s*\n?/.exec(raw);
    if (!m) return [{}, raw];
    const meta: Record<string, string> = {};
    for (const line of m[1].split('\n')) {
        const idx = line.indexOf(':');
        if (idx === -1) continue;
        const key = line.slice(0, idx).trim();
        let val = line.slice(idx + 1).trim();
        if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
            val = val.slice(1, -1);
        }
        meta[key] = val;
    }
    return [meta, raw.slice(m[0].length)];
}

/** Parse mảng dạng `["a", "b"]` trong frontmatter. */
function parseList(val: string | undefined): string[] {
    if (!val) return [];
    const inner = val.replace(/^\[/, '').replace(/\]$/, '').trim();
    if (inner === '') return [];
    return inner
        .split(',')
        .map((s) => s.trim().replace(/^["']|["']$/g, ''))
        .filter(Boolean);
}

/** Bỏ ký hiệu markdown thô để có text tìm kiếm gọn. */
function toPlain(md: string): string {
    return md
        .replace(/!\[[^\]]*\]\([^)]*\)/g, ' ') // ảnh
        .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1') // link -> nhãn
        .replace(/[#>*`_|-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

// Vite đóng gói toàn bộ markdown ở build-time (eager, dạng chuỗi thô).
const files = import.meta.glob('../../../help-center/*.md', {
    query: '?raw',
    import: 'default',
    eager: true,
}) as Record<string, string>;

export const helpArticles: HelpArticle[] = Object.entries(files)
    .map(([path, raw]) => {
        const fileSlug = path.split('/').pop()!.replace(/\.md$/, '');
        const [meta, body] = splitFrontmatter(raw);
        const slug = meta.slug || fileSlug;
        const numMatch = /^(\d+)/.exec(fileSlug);
        return {
            slug,
            title: meta.title || slug,
            menu: meta.menu || '',
            plan: meta.plan || undefined,
            roles: parseList(meta.roles),
            body,
            searchText: `${meta.title || ''} ${toPlain(body)}`.toLowerCase(),
            order: numMatch ? parseInt(numMatch[1], 10) : 999,
        } as HelpArticle;
    })
    .sort((a, b) => a.order - b.order);

export function findArticle(slug: string | undefined): HelpArticle | undefined {
    if (!slug) return undefined;
    return helpArticles.find((a) => a.slug === slug);
}
