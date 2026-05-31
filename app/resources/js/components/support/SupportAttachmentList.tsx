import { Image } from 'antd';
import { FileOutlined, FileImageOutlined, VideoCameraOutlined } from '@ant-design/icons';
import type { SupportAttachment } from '@/lib/support';

/**
 * Hiển thị đính kèm 1 tin CSKH: ảnh = thumbnail (bấm mở tab mới), video = trình phát,
 * tệp khác = link tải kèm icon + tên + dung lượng. Dùng chung cho widget user & trang admin.
 */

function humanSize(bytes: number | null): string {
    if (!bytes || bytes <= 0) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let n = bytes;
    let i = 0;
    while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
    return `${n.toFixed(n >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
}

function FileChip({ a }: { a: SupportAttachment }) {
    const Icon = a.kind === 'image' ? FileImageOutlined : a.kind === 'video' ? VideoCameraOutlined : FileOutlined;
    return (
        <a
            href={a.download_url ?? undefined}
            target="_blank"
            rel="noopener noreferrer"
            style={{
                display: 'inline-flex', alignItems: 'center', gap: 8, maxWidth: '100%',
                background: '#fff', border: '1px solid #E2E8F0', borderRadius: 8, padding: '6px 10px',
                color: '#0F172A', textDecoration: 'none',
            }}
        >
            <Icon style={{ fontSize: 18, color: '#2563EB', flexShrink: 0 }} />
            <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                {a.filename ?? 'Tệp đính kèm'}
            </span>
            {humanSize(a.size_bytes) && (
                <span style={{ color: '#94A3B8', fontSize: 11, flexShrink: 0 }}>{humanSize(a.size_bytes)}</span>
            )}
        </a>
    );
}

export function SupportAttachmentList({ attachments }: { attachments?: SupportAttachment[] }) {
    if (!attachments || attachments.length === 0) return null;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, marginTop: 6 }}>
            {/* PreviewGroup: ấn 1 ảnh mở lightbox phóng to + lướt giữa các ảnh cùng tin (cả user & CSKH). */}
            <Image.PreviewGroup>
                {attachments.map((a) => {
                    if (a.kind === 'image' && a.download_url) {
                        return (
                            <Image
                                key={a.id}
                                src={a.download_url}
                                alt={a.filename ?? 'Ảnh đính kèm'}
                                width={180}
                                style={{ maxHeight: 200, borderRadius: 8, objectFit: 'cover', cursor: 'zoom-in' }}
                            />
                        );
                    }
                    if (a.kind === 'video' && a.download_url) {
                        return <video key={a.id} src={a.download_url} controls style={{ maxWidth: 240, borderRadius: 8 }} />;
                    }
                    return <FileChip key={a.id} a={a} />;
                })}
            </Image.PreviewGroup>
        </div>
    );
}
