import ReactMarkdown from 'react-markdown';
// Tài liệu API biên soạn riêng cho bên thứ 3 (Vite ?raw import). SPEC 2026-06-26.
import md from '../../../docs/api-public.md?raw';

export function ApiDocsPage() {
    return (
        <div style={{ maxWidth: 880, margin: '0 auto', padding: '40px 24px' }} className="api-docs-md">
            <ReactMarkdown>{md}</ReactMarkdown>
        </div>
    );
}
