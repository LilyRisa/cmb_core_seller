# Facebook Messaging — Frontend Implementation Plan (avatar · sync UI · media/emoji composer · inbox management)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Surface the new backend features in the React/Ant Design UI — page avatar + sync progress + message count on the connect page; image/video/document + emoji sending and attachment rendering in the composer; buyer avatars, mark-unread, unread filter, and block/unblock in the inbox.

**Architecture:** Extend the existing Inertia-less React SPA (`resources/js`). Data flows through the `@tanstack/react-query` hooks in `lib/messaging.tsx` (inbox) and `lib/messagingConfig.tsx` (channels), which call `/api/v1/messaging/*` via the tenant-scoped axios client. UI is Ant Design v5. New emoji picker via `@emoji-mart/react`.

**Tech Stack:** React 18 + TypeScript, Ant Design 5, `@ant-design/icons`, `@tanstack/react-query` v5, axios, react-router-dom 6. New dep: `@emoji-mart/react` + `@emoji-mart/data`.

**Spec:** `docs/superpowers/specs/2026-05-21-facebook-messaging-sync-and-inbox-design.md` (slices 7–9). **Depends on the backend plan** (`2026-05-21-facebook-messaging-backend.md`) being implemented first — it provides `sync`/`avatar_url`/`message_count` on `GET /messaging/channels`, the `POST channels/{id}/sync`, `conversations/{id}/unread`, `conversations/{id}/block` endpoints, and the `messages/media` endpoint (already live).

---

## ⚠️ Testing note (read first)

This repo has **no frontend test runner** — `app/package.json` defines only `lint` (ESLint) and `typecheck` (`tsc --noEmit`); there are zero `*.test.tsx` files and no Vitest config. The spec listed "FE (Vitest)" tests, but standing up a test framework is a separate scope decision and is **not** included here. Each task is therefore verified with the repo's actual gates:

- `npm run typecheck` (from `app/`) — must pass with no errors
- `npm run lint` (from `app/`) — must pass (run `npm run lint:fix` to autofix)
- Manual smoke per task's "Manual check" note

If the team later wants Vitest, add it as a separate prerequisite task (install `vitest` + `@testing-library/react` + `jsdom`, add `vitest.config.ts` + a `test` script) before converting these tasks to TDD.

All commands run from `app/`. Commit after each task.

---

## File Structure

**Modify:**
- `app/resources/js/lib/messaging.tsx` — types (`Conversation`, `ConversationFilters`), hooks (`useSendMedia`, `useMarkUnread`, `useBlockConversation`, `useUnblockConversation`)
- `app/resources/js/lib/messagingConfig.tsx` — `MessagingChannel` type + `ChannelSync`, `useSyncChannel`, polling on `useMessagingChannels`
- `app/resources/js/pages/MessagingChannelsPage.tsx` — avatar, sync progress, message count, sync button
- `app/resources/js/pages/MessagingPage.tsx` — buyer avatar, media/emoji composer, attachment rendering, mark-unread, unread filter, block/unblock, composer lock
- `app/app/Modules/Messaging/Http/Resources/ConversationResource.php` — serve buyer avatar from relayed path + expose `blocked_at`/`manually_unread` (backend tweak needed by FE)
- `app/app/Modules/Messaging/Http/Controllers/ConversationController.php` — eager-load `attachments` in `show()` (backend tweak needed by FE)
- `app/package.json` — add `@emoji-mart/react`, `@emoji-mart/data`

---

## Task 1: Data layer — deps, backend serialization tweaks, types, hooks

**Files:**
- Modify: `app/package.json` (via npm)
- Modify: `app/app/Modules/Messaging/Http/Resources/ConversationResource.php`
- Modify: `app/app/Modules/Messaging/Http/Controllers/ConversationController.php`
- Modify: `app/resources/js/lib/messaging.tsx`
- Modify: `app/resources/js/lib/messagingConfig.tsx`

