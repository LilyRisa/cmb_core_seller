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

    function patchSchedule(adsetKey: string, patch: { start_time?: string | null; end_time?: string | null }) {
        const current = adsets.find((a) => a.key === adsetKey)?.schedule ?? {};
        updateAdSet(adsetKey, { schedule: { ...current, ...patch } });
    }

    function handleStartTimeChange(d: dayjs.Dayjs | null, adsetKey: string) {
        patchSchedule(adsetKey, { start_time: d != null ? d.toISOString() : null });
    }

    function handleEndTimeChange(d: dayjs.Dayjs | null, adsetKey: string) {
        patchSchedule(adsetKey, { end_time: d != null ? d.toISOString() : null });
    }

    /** Lịch chạy: ngày bắt đầu (để trống = chạy ngay) + ngày kết thúc tuỳ chọn (phải sau bắt đầu). */
    function renderSchedule(a: NonNullable<typeof adset>) {
        const start = a.schedule?.start_time != null ? dayjs(a.schedule.start_time) : null;
        return (
            <>
                <Form.Item label="Bắt đầu chạy">
                    <Space direction="vertical" size={4}>
                        <DatePicker
                            showTime
                            value={start}
                            onChange={(d) => handleStartTimeChange(d, a.key)}
                            placeholder="Chọn ngày giờ"
                        />
                        <Text type="secondary">Để trống = chạy ngay khi xuất bản.</Text>
                    </Space>
                </Form.Item>
                <Form.Item label="Ngày kết thúc (tuỳ chọn)">
                    <Space direction="vertical" size={4}>
                        <DatePicker
                            showTime
                            value={a.schedule?.end_time != null ? dayjs(a.schedule.end_time) : null}
                            onChange={(d) => handleEndTimeChange(d, a.key)}
                            placeholder="Chọn ngày giờ kết thúc"
                            disabledDate={(d) => start != null && d.isBefore(start, 'minute')}
                        />
                        <Text type="secondary">Để trống = chạy liên tục đến khi bạn dừng.</Text>
                    </Space>
                </Form.Item>
            </>
        );
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

                    {adset != null && renderSchedule(adset)}
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

                            {renderSchedule(adset)}
                        </>
                    )}
                </>
            )}
        </Form>
    );
}
