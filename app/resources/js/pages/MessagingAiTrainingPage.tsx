import { Tabs } from 'antd';

import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';

import { KnowledgeDocsPanel } from './MessagingKnowledgePage';
import { VisualTrainingPanel } from './MessagingVisualSearchPage';

/**
 * /messaging/knowledge — AI training GỘP: tab "Tài liệu (chữ)" (RAG) + tab
 * "Ảnh sản phẩm" (nhận diện bằng ảnh). Hai kênh huấn luyện bổ trợ nhau.
 */
export function MessagingAiTrainingPage() {
    return (
        <div>
            <PageHeader title="AI training" subtitle="Dạy AI bằng tài liệu (chữ) và ảnh sản phẩm để tư vấn đúng." />
            <MessagingNav />
            <Tabs
                defaultActiveKey="docs"
                items={[
                    { key: 'docs', label: 'Tài liệu (chữ)', children: <KnowledgeDocsPanel /> },
                    { key: 'images', label: 'Ảnh sản phẩm', children: <VisualTrainingPanel /> },
                ]}
            />
        </div>
    );
}

export default MessagingAiTrainingPage;