- [ ] **Step 1: Install emoji picker deps**

Run (from `app/`): `npm install @emoji-mart/react @emoji-mart/data`
Expected: both added to `dependencies` in `package.json`; `package-lock.json` updated.

- [ ] **Step 2: Backend — `ConversationResource` serves buyer avatar + block/unread fields**

In `ConversationResource::toArray`, replace the `'buyer_avatar_url'` line and add two fields (these fulfil the "verify ConversationResource" note in the backend plan Task 9):

```php
            'buyer_name' => $this->buyer_name,
            'buyer_avatar_url' => app(\CMBcoreSeller\Modules\Messaging\Services\MediaStorage::class)
                ->temporaryUrlForPath($this->buyer_avatar_path) ?? $this->buyer_avatar_url,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'manually_unread' => (bool) $this->manually_unread,
            'customer_id' => $this->customer_id,
```

- [ ] **Step 3: Backend — eager-load attachments in the thread**

In `ConversationController::show`, add `->with('attachments')` so `MessageResource` includes them:

```php
        $messagesQuery = Message::query()
            ->with('attachments')
            ->where('conversation_id', $conv->id)
            ->orderByDesc('created_at');
```

- [ ] **Step 4: Run backend tests for these resources**

Run: `php artisan test --filter "MessagingApiTest|MessagingInboxManagementTest"` (from `app/`)
Expected: PASS (no regression; `MessagingInboxManagementTest` from the backend plan exercises `blocked_at`/`manually_unread`).

- [ ] **Step 5: Extend inbox types** (`lib/messaging.tsx`)

In the `Conversation` interface, add (after `status`):

```ts
    status: ConversationStatus;
    blocked_at: string | null;
    manually_unread: boolean;
    unread_count: number;
```

In `ConversationFilters`, add `blocked`:

```ts
export interface ConversationFilters {
    provider?: string;
    status?: string;
    unread?: boolean;
    blocked?: boolean;
    assigned?: string;
    q?: string;
    page?: number;
    per_page?: number;
}
```

- [ ] **Step 6: Add inbox mutation hooks** (`lib/messaging.tsx`, after `useMarkRead`)

```ts
export function useSendMedia(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { file: File; kind: 'image' | 'video' | 'file'; caption?: string }) => {
            const form = new FormData();
            form.append('file', input.file);
            form.append('kind', input.kind);
            if (input.caption) form.append('caption', input.caption);
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/messages/media`, form,
            );
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function useMarkUnread() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.post(`/messaging/conversations/${conversationId}/unread`);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] }),
    });
}

export function useBlockConversation() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.post(`/messaging/conversations/${conversationId}/block`);
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
        },
    });
}

export function useUnblockConversation() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.delete(`/messaging/conversations/${conversationId}/block`);
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
        },
    });
}
```

- [ ] **Step 7: Extend channel type + sync hook** (`lib/messagingConfig.tsx`)

Replace the `MessagingChannel` interface and `useMessagingChannels`, and add `useSyncChannel`:

```ts
export interface ChannelSync {
    status: 'idle' | 'queued' | 'running' | 'done' | 'failed';
    total: number | null;
    done: number;
    message_count: number;
    started_at: string | null;
    finished_at: string | null;
    last_synced_at: string | null;
    error: string | null;
}

export interface MessagingChannel {
    id: number;
    provider: string;
    shop_name: string | null;
    name: string;
    external_shop_id: string;
    status: string;
    messaging_enabled: boolean;
    token_expired: boolean;
    connected_at: string | null;
    avatar_url: string | null;
    message_count: number;
    sync: ChannelSync;
}

export function useMessagingChannels() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'channels', tenantId],
        enabled: api != null,
        // Poll nhanh khi còn page đang đồng bộ; dừng khi tất cả idle/done/failed.
        refetchInterval: (q) =>
            q.state.data?.some((c) => c.sync.status === 'queued' || c.sync.status === 'running') ? 4_000 : false,
        queryFn: async () => (await api!.get<{ data: MessagingChannel[] }>('/messaging/channels')).data.data,
    });
}

