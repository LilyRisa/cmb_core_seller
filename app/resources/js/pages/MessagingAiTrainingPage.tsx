import { Tabs } from 'antd';
import { useLocation } from 'react-router-dom';

import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';

import { KnowledgeDocsPanel } from './MessagingKnowledgePage';
import { VisualTrainingPanel } from './MessagingVisualSearchPage';

/**
 * /messaging/knowledge — AI training GỘP: tab "Tài liệu (chữ)" (RAG) + tab
 * "Ảnh sản phẩm" (nhận diện bằng ảnh). Hai kênh huấn luyện bổ trợ nhau.
 *
 * `?platform=zalo_oa` → picker hiển thị kênh Zalo OA (giống channels page).
 * Không có param → mặc định 'facebook_page'.
 */
export function MessagingAiTrainingPage() {
    const location = useLocation();
    const platform = new URLSearchParams(location.search).get('platform') ?? 'facebook_page';

    return (
        <div>
            <PageHeader title="AI training" subtitle="Dạy AI bằng tài liệu (chữ) và ảnh sản phẩm để tư vấn đúng." />
            <MessagingNav />
            <Tabs
                defaultActiveKey="docs"
                items={[
                    { key: 'docs', label: 'Tài liệu (chữ)', children: <KnowledgeDocsPanel provider={platform} /> },
                    { key: 'images', label: 'Ảnh sản phẩm', children: <VisualTrainingPanel /> },
                ]}
            />
        </div>
    );
}

export default MessagingAiTrainingPage;
