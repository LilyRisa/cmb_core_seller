// Spec 2026-05-17 — một dòng trong SystemSettingsPage. Control tự thay đổi theo
// `row.type`: bool → Switch tự-lưu; int → InputNumber + nút Lưu; string →
// Input/TextArea + nút Lưu; json → TextArea + validate JSON; secret →
// SecretInput component riêng (mask + reveal + ghi audit).
//
// Badge: "Đang dùng env" (chưa có row DB) vs "Đã đổi (admin)" (đã set qua UI).

import { useState } from 'react';
import { Space, Switch, Input, InputNumber, Button, Typography, Tag, App, Popconfirm } from 'antd';
import { errorMessage } from '@/lib/api';
import { useUpdateSetting, useDeleteSetting, type SettingRow as SR } from '../lib/systemSettings';
import { SecretInput } from './SecretInput';

export function SettingRow({ row }: { row: SR }) {
    const update = useUpdateSetting();
    const del = useDeleteSetting();
    const { message } = App.useApp();
    const [draft, setDraft] = useState<unknown>(row.value);

    // Phân biệt "đã đặt qua admin" vs "đang lấy từ env":
    //   - non-secret: value === null/undefined ⇒ chưa đặt
    //   - secret: backend trả `"****"` khi đã đặt, `null` khi chưa
    const isPersisted = row.is_secret ? row.value === '****' : row.value !== null && row.value !== undefined;

    function save(nextValue: unknown) {
        update.mutate(
            { key: row.key, value: nextValue },
            {
                onSuccess: () => message.success(`Đã lưu: ${row.label}`),
                onError: (e) => message.error(errorMessage(e, 'Lưu thất bại.')),
            },
        );
    }

    let control: React.ReactNode;
    if (row.is_secret) {
        control = <SecretInput settingKey={row.key} hasValue={isPersisted} onSave={save} />;
    } else if (row.type === 'bool') {
        const b = row.value === true || row.value === '1' || row.value === 1;
        control = <Switch checked={b} onChange={(v) => save(v)} />;
    } else if (row.type === 'int') {
        control = (
            <Space.Compact>
                <InputNumber
                    value={(draft as number) ?? (row.value as number)}
                    onChange={(v) => setDraft(v)}
                    style={{ width: 140 }}
                />
                <Button type="primary" onClick={() => save(Number(draft ?? row.value))}>Lưu</Button>
            </Space.Compact>
        );
    } else if (row.type === 'json') {
        control = (
            <Space.Compact style={{ width: '100%' }}>
                <Input.TextArea
                    value={
                        typeof draft === 'string'
                            ? draft
                            : draft === undefined && typeof row.value === 'string'
                                ? row.value
                                : JSON.stringify(row.value ?? null, null, 2)
                    }
                    onChange={(e) => setDraft(e.target.value)}
                    rows={3}
                />
                <Button
                    type="primary"
                    onClick={() => {
                        const v = (draft as string) ?? '';
                        try {
                            JSON.parse(v);
                        } catch {
                            message.error('JSON không hợp lệ.');
                            return;
                        }
                        save(v);
                    }}
                >
                    Lưu
                </Button>
            </Space.Compact>
        );
    } else {
        // string — TextArea autosize: 1 dòng như input thường, tự giãn cho prompt dài.
        control = (
            <Space.Compact style={{ width: '100%' }}>
                <Input.TextArea
                    value={(draft as string) ?? (row.value as string) ?? ''}
                    onChange={(e) => setDraft(e.target.value)}
                    autoSize={{ minRows: 1, maxRows: 12 }}
                />
                <Button type="primary" onClick={() => save((draft as string) ?? '')}>Lưu</Button>
            </Space.Compact>
        );
    }

    return (
        <div style={{ padding: '12px 0', borderBottom: '1px solid #E5E7EB' }}>
            <Space size={8} style={{ marginBottom: 6 }} wrap>
                <Typography.Text strong>{row.label}</Typography.Text>
                <Typography.Text code style={{ fontSize: 11 }}>{row.key}</Typography.Text>
                {isPersisted ? <Tag color="blue">Đã đổi (admin)</Tag> : <Tag>Đang dùng env</Tag>}
            </Space>
            {row.description && (
                <Typography.Paragraph type="secondary" style={{ marginBottom: 8, fontSize: 12 }}>
                    {row.description}
                </Typography.Paragraph>
            )}
            <div>{control}</div>
            {isPersisted && (
                <Popconfirm
                    title="Khôi phục về giá trị env (xoá row DB)?"
                    onConfirm={() =>
                        del.mutate(row.key, {
                            onSuccess: () => message.success('Đã khôi phục từ env.'),
                            onError: (e) => message.error(errorMessage(e)),
                        })
                    }
                >
                    <Button size="small" type="link" style={{ paddingLeft: 0 }}>
                        Khôi phục từ env
                    </Button>
                </Popconfirm>
            )}
        </div>
    );
}
