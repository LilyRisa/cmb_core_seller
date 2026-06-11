import { useMemo, useState } from 'react';
import { Cascader } from 'antd';
import { getCurrentTenantId } from '@/lib/auth';
import { tenantApi } from '@/lib/api';
import { getCategories, type CategoryNode } from './api';

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
    const client = useMemo(() => {
        const tid = getCurrentTenantId();
        return tid == null ? null : tenantApi(tid);
    }, []);
    const [options, setOptions] = useState<Option[]>([]);
    const [loaded, setLoaded] = useState(false);

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

    return (
        <Cascader
            style={{ width: '100%' }}
            placeholder="Chọn ngành hàng"
            disabled={disabled}
            options={options}
            // chỉ cho chọn nút lá (is_leaf)
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
    );
}
