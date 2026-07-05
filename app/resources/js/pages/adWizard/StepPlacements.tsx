import { Alert, Checkbox, Empty, Form, Segmented, Space, Tag, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import { LayoutOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import type { PlacementConfig } from '@/lib/adWizard';

const { Text } = Typography;

const PLACEMENT_MODE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Advantage+ (tự động — khuyến nghị)', value: 'automatic' },
    { label: 'Thủ công', value: 'manual' },
];

const DEVICE_OPTIONS = [
    { label: 'Điện thoại', value: 'mobile' },
    { label: 'Máy tính', value: 'desktop' },
];

interface PositionDef {
    label: string;
    value: string;
    /** Ràng buộc thiết bị: 'mobile'/'desktop' = chỉ chạy thiết bị đó; bỏ trống = cả hai. */
    device?: 'mobile' | 'desktop';
}

interface PlatformDef {
    value: string;
    label: string;
    positions: PositionDef[];
}

// Giá trị vị trí theo doc Placement Targeting (v25). KHÔNG có messenger_home (Meta khai tử
// Messenger Inbox); Messenger chỉ còn 'story' (mobile-only). Backend còn lọc lại lần nữa.
const PLATFORMS: PlatformDef[] = [
    {
        value: 'facebook',
        label: 'Facebook',
        positions: [
            { label: 'Bảng tin', value: 'feed' },
            { label: 'Marketplace', value: 'marketplace' },
            { label: 'Tin (Stories)', value: 'story', device: 'mobile' },
            { label: 'Reels', value: 'facebook_reels' },
            { label: 'Video trong luồng', value: 'instream_video' },
            { label: 'Cột phải', value: 'right_hand_column', device: 'desktop' },
            { label: 'Tìm kiếm', value: 'search' },
        ],
    },
    {
        value: 'instagram',
        label: 'Instagram',
        positions: [
            { label: 'Bảng tin', value: 'stream' },
            { label: 'Tin (Stories)', value: 'story' },
            { label: 'Reels', value: 'reels' },
            { label: 'Khám phá', value: 'explore' },
        ],
    },
    {
        value: 'messenger',
        label: 'Messenger',
        positions: [
            { label: 'Tin (Stories)', value: 'story', device: 'mobile' },
        ],
    },
    {
        value: 'audience_network',
        label: 'Audience Network',
        positions: [
            { label: 'Cổ điển', value: 'classic' },
            { label: 'Video thưởng', value: 'rewarded_video' },
        ],
    },
];

const PLATFORM_BY_VALUE: Record<string, PlatformDef> = Object.fromEntries(PLATFORMS.map((p) => [p.value, p]));

/** Một vị trí có hợp lệ với tập thiết bị đang chọn không (rỗng = mọi thiết bị). */
function positionValidForDevices(pos: PositionDef, devices: string[]): boolean {
    if (pos.device == null) return true;
    if (devices.length === 0) return true;
    return devices.includes(pos.device);
}

/** Các vị trí hợp lệ (đã lọc theo thiết bị) của một nền tảng. */
function validPositions(platform: string, devices: string[]): PositionDef[] {
    return (PLATFORM_BY_VALUE[platform]?.positions ?? []).filter((p) => positionValidForDevices(p, devices));
}

export function StepPlacements() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);

    const adset = adsets.find((a) => a.key === selectedAdSetKey);

    if (adset == null) {
        return <Empty description="Chọn hoặc thêm một nhóm quảng cáo" style={{ padding: 32 }} />;
    }

    const pc: PlacementConfig = adset.placement_config ?? { automatic: true };
    const patch = (next: PlacementConfig) => {
        if (selectedAdSetKey != null) updateAdSet(selectedAdSetKey, { placement_config: next });
    };

    const devices = pc.device_platforms ?? [];
    const selectedPlatforms = pc.publisher_platforms ?? [];
    const positions = pc.positions ?? {};

    function handleModeChange(v: string | number) {
        patch({ ...pc, automatic: v === 'automatic' });
    }

    // Đổi thiết bị: lọc lại vị trí đã chọn của mỗi nền tảng cho khớp thiết bị mới.
    function handleDevicesChange(next: string[]) {
        const nextPositions: Record<string, string[]> = {};
        for (const [plat, vals] of Object.entries(positions)) {
            const valid = validPositions(plat, next).map((p) => p.value);
            nextPositions[plat] = (vals ?? []).filter((v) => valid.includes(v));
        }
        patch({ ...pc, device_platforms: next, positions: nextPositions });
    }

    // Tích/bỏ nền tảng: tích ⇒ tự chọn HẾT vị trí hợp lệ; bỏ ⇒ gỡ nền tảng + vị trí của nó.
    function handlePlatformsChange(next: string[]) {
        const added = next.filter((p) => !selectedPlatforms.includes(p));
        const removed = selectedPlatforms.filter((p) => !next.includes(p));
        const nextPositions: Record<string, string[]> = { ...positions };
        for (const p of added) nextPositions[p] = validPositions(p, devices).map((x) => x.value);
        for (const p of removed) delete nextPositions[p];
        patch({ ...pc, publisher_platforms: next, positions: nextPositions });
    }

    function handlePositionsChange(platform: string, checked: string[]) {
        patch({ ...pc, positions: { ...positions, [platform]: checked } });
    }

    // Danh sách tóm tắt vị trí thực sẽ hiển thị (nền tảng · vị trí; rỗng = tất cả mặc định).
    const summary: { platform: string; label: string }[] = [];
    for (const plat of selectedPlatforms) {
        const platLabel = PLATFORM_BY_VALUE[plat]?.label ?? plat;
        const valid = validPositions(plat, devices);
        const chosen = (positions[plat] ?? []).filter((v) => valid.some((p) => p.value === v));
        if (chosen.length === 0) {
            summary.push({ platform: plat, label: `${platLabel} · Tất cả vị trí` });
        } else {
            for (const v of chosen) {
                const posLabel = valid.find((p) => p.value === v)?.label ?? v;
                summary.push({ platform: plat, label: `${platLabel} · ${posLabel}` });
            }
        }
    }

    return (
        <Form layout="vertical">
            <Form.Item
                label={
                    <Space size={6}>
                        <LayoutOutlined />
                        <Text strong>Vị trí hiển thị</Text>
                    </Space>
                }
            >
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
                    <Form.Item label="Thiết bị" extra="Bỏ trống = mọi thiết bị. Một số vị trí chỉ chạy trên điện thoại (Tin) hoặc máy tính (Cột phải).">
                        <Checkbox.Group
                            options={DEVICE_OPTIONS}
                            value={devices}
                            onChange={(checked) => handleDevicesChange(checked as string[])}
                        />
                    </Form.Item>

                    <Form.Item label="Nền tảng" extra="Tích một nền tảng sẽ tự chọn hết vị trí của nó — bỏ tích từng vị trí bên dưới để loại.">
                        <Checkbox.Group
                            options={PLATFORMS.map((p) => ({ label: p.label, value: p.value }))}
                            value={selectedPlatforms}
                            onChange={(checked) => handlePlatformsChange(checked as string[])}
                        />
                    </Form.Item>

                    {selectedPlatforms.map((plat) => {
                        const opts = validPositions(plat, devices);
                        return (
                            <Form.Item key={plat} label={`Vị trí — ${PLATFORM_BY_VALUE[plat]?.label ?? plat}`}>
                                <Checkbox.Group
                                    options={opts.map((o) => ({ label: o.label, value: o.value }))}
                                    value={(positions[plat] ?? []).filter((v) => opts.some((o) => o.value === v))}
                                    onChange={(checked) => handlePositionsChange(plat, checked as string[])}
                                />
                            </Form.Item>
                        );
                    })}

                    {selectedPlatforms.length > 0 && (
                        <Form.Item label="Vị trí sẽ hiển thị">
                            {summary.length === 0 ? (
                                <Text type="secondary">Chưa chọn vị trí nào.</Text>
                            ) : (
                                <Space size={[4, 8]} wrap>
                                    {summary.map((s, i) => (
                                        <Tag key={`${s.platform}-${i}`} color="blue" style={{ marginInlineEnd: 0 }}>
                                            {s.label}
                                        </Tag>
                                    ))}
                                </Space>
                            )}
                        </Form.Item>
                    )}
                </>
            )}
        </Form>
    );
}
