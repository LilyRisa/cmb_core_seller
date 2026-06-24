import { Result, Typography } from 'antd';
import { ToolOutlined } from '@ant-design/icons';

/** Placeholder cho trang module chưa xây (Phase 2+). */
export function ComingSoon({ title, phase }: { title: string; phase?: string }) {
    return (
        <Result
            icon={<ToolOutlined style={{ fontSize: 48, color: '#2563EB' }} />}
            title={title}
            subTitle={<Typography.Text type="secondary">Tính năng này sẽ được xây dựng theo roadmap{phase ? ` (${phase})` : ''}.</Typography.Text>}
        />
    );
}