export function useSyncChannel() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/messaging/channels/${id}/sync`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}
```

- [ ] **Step 8: Verify**

Run: `npm run typecheck` then `npm run lint` (from `app/`)
Expected: no type errors; lint clean.
Manual check: not yet (UI tasks follow).

- [ ] **Step 9: Commit**

```bash
git add app/package.json app/package-lock.json \
        app/app/Modules/Messaging/Http/Resources/ConversationResource.php \
        app/app/Modules/Messaging/Http/Controllers/ConversationController.php \
        app/resources/js/lib/messaging.tsx app/resources/js/lib/messagingConfig.tsx
git commit -m "feat(messaging-ui): data layer — emoji dep, types, hooks (media/unread/block/sync) + resource avatar/blocked"
```

---

## Task 2: Channels page — avatar, sync progress, message count, sync button

**Files:**
- Modify: `app/resources/js/pages/MessagingChannelsPage.tsx`

- [ ] **Step 1: Add imports**

Add to the existing imports:

```ts
import { App as AntApp, Avatar, Button, Card, Empty, Popconfirm, Progress, Result, Space, Spin, Tag, Tooltip, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled, KeyOutlined, SyncOutlined } from '@ant-design/icons';
import { useConnectFacebook, useDisconnectFacebookPage, useMessagingChannels, useSyncChannel } from '@/lib/messagingConfig';
import dayjs from 'dayjs';
```

- [ ] **Step 2: Wire the sync hook + handler**

Inside the component, after `const disconnect = useDisconnectFacebookPage();`:

```ts
    const syncChannel = useSyncChannel();
    const [syncingId, setSyncingId] = useState<number | null>(null);

    const handleSync = (id: number) => {
        setSyncingId(id);
        syncChannel.mutate(id, {
            onSuccess: () => { setSyncingId(null); message.success('Đã bắt đầu đồng bộ tin nhắn.'); },
            onError: (e) => { setSyncingId(null); message.error(errorMessage(e, 'Không bắt đầu được đồng bộ.')); },
        });
    };
```

- [ ] **Step 3: Render avatar + sync state + count + sync button**

Replace the per-page `<Card key={p.id} ...>` block (the `pages.map((p) => (...))` body) with:

