import { Alert, Checkbox, Empty, Form, Segmented, Space, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import { LayoutOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';

const { Text } = Typography;

type PlacementMode = 'automatic' | 'manual';

const PLACEMENT_MODE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Tự động (khuyến nghị)', value: 'automatic' },
    { label: 'Thủ công', value: 'manual' },
];

interface PlatformOption {
    label: string;
    value: string;
}

const PLATFORM_OPTIONS: PlatformOption[] = [
    { label: 'Facebook Feed', value: 'facebook_feed' },
    { label: 'Reels', value: 'facebook_reels' },
    { label: 'Stories', value: 'facebook_stories' },
    { label: 'Instagram', value: 'instagram_all' },
];

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

    const mode: PlacementMode = (adset.placements as PlacementMode | undefined) ?? 'automatic';
    const platforms = adset.placement_platforms ?? [];

    function handleModeChange(v: string | number) {
        if (selectedAdSetKey == null) return;
        updateAdSet(selectedAdSetKey, { placements: v as PlacementMode });
    }

    function handlePlatformsChange(checked: string[]) {
        if (selectedAdSetKey == null) return;
        updateAdSet(selectedAdSetKey, { placement_platforms: checked });
    }

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
                    value={mode}
                    onChange={handleModeChange}
                />
            </Form.Item>

            {mode === 'automatic' && (
                <Alert
                    type="info"
                    showIcon
                    message="Facebook tự phân bổ tới nơi hiệu quả nhất — khuyến nghị cho người mới."
                    style={{ maxWidth: 560 }}
                />
            )}

            {mode === 'manual' && (
                <Form.Item label="Chọn vị trí thủ công">
                    <Checkbox.Group
                        options={PLATFORM_OPTIONS}
                        value={platforms}
                        onChange={(checked) => handlePlatformsChange(checked as string[])}
                    />
                </Form.Item>
            )}
        </Form>
    );
}
