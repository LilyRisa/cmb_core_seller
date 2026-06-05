import axios from 'axios';
import { useQuery } from '@tanstack/react-query';

/**
 * SPEC 0030 — public order tracking. The endpoint is un-authenticated, so we use
 * a bare axios instance (no credentials, no X-Tenant-Id) instead of `lib/api.ts`.
 * Every field below is already masked server-side.
 */

export type TrackStepState = 'done' | 'process' | 'wait' | 'error';

export interface TrackStep {
    key: string;
    label: string;
    state: TrackStepState;
}

export interface TrackTimelineItem {
    at: string | null;
    label: string;
    source: string;
}

export interface TrackItem {
    name: string;
    variation: string | null;
    qty: number;
    image: string | null;
}

export interface PublicTracking {
    order_number: string;
    status: string;
    status_label: string;
    placed_at: string | null;
    delivered_at: string | null;
    carrier_name: string | null;
    cod: { amount: number; is_cod: boolean };
    recipient: { name: string | null; phone: string | null; area: string | null };
    items: TrackItem[];
    steps: TrackStep[];
    timeline: TrackTimelineItem[];
}

const publicApi = axios.create({
    baseURL: '/api/v1',
    headers: { Accept: 'application/json' },
});

export function usePublicTracking(code: string | null) {
    return useQuery<PublicTracking>({
        queryKey: ['public-tracking', code],
        enabled: !!code,
        retry: false,
        staleTime: 30_000,
        queryFn: async () => {
            const res = await publicApi.get('/public/track', { params: { code } });
            return res.data.data as PublicTracking;
        },
    });
}

/** Build the shareable public URL for a manual order code. */
export function publicTrackingUrl(orderNumber: string): string {
    return `${window.location.origin}/tracking?code=${encodeURIComponent(orderNumber)}`;
}
