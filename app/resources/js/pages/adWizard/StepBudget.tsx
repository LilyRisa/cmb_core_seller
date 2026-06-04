import { DatePicker, Empty, Form, InputNumber, Segmented, Space, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import dayjs from 'dayjs';
import { useDraftStore } from '@/lib/adWizard/draftStore';

const { Text } = Typography;

type BudgetType = 'daily';

const BUDGET_TYPE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Hằng ngày', value: 'daily' },
];

const BUDGET_LEVEL_OPTIONS: SegmentedProps['options'] = [
    { label: 'Chiến dịch (tối ưu tự động)', value: 'campaign' },
    { label: 'Nhóm quảng cáo', value: 'adset' },
];

export function StepBudget() {
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);
    const payload = useDraftStore((s) => s.payload);
    const setBudgetMode = useDraftStore((s) => s.setBudgetMode);
    const setCampaignBudget = useDraftStore((s) => s.setCampaignBudget);

    const budgetMode = payload.campaign?.budget_mode ?? 'adset';

    const adset = adsets.find((a) => a.key === selectedAdSetKey);

    function handleDailyMajorChange(value: number | null) {
        if (adset == null) return;
        updateAdSet(adset.key, { budget: { daily_major: Number(value) || 0 } });
    }

    function handleStartTimeChange(d: dayjs.Dayjs | null, adsetKey: string) {
        updateAdSet(adsetKey, { schedule: { start_time: d != null ? d.toISOString() : null } });
    }

    function handleCampaignBudgetChange(value: number | null) {
        setCampaignBudget(Number(value) || 0);
    }

    return (
        <Form layout="vertical">
            <Form.Item label="Cấp ngân sách">
                <Segmented
                    options={BUDGET_LEVEL_OPTIONS}
                    value={budgetMode}
                    onChange={(v) => setBudgetMode(v as 'campaign' | 'adset')}
                />
            </Form.Item>

            {budgetMode === 'campaign' ? (
                <>
                    <Form.Item label="Ngân sách chiến dịch mỗi ngày (VND)">
                        <Space direction="vertical" size={4}>
                            <InputNumber
                                min={1000}
                                step={10000}
                                style={{ width: 220 }}
                                value={payload.campaign?.daily_budget_major}
                                onChange={handleCampaignBudgetChange}
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
                            <Text type="secondary">
                                Facebook tự chia ngân sách cho các nhóm hiệu quả nhất.
                            </Text>
                        </Space>
                    </Form.Item>

                    {adset != null && (
                        <Form.Item label="Bắt đầu chạy">
                            <Space direction="vertical" size={4}>
                                <DatePicker
                                    showTime
                                    value={adset.schedule?.start_time != null ? dayjs(adset.schedule.start_time) : null}
                                    onChange={(d) => handleStartTimeChange(d, adset.key)}
                                    placeholder="Chọn ngày giờ"
                                />
                                <Text type="secondary">Để trống = chạy ngay khi xuất bản.</Text>
                            </Space>
                        </Form.Item>
                    )}
                </>
            ) : (
                <>
                    {adset == null ? (
                        <Empty description="Chọn hoặc thêm một nhóm quảng cáo" />
                    ) : (
                        <>
                            <Form.Item>
                                <Text type="secondary">
                                    Ngân sách áp dụng cho nhóm: <b>{adset.name}</b>.
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
                                        value={adset.budget?.daily_major}
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
                                        value={adset.schedule?.start_time != null ? dayjs(adset.schedule.start_time) : null}
                                        onChange={(d) => handleStartTimeChange(d, adset.key)}
                                        placeholder="Chọn ngày giờ"
                                    />
                                    <Text type="secondary">Để trống = chạy ngay khi xuất bản.</Text>
                                </Space>
                            </Form.Item>
                        </>
                    )}
                </>
            )}
        </Form>
    );
}
