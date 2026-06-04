import { DatePicker, Empty, Form, InputNumber, Segmented, Space, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import dayjs from 'dayjs';
import { useDraftStore } from '@/lib/adWizard/draftStore';

const { Text } = Typography;

type BudgetType = 'daily';

const BUDGET_TYPE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Hằng ngày', value: 'daily' },
];

export function StepBudget() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);

    const adset = adsets.find((a) => a.key === selectedAdSetKey);

    if (adset == null) {
        return <Empty description="Chọn hoặc thêm một nhóm quảng cáo" />;
    }

    const adsetKey = adset.key;
    const adsetName = adset.name;
    const dailyMajor = adset.budget?.daily_major;
    const startTime = adset.schedule?.start_time;

    function handleDailyMajorChange(value: number | null) {
        updateAdSet(adsetKey, { budget: { daily_major: Number(value) || 0 } });
    }

    function handleStartTimeChange(d: dayjs.Dayjs | null) {
        updateAdSet(adsetKey, { schedule: { start_time: d != null ? d.toISOString() : null } });
    }

    return (
        <Form layout="vertical">
            <Form.Item>
                <Text type="secondary">
                    Ngân sách áp dụng cho nhóm: <b>{adsetName}</b>.
                </Text>
            </Form.Item>

            <Form.Item label="Kiểu ngân sách">
                <Segmented
                    options={BUDGET_TYPE_OPTIONS}
                    value={'daily' as BudgetType}
                />
            </Form.Item>

            <Form.Item label="Ngân sách mỗi ngày (VND)">
                <Space direction="vertical" size={4}>
                    <InputNumber
                        min={1000}
                        step={10000}
                        style={{ width: 220 }}
                        value={dailyMajor}
                        onChange={handleDailyMajorChange}
                        formatter={(value) =>
                            value != null
                                ? Number(value).toLocaleString('vi-VN')
                                : ''
                        }
                        parser={(value) =>
                            value != null
                                ? Number(value.replace(/\./g, '').replace(/,/g, ''))
                                : 0
                        }
                    />
                    <Text type="secondary">Gợi ý 100.000 – 300.000đ/ngày</Text>
                </Space>
            </Form.Item>

            <Form.Item label="Bắt đầu chạy">
                <Space direction="vertical" size={4}>
                    <DatePicker
                        showTime
                        value={startTime != null ? dayjs(startTime) : null}
                        onChange={handleStartTimeChange}
                        placeholder="Chọn ngày giờ"
                    />
                    <Text type="secondary">Để trống = chạy ngay khi xuất bản.</Text>
                </Space>
            </Form.Item>
        </Form>
    );
}
