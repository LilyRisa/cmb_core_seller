import { useCallback, useRef, useState } from 'react';

export type BulkItemStatus = 'pending' | 'running' | 'ok' | 'skipped' | 'error';

export interface BulkItem {
    id: number;
    label: string;       // hiển thị: order_number / mã
    sub?: string;        // nền tảng / ĐVVC
    status: BulkItemStatus;
    reason?: string;
    technical?: string;
}

export interface BulkServerResult {
    id: number;
    status: 'ok' | 'skipped' | 'error';
    reason?: string;
    technical?: string;
}

/** Hàm chạy 1 chunk id → trả kết quả per-id từ backend. */
export type ChunkRunner = (ids: number[]) => Promise<BulkServerResult[]>;

const CHUNK_SIZE = 25;

export function useBulkAction() {
    const [title, setTitle] = useState('');
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<BulkItem[]>([]);
    const [running, setRunning] = useState(false);
    const runnerRef = useRef<ChunkRunner | null>(null);

    const apply = useCallback((results: BulkServerResult[]) => {
        setItems((prev) => {
            const byId = new Map(results.map((r) => [r.id, r]));
            return prev.map((it) => {
                const r = byId.get(it.id);
                return r ? { ...it, status: r.status, reason: r.reason, technical: r.technical } : it;
            });
        });
    }, []);

    const runIds = useCallback(async (ids: number[], runner: ChunkRunner) => {
        setRunning(true);
        for (let i = 0; i < ids.length; i += CHUNK_SIZE) {
            const chunk = ids.slice(i, i + CHUNK_SIZE);
            setItems((prev) => prev.map((it) => (chunk.includes(it.id) ? { ...it, status: 'running' } : it)));
            try {
                apply(await runner(chunk));
            } catch (e) {
                const msg = e instanceof Error ? e.message : 'Lỗi không xác định';
                setItems((prev) => prev.map((it) => (chunk.includes(it.id) ? { ...it, status: 'error', reason: msg } : it)));
            }
        }
        setRunning(false);
    }, [apply]);

    const start = useCallback(async (cfg: { title: string; items: Omit<BulkItem, 'status'>[]; runner: ChunkRunner }) => {
        runnerRef.current = cfg.runner;
        setTitle(cfg.title);
        setItems(cfg.items.map((it) => ({ ...it, status: 'pending' as const })));
        setOpen(true);
        await runIds(cfg.items.map((it) => it.id), cfg.runner);
    }, [runIds]);

    const retryErrors = useCallback(async () => {
        const runner = runnerRef.current;
        if (!runner) return;
        const ids = items.filter((it) => it.status === 'error').map((it) => it.id);
        if (ids.length) await runIds(ids, runner);
    }, [items, runIds]);

    return { title, open, items, running, start, retryErrors, close: () => setOpen(false) };
}
