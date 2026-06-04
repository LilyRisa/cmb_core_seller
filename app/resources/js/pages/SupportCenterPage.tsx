import { useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Card, Col, Empty, Grid, Input, Menu, Row, Tag, Typography } from 'antd';
import { ReadOutlined, SearchOutlined } from '@ant-design/icons';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { HELP_IMAGE_BASE, findArticle, helpArticles } from '@/features/support/helpContent';

const { Title, Text, Paragraph } = Typography;

/** Đổi ảnh trong bài (images/x.png) sang CDN; giữ nguyên link khác. */
function transformUrl(url: string): string {
    if (url.startsWith('images/')) return `${HELP_IMAGE_BASE}/${url.slice('images/'.length)}`;
    return url;
}

function planColor(plan?: string): string {
    if (!plan) return 'default';
    if (/business/i.test(plan)) return 'gold';
    if (/pro/i.test(plan)) return 'blue';
    return 'green';
}

/**
 * Trung tâm trợ giúp — danh sách bài hướng dẫn (đồng bộ từ support_doc/) + tìm kiếm,
 * render markdown. Ảnh lấy từ CDN https://static.cmbcore.com/help. Không cần backend.
 */
export function SupportCenterPage() {
    const screens = Grid.useBreakpoint();
    const [params, setParams] = useSearchParams();
    const [q, setQ] = useState('');

    const initial = findArticle(params.get('article') ?? undefined)?.slug ?? helpArticles[0]?.slug;
    const [activeSlug, setActiveSlug] = useState<string>(initial);

    const filtered = useMemo(() => {
        const kw = q.trim().toLowerCase();
        if (kw === '') return helpArticles;
        return helpArticles.filter((a) => a.searchText.includes(kw));
    }, [q]);

    const active = findArticle(activeSlug) ?? filtered[0] ?? helpArticles[0];

    const select = (slug: string) => {
        setActiveSlug(slug);
        setParams((p) => {
            p.set('article', slug);
            return p;
        }, { replace: true });
    };

    const list = (
        <Menu
            mode="inline"
            selectedKeys={active ? [active.slug] : []}
            style={{ borderInlineEnd: 'none' }}
            onClick={({ key }) => select(key)}
            items={filtered.map((a) => ({ key: a.slug, label: a.title }))}
        />
    );

    return (
        <div>
            <Title level={4} style={{ marginBottom: 4 }}><ReadOutlined /> Trung tâm trợ giúp</Title>
            <Text type="secondary">Hướng dẫn sử dụng từng tính năng, theo từng bước — tìm nhanh việc bạn cần làm.</Text>

            <Row gutter={16} style={{ marginTop: 16 }}>
                <Col xs={24} md={8} lg={6}>
                    <Card size="small" styles={{ body: { padding: 8 } }}>
                        <Input
                            allowClear
                            prefix={<SearchOutlined />}
                            placeholder="Tìm bài hướng dẫn…"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            style={{ marginBottom: 8 }}
                        />
                        {filtered.length === 0
                            ? <Empty description="Không tìm thấy bài phù hợp" image={Empty.PRESENTED_IMAGE_SIMPLE} />
                            : list}
                    </Card>
                </Col>
                <Col xs={24} md={16} lg={18} style={{ marginTop: screens.md ? 0 : 16 }}>
                    <Card>
                        {active ? (
                            <>
                                <div style={{ marginBottom: 8 }}>
                                    {active.menu && <Tag>{active.menu}</Tag>}
                                    {active.plan && <Tag color={planColor(active.plan)}>Cần gói {active.plan}</Tag>}
                                    {active.roles.map((r) => <Tag key={r} color="purple">{r}</Tag>)}
                                </div>
                                <article className="help-article">
                                    <ReactMarkdown
                                        remarkPlugins={[remarkGfm]}
                                        urlTransform={transformUrl}
                                        components={{
                                            img: ({ node: _node, alt, ...props }) => (
                                                <img {...props} alt={alt ?? ''} style={{ maxWidth: '100%', borderRadius: 8, border: '1px solid #f0f0f0', margin: '8px 0' }} />
                                            ),
                                            a: ({ node: _node, href, children, ...props }) => {
                                                if (href && /\.md($|[?#])/.test(href)) {
                                                    const slug = href.replace(/[?#].*$/, '').replace(/\.md$/, '');
                                                    return (
                                                        <a
                                                            {...props}
                                                            href={`?article=${slug}`}
                                                            onClick={(e) => { e.preventDefault(); select(slug); }}
                                                        >{children}</a>
                                                    );
                                                }
                                                return <a {...props} href={href} target="_blank" rel="noreferrer">{children}</a>;
                                            },
                                        }}
                                    >
                                        {active.body}
                                    </ReactMarkdown>
                                </article>
                            </>
                        ) : (
                            <Paragraph type="secondary">Chưa có bài hướng dẫn.</Paragraph>
                        )}
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

export default SupportCenterPage;
