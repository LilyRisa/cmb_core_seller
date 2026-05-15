import { useMemo } from 'react';
import { TreeSelect } from 'antd';
import { ChartAccount, useChartAccounts } from '@/lib/accounting';

interface AccountTreeSelectProps {
    value?: string;
    onChange?: (code: string | undefined) => void;
    placeholder?: string;
    onlyPostable?: boolean;
    onlyType?: ChartAccount['type'];
    disabled?: boolean;
    size?: 'small' | 'middle' | 'large';
    style?: React.CSSProperties;
    /** Filter TK theo prefix code (vd '1' chỉ TK loại tài sản). */
    codePrefix?: string;
}

interface TreeNode {
    title: string;
    value: string;
    selectable: boolean;
    disabled?: boolean;
    children?: TreeNode[];
}

/**
 * TreeSelect chọn tài khoản từ Chart of Accounts.
 *  - Hiển thị cây phân cấp parent→child.
 *  - TK không-postable hiển thị nhưng KHÔNG chọn được (selectable=false).
 *  - Search inline theo `code` và `name`.
 */
export function AccountTreeSelect({
    value, onChange, placeholder = 'Chọn tài khoản', onlyPostable = true, onlyType, disabled, size = 'middle', style, codePrefix,
}: AccountTreeSelectProps) {
    const { data: accounts = [], isLoading } = useChartAccounts({ type: onlyType });

    const treeData = useMemo<TreeNode[]>(() => {
        const filtered = accounts.filter((a) => {
            if (!a.is_active) return false;
            if (codePrefix && !a.code.startsWith(codePrefix)) return false;
            return true;
        });
        const byId = new Map<number, ChartAccount>();
        filtered.forEach((a) => byId.set(a.id, a));
        const roots: TreeNode[] = [];
        const cache = new Map<number, TreeNode>();

        const toNode = (a: ChartAccount): TreeNode => ({
            title: `${a.code} · ${a.name}`,
            value: a.code,
            selectable: a.is_postable && (!onlyPostable || a.is_postable),
            disabled: !a.is_postable && onlyPostable,
            children: [],
        });

        filtered.forEach((a) => {
            const node = toNode(a);
            cache.set(a.id, node);
        });
        filtered.forEach((a) => {
            const node = cache.get(a.id)!;
            if (a.parent_id && cache.has(a.parent_id)) {
                cache.get(a.parent_id)!.children!.push(node);
            } else {
                roots.push(node);
            }
        });
        // Bỏ children rỗng để TreeSelect không hiển thị mũi tên trống.
        const cleanup = (n: TreeNode): TreeNode => {
            if (n.children && n.children.length === 0) {
                const { children, ...rest } = n;
                return rest;
            }
            if (n.children) {
                n.children = n.children.map(cleanup);
            }
            return n;
        };

        return roots.map(cleanup);
    }, [accounts, onlyPostable, codePrefix]);

    return (
        <TreeSelect
            showSearch
            value={value}
            placeholder={placeholder}
            allowClear
            treeDefaultExpandAll={false}
            treeData={treeData}
            disabled={disabled}
            size={size}
            style={style ?? { width: '100%' }}
            loading={isLoading}
            onChange={(v) => onChange?.(v as string | undefined)}
            filterTreeNode={(input, node) => {
                const t = String(node.title ?? '').toLowerCase();
                return t.includes(input.toLowerCase());
            }}
            dropdownStyle={{ maxHeight: 480, overflow: 'auto' }}
        />
    );
}
