import { Button, Space, Typography } from 'antd';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { FIELD_GROUPS, FIELD_REGISTRY } from './fieldTypes';
import type { Field } from '@/lib/shippingLabelTypes';
import { nanoid } from 'nanoid';

export function FieldPalette() {
    const addField = useEditorStore((s) => s.addField);

    const onAdd = (type: Field['type']) => () => {
        const def = FIELD_REGISTRY[type];
        const props = def.defaultProps();
        addField({ ...props, id: nanoid(8) } as Field);
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12, padding: 12, borderRight: '1px solid #f0f0f0', minWidth: 180 }}>
            {FIELD_GROUPS.map((g) => {
                const items = Object.values(FIELD_REGISTRY).filter((f) => f.group === g.key);
                if (items.length === 0) return null;
                return (
                    <div key={g.key}>
                        <Typography.Text type="secondary" style={{ fontSize: 11, textTransform: 'uppercase' }}>{g.label}</Typography.Text>
                        <Space direction="vertical" size={4} style={{ width: '100%', marginTop: 4 }}>
                            {items.map((f) => (
                                <Button key={f.type} icon={f.icon} block onClick={onAdd(f.type)} style={{ textAlign: 'left' }}>
                                    {f.label}
                                </Button>
                            ))}
                        </Space>
                    </div>
                );
            })}
        </div>
    );
}
