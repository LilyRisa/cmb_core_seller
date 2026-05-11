import { Tag } from 'antd';
import { CHANNEL_META } from '@/lib/format';

export function ChannelBadge({ provider }: { provider: string }) {
    const meta = CHANNEL_META[provider] ?? { name: provider, color: '#8c8c8c' };
    return (
        <Tag style={{ color: '#fff', background: meta.color, border: 'none', borderRadius: 4, fontWeight: 500 }}>
            {meta.name}
        </Tag>
    );
}
