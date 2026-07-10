import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

/**
 * Ô soạn nội dung mẫu tin dạng "chip inline": các biến `{{buyer.name}}` hiển thị
 * thành chip bo tròn tiếng Việt NGAY trong dòng chữ (không lộ cú pháp biến).
 * Xoá 1 chip = xoá cả cụm (chip là 1 node contenteditable=false → Backspace xoá
 * nguyên khối). Lưu ra ngoài vẫn là chuỗi `{{...}}` để backend resolve như cũ.
 *
 * Mẫu "uncontrolled sau mount": chỉ dựng lại DOM từ `value` khi `resetSignal`
 * đổi (mở modal / đổi mẫu đang sửa) — trong lúc gõ KHÔNG reset để con trỏ không nhảy.
 */

export interface ChipTextEditorHandle {
    /** Chèn 1 chip biến vào vị trí con trỏ (hoặc cuối nếu chưa focus). */
    insertVar: (key: string, label: string) => void;
}

interface Props {
    /** Chuỗi body có `{{token}}` (do AntD Form.Item truyền vào). */
    value?: string;
    /** Trả chuỗi body đã serialize khi người dùng gõ/chèn/xoá. */
    onChange?: (v: string) => void;
    /** Đổi giá trị này để ép dựng lại DOM từ `value` (mở modal, đổi mẫu sửa). */
    resetSignal?: unknown;
    /** key biến → nhãn tiếng Việt; trả undefined ⇒ giữ nguyên `{{token}}` dạng chữ. */
    labelFor: (key: string) => string | undefined;
    placeholder?: string;
    minHeight?: number;
}

// contenteditable không hỗ trợ placeholder — dùng :empty:before (inject 1 lần).
if (typeof document !== 'undefined' && !document.getElementById('tpl-chip-editor-style')) {
    const s = document.createElement('style');
    s.id = 'tpl-chip-editor-style';
    s.textContent = '.tpl-chip-editor:empty:before{content:attr(data-placeholder);color:#bfbfbf;pointer-events:none;}';
    document.head.appendChild(s);
}

const TOKEN_RE = /\{\{\s*([a-zA-Z0-9_.]+)(?:\s*\|[^}]*)?\s*\}\}/g;

function escapeHtml(s: string): string {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function textToHtml(s: string): string {
    return escapeHtml(s).replace(/\n/g, '<br>');
}

function chipHtml(key: string, label: string): string {
    // contenteditable=false ⇒ Backspace xoá cả chip; inline style để tự chứa.
    return (
        `<span class="tpl-chip" data-token="${escapeHtml(key)}" contenteditable="false" ` +
        'style="display:inline-block;background:#2563eb;color:#fff;border-radius:10px;padding:0 8px;margin:0 2px;' +
        'font-size:12px;line-height:20px;white-space:nowrap;vertical-align:baseline;">' +
        escapeHtml(label) +
        '</span>'
    );
}

function buildHtml(value: string, labelFor: (k: string) => string | undefined): string {
    let html = '';
    let last = 0;
    let m: RegExpExecArray | null;
    TOKEN_RE.lastIndex = 0;
    while ((m = TOKEN_RE.exec(value)) !== null) {
        html += textToHtml(value.slice(last, m.index));
        const key = m[1];
        const label = labelFor(key);
        html += label ? chipHtml(key, label) : textToHtml(m[0]);
        last = m.index + m[0].length;
    }
    html += textToHtml(value.slice(last));
    return html;
}

function serialize(root: HTMLElement): string {
    let out = '';
    root.childNodes.forEach((child) => {
        if (child.nodeType === Node.TEXT_NODE) {
            out += child.textContent ?? '';
        } else if (child instanceof HTMLElement) {
            const token = child.dataset.token;
            if (token) {
                out += `{{${token}}}`;
            } else if (child.tagName === 'BR') {
                out += '\n';
            } else {
                // DIV do một số trình duyệt tạo khi Enter — coi như xuống dòng.
                if (child.tagName === 'DIV' && out.length > 0 && !out.endsWith('\n')) {
                    out += '\n';
                }
                out += serialize(child);
            }
        }
    });
    return out;
}

export const ChipTextEditor = forwardRef<ChipTextEditorHandle, Props>(function ChipTextEditor(
    { value, onChange, resetSignal, labelFor, placeholder, minHeight = 96 },
    ref,
) {
    const divRef = useRef<HTMLDivElement | null>(null);

    // Dựng lại DOM từ value CHỈ khi resetSignal đổi (không phụ thuộc value để tránh reset khi gõ).
    useEffect(() => {
        if (divRef.current) {
            divRef.current.innerHTML = buildHtml(value ?? '', labelFor);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [resetSignal]);

    const emit = () => {
        if (divRef.current) onChange?.(serialize(divRef.current));
    };

    useImperativeHandle(ref, () => ({
        insertVar: (key: string, label: string) => {
            const el = divRef.current;
            if (!el) return;
            el.focus();
            const sel = window.getSelection();
            let range: Range;
            if (sel && sel.rangeCount > 0 && el.contains(sel.getRangeAt(0).commonAncestorContainer)) {
                range = sel.getRangeAt(0);
                range.deleteContents();
            } else {
                // Chưa có con trỏ trong ô ⇒ chèn ở cuối.
                range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
            }
            const tpl = document.createElement('template');
            tpl.innerHTML = chipHtml(key, label) + ' ';
            const frag = tpl.content;
            const lastNode = frag.lastChild;
            range.insertNode(frag);
            // Đưa con trỏ ra sau khoảng trắng vừa chèn.
            if (lastNode) {
                const after = document.createRange();
                after.setStartAfter(lastNode);
                after.collapse(true);
                sel?.removeAllRanges();
                sel?.addRange(after);
            }
            emit();
        },
    }));

    return (
        <div
            ref={divRef}
            contentEditable
            suppressContentEditableWarning
            role="textbox"
            aria-multiline="true"
            data-placeholder={placeholder}
            onInput={emit}
            onBlur={emit}
            className="tpl-chip-editor"
            style={{
                minHeight,
                maxHeight: 260,
                overflowY: 'auto',
                border: '1px solid #d9d9d9',
                borderRadius: 6,
                padding: '6px 11px',
                fontSize: 14,
                lineHeight: 1.6,
                whiteSpace: 'pre-wrap',
                wordBreak: 'break-word',
                outline: 'none',
                background: '#fff',
            }}
        />
    );
});