```tsx
                    )) : pages.map((p) => {
                        const syncing = p.sync.status === 'queued' || p.sync.status === 'running';
                        return (
                        <Card key={p.id} size="small" styles={{ body: { padding: 12 } }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                                <Space size={12} align="start">
                                    <Avatar src={p.avatar_url ?? undefined} icon={<FacebookFilled />} size={40} style={{ background: p.avatar_url ? undefined : '#1877F2' }} />
                                    <Space direction="vertical" size={2}>
                                        <Space size={6}>
                                            <Text strong>{p.name}</Text>
                                            <Tag color={p.token_expired ? 'red' : 'green'}>{p.token_expired ? 'Hết hạn token' : 'Đang hoạt động'}</Tag>
                                            {p.sync.status === 'failed' && (
                                                <Tooltip title={p.sync.error ?? 'Đồng bộ lỗi'}><Tag color="red">Đồng bộ lỗi</Tag></Tooltip>
                                            )}
                                        </Space>
                                        <Text type="secondary" style={{ fontSize: 12 }}>Page ID: {p.external_shop_id}</Text>
                                        {syncing ? (
                                            <Space size={8} style={{ width: 240 }}>
                                                <Progress percent={100} status="active" showInfo={false} size="small" style={{ flex: 1, margin: 0 }} />
                                                <Text type="secondary" style={{ fontSize: 12 }}>Đang đồng bộ… {p.sync.done} hội thoại</Text>
                                            </Space>
                                        ) : p.sync.status === 'done' ? (
                                            <Text type="secondary" style={{ fontSize: 12 }}>
                                                Đã đồng bộ • {p.message_count} tin nhắn
                                                {p.sync.last_synced_at ? ` • ${dayjs(p.sync.last_synced_at).format('DD/MM HH:mm')}` : ''}
                                            </Text>
                                        ) : (
                                            <Text type="secondary" style={{ fontSize: 12 }}>{p.message_count} tin nhắn</Text>
                                        )}
                                    </Space>
                                </Space>
                                {canConnect && (
                                    <Space>
                                        <Button size="small" icon={<SyncOutlined spin={syncing} />} loading={syncingId === p.id} disabled={syncing} onClick={() => handleSync(p.id)}>
                                            Đồng bộ lại
                                        </Button>
                                        {p.token_expired && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={reconnectingId === p.id} onClick={() => handleReconnect(p.id)}>Kết nối lại</Button>
                                        )}
                                        <Popconfirm
                                            title="Ngắt kết nối Page?"
                                            description="Sẽ gỡ Page và xoá toàn bộ hội thoại liên quan, không khôi phục được."
                                            okText="Ngắt kết nối" okButtonProps={{ danger: true, loading: disconnectingId === p.id }} cancelText="Huỷ"
                                            onConfirm={() => {
                                                setDisconnectingId(p.id);
                                                disconnect.mutate(p.id, {
                                                    onSuccess: () => { setDisconnectingId(null); message.success('Đã ngắt kết nối Page.'); },
                                                    onError: (e) => { setDisconnectingId(null); message.error(errorMessage(e)); },
                                                });
                                            }}
                                        >
                                            <Button size="small" danger icon={<DisconnectOutlined />} loading={disconnectingId === p.id}>Ngắt kết nối</Button>
                                        </Popconfirm>
                                    </Space>
                                )}
                            </div>
                        </Card>
                        );
                    })}
```

- [ ] **Step 4: Verify**

Run: `npm run typecheck` then `npm run lint`
Expected: clean.
Manual check: open `/messaging/channels` with a connected page; avatar shows; clicking "Đồng bộ lại" flips the row to "Đang đồng bộ…" and the list polls every 4s until "Đã đồng bộ • N tin nhắn".

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/pages/MessagingChannelsPage.tsx
git commit -m "feat(messaging-ui): channels — avatar page + tiến trình đồng bộ + số tin nhắn + nút đồng bộ lại"
```

---

## Task 3: Composer — send image/video/document + render attachments

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx`

- [ ] **Step 1: Add imports**

```ts
import { App, Avatar, Badge, Button, Dropdown, Empty, Image, Input, List, Popconfirm, Segmented, Space, Spin, Tag, Typography, Upload } from 'antd';
import {
    FileOutlined, MoreOutlined, PaperClipOutlined, PictureOutlined, RobotOutlined,
    SendOutlined, ShopOutlined, SmileOutlined, VideoCameraOutlined,
} from '@ant-design/icons';
import {
    type Conversation, type ConversationStatus, INBOX_GROUP_PROVIDERS, type InboxGroup, type Message,
    providerLabel, useAiSuggestion, useBlockConversation, useConversations, useConversationThread,
    useMarkRead, useMarkUnread, useSendMedia, useSendText, useUnblockConversation,
} from '@/lib/messaging';
```

- [ ] **Step 2: Wire the media hook + handler**

After `const aiSuggest = useAiSuggestion(activeId);`:

```ts
    const sendMedia = useSendMedia(activeId);

    const handleMedia = (file: File, kind: 'image' | 'video' | 'file') => {
        if (!activeId) return false;
        sendMedia.mutate({ file, kind }, {
            onError: (e) => message.error(errorMessage(e, 'Không gửi được tệp.')),
        });
        return false; // chặn antd Upload tự upload
    };
```

- [ ] **Step 3: Render attachments in the thread**

Replace the message body line inside the thread `.map((m) => ...)` — i.e. replace:

