import { Alert, Checkbox, Form, Segmented, Space, Typography } from 'antd';
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
    const payload = useDraftStore((s) => s.payload);
    const patchPayload = useDraftStore((s) => s.patchPayload);

    const mode: PlacementMode = (payload.placements as PlacementMode | undefined) ?? 'automatic';
    const platforms = (payload.placement_platforms as string[] | undefined) ?? [];

    function handleModeChange(v: string | number) {
        patchPayload({ placements: v as PlacementMode });
    }

    function handlePlatformsChange(checked: string[]) {
        patchPayload({ placement_platforms: checked });
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
