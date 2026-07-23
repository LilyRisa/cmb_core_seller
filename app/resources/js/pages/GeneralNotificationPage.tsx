import { useParams } from 'react-router-dom';
import { Alert, Button, Card, Spin, Typography } from 'antd';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useGeneralNotificationPage } from '@/lib/generalNotificationPage';

/**
 * Plan C (2026-07-23) — trang nội dung "Chung" (ưu đãi/tin chung) admin gửi, mở qua tab mới từ
 * chuông thông báo. `body_html` đã được server sanitize (HtmlSanitizer) trước khi lưu — an toàn
 * để render trực tiếp.
 */
export function GeneralNotificationPage() {
    const { slug } = useParams<{ slug: string }>();
    const { data, isLoading, error } = useGeneralNotificationPage(slug);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin size="large" /></div>;
    if (error) return <Alert type="error" showIcon message={errorMessage(error, 'Không thể tải nội dung.')} style={{ margin: 24 }} />;
    if (!data) return null;

    return (
        <>
            <PageHeader title={data.title} />
            <Card>
                {data.cover_image_url && (
                    <img src={data.cover_image_url} alt="" style={{ maxWidth: '100%', borderRadius: 8, marginBottom: 16 }} />
                )}
                <Typography.Paragraph>
                    <div dangerouslySetInnerHTML={{ __html: data.body_html }} />
                </Typography.Paragraph>
                {data.cta_url && (
                    <Button type="primary" size="large" href={data.cta_url} target="_blank" rel="noopener noreferrer">
                        {data.cta_label || 'Xem chi tiết'}
                    </Button>
                )}
            </Card>
        </>
    );
}