```tsx
                                            {m.sent_by_ai && <Tag color="purple" style={{ marginBottom: 4 }}>AI</Tag>}
                                            <div style={{ whiteSpace: 'pre-wrap' }}>{m.body ?? `[${m.kind}]`}</div>
```

with:

```tsx
                                            {m.sent_by_ai && <Tag color="purple" style={{ marginBottom: 4 }}>AI</Tag>}
                                            {(m.attachments ?? []).map((a) => (
                                                <div key={a.id} style={{ marginBottom: m.body ? 6 : 0 }}>
                                                    {a.kind === 'image' && a.download_url ? (
                                                        <Image src={a.download_url} alt={a.filename ?? ''} style={{ maxWidth: 220, borderRadius: 8 }} />
                                                    ) : a.kind === 'video' && a.download_url ? (
                                                        <video src={a.download_url} controls style={{ maxWidth: 240, borderRadius: 8 }} />
                                                    ) : (
                                                        <a href={a.download_url ?? '#'} target="_blank" rel="noreferrer" style={{ color: 'inherit' }}>
                                                            <Space size={4}><FileOutlined /> {a.filename ?? 'Tệp đính kèm'}</Space>
                                                        </a>
                                                    )}
                                                </div>
                                            ))}
                                            {m.body != null && <div style={{ whiteSpace: 'pre-wrap' }}>{m.body}</div>}
                                            {m.body == null && (m.attachments ?? []).length === 0 && <div>{`[${m.kind}]`}</div>}
```

- [ ] **Step 4: Add upload buttons to the composer toolbar**

Replace the composer `<Space style={{ marginTop: 8, ... }}>` block (the AI/Send buttons row) with:

```tsx
                            <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                                <Space size={4}>
                                    <Upload showUploadList={false} accept="image/*" beforeUpload={(f) => handleMedia(f as File, 'image')}>
                                        <Button icon={<PictureOutlined />} title="Gửi ảnh" />
                                    </Upload>
                                    <Upload showUploadList={false} accept="video/*" beforeUpload={(f) => handleMedia(f as File, 'video')}>
                                        <Button icon={<VideoCameraOutlined />} title="Gửi video" />
                                    </Upload>
                                    <Upload showUploadList={false} beforeUpload={(f) => handleMedia(f as File, 'file')}>
                                        <Button icon={<PaperClipOutlined />} title="Gửi tài liệu" />
                                    </Upload>
                                    <Button icon={<RobotOutlined />} loading={aiSuggest.isPending} onClick={handleAi}>AI gợi ý</Button>
                                </Space>
                                <Button type="primary" icon={<SendOutlined />} loading={sendText.isPending} onClick={handleSend} disabled={!draft.trim()}>Gửi</Button>
                            </Space>
```

- [ ] **Step 5: Verify**

Run: `npm run typecheck` then `npm run lint`
Expected: clean.
Manual check: open a conversation; the three attach buttons appear; selecting an image sends it (appears as a thumbnail in the thread within ~10s poll); a received image/file renders.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): composer gửi ảnh/video/tài liệu + render đính kèm trong luồng tin"
```

---

## Task 4: Composer — emoji picker

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx`

- [ ] **Step 1: Add imports + Popover**

Add to the antd import: `Popover`. Add emoji-mart imports at the top of the file:

```ts
import Picker from '@emoji-mart/react';
import emojiData from '@emoji-mart/data';
```

- [ ] **Step 2: Add emoji insert handler**

After `handleMedia`:

```ts
    const [emojiOpen, setEmojiOpen] = useState(false);

    const handleEmoji = (emoji: { native?: string }) => {
        if (emoji.native) setDraft((d) => d + emoji.native);
    };
```

> Emoji here is **message content** (sent to the buyer), not a UI icon — so a literal emoji character is correct. The picker *trigger* uses `<SmileOutlined/>` (font icon), per the repo icon convention.

- [ ] **Step 3: Add the emoji button to the composer toolbar**

