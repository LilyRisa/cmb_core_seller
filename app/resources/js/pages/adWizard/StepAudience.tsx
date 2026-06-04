import { useEffect, useRef, useState } from 'react';
import { Alert, Empty, Form, Select, Segmented, Slider, Space, Statistic, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import type { DefaultOptionType } from 'rc-select/lib/Select';
import { TeamOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import { useTargetingSearch, useAudienceEstimate } from '@/lib/adWizard';

const { Text } = Typography;

type Gender = 'all' | 'male' | 'female';

const GENDER_OPTIONS: SegmentedProps['options'] = [
    { label: 'Tất cả', value: 'all' },
    { label: 'Nam', value: 'male' },
    { label: 'Nữ', value: 'female' },
];

const COUNTRY_OPTIONS: DefaultOptionType[] = [
    { label: 'Việt Nam', value: 'VN' },
    { label: 'Thái Lan', value: 'TH' },
    { label: 'Indonesia', value: 'ID' },
    { label: 'Philippines', value: 'PH' },
    { label: 'Malaysia', value: 'MY' },
    { label: 'Singapore', value: 'SG' },
];

interface InterestItem {
    id: string;
    name: string;
}

/** Read initial targeting fields out of targeting spec (best-effort). */
function initFromTargeting(targeting: Record<string, unknown> | undefined): {
    countries: string[];
    ageRange: [number, number];
    gender: Gender;
    interests: InterestItem[];
} {
    if (targeting == null) {
        return { countries: ['VN'], ageRange: [18, 45], gender: 'all', interests: [] };
    }

    // countries
    const geoRaw = targeting.geo_locations as Record<string, unknown> | undefined;
    const countriesRaw = geoRaw?.countries;
    const countries =
        Array.isArray(countriesRaw) && countriesRaw.length > 0
            ? (countriesRaw as string[])
            : ['VN'];

    // age range
    const ageMin = typeof targeting.age_min === 'number' ? targeting.age_min : 18;
    const ageMax = typeof targeting.age_max === 'number' ? targeting.age_max : 45;

    // gender
    const gendersRaw = targeting.genders;
    let gender: Gender = 'all';
    if (Array.isArray(gendersRaw)) {
        if (gendersRaw.includes(1) && !gendersRaw.includes(2)) gender = 'male';
        else if (gendersRaw.includes(2) && !gendersRaw.includes(1)) gender = 'female';
    }

    // interests
    const flexRaw = targeting.flexible_spec;
    let interests: InterestItem[] = [];
    if (Array.isArray(flexRaw) && flexRaw.length > 0) {
        const firstSpec = flexRaw[0] as Record<string, unknown> | undefined;
        const interestsRaw = firstSpec?.interests;
        if (Array.isArray(interestsRaw)) {
            interests = (interestsRaw as Array<{ id: string; name: string }>).map((i) => ({
                id: i.id,
                name: i.name,
            }));
        }
    }

    return { countries, ageRange: [ageMin, ageMax], gender, interests };
}

/** Build a Graph API targeting spec, stripping undefined keys. */
function buildTargetingSpec(
    countries: string[],
    ageRange: [number, number],
    gender: Gender,
    interests: InterestItem[],
): Record<string, unknown> {
    const spec: Record<string, unknown> = {
        geo_locations: { countries },
        age_min: ageRange[0],
        age_max: ageRange[1],
    };
    if (gender !== 'all') {
        spec.genders = gender === 'male' ? [1] : [2];
    }
    if (interests.length > 0) {
        spec.flexible_spec = [
            { interests: interests.map((i) => ({ id: i.id, name: i.name })) },
        ];
    }
    return spec;
}

/** Inner form — rendered only when an ad set is selected. */
function AudienceForm({ adsetKey }: { adsetKey: string }) {
    const adsets = useDraftStore((s) => s.adsets);
    const accountId = useDraftStore((s) => s.accountId);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);

    const adset = adsets.find((a) => a.key === adsetKey);
    const init = initFromTargeting(adset?.targeting);

    const [countries, setCountries] = useState<string[]>(init.countries);
    const [ageRange, setAgeRange] = useState<[number, number]>(init.ageRange);
    const [gender, setGender] = useState<Gender>(init.gender);
    const [interests, setInterests] = useState<InterestItem[]>(init.interests);

    // Select options for interests (search results)
    const [interestOptions, setInterestOptions] = useState<DefaultOptionType[]>([]);

    const targetingSearch = useTargetingSearch();
    const audienceEstimate = useAudienceEstimate();

    // Debounce refs
    const searchDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const estimateDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Track whether this is the first render so we don't patch on mount
    const isFirstRenderRef = useRef(true);

    // Stable serialized keys to avoid object-identity issues in effect deps
    const countriesKey = countries.join(',');
    const interestsKey = interests.map((i) => i.id).join(',');

    // Patch ad set targeting and debounce audience estimate whenever fields change
    useEffect(() => {
        if (isFirstRenderRef.current) {
            isFirstRenderRef.current = false;
            return;
        }

        const spec = buildTargetingSpec(countries, ageRange, gender, interests);
        updateAdSet(adsetKey, { targeting: spec });

        // Debounced estimate
        if (estimateDebounceRef.current != null) {
            clearTimeout(estimateDebounceRef.current);
        }
        if (accountId != null) {
            estimateDebounceRef.current = setTimeout(() => {
                audienceEstimate.mutate({
                    accountId,
                    targeting: spec,
                    optimization_goal: 'REACH',
                });
            }, 600);
        }

        return () => {
            if (estimateDebounceRef.current != null) {
                clearTimeout(estimateDebounceRef.current);
            }
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [countriesKey, ageRange[0], ageRange[1], gender, interestsKey, adsetKey]);

    function handleInterestSearch(q: string) {
        if (searchDebounceRef.current != null) {
            clearTimeout(searchDebounceRef.current);
        }
        if (!q || accountId == null) return;
        searchDebounceRef.current = setTimeout(() => {
            targetingSearch.mutate(
                { accountId, q },
                {
                    onSuccess: (results) => {
                        setInterestOptions(
                            results.map((o) => ({ label: o.name, value: o.id })),
                        );
                    },
                },
            );
        }, 400);
    }

    // labelInValue interest values for the Select
    const interestSelectValue: { label: string; value: string }[] = interests.map((i) => ({
        label: i.name,
        value: i.id,
    }));

    function handleInterestChange(selected: { label: string; value: string }[]) {
        setInterests(selected.map((s) => ({ id: s.value, name: s.label as string })));
    }

    // Audience size display
    const lower = audienceEstimate.data?.lower_bound;
    const upper = audienceEstimate.data?.upper_bound;
    const hasEstimate =
        !audienceEstimate.isPending && lower != null && upper != null;

    const estimateValue =
        hasEstimate
            ? `${lower!.toLocaleString('vi-VN')} – ${upper!.toLocaleString('vi-VN')}`
            : '—';

    const isTooWide = hasEstimate && (upper ?? 0) > 5_000_000;

    return (
        <Form layout="vertical">
            <Form.Item label="Quốc gia">
                <Select
                    mode="multiple"
                    options={COUNTRY_OPTIONS}
                    value={countries}
                    onChange={(v: string[]) => setCountries(v.length > 0 ? v : ['VN'])}
                    style={{ maxWidth: 400 }}
                    placeholder="Chọn quốc gia"
                />
            </Form.Item>

            <Form.Item label="Độ tuổi">
                <Space direction="vertical" size={4} style={{ width: '100%', maxWidth: 400 }}>
                    <Slider
                        range
                        min={13}
                        max={65}
                        value={ageRange}
                        onChange={(v) => setAgeRange(v as [number, number])}
                        marks={{ 13: '13', 18: '18', 35: '35', 55: '55', 65: '65' }}
                    />
                    <Text type="secondary">{ageRange[0]} – {ageRange[1]} tuổi</Text>
                </Space>
            </Form.Item>

            <Form.Item label="Giới tính">
                <Segmented
                    options={GENDER_OPTIONS}
                    value={gender}
                    onChange={(v) => setGender(v as Gender)}
                />
            </Form.Item>

            <Form.Item label="Sở thích">
                <Select
                    mode="multiple"
                    labelInValue
                    filterOption={false}
                    showSearch
                    value={interestSelectValue}
                    options={interestOptions}
                    onSearch={handleInterestSearch}
                    onChange={handleInterestChange}
                    loading={targetingSearch.isPending}
                    placeholder="Tìm kiếm sở thích..."
                    notFoundContent={
                        targetingSearch.isPending ? 'Đang tìm...' : 'Nhập để tìm kiếm'
                    }
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item>
                <Space align="start" size={16}>
                    <Statistic
                        title="Quy mô tệp ước tính"
                        value={estimateValue}
                        prefix={<TeamOutlined />}
                        loading={audienceEstimate.isPending}
                    />
                    {isTooWide && (
                        <Alert
                            type="warning"
                            showIcon
                            message="Tệp quá rộng nên thu hẹp."
                            style={{ alignSelf: 'flex-end' }}
                        />
                    )}
                </Space>
            </Form.Item>
        </Form>
    );
}

export function StepAudience() {
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const adsets = useDraftStore((s) => s.adsets);
    const adset = adsets.find((a) => a.key === selectedAdSetKey);

    if (adset == null) {
        return (
            <Empty description="Chọn hoặc thêm một nhóm quảng cáo" style={{ padding: 32 }} />
        );
    }

    return <AudienceForm key={adset.key} adsetKey={adset.key} />;
}
