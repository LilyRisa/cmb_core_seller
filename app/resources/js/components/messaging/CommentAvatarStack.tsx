import { Avatar } from 'antd';

/**
 * Avatar đại diện hội thoại bình luận Facebook.
 * - 1 người tham gia (trừ page) → 1 avatar bình thường.
 * - ≥2 người → chồng 2 avatar đè nhau 1 phần (góc trên-trái + dưới-phải) giống
 *   avatar nhóm trong app nhắn tin. Chỉ chồng 2 (không "+N").
 * Avatar thiếu ảnh → fallback chữ cái đầu của tên.
 */
export function CommentAvatarStack({
    avatars,
    names,
    size = 40,
}: {
    avatars?: string[] | null;
    names?: string[] | null;
    size?: number;
}) {
    const imgs = (avatars ?? []).filter((u): u is string => !!u);
    const labels = (names ?? []).filter((n): n is string => !!n && n.trim() !== '');
    const initial = (i: number) => labels[i]?.slice(0, 1).toUpperCase() ?? '?';
    const stacked = labels.length >= 2 || imgs.length >= 2;

    if (!stacked) {
        return (
            <Avatar size={size} src={imgs[0] ?? undefined} style={{ background: '#2563EB', flexShrink: 0 }}>
                {initial(0)}
            </Avatar>
        );
    }

    const small = Math.round(size * 0.68);
    const ring = '0 0 0 2px #fff';

    return (
        <div style={{ position: 'relative', width: size, height: size, flexShrink: 0 }}>
            <Avatar
                size={small}
                src={imgs[0] ?? undefined}
                style={{ position: 'absolute', top: 0, left: 0, background: '#2563EB', boxShadow: ring }}
            >
                {initial(0)}
            </Avatar>
            <Avatar
                size={small}
                src={imgs[1] ?? undefined}
                style={{ position: 'absolute', bottom: 0, right: 0, background: '#7C3AED', boxShadow: ring }}
            >
                {initial(1)}
            </Avatar>
        </div>
    );
}