In the left `<Space size={4}>` from Task 3, add before the AI button:

```tsx
                                    <Popover
                                        open={emojiOpen}
                                        onOpenChange={setEmojiOpen}
                                        trigger="click"
                                        content={<Picker data={emojiData} onEmojiSelect={(e: { native?: string }) => handleEmoji(e)} previewPosition="none" locale="vi" />}
                                    >
                                        <Button icon={<SmileOutlined />} title="Chèn emoji" />
                                    </Popover>
```

- [ ] **Step 4: Verify**

Run: `npm run typecheck` then `npm run lint`
Expected: clean. If `@emoji-mart/data` lacks types, add `// @ts-expect-error - emoji-mart data has no types` above the `import emojiData` line, or declare a module in `app/resources/js/vite-env.d.ts`:
```ts
declare module '@emoji-mart/data';
declare module '@emoji-mart/react';
```
Manual check: clicking the smiley opens the picker; selecting an emoji appends it to the draft text.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx app/resources/js/vite-env.d.ts
git commit -m "feat(messaging-ui): bộ chọn emoji ở composer (emoji-mart, chèn vào nội dung tin)"
```

---

## Task 5: Inbox — buyer avatar (list + thread header)

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx`

- [ ] **Step 1: Avatar in the conversation list item**

In the list `renderItem`, give `List.Item.Meta` an `avatar` prop. Replace the opening `<List.Item.Meta` tag with:

```tsx
                                    <List.Item.Meta
                                        avatar={<Avatar src={c.buyer_avatar_url ?? undefined}>{(c.buyer_name ?? c.buyer_external_id ?? '?').slice(0, 1).toUpperCase()}</Avatar>}
                                        title={(
```

- [ ] **Step 2: Avatar in the thread header**

Replace the thread header inner content (the `<Text strong>{active?.buyer_name ...}</Text>` block) with:

```tsx
                        <div style={{ padding: 12, borderBottom: '1px solid #F1F5F9', display: 'flex', alignItems: 'center', gap: 8 }}>
                            <Avatar src={active?.buyer_avatar_url ?? undefined} size={32}>{(active?.buyer_name ?? active?.buyer_external_id ?? '?').slice(0, 1).toUpperCase()}</Avatar>
                            <div style={{ flex: 1 }}>
                                <Text strong>{active?.buyer_name ?? active?.buyer_external_id}</Text>{' '}
                                <Tag color="blue">{providerLabel(active?.provider ?? '')}</Tag>
                                {active?.channel_account_name && (
                                    <Text type="secondary" style={{ marginInlineStart: 4 }}>· {active.channel_account_name}</Text>
                                )}
                            </div>
                        </div>
```

(Remove the old single-line header `div` it replaces.)

- [ ] **Step 3: Verify**

Run: `npm run typecheck` then `npm run lint`
Expected: clean.
Manual check: conversations show buyer avatars (or initials fallback) in the list and header.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): hiển thị avatar buyer ở danh sách hội thoại + header luồng tin"
```

---

## Task 6: Inbox — mark-unread, unread filter, manual-unread dot

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx`

- [ ] **Step 1: Add an "unread only" filter state + wire it**

After `const [status, setStatus] = useState<...>('open');` add:

```ts
    const [unreadOnly, setUnreadOnly] = useState(false);
    const [view, setView] = useState<'inbox' | 'blocked'>('inbox');
```

Update the `useConversations` call:

```ts
    const list = useConversations({
        status: status === 'all' ? undefined : status,
        provider: INBOX_GROUP_PROVIDERS[group],
        unread: unreadOnly || undefined,
        blocked: view === 'blocked' || undefined,
    });
```

- [ ] **Step 2: Add the filter control**

After the status `<Segmented>` in the left column header, add a third row:

```tsx
                    <Segmented
                        block
                        value={unreadOnly ? 'unread' : 'all_msgs'}
                        onChange={(v) => setUnreadOnly(v === 'unread')}
                        options={[
                            { label: 'Tất cả tin', value: 'all_msgs' },
                            { label: 'Chưa đọc', value: 'unread' },
                        ]}
                    />
```

