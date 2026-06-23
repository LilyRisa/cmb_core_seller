import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Tích hợp máy quét đơn cầm tay cho màn "Quét đơn".
 *
 * Hai chế độ bổ trợ nhau:
 *  - Bàn phím (mặc định): máy quét USB/Bluetooth giả lập bàn phím gõ mã rồi (thường) Enter.
 *    ScanTab tự giữ focus + tự gửi khi phát hiện một chuỗi gõ "tốc độ máy quét".
 *  - Web Serial: kết nối trực tiếp tới máy quét ở chế độ cổng COM ảo, đọc mã kể cả khi ô
 *    nhập không focus. Chỉ chạy trên Chrome/Edge desktop (HTTPS hoặc localhost).
 */

// ---- Bíp âm thanh phản hồi quét ---------------------------------------------

let audioCtx: AudioContext | null = null;

/** Phát một tiếng bíp ngắn: tần số cao = OK, tần số thấp = lỗi. Web Audio, không cần file. */
export function playScanBeep(ok: boolean): void {
    try {
        const Ctx = window.AudioContext ?? (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;
        if (!Ctx) return;
        audioCtx ??= new Ctx();
        const ctx = audioCtx;
        if (ctx.state === 'suspended') void ctx.resume();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'square';
        osc.frequency.value = ok ? 1320 : 320;
        gain.gain.setValueAtTime(0.0001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (ok ? 0.12 : 0.28));
        osc.connect(gain).connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + (ok ? 0.13 : 0.3));
    } catch {
        /* trình duyệt chặn audio chưa có tương tác — bỏ qua */
    }
}

// ---- Kết nối Web Serial ------------------------------------------------------

export type SerialStatus = 'unsupported' | 'idle' | 'connecting' | 'connected' | 'error';

interface SerialPortLike {
    open(options: { baudRate: number }): Promise<void>;
    close(): Promise<void>;
    readable: ReadableStream<Uint8Array> | null;
}

const serialApi = (): { requestPort(): Promise<SerialPortLike> } | undefined =>
    (navigator as unknown as { serial?: { requestPort(): Promise<SerialPortLike> } }).serial;

/**
 * Hook kết nối máy quét qua Web Serial. `onScan` được gọi với từng mã (tách theo CR/LF).
 * `baudRate` mặc định 9600 — đúng với phần lớn máy quét cổng COM ảo.
 */
export function useSerialScanner(onScan: (code: string) => void, baudRate = 9600) {
    const supported = typeof navigator !== 'undefined' && 'serial' in navigator;
    const [status, setStatus] = useState<SerialStatus>(supported ? 'idle' : 'unsupported');

    const portRef = useRef<SerialPortLike | null>(null);
    const readerRef = useRef<ReadableStreamDefaultReader<Uint8Array> | null>(null);
    const keepRef = useRef(false);
    const onScanRef = useRef(onScan);
    onScanRef.current = onScan;

    const disconnect = useCallback(async () => {
        keepRef.current = false;
        try { await readerRef.current?.cancel(); } catch { /* noop */ }
        try { await portRef.current?.close(); } catch { /* noop */ }
        readerRef.current = null;
        portRef.current = null;
        setStatus(supported ? 'idle' : 'unsupported');
    }, [supported]);

    const connect = useCallback(async () => {
        const api = serialApi();
        if (!api) { setStatus('unsupported'); return; }
        try {
            setStatus('connecting');
            const port = await api.requestPort();
            await port.open({ baudRate });
            portRef.current = port;
            keepRef.current = true;
            setStatus('connected');

            const decoder = new TextDecoder();
            let buffer = '';
            while (keepRef.current && port.readable) {
                const reader = port.readable.getReader();
                readerRef.current = reader;
                try {
                    for (;;) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        let idx: number;
                        while ((idx = buffer.search(/[\r\n]/)) >= 0) {
                            const line = buffer.slice(0, idx).trim();
                            buffer = buffer.slice(idx + 1);
                            if (line) onScanRef.current(line);
                        }
                    }
                } catch {
                    /* máy quét bị rút / lỗi đọc — thoát vòng */
                    break;
                } finally {
                    try { reader.releaseLock(); } catch { /* noop */ }
                }
            }
        } catch (e) {
            // Người dùng huỷ hộp thoại chọn cổng → quay lại idle, không coi là lỗi.
            const name = (e as { name?: string } | null)?.name;
            setStatus(name === 'NotFoundError' || name === 'AbortError' ? 'idle' : 'error');
            keepRef.current = false;
        }
    }, [baudRate]);

    // Dọn dẹp khi unmount: đóng cổng đang mở.
    useEffect(() => () => { void disconnect(); }, [disconnect]);

    return { supported, status, connect, disconnect };
}
