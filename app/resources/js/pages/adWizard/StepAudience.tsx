import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { Alert, App, Button, Empty, Form, Input, Modal, Popconfirm, Select, Segmented, Slider, Space, Statistic, Switch, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import type { DefaultOptionType } from 'rc-select/lib/Select';
import { DeleteOutlined, SaveOutlined, TeamOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import { useTargetingSearch, useAudienceEstimate } from '@/lib/adWizard';
import type { GeoItem } from '@/lib/adWizard';
import {
    useCreateExclusionTemplate,
    useDeleteExclusionTemplate,
    useExclusionTemplates,
} from '@/lib/adWizard/exclusionTemplates';
import {
    useAudienceTemplates,
    useCreateAudienceTemplate,
    useDeleteAudienceTemplate,
} from '@/lib/adWizard/audienceTemplates';
import { errorMessage } from '@/lib/api';

const { Text } = Typography;

type Gender = 'all' | 'male' | 'female';

const GENDER_OPTIONS: SegmentedProps['options'] = [
    { label: 'Tất cả', value: 'all' },
    { label: 'Nam', value: 'male' },
    { label: 'Nữ', value: 'female' },
];

/** A detailed-targeting item: an interest/behaviour/demographic. `type` is the
 *  Facebook flexible_spec/exclusions key (e.g. interests, behaviors, family_statuses). */
interface DetailedItem {
    id: string;
    name: string;
    type: string;
}

// Friendly Vietnamese labels for the common detailed-targeting category keys.
const DETAILED_TYPE_LABEL: Record<string, string> = {
    interests: 'Sở thích',
    behaviors: 'Hành vi',
    family_statuses: 'Tình trạng gia đình',
    relationship_statuses: 'Tình trạng quan hệ',
    life_events: 'Sự kiện trong đời',
    industries: 'Ngành nghề',
    income: 'Thu nhập',
    education_statuses: 'Trình độ học vấn',
    work_positions: 'Chức danh',
    work_employers: 'Nơi làm việc',
};
const detailedLabel = (t: string) => DETAILED_TYPE_LABEL[t] ?? t;

/** Group detailed items into a Graph flexible_spec/exclusions object keyed by type. */
function groupDetailed(items: DetailedItem[]): Record<string, { id: string; name: string }[]> {
    const out: Record<string, { id: string; name: string }[]> = {};
    for (const it of items) {
        (out[it.type] ??= []).push({ id: it.id, name: it.name });
    }
    return out;
}

/** Flatten a flexible_spec/exclusions group object back into detailed items. */
function flattenGroup(group: unknown): DetailedItem[] {
    if (group == null || typeof group !== 'object') return [];
    const out: DetailedItem[] = [];
    for (const [type, arr] of Object.entries(group as Record<string, unknown>)) {
        if (!Array.isArray(arr)) continue;
        for (const raw of arr as Array<{ id?: string; name?: string }>) {
            if (raw?.id != null) out.push({ id: String(raw.id), name: String(raw.name ?? raw.id), type });
        }
    }
    return out;
}

/** Read initial targeting fields out of targeting spec (best-effort). */
function initFromTargeting(
    targeting: Record<string, unknown> | undefined,
    geoMeta: { include: GeoItem[]; exclude: GeoItem[] } | undefined,
): {
    geo: { include: GeoItem[]; exclude: GeoItem[] };
    ageRange: [number, number];
    gender: Gender;
    detailedInclude: DetailedItem[];
    detailedNarrow: DetailedItem[];
    detailedExclude: DetailedItem[];
    advantageAudience: boolean;
} {
    if (targeting == null) {
        return {
            geo: { include: [{ key: 'VN', name: 'Việt Nam', type: 'country' }], exclude: [] },
            ageRange: [18, 45],
            gender: 'all',
            detailedInclude: [],
            detailedNarrow: [],
            detailedExclude: [],
            advantageAudience: false,
        };
    }

    // geo — prefer FE-only metadata, else back-compat from targeting.geo_locations.countries
    let geo: { include: GeoItem[]; exclude: GeoItem[] };
    if (geoMeta != null) {
        geo = geoMeta;
    } else {
        const geoRaw = targeting.geo_locations as Record<string, unknown> | undefined;
        const countriesRaw = geoRaw?.countries;
        const include: GeoItem[] =
            Array.isArray(countriesRaw) && countriesRaw.length > 0
                ? (countriesRaw as string[]).map((c) => ({ key: c, name: c, type: 'country' }))
                : [{ key: 'VN', name: 'Việt Nam', type: 'country' }];
        geo = { include, exclude: [] };
    }

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

    // detailed targeting: flexible_spec[0] = include group, [1] = narrow group (AND);
    // exclusions = excluded detailed targeting.
    const flexRaw = targeting.flexible_spec;
    const detailedInclude = Array.isArray(flexRaw) ? flattenGroup(flexRaw[0]) : [];
    const detailedNarrow = Array.isArray(flexRaw) ? flattenGroup(flexRaw[1]) : [];
    const detailedExclude = flattenGroup(targeting.exclusions);

    // Advantage+ Audience: Meta auto-expands the audience when on.
    const automation = targeting.targeting_automation as Record<string, unknown> | undefined;
    const advantageAudience = Number(automation?.advantage_audience ?? 0) === 1;

    return { geo, ageRange: [ageMin, ageMax], gender, detailedInclude, detailedNarrow, detailedExclude, advantageAudience };
}

/** Group geo items into FB geo_locations buckets by type. */
function bucket(items: GeoItem[]): Record<string, unknown> {
    const countries: string[] = [];
    const regions: { key: string }[] = [];
    const cities: { key: string; radius: number; distance_unit: string }[] = [];
    for (const it of items) {
        if (it.type === 'country') countries.push(it.country_code || it.key);
        else if (it.type === 'region') regions.push({ key: it.key });
        else cities.push({ key: it.key, radius: 25, distance_unit: 'kilometer' });
    }
    const geo: Record<string, unknown> = {};
    if (countries.length) geo.countries = countries;
    if (regions.length) geo.regions = regions;
    if (cities.length) geo.cities = cities;
    return geo;
}

/** Build a Graph API targeting spec, stripping undefined keys. */
function buildTargetingSpec(
    geo: { include: GeoItem[]; exclude: GeoItem[] },
    ageRange: [number, number],
    gender: Gender,
    detailedInclude: DetailedItem[],
    detailedNarrow: DetailedItem[],
    detailedExclude: DetailedItem[],
    advantageAudience: boolean,
): Record<string, unknown> {
    const inc = bucket(geo.include);
    const spec: Record<string, unknown> = {
        geo_locations: Object.keys(inc).length ? inc : { countries: ['VN'] },
    };
    // Advantage+ Audience (theo doc Meta): khi BẬT, tuổi tối thiểu chỉ 18–25 và KHÔNG đặt được
    // tuổi tối đa (Meta cố định 65). Cờ advantage_audience phải luôn khai rõ 0/1 (v23+).
    if (advantageAudience) {
        spec.age_min = Math.min(ageRange[0], 25);
        // age_max cố ý bỏ (Meta cố định 65).
        spec.targeting_automation = { advantage_audience: 1 };
    } else {
        spec.age_min = ageRange[0];
        spec.age_max = ageRange[1];
        spec.targeting_automation = { advantage_audience: 0 };
    }
    const exc = bucket(geo.exclude);
    if (Object.keys(exc).length) spec.excluded_geo_locations = exc;
    if (gender !== 'all') {
        spec.genders = gender === 'male' ? [1] : [2];
    }
    // flexible_spec: each group is an OR-set; multiple groups AND together (narrow).
    const includeGroup = groupDetailed(detailedInclude);
    const narrowGroup = groupDetailed(detailedNarrow);
    const flexible = [includeGroup, narrowGroup].filter((g) => Object.keys(g).length > 0);
    if (flexible.length > 0) spec.flexible_spec = flexible;
    // exclusions: detailed targeting to exclude.
    const exclusions = groupDetailed(detailedExclude);
    if (Object.keys(exclusions).length > 0) spec.exclusions = exclusions;
    return spec;
}

/** Inner form — rendered only when an ad set is selected. */
function AudienceForm({ adsetKey }: { adsetKey: string }) {
    const adsets = useDraftStore((s) => s.adsets);
    const accountId = useDraftStore((s) => s.accountId);
    const updateAdSet = useDraftStore((s) => s.updateAdSet);

    const adset = adsets.find((a) => a.key === adsetKey);
    const init = initFromTargeting(adset?.targeting, adset?.geo);

    const [geo, setGeo] = useState<{ include: GeoItem[]; exclude: GeoItem[] }>(init.geo);
    const [ageRange, setAgeRange] = useState<[number, number]>(init.ageRange);
    const [gender, setGender] = useState<Gender>(init.gender);
    const [detailedInclude, setDetailedInclude] = useState<DetailedItem[]>(init.detailedInclude);
    const [detailedNarrow, setDetailedNarrow] = useState<DetailedItem[]>(init.detailedNarrow);
    const [detailedExclude, setDetailedExclude] = useState<DetailedItem[]>(init.detailedExclude);
    const [advantageAudience, setAdvantageAudience] = useState<boolean>(init.advantageAudience);

    // Detailed-targeting search results (shared by include/narrow/exclude pickers).
    const [detailedOptions, setDetailedOptions] = useState<DefaultOptionType[]>([]);
    const detailedItemMapRef = useRef<Record<string, DetailedItem>>({});

    // Geo search-result options + lookup map (shared by both geo pickers)
    const [geoOptions, setGeoOptions] = useState<DefaultOptionType[]>([]);
    const geoItemMapRef = useRef<Record<string, GeoItem>>({});

    const targetingSearch = useTargetingSearch();
    const audienceEstimate = useAudienceEstimate();

    // Exclusion templates (save & apply)
    const { message } = App.useApp();
    const templates = useExclusionTemplates();
    const createTpl = useCreateExclusionTemplate();
    const deleteTpl = useDeleteExclusionTemplate();
    const [saveOpen, setSaveOpen] = useState(false);
    const [tplName, setTplName] = useState('');

    // Detailed-audience templates (save & apply include/narrow/exclude as a set)
    const audTemplates = useAudienceTemplates();
    const createAudTpl = useCreateAudienceTemplate();
    const deleteAudTpl = useDeleteAudienceTemplate();
    const [saveAudOpen, setSaveAudOpen] = useState(false);
    const [audTplName, setAudTplName] = useState('');

    const mergeDetailed = (base: DetailedItem[], add: DetailedItem[]): DetailedItem[] => {
        const map = new Map(base.map((i) => [i.id, i]));
        for (const it of add) map.set(it.id, { id: it.id, name: it.name, type: it.type });
        return [...map.values()];
    };

    function applyAudienceTemplate(id: number) {
        const tpl = (audTemplates.data ?? []).find((t) => t.id === id);
        if (tpl == null) return;
        setDetailedInclude((s) => mergeDetailed(s, tpl.payload.include ?? []));
        setDetailedNarrow((s) => mergeDetailed(s, tpl.payload.narrow ?? []));
        setDetailedExclude((s) => mergeDetailed(s, tpl.payload.exclude ?? []));
        message.success('Đã áp mẫu đối tượng.');
    }

    function onSaveAudienceTemplate() {
        createAudTpl.mutate(
            { name: audTplName.trim(), payload: { include: detailedInclude, narrow: detailedNarrow, exclude: detailedExclude } },
            {
                onSuccess: () => {
                    message.success('Đã lưu mẫu đối tượng.');
                    setSaveAudOpen(false);
                    setAudTplName('');
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    }

    const hasAnyDetailed = detailedInclude.length + detailedNarrow.length + detailedExclude.length > 0;

    function applyTemplate(id: number) {
        const tpl = (templates.data ?? []).find((t) => t.id === id);
        if (tpl == null) return;
        setGeo((g) => {
            const map = new Map(g.exclude.map((i) => [i.key, i]));
            for (const it of tpl.payload) map.set(it.key, it);
            return { ...g, exclude: [...map.values()] };
        });
        message.success('Đã áp mẫu loại trừ.');
    }

    function onSaveTemplate() {
        createTpl.mutate(
            { name: tplName.trim(), payload: geo.exclude },
            {
                onSuccess: () => {
                    message.success('Đã lưu mẫu.');
                    setSaveOpen(false);
                    setTplName('');
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    }

    // Debounce refs
    const searchDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const geoDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const estimateDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Track whether this is the first render so we don't patch on mount
    const isFirstRenderRef = useRef(true);

    // Stable serialized keys to avoid object-identity issues in effect deps
    const geoKey =
        geo.include.map((i) => i.key).join(',') + '|' + geo.exclude.map((i) => i.key).join(',');
    const detailedKey =
        detailedInclude.map((i) => i.id).join(',') + '|'
        + detailedNarrow.map((i) => i.id).join(',') + '|'
        + detailedExclude.map((i) => i.id).join(',');

    // Patch ad set targeting and debounce audience estimate whenever fields change
    useEffect(() => {
        if (isFirstRenderRef.current) {
            isFirstRenderRef.current = false;
            return;
        }

        const spec = buildTargetingSpec(geo, ageRange, gender, detailedInclude, detailedNarrow, detailedExclude, advantageAudience);
        updateAdSet(adsetKey, { targeting: spec, geo });

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
    }, [geoKey, ageRange[0], ageRange[1], gender, detailedKey, advantageAudience, adsetKey]);

    function handleDetailedSearch(q: string) {
        if (searchDebounceRef.current != null) {
            clearTimeout(searchDebounceRef.current);
        }
        if (!q || accountId == null) return;
        searchDebounceRef.current = setTimeout(() => {
            targetingSearch.mutate(
                { accountId, q, type: 'adTargetingCategory' },
                {
                    onSuccess: (results) => {
                        results.forEach((r) => {
                            detailedItemMapRef.current[r.id] = { id: r.id, name: r.name, type: r.type };
                        });
                        setDetailedOptions(
                            results.map((o) => ({
                                label: `${o.name} · ${detailedLabel(o.type)}`,
                                value: o.id,
                            })),
                        );
                    },
                },
            );
        }, 400);
    }

    function resolveDetailedItems(selected: { label: ReactNode; value: string }[]): DetailedItem[] {
        return selected.map(
            (s) => detailedItemMapRef.current[s.value] ?? { id: s.value, name: String(s.label), type: 'interests' },
        );
    }

    function handleGeoSearch(q: string) {
        if (geoDebounceRef.current != null) {
            clearTimeout(geoDebounceRef.current);
        }
        if (!q || accountId == null) return;
        geoDebounceRef.current = setTimeout(() => {
            targetingSearch.mutate(
                { accountId, q, type: 'adgeolocation' },
                {
                    onSuccess: (results) => {
                        results.forEach((r) => {
                            geoItemMapRef.current[r.id] = {
                                key: r.id,
                                name: r.name,
                                type: r.type as GeoItem['type'],
                            };
                        });
                        setGeoOptions(results.map((o) => ({ label: o.name, value: o.id })));
                    },
                },
            );
        }, 400);
    }

    function resolveGeoItems(selected: { label: ReactNode; value: string }[]): GeoItem[] {
        return selected.map(
            (s) =>
                geoItemMapRef.current[s.value] ?? {
                    key: s.value,
                    name: String(s.label),
                    type: 'country',
                },
        );
    }

    function handleGeoIncludeChange(selected: { label: ReactNode; value: string }[]) {
        setGeo((g) => ({ ...g, include: resolveGeoItems(selected) }));
    }

    function handleGeoExcludeChange(selected: { label: ReactNode; value: string }[]) {
        setGeo((g) => ({ ...g, exclude: resolveGeoItems(selected) }));
    }

    const detailedValue = (items: DetailedItem[]) =>
        items.map((i) => ({ label: `${i.name} · ${detailedLabel(i.type)}`, value: i.id }));

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
            <Form.Item label="Advantage+ Audience (Meta tự mở rộng đối tượng)">
                <Space direction="vertical" size={4}>
                    <Switch
                        checked={advantageAudience}
                        onChange={(on) => {
                            setAdvantageAudience(on);
                            // Bật Advantage+: Meta chỉ nhận tuổi tối thiểu 18–25, tối đa cố định 65 ⇒ ép về hợp lệ.
                            if (on) setAgeRange(([lo]) => [Math.min(lo, 25), 65]);
                        }}
                    />
                    <Text type="secondary" style={{ maxWidth: 400, display: 'inline-block' }}>
                        Khi bật, Meta GIỮ nguyên: khu vực, tuổi tối thiểu (18–25) và các loại trừ. Còn giới tính
                        và nhắm mục tiêu chi tiết chỉ là <b>gợi ý</b> — Meta tự mở rộng để tìm thêm khách. Tuổi tối
                        đa cố định 65, không đặt được.
                    </Text>
                </Space>
            </Form.Item>

            <Form.Item label="Khu vực nhắm đến">
                <Select
                    mode="multiple"
                    labelInValue
                    filterOption={false}
                    showSearch
                    value={geo.include.map((i) => ({ label: i.name, value: i.key }))}
                    options={geoOptions}
                    onSearch={handleGeoSearch}
                    onChange={handleGeoIncludeChange}
                    loading={targetingSearch.isPending}
                    placeholder="Tìm quốc gia / tỉnh / thành phố…"
                    notFoundContent={
                        targetingSearch.isPending ? 'Đang tìm...' : 'Nhập để tìm kiếm'
                    }
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item label="Loại trừ khu vực">
                <Select
                    mode="multiple"
                    labelInValue
                    filterOption={false}
                    showSearch
                    value={geo.exclude.map((i) => ({ label: i.name, value: i.key }))}
                    options={geoOptions}
                    onSearch={handleGeoSearch}
                    onChange={handleGeoExcludeChange}
                    loading={targetingSearch.isPending}
                    placeholder="Loại trừ tỉnh / thành phố…"
                    notFoundContent={
                        targetingSearch.isPending ? 'Đang tìm...' : 'Nhập để tìm kiếm'
                    }
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item label="Mẫu loại trừ">
                <Space direction="vertical" size={8} style={{ width: '100%', maxWidth: 400 }}>
                    <Space wrap>
                        <Select<number>
                            style={{ width: 280 }}
                            placeholder="Chọn mẫu để áp"
                            value={undefined}
                            loading={templates.isLoading}
                            options={(templates.data ?? []).map((t) => ({ label: t.name, value: t.id }))}
                            onChange={(id) => applyTemplate(id)}
                        />
                        <Button
                            icon={<SaveOutlined />}
                            onClick={() => setSaveOpen(true)}
                            disabled={geo.exclude.length === 0}
                        >
                            Lưu thành mẫu
                        </Button>
                    </Space>

                    {(templates.data ?? []).length === 0 ? (
                        <Text type="secondary">Chưa có mẫu</Text>
                    ) : (
                        <Space direction="vertical" size={2} style={{ width: '100%' }}>
                            {(templates.data ?? []).map((t) => (
                                <Space key={t.id} size={4} style={{ width: '100%', justifyContent: 'space-between' }}>
                                    <Text>{t.name}</Text>
                                    <Popconfirm
                                        title="Xoá mẫu này?"
                                        okText="Xoá"
                                        cancelText="Huỷ"
                                        onConfirm={() =>
                                            deleteTpl.mutate(t.id, {
                                                onSuccess: () => message.success('Đã xoá mẫu.'),
                                            })
                                        }
                                    >
                                        <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                                    </Popconfirm>
                                </Space>
                            ))}
                        </Space>
                    )}
                </Space>
            </Form.Item>

            <Modal
                title="Lưu mẫu loại trừ"
                open={saveOpen}
                onOk={onSaveTemplate}
                onCancel={() => setSaveOpen(false)}
                confirmLoading={createTpl.isPending}
                okText="Lưu"
                cancelText="Huỷ"
                okButtonProps={{ disabled: !tplName.trim() }}
            >
                <Input
                    value={tplName}
                    onChange={(e) => setTplName(e.target.value)}
                    placeholder="Tên mẫu, vd: Loại trừ nội thành"
                    maxLength={120}
                />
            </Modal>

            <Form.Item label="Độ tuổi">
                {advantageAudience ? (
                    <Space direction="vertical" size={4} style={{ width: '100%', maxWidth: 400 }}>
                        <Slider
                            min={18}
                            max={25}
                            value={Math.min(ageRange[0], 25)}
                            onChange={(v) => setAgeRange([v as number, 65])}
                            marks={{ 18: '18', 21: '21', 25: '25' }}
                        />
                        <Text type="secondary">Từ {Math.min(ageRange[0], 25)} tuổi · tối đa cố định 65 (Advantage+)</Text>
                    </Space>
                ) : (
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
                )}
            </Form.Item>

            <Form.Item label={advantageAudience ? 'Giới tính (gợi ý — Meta có thể mở rộng)' : 'Giới tính'}>
                <Segmented
                    options={GENDER_OPTIONS}
                    value={gender}
                    onChange={(v) => setGender(v as Gender)}
                />
            </Form.Item>

            <Form.Item
                label={advantageAudience ? 'Nhắm mục tiêu chi tiết (gợi ý)' : 'Nhắm mục tiêu chi tiết'}
                tooltip={advantageAudience
                    ? 'Khi bật Advantage+, đây chỉ là gợi ý — Meta tự mở rộng ra ngoài các tiêu chí này.'
                    : 'Tìm & thêm sở thích, hành vi, nhân khẩu học để thu hẹp đối tượng.'}
            >
                <Select
                    mode="multiple"
                    labelInValue
                    filterOption={false}
                    showSearch
                    value={detailedValue(detailedInclude)}
                    options={detailedOptions}
                    onSearch={handleDetailedSearch}
                    onChange={(sel) => setDetailedInclude(resolveDetailedItems(sel))}
                    loading={targetingSearch.isPending}
                    placeholder="Tìm sở thích / hành vi / nhân khẩu học…"
                    notFoundContent={targetingSearch.isPending ? 'Đang tìm...' : 'Nhập để tìm kiếm'}
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item
                label="Thu hẹp đối tượng (VÀ)"
                tooltip="Đối tượng phải khớp CẢ nhóm trên VÀ nhóm này (AND) — thu hẹp tệp."
            >
                <Select
                    mode="multiple"
                    labelInValue
                    filterOption={false}
                    showSearch
                    value={detailedValue(detailedNarrow)}
                    options={detailedOptions}
                    onSearch={handleDetailedSearch}
                    onChange={(sel) => setDetailedNarrow(resolveDetailedItems(sel))}
                    loading={targetingSearch.isPending}
                    placeholder="Thêm điều kiện thu hẹp (tuỳ chọn)…"
                    notFoundContent={targetingSearch.isPending ? 'Đang tìm...' : 'Nhập để tìm kiếm'}
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item
                label="Loại trừ đối tượng"
                tooltip="Không hiển thị cho người khớp các tiêu chí này."
            >
                <Select
                    mode="multiple"
                    labelInValue
                    filterOption={false}
                    showSearch
                    value={detailedValue(detailedExclude)}
                    options={detailedOptions}
                    onSearch={handleDetailedSearch}
                    onChange={(sel) => setDetailedExclude(resolveDetailedItems(sel))}
                    loading={targetingSearch.isPending}
                    placeholder="Loại trừ sở thích / hành vi (tuỳ chọn)…"
                    notFoundContent={targetingSearch.isPending ? 'Đang tìm...' : 'Nhập để tìm kiếm'}
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item label="Mẫu đối tượng chi tiết">
                <Space direction="vertical" size={8} style={{ width: '100%', maxWidth: 400 }}>
                    <Space wrap>
                        <Select<number>
                            style={{ width: 280 }}
                            placeholder="Chọn mẫu để áp"
                            value={undefined}
                            loading={audTemplates.isLoading}
                            options={(audTemplates.data ?? []).map((t) => ({ label: t.name, value: t.id }))}
                            onChange={(id) => applyAudienceTemplate(id)}
                        />
                        <Button
                            icon={<SaveOutlined />}
                            onClick={() => setSaveAudOpen(true)}
                            disabled={!hasAnyDetailed}
                        >
                            Lưu thành mẫu
                        </Button>
                    </Space>

                    {(audTemplates.data ?? []).length === 0 ? (
                        <Text type="secondary">Chưa có mẫu</Text>
                    ) : (
                        <Space direction="vertical" size={2} style={{ width: '100%' }}>
                            {(audTemplates.data ?? []).map((t) => (
                                <Space key={t.id} size={4} style={{ width: '100%', justifyContent: 'space-between' }}>
                                    <Text>{t.name}</Text>
                                    <Popconfirm
                                        title="Xoá mẫu này?"
                                        okText="Xoá"
                                        cancelText="Huỷ"
                                        onConfirm={() =>
                                            deleteAudTpl.mutate(t.id, {
                                                onSuccess: () => message.success('Đã xoá mẫu.'),
                                            })
                                        }
                                    >
                                        <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                                    </Popconfirm>
                                </Space>
                            ))}
                        </Space>
                    )}
                </Space>
            </Form.Item>

            <Modal
                title="Lưu mẫu đối tượng chi tiết"
                open={saveAudOpen}
                onOk={onSaveAudienceTemplate}
                onCancel={() => setSaveAudOpen(false)}
                confirmLoading={createAudTpl.isPending}
                okText="Lưu"
                cancelText="Huỷ"
                okButtonProps={{ disabled: !audTplName.trim() }}
            >
                <Input
                    value={audTplName}
                    onChange={(e) => setAudTplName(e.target.value)}
                    placeholder="Tên mẫu, vd: Tệp mua sắm cao cấp"
                    maxLength={120}
                />
            </Modal>

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
