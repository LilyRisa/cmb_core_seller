import { formatMoney } from '@/lib/format';

export function MoneyText({ value, currency, strong }: { value: number | null | undefined; currency?: string; strong?: boolean }) {
    const text = formatMoney(value, currency);
    return strong ? <strong>{text}</strong> : <span>{text}</span>;
}

export function DateText({ value, withTime = true }: { value: string | null | undefined; withTime?: boolean }) {
    return <span title={value ?? undefined}>{value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', ...(withTime ? { timeStyle: 'short' } : {}), timeZone: 'Asia/Ho_Chi_Minh' }).format(new Date(value)) : '—'}</span>;
}
