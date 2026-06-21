import { useEffect, useRef, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { Button, Divider, Input, Modal, Space, Tooltip } from 'antd';
import {
    BoldOutlined,
    ItalicOutlined,
    LinkOutlined,
    OrderedListOutlined,
    PictureOutlined,
    UnderlineOutlined,
    UnorderedListOutlined,
} from '@ant-design/icons';

/**
 * Trình soạn thảo HTML (TipTap) dùng chung cho bundle app — đậm/nghiêng/gạch chân, tiêu đề,
 * danh sách, liên kết và CHÈN ẢNH (qua callback `uploadImage` do trang cha cấp endpoint).
 * value/onChange = HTML. Đồng bộ lại nội dung khi `value` đổi từ bên ngoài (AI gợi ý, tải lại).
 * Sàn dùng mô tả HTML (TikTok, Lazada); Shopee chỉ text thuần nên KHÔNG dùng trình này.
 */
export function RichTextEditor({
    value,
    onChange,
    uploadImage,
    placeholder,
}: {
    value: string;
    onChange: (html: string) => void;
    uploadImage?: (file: File) => Promise<string>;
    placeholder?: string;
}) {
    const imgInput = useRef<HTMLInputElement>(null);
    const [linkOpen, setLinkOpen] = useState(false);
    const [linkUrl, setLinkUrl] = useState('');
    const [uploading, setUploading] = useState(false);

    const editor = useEditor({
        extensions: [StarterKit, Underline, Link.configure({ openOnClick: false }), Image],
        content: value,
        editorProps: { attributes: { style: 'min-height: 160px; padding: 12px; outline: none;', 'aria-label': placeholder ?? 'Mô tả' } },
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
    });

    // Đồng bộ khi value đổi từ ngoài (AI thay mô tả, re-seed từ sàn). So với getHTML() để
    // KHÔNG setContent mỗi lần gõ (gây nhảy con trỏ) — chỉ chạy khi thật sự khác.
    useEffect(() => {
        if (editor && value !== editor.getHTML()) {
            editor.commands.setContent(value || '', { emitUpdate: false });
        }
    }, [value, editor]);

    if (!editor) return null;

    const pickImage = async (file: File) => {
        if (!uploadImage) return;
        setUploading(true);
        try {
            const url = await uploadImage(file);
            editor.chain().focus().setImage({ src: url }).run();
        } catch {
            // Lỗi tải ảnh do trang cha báo (qua uploadImage).
        } finally {
            setUploading(false);
        }
    };

    const applyLink = () => {
        const url = linkUrl.trim();
        if (url === '') editor.chain().focus().unsetLink().run();
        else editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        setLinkOpen(false);
        setLinkUrl('');
    };

    const btn = (active: boolean): 'primary' | 'text' => (active ? 'primary' : 'text');

    return (
        <div style={{ border: '1px solid #d9d9d9', borderRadius: 8, overflow: 'hidden' }}>
            <Space wrap style={{ padding: 8, borderBottom: '1px solid #f0f0f0', background: '#fafafa' }} size={2}>
                <Tooltip title="Đậm"><Button size="small" type={btn(editor.isActive('bold'))} icon={<BoldOutlined />} onClick={() => editor.chain().focus().toggleBold().run()} /></Tooltip>
                <Tooltip title="Nghiêng"><Button size="small" type={btn(editor.isActive('italic'))} icon={<ItalicOutlined />} onClick={() => editor.chain().focus().toggleItalic().run()} /></Tooltip>
                <Tooltip title="Gạch chân"><Button size="small" type={btn(editor.isActive('underline'))} icon={<UnderlineOutlined />} onClick={() => editor.chain().focus().toggleUnderline().run()} /></Tooltip>
                <Divider type="vertical" />
                <Button size="small" type={btn(editor.isActive('heading', { level: 2 }))} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}>H2</Button>
                <Button size="small" type={btn(editor.isActive('heading', { level: 3 }))} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}>H3</Button>
                <Tooltip title="Danh sách chấm"><Button size="small" type={btn(editor.isActive('bulletList'))} icon={<UnorderedListOutlined />} onClick={() => editor.chain().focus().toggleBulletList().run()} /></Tooltip>
                <Tooltip title="Danh sách số"><Button size="small" type={btn(editor.isActive('orderedList'))} icon={<OrderedListOutlined />} onClick={() => editor.chain().focus().toggleOrderedList().run()} /></Tooltip>
                <Divider type="vertical" />
                <Tooltip title="Liên kết"><Button size="small" type={btn(editor.isActive('link'))} icon={<LinkOutlined />} onClick={() => { setLinkUrl(editor.getAttributes('link').href ?? ''); setLinkOpen(true); }} /></Tooltip>
                {uploadImage && (
                    <Tooltip title="Chèn ảnh"><Button size="small" type="text" icon={<PictureOutlined />} loading={uploading} onClick={() => imgInput.current?.click()} /></Tooltip>
                )}
            </Space>

            <input ref={imgInput} type="file" accept="image/*" hidden onChange={(e) => { const f = e.target.files?.[0]; if (f) void pickImage(f); e.target.value = ''; }} />

            <EditorContent editor={editor} />

            <Modal title="Chèn liên kết" open={linkOpen} onOk={applyLink} onCancel={() => setLinkOpen(false)} okText="Áp dụng">
                <Input placeholder="https://..." value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} onPressEnter={applyLink} />
            </Modal>
        </div>
    );
}