- [ ] **Step 3: Wire the mark-unread hook + dropdown per conversation**

After `const markRead = useMarkRead();`:

```ts
    const markUnread = useMarkUnread();
```

In the list `renderItem`, wrap the title `<Space>` to include a per-item actions dropdown. Replace the `title={(...)}` content's `<Space size={6}>...</Space>` with one that shows a manual-unread dot and a `MoreOutlined` menu:

```tsx
                                        title={(
                                            <Space size={6} style={{ width: '100%', justifyContent: 'space-between' }}>
                                                <Space size={6}>
                                                    <Badge dot={c.manually_unread} offset={[0, 2]}>
                                                        <Badge count={c.unread_count} size="small" />
                                                    </Badge>
                                                    <Text strong ellipsis style={{ maxWidth: 120 }}>{c.buyer_name ?? c.buyer_external_id}</Text>
                                                    <Tag color="blue" style={{ marginInlineEnd: 0 }}>{providerLabel(c.provider)}</Tag>
                                                </Space>
                                                <Dropdown
                                                    trigger={['click']}
                                                    menu={{ items: convMenuItems(c), onClick: ({ key, domEvent }) => { domEvent.stopPropagation(); onConvAction(key, c); } }}
                                                >
                                                    <Button type="text" size="small" icon={<MoreOutlined />} onClick={(e) => e.stopPropagation()} />
                                                </Dropdown>
                                            </Space>
                                        )}
```

- [ ] **Step 4: Add the menu builder + action handler**

Inside the component (before `return`), add the mark-unread part of the handler (block/unblock added in Task 7 — define the full versions now to avoid rework):

```ts
    const block = useBlockConversation();
    const unblock = useUnblockConversation();

    const convMenuItems = (c: Conversation) => [
        { key: 'unread', label: 'Đánh dấu chưa đọc' },
        c.blocked_at
            ? { key: 'unblock', label: 'Bỏ chặn người dùng' }
            : { key: 'block', label: 'Chặn người dùng', danger: true },
    ];

    const onConvAction = (key: string, c: Conversation) => {
        if (key === 'unread') {
            markUnread.mutate(c.id, { onSuccess: () => message.success('Đã đánh dấu chưa đọc.') });
        } else if (key === 'block') {
            block.mutate(c.id, {
                onSuccess: () => { message.success('Đã chặn người dùng.'); if (activeId === c.id) setActiveId(null); },
                onError: (e) => message.error(errorMessage(e)),
            });
        } else if (key === 'unblock') {
            unblock.mutate(c.id, { onSuccess: () => message.success('Đã bỏ chặn.') });
        }
    };
```

> The auto-mark-read effect (`useEffect ... markRead.mutate(activeId)`) already clears `manually_unread` server-side (backend `markRead` sets it false), so opening a manually-unread conversation clears the dot. Extend the effect condition so opening clears a manual-unread even when `unread_count === 0`:

```ts
    useEffect(() => {
        if (activeId && active && (active.unread_count > 0 || active.manually_unread)) {
            markRead.mutate(activeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeId]);
```

- [ ] **Step 5: Verify**

Run: `npm run typecheck` then `npm run lint`
Expected: clean.
Manual check: "Chưa đọc" filter narrows the list; the `⋯` menu → "Đánh dấu chưa đọc" shows a dot; opening that conversation clears it.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): đánh dấu chưa đọc + lọc chưa đọc + menu hành động hội thoại"
```

---

## Task 7: Inbox — block view + composer lock when blocked

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx`

(The block/unblock hooks + menu actions were added in Task 6. This task adds the "Đã chặn" view toggle and the composer lock.)

- [ ] **Step 1: Add the inbox/blocked view toggle**

At the top of the left column header (above the group `<Segmented>`), add:

