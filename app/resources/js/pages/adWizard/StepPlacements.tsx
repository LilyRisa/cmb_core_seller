import { Alert, Checkbox, Empty, Form, Segmented, Space, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import { LayoutOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import type { PlacementConfig } from '@/lib/adWizard';

const { Text } = Typography;

const PLACEMENT_MODE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Tự động (khuyến nghị)', value: 'automatic' },
    { label: 'Thủ công', value: 'manual' },
];

interface CheckboxOption {
    label: string;
    value: string;
}

const DEVICE_OPTIONS: CheckboxOption[] = [
    { label: 'Điện thoại', value: 'mobile' },
    { label: 'Máy tính', value: 'desktop' },
];

const PLATFORM_OPTIONS: CheckboxOption[] = [
    { label: 'Facebook', value: 'facebook' },
    { label: 'Instagram', value: 'instagram' },
    { label: 'Messenger', value: 'messenger' },
    { label: 'Audience Network', value: 'audience_network' },
];

const PLATFORM_LABEL: Record<string, string> = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    messenger: 'Messenger',
    audience_network: 'Audience Network',
};

const POSITION_OPTIONS: Record<string, CheckboxOption[]> = {
    facebook: [
        { label: 'Bảng tin', value: 'feed' },
        { label: 'Marketplace', value: 'marketplace' },
        { label: 'Video feeds', value: 'video_feeds' },
        { label: 'Tin', value: 'story' },
        { label: 'Reels', value: 'facebook_reels' },
        { label: 'Cột phải', value: 'right_hand_column' },
        { label: 'Tìm kiếm', value: 'search' },
    ],
    instagram: [
        { label: 'Bảng tin', value: 'stream' },
        { label: 'Tin', value: 'story' },
        { label: 'Reels', value: 'reels' },
        { label: 'Khám phá', value: 'explore' },
    ],
    messenger: [
        { label: 'Trang chủ', value: 'messenger_home' },
        { label: 'Tin', value: 'story' },
    ],
    audience_network: [
        { label: 'Cổ điển', value: 'classic' },
        { label: 'Video thưởng', value: 'rewarded_video' },
    ],
};

export function StepPlacements() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);

    const adset = adsets.find((a) => a.key === selectedAdSetKey);

    if (adset == null) {
        return (
            <Empty description="Chọn hoặc thêm một nhóm quảng cáo" style={{ padding: 32 }} />
        );
    }

    const pc: PlacementConfig = adset.placement_config ?? { automatic: true };
    const patch = (next: PlacementConfig) => {
        if (selectedAdSetKey != null) updateAdSet(selectedAdSetKey, { placement_config: next });
    };

    function handleModeChange(v: string | number) {
        patch({ ...pc, automatic: v === 'automatic' });
    }

    const selectedPlatforms = pc.publisher_platforms ?? [];

    return (
        <Form layout="vertical">
            <Form.Item label={
                <Space size={6}>
                    <LayoutOutlined />
                    <Text strong>Vị trí hiển thị</Text>
                </Space>
            }>
                <Segmented
                    options={PLACEMENT_MODE_OPTIONS}
                    value={pc.automatic ? 'automatic' : 'manual'}
                    onChange={handleModeChange}
                />
            </Form.Item>

            {pc.automatic && (
                <Alert
                    type="info"
                    showIcon
                    message="Facebook tự phân bổ tới nơi hiệu quả nhất — khuyến nghị cho người mới."
                    style={{ maxWidth: 560 }}
                />
            )}

            {!pc.automatic && (
                <>
                    <Form.Item label="Thiết bị">
                        <Checkbox.Group
                            options={DEVICE_OPTIONS}
                            value={pc.device_platforms ?? []}
                            onChange={(checked) => patch({ ...pc, device_platforms: checked as string[] })}
                        />
                    </Form.Item>

                    <Form.Item label="Nền tảng">
                        <Checkbox.Group
                            options={PLATFORM_OPTIONS}
                            value={selectedPlatforms}
                            onChange={(checked) => patch({ ...pc, publisher_platforms: checked as string[] })}
                        />
                    </Form.Item>

                    {selectedPlatforms.map((plat) => (
                        <Form.Item key={plat} label={`Vị trí — ${PLATFORM_LABEL[plat] ?? plat}`}>
                            <Checkbox.Group
                                options={POSITION_OPTIONS[plat] ?? []}
                                value={pc.positions?.[plat] ?? []}
                                onChange={(checked) => patch({
                                    ...pc,
                                    positions: { ...(pc.positions ?? {}), [plat]: checked as string[] },
                                })}
                            />
                        </Form.Item>
                    ))}
                </>
            )}
        </Form>
    );
}
