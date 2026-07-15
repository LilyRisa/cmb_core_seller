// SPEC 2026-07-15 — hooks quản lý email nhận thông báo admin ở /api/v1/admin/notification-emails/*.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export interface AdminNotificationEmail {
    id: number;
    email: string;
    label: string | null;
    is_active: boolean;
    notification_types: string[];
}

export interface AdminNotificationType {
    code: string;
    label: string;
}

export interface AdminNotificationEmailInput {
    email: string;
    label?: string | null;
    is_active?: boolean;
    notification_types: string[];
}

export function useAdminNotificationEmails() {
    return useQuery({
        queryKey: ['admin', 'notification-emails'],
        queryFn: async () => (await adminClient.get<{ data: AdminNotificationEmail[] }>('/notification-emails')).data.data,
    });
}

export function useAdminNotificationTypes() {
    return useQuery({
        queryKey: ['admin', 'notification-emails', 'types'],
        queryFn: async () => (await adminClient.get<{ data: AdminNotificationType[] }>('/notification-emails/types')).data.data,
    });
}

export function useCreateAdminNotificationEmail() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: AdminNotificationEmailInput) =>
            (await adminClient.post<{ data: AdminNotificationEmail }>('/notification-emails', input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'notification-emails'] }),
    });
}

export function useUpdateAdminNotificationEmail() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...input }: { id: number } & Partial<AdminNotificationEmailInput>) =>
            (await adminClient.patch<{ data: AdminNotificationEmail }>(`/notification-emails/${id}`, input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'notification-emails'] }),
    });
}

export function useDeleteAdminNotificationEmail() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.delete(`/notification-emails/${id}`)).data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'notification-emails'] }),
    });
}

export function useTestAdminNotificationEmail() {
    return useMutation({
        mutationFn: async (id: number) =>
            (await adminClient.post<{ data: { sent: boolean } }>(`/notification-emails/${id}/test`)).data.data,
    });
}
