import { DatePicker, Form, InputNumber, Segmented, Space, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import dayjs from 'dayjs';
import { useDraftStore } from '@/lib/adWizard/draftStore';

const { Text } = Typography;

type BudgetType = 'daily';

const BUDGET_TYPE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Hằng ngày', value: 'daily' },
];

export function StepBudget() {
    const payload = useDraftStore((s) => s.payload);
    const patchPayload = useDraftStore((s) => s.patchPayload);

    const budgetType: BudgetType = (payload.budget?.type as BudgetType | undefined) ?? 'daily';
    const dailyMajor = payload.budget?.daily_major;
    const startTime = payload.schedule?.start_time;

    function handleBudgetTypeChange(value: string | number) {
        patchPayload({ budget: { type: value as BudgetType, daily_major: dailyMajor ?? 0 } });
    }

    function handleDailyMajorChange(value: number | null) {
        patchPayload({ budget: { type: budgetType, daily_major: Number(value) || 0 } });
    }

    function handleStartTimeChange(d: dayjs.Dayjs | null) {
        patchPayload({ schedule: { start_time: d != null ? d.toISOString() : null } });
    }

    return (
        <Form layout="vertical">
            <Form.Item label="Kiểu ngân sách">
                <Segmented
                    options={BUDGET_TYPE_OPTIONS}
                    value={budgetType}
                    onChange={handleBudgetTypeChange}
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
