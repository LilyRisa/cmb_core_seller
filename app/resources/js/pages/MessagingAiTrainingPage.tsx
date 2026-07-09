import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';

import { VisualTrainingPanel } from './MessagingVisualSearchPage';

/**
 * /messaging/knowledge — AI training: "Kiến thức" (nội dung + ảnh tùy chọn) để AI tư vấn đúng.
 * Hệ tài liệu text thuần cũ (RAG document) đã gỡ — chỉ còn Kiến thức (visual item).
 */
export function MessagingAiTrainingPage() {
    return (
        <div>
            <PageHeader title="AI training" subtitle="Dạy AI bằng Kiến thức (nội dung + ảnh tùy chọn) để tư vấn đúng." />
            <MessagingNav />
            <VisualTrainingPanel />
        </div>
    );
}

export default MessagingAiTrainingPage;
