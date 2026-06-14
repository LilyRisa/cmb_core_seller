import { useEffect, useMemo, useRef, useState } from 'react';
import { Cascader, Select, Space, Spin, Typography } from 'antd';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import { getCategories, getCategoryPath, searchCategories, type CategoryNode, type CategorySearchHit } from './api';

interface Option {
    value: string;
    label: string;
    isLeaf: boolean;
    loading?: boolean;
    children?: Option[];
}

function toOption(node: CategoryNode): Option {
    return { value: node.id, label: node.name, isLeaf: node.is_leaf };
}

/**
 * Chọn ngành hàng cho 1 gian hàng: ô tìm kiếm nâng cao (gõ từ khóa → ra ngành hàng
 * lá kèm đường dẫn breadcrumb, chọn trực tiếp) + duyệt cây phân cấp (Cascader lazy-load).
 * Hiển thị đường dẫn ngành hàng đang chọn để người bán biết mình đã chọn gì.
 */
export function CategoryPicker({
    provider,
    channelAccountId,
    value,
    onChange,
    disabled,
}: {
    provider: string;
    channelAccountId: number;
    /** category_id của lá đang chọn (nếu có). */
    value?: string | null;
    onChange?: (categoryId: string | null) => void;
    disabled?: boolean;
}) {
    const tenantId = useCurrentTenantId();
    const client = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const [options, setOptions] = useState<Option[]>([]);
    const [loaded, setLoaded] = useState(false);

    const [searchHits, setSearchHits] = useState<CategorySearchHit[]>([]);
    const [searching, setSearching] = useState(false);
    const debounce = useRef<ReturnType<typeof setTimeout> | null>(null);

    const [selectedPath, setSelectedPath] = useState<string | null>(null);

    // Hiển thị đường dẫn ngành hàng đang chọn (kể cả khi mở lại nháp đã lưu).
    useEffect(() => {
        let alive = true;
        if (!client || !value) {
            setSelectedPath(null);
            return;
        }
        getCategoryPath(client, provider, channelAccountId, value)
            .then((hit) => alive && setSelectedPath(hit.path || hit.name))
            .catch(() => alive && setSelectedPath(null));
        return () => {
            alive = false;
        };
    }, [client, provider, channelAccountId, value]);

    const loadRoot = async () => {
        if (loaded || !client) return;
        const nodes = await getCategories(client, provider, channelAccountId);
        setOptions(nodes.map(toOption));
        setLoaded(true);
    };

    const loadData = async (selectedOptions: Option[]) => {
        const target = selectedOptions[selectedOptions.length - 1];
        if (!client || target.isLeaf) return;
        target.loading = true;
        const nodes = await getCategories(client, provider, channelAccountId, target.value);
        target.loading = false;
        target.children = nodes.map(toOption);
        setOptions((prev) => [...prev]);
    };

    const runSearch = (term: string) => {
        if (debounce.current) clearTimeout(debounce.current);
        const q = term.trim();
        if (!client || q.length < 2) {
            setSearchHits([]);
            return;
        }
        debounce.current = setTimeout(async () => {
            setSearching(true);
            try {
                setSearchHits(await searchCategories(client, provider, channelAccountId, q));
            } finally {
                setSearching(false);
            }
        }, 350);
    };

    return (
        <Space direction="vertical" style={{ width: '100%' }} size={6}>
            <Select
                style={{ width: '100%' }}
                showSearch
                disabled={disabled}
                placeholder="Tìm nhanh ngành hàng (gõ từ khóa)…"
                filterOption={false}
                notFoundContent={searching ? <Spin size="small" /> : null}
                onSearch={runSearch}
                value={null}
                onChange={(v) => v && onChange?.(String(v))}
                options={searchHits.map((h) => ({ value: h.id, label: h.path || h.name }))}
            />
            <Cascader
                style={{ width: '100%' }}
                placeholder="hoặc duyệt cây ngành hàng"
                disabled={disabled}
                options={options}
                changeOnSelect={false}
                value={value ? [value] : undefined}
                loadData={loadData as never}
                onDropdownVisibleChange={(o) => {
                    if (o) void loadRoot();
                }}
                onChange={(vals) => {
                    const leaf = Array.isArray(vals) && vals.length ? String(vals[vals.length - 1]) : null;
                    onChange?.(leaf);
                }}
            />
            {value && (
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                    Đã chọn: {selectedPath ?? value}
                </Typography.Text>
            )}
        </Space>
    );
}
