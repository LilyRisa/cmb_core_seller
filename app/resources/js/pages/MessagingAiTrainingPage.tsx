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
            <PageHeader title="AI training" subtitle="Dạy AI bằng Kiến thức (nội dung + ảnh tùy chọn) để tư vấn đúng." />
            <MessagingNav />
            <Tabs
                defaultActiveKey="knowledge"
                items={[
                    { key: 'knowledge', label: 'Kiến thức', children: <VisualTrainingPanel /> },
                    { key: 'docs', label: 'Tài liệu cũ (chỉ xem)', children: <KnowledgeDocsPanel provider={platform} /> },
                ]}
            />
        </div>
    );
}

export default MessagingAiTrainingPage;