```tsx
                    <Segmented
                        block
                        value={view}
                        onChange={(v) => { setView(v as 'inbox' | 'blocked'); setActiveId(null); }}
                        options={[
                            { label: 'Hộp thư', value: 'inbox' },
                            { label: 'Đã chặn', value: 'blocked' },
                        ]}
                    />
```

- [ ] **Step 2: Lock the composer when the active conversation is blocked**

Replace the composer container (the `<div style={{ padding: 12, borderTop: '1px solid #F1F5F9' }}>` that wraps the `Input.TextArea` + buttons) with a conditional:

```tsx
                        {active?.blocked_at ? (
                            <div style={{ padding: 16, borderTop: '1px solid #F1F5F9', textAlign: 'center' }}>
                                <Space direction="vertical" size={8}>
                                    <Text type="secondary">Đã chặn người dùng này — không thể gửi tin.</Text>
                                    <Button onClick={() => activeId && onConvAction('unblock', active)}>Bỏ chặn để nhắn lại</Button>
                                </Space>
                            </div>
                        ) : (
                        <div style={{ padding: 12, borderTop: '1px solid #F1F5F9' }}>
                            <Input.TextArea
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                placeholder="Nhập tin nhắn… (Enter để gửi, Shift+Enter xuống dòng)"
                                autoSize={{ minRows: 1, maxRows: 4 }}
                                onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); handleSend(); } }}
                            />
                            {/* ... toolbar Space from Task 3/4 stays here unchanged ... */}
                        </div>
                        )}
```

> Keep the existing toolbar `<Space>` (attach/emoji/AI/send buttons) inside the `else` branch's `<div>` exactly as built in Tasks 3–4 — only the wrapping conditional is new.

- [ ] **Step 3: Verify**

Run: `npm run typecheck` then `npm run lint`
Expected: clean.
Manual check: "Đã chặn" tab lists only blocked conversations; opening a blocked one shows the locked composer with a "Bỏ chặn" button that restores it; blocking a conversation from the menu removes it from the inbox view and clears the open thread.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): tab 'Đã chặn' + khoá composer khi hội thoại bị chặn"
```

---

## Final verification

- [ ] **Typecheck + lint (whole FE)**

Run (from `app/`): `npm run typecheck` and `npm run lint`
Expected: both clean.

- [ ] **Build**

Run: `npm run build`
Expected: Vite build succeeds (catches import/runtime-shape issues the typecheck might miss, e.g. emoji-mart default export).

- [ ] **Manual end-to-end smoke** (dev server `npm run dev` + Laravel running)

1. `/messaging/channels`: page avatar renders; "Đồng bộ lại" → progress → "Đã đồng bộ • N tin nhắn".
2. Inbox: buyer avatars in list/header; open a conversation; send text, an image, a document, and an emoji; received attachments render.
3. `⋯` menu: "Đánh dấu chưa đọc" → dot; "Chưa đọc" filter; "Chặn người dùng" → moves to "Đã chặn" tab + composer locks; "Bỏ chặn" restores.

---

## Self-review notes (spec coverage)

- Avatar page (Task 2) + avatar buyer (Task 5) — served via relayed `buyer_avatar_url`/`avatar_url`. ✓
- Sync progress + message count on connect UI (Task 2) with 4s polling while syncing. ✓
- Send image/video/document (Task 3) + emoji (Task 4) + attachment rendering (Task 3). ✓
- Mark-unread + unread filter (Task 6); block/unblock + locked composer + "Đã chặn" view (Tasks 6–7). ✓
- Icon convention respected: all UI affordances use `@ant-design/icons`; emoji characters are message *content*. ✓
- Backend serialization gaps closed in Task 1 (`ConversationResource` buyer avatar/blocked/unread; `show` eager-loads attachments).
- Deviation from spec: spec listed Vitest FE tests; repo has no FE test runner, so verification is `typecheck` + `lint` + `build` + manual. Standing up Vitest is a separate optional task.
```