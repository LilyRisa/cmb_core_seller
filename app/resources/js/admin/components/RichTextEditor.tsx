import { useRef, useState } from 'react';
import { Node, mergeAttributes } from '@tiptap/core';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { App, Button, Divider, Input, Modal, Space, Tooltip } from 'antd';
import {
    BoldOutlined,
    ItalicOutlined,
    LinkOutlined,
    OrderedListOutlined,
    PictureOutlined,
    UnderlineOutlined,
    UnorderedListOutlined,
    VideoCameraOutlined,
} from '@ant-design/icons';
import { uploadAnnouncementMedia } from '@admin/lib/announcements';

/** Node Video tối giản (TipTap không có sẵn) — render <video controls>. */
const Video = Node.create({
    name: 'video',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        return { src: { default: null } };
    },
    parseHTML() {
        return [{ tag: 'video' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['video', mergeAttributes(HTMLAttributes, { controls: 'controls', style: 'max-width:100%' })];
    },
});

/**
 * SPEC 0037 — trình soạn thảo nâng cao (TipTap) cho nội dung announcement. Chèn ảnh/video
 * upload lên R2 (qua uploadAnnouncementMedia). CHỈ dùng ở bundle admin. value/onChange = HTML.
 */
export function RichTextEditor({ value, onChange }: { value: string; onChange: (html: string) => void }) {
    const { message } = App.useApp();
    const imgInput = useRef<HTMLInputElement>(null);
    const vidInput = useRef<HTMLInputElement>(null);
    const [linkOpen, setLinkOpen] = useState(false);
    const [linkUrl, setLinkUrl] = useState('');
    const [uploading, setUploading] = useState(false);

    const editor = useEditor({
        extensions: [StarterKit, Underline, Link.configure({ openOnClick: false }), Image, Video],
        content: value,
        editorProps: { attributes: { style: 'min-height: 200px; padding: 12px; outline: none;' } },
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
    });

    if (!editor) return null;

    const upload = async (file: File, kind: 'image' | 'video') => {
        setUploading(true);
        try {
            const url = await uploadAnnouncementMedia(file);
            editor.chain().focus().insertContent({ type: kind === 'image' ? 'image' : 'video', attrs: { src: url } }).run();
        } catch {
            message.error('Tải tệp lên thất bại.');
        } finally {
            setUploading(false);
        }
    };

    const applyLink = () => {
        const url = linkUrl.trim();
        if (url === '') {
            editor.chain().focus().unsetLink().run();
        } else {
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        }
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
                <Tooltip title="Chèn ảnh"><Button size="small" type="text" icon={<PictureOutlined />} loading={uploading} onClick={() => imgInput.current?.click()} /></Tooltip>
                <Tooltip title="Chèn video"><Button size="small" type="text" icon={<VideoCameraOutlined />} loading={uploading} onClick={() => vidInput.current?.click()} /></Tooltip>
            </Space>

            <input ref={imgInput} type="file" accept="image/*" hidden onChange={(e) => { const f = e.target.files?.[0]; if (f) void upload(f, 'image'); e.target.value = ''; }} />
            <input ref={vidInput} type="file" accept="video/mp4,video/webm" hidden onChange={(e) => { const f = e.target.files?.[0]; if (f) void upload(f, 'video'); e.target.value = ''; }} />

            <EditorContent editor={editor} />

            <Modal title="Chèn liên kết" open={linkOpen} onOk={applyLink} onCancel={() => setLinkOpen(false)} okText="Áp dụng">
                <Input placeholder="https://..." value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} onPressEnter={applyLink} />
            </Modal>
        </div>
    );
}
