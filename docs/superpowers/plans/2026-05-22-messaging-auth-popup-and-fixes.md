# Cấp quyền popup + sửa lỗi Hộp thư Facebook — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cấp quyền OAuth bằng popup tự đóng (mọi kênh) + hiển thị thời gian tin, icon to hơn, chip sđt trong nội dung tin, sửa tin rỗng (field `shares`), và cho người dùng chọn thẻ khi gửi tin ngoài cửa sổ 24h.

**Architecture:** Backend Laravel: callback OAuth trả 1 blade view chung `oauth-callback` (popup → `postMessage` về cha rồi `window.close()`; không popup → `location.replace` fallback). Frontend React/AntD: helper `openOAuthPopup` mở popup + nhận kết quả; tái dùng logic xử lý query-param sẵn có. Connector Facebook bổ sung field `shares`; ingestion dùng `sent_at` cho mốc thời gian hội thoại; composer thêm bộ chọn thẻ khi quá 24h.

**Tech Stack:** PHP 8 / Laravel, PHPUnit, Pint, PHPStan; React 18 + TypeScript + Ant Design v5 + dayjs + TanStack Query; Vite.

**Spec:** `docs/superpowers/specs/2026-05-22-messaging-auth-popup-and-fixes-design.md`

**Quy ước dự án (bắt buộc tuân theo):**
- Icon UI chỉ dùng `@ant-design/icons`, KHÔNG dùng ký tự emoji.
- Hạn chế `<Select>`; ưu tiên `Radio.Group`/`Segmented` cho tập lựa chọn nhỏ.
- Mọi lệnh chạy ở thư mục `app/` (Laravel root). FE: `npm run …`; BE: `vendor/bin/…` / `php artisan`.

**Lưu ý kiểm thử:** Repo **không** có test runner JS → task FE verify bằng `npm run typecheck && npm run lint && npm run build` + kiểm thủ công. Task BE dùng `php artisan test` (PHPUnit) theo TDD.

---

## Task 1: Blade view `oauth-callback` + marketplace callback trả view

**Files:**
- Create: `app/resources/views/oauth-callback.blade.php`
- Modify: `app/app/Modules/Channels/Http/Controllers/OAuthCallbackController.php`
- Test: `app/tests/Feature/Channels/ChannelConnectFlowTest.php:77,93`

- [ ] **Step 1: Tạo blade view**

Create `app/resources/views/oauth-callback.blade.php`:

```blade
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đang hoàn tất kết nối…</title>
</head>
<body style="font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#475569;background:#F8FAFC">
    <p>Đang hoàn tất kết nối…</p>
    <script>
        (function () {
            var redirect = @json($redirect);
            try {
                if (window.opener && !window.opener.closed) {
                    window.opener.postMessage({ source: 'cmb-oauth', redirect: redirect }, window.location.origin);
                    window.close();
                    return;
                }
            } catch (e) { /* opener cross-origin / đã đóng — rơi xuống fallback */ }
            window.location.replace(redirect);
        })();
    </script>
</body>
</html>
```

- [ ] **Step 2: Cập nhật test cho redirect → view (TDD: sửa kỳ vọng trước)**

Trong `app/tests/Feature/Channels/ChannelConnectFlowTest.php`, đổi 2 assertion:

Dòng ~76-77 (success):
```php
        $this->get('/oauth/tiktok/callback?app_key='.F::APP_KEY."&code=auth_code_abc&state={$state->state}")
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/channels?connected=tiktok');
```

Dòng ~93 (stale state):
```php
        $this->get("/oauth/tiktok/callback?code=abc&state={$state->state}")
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/channels?error=oauth_state');
```

- [ ] **Step 3: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit --filter test_oauth_callback tests/Feature/Channels/ChannelConnectFlowTest.php`
Expected: FAIL (controller vẫn trả redirect, chưa trả view `oauth-callback`).

- [ ] **Step 4: Đổi controller trả view**

Trong `OAuthCallbackController.php`:

(a) Đổi kiểu trả về của `__invoke` từ `: RedirectResponse` sang `: \Illuminate\Http\Response`.

(b) Thay TẤT CẢ `return redirect(<X>);` bằng `return $this->finish(<X>);`. Cụ thể 4 chỗ:
- dòng ~54: `return $this->finish('/channels?'.http_build_query($params));`
- dòng ~60: `return $this->finish('/channels?error=oauth_missing_params');`
- dòng ~99: `return $this->finish('/channels?'.http_build_query($params));`
- dòng ~104: `return $this->finish($result['redirect']);`

(c) Thêm helper private cuối class (trước dấu `}` đóng class):
```php
    /** Trả view popup-friendly: popup → postMessage + close; không popup → redirect SPA. */
    private function finish(string $redirect): \Illuminate\Http\Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }
```

(d) Xoá `use Illuminate\Http\RedirectResponse;` nếu không còn dùng (PHPStan/Pint sẽ báo nếu thừa).

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Channels/ChannelConnectFlowTest.php`
Expected: PASS toàn bộ.

- [ ] **Step 6: Lint backend**

Run: `cd app && vendor/bin/pint app/Modules/Channels/Http/Controllers/OAuthCallbackController.php && vendor/bin/phpstan analyse --no-progress`
Expected: không lỗi mới.

- [ ] **Step 7: Commit**

```bash
git add app/resources/views/oauth-callback.blade.php app/app/Modules/Channels/Http/Controllers/OAuthCallbackController.php app/tests/Feature/Channels/ChannelConnectFlowTest.php
git commit -m "feat(oauth): callback marketplace trả view popup-friendly (postMessage + close)"
```

---

## Task 2: Facebook callback trả view popup-friendly

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php:57-125`
- Test: `app/tests/Feature/Messaging/MessagingFacebookOAuthTest.php:46,62`

- [ ] **Step 1: Cập nhật test (TDD: sửa kỳ vọng trước)**

Trong `MessagingFacebookOAuthTest.php`:

Dòng ~46-47 (success):
```php
        $this->get('/oauth/facebook_page/callback?code=CODE&state=st_valid_123')
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?connected=facebook_page');
```

Dòng ~62-63 (invalid state):
```php
        $this->get('/oauth/facebook_page/callback?code=CODE&state=bogus')
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?error=facebook_oauth_state');
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/MessagingFacebookOAuthTest.php`
Expected: FAIL (vẫn trả redirect).

- [ ] **Step 3: Đổi controller trả view**

Trong `FacebookOAuthController.php`, method `callback`:

(a) Đổi kiểu trả về `callback(Request $request): RedirectResponse` → `callback(Request $request): \Illuminate\Http\Response`.

(b) Thay tất cả `return redirect(<X>);` trong `callback` bằng `return $this->finish(<X>);`. Các chỗ:
- dòng ~64: `return $this->finish('/messaging/channels?error=facebook_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));`
- dòng ~69: `return $this->finish('/messaging/channels?error=facebook_oauth_state');`
- dòng ~78: `return $this->finish('/messaging/channels?error=facebook_no_pages');`
- dòng ~121: `return $this->finish('/messaging/channels?error=facebook_oauth_failed');`
- dòng ~124: `return $this->finish($state->redirect_after ?: '/messaging/channels?connected=facebook_page');`

(c) Thêm helper private cuối class:
```php
    /** Trả view popup-friendly: popup → postMessage + close; không popup → redirect SPA. */
    private function finish(string $redirect): \Illuminate\Http\Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }
```

(d) `start()` vẫn trả `JsonResponse` — KHÔNG đổi. Giữ `use Illuminate\Http\JsonResponse;`. Xoá `use Illuminate\Http\RedirectResponse;` nếu PHPStan báo thừa.

- [ ] **Step 4: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/MessagingFacebookOAuthTest.php`
Expected: PASS.

- [ ] **Step 5: Lint backend**

Run: `cd app && vendor/bin/pint app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php && vendor/bin/phpstan analyse --no-progress`
Expected: không lỗi mới.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php app/tests/Feature/Messaging/MessagingFacebookOAuthTest.php
git commit -m "feat(oauth): callback Facebook trả view popup-friendly"
```

---

## Task 3: Frontend helper `openOAuthPopup`

**Files:**
- Create: `app/resources/js/lib/oauthPopup.ts`

- [ ] **Step 1: Tạo helper**

Create `app/resources/js/lib/oauthPopup.ts`:

```ts
/**
 * Mở cửa sổ popup cho luồng OAuth. Callback (blade `oauth-callback`) sẽ
 * postMessage `{source:'cmb-oauth', redirect}` về cửa sổ cha rồi tự đóng.
 *
 * - Popup bị trình duyệt chặn → fallback redirect toàn trang (luồng cũ); promise
 *   không resolve vì trang sẽ điều hướng đi.
 * - Người dùng tự đóng popup trước khi xong → resolve `{status:'cancelled'}`.
 */
export interface OAuthPopupOutcome {
    status: 'done' | 'cancelled';
    /** Đường dẫn SPA kèm query (vd `/channels?connected=tiktok`). */
    redirect?: string;
}

export function openOAuthPopup(authUrl: string): Promise<OAuthPopupOutcome> {
    const width = 600;
    const height = 720;
    const left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
    const top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);

    const popup = window.open(
        authUrl,
        'cmb_oauth',
        `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=yes,status=no`,
    );

    if (!popup) {
        // Popup bị chặn → giữ hành vi cũ: redirect toàn trang.
        window.location.href = authUrl;
        return new Promise<OAuthPopupOutcome>(() => {}); // trang sẽ điều hướng đi
    }

    return new Promise<OAuthPopupOutcome>((resolve) => {
        let settled = false;

        const cleanup = () => {
            window.removeEventListener('message', onMessage);
            window.clearInterval(timer);
        };

        const finish = (outcome: OAuthPopupOutcome) => {
            if (settled) return;
            settled = true;
            cleanup();
            try { if (!popup.closed) popup.close(); } catch { /* noop */ }
            resolve(outcome);
        };

        const onMessage = (e: MessageEvent) => {
            if (e.origin !== window.location.origin) return;
            const data = e.data as { source?: string; redirect?: string } | null;
            if (!data || data.source !== 'cmb-oauth') return;
            finish({ status: 'done', redirect: data.redirect });
        };

        const timer = window.setInterval(() => {
            if (popup.closed) finish({ status: 'cancelled' });
        }, 500);

        window.addEventListener('message', onMessage);
    });
}
```

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: PASS (không lỗi type).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/oauthPopup.ts
git commit -m "feat(oauth-ui): helper openOAuthPopup (popup + nhận postMessage)"
```

---

## Task 4: Dùng popup cho kết nối marketplace (ChannelsPage)

**Files:**
- Modify: `app/resources/js/lib/channels.tsx:64-73`
- Modify: `app/resources/js/pages/ChannelsPage.tsx` (import, `connect` mutate, useEffect refactor)

- [ ] **Step 1: Bỏ redirect trong hook `useConnectChannel`**

Trong `app/resources/js/lib/channels.tsx`, sửa hook để KHÔNG tự redirect (trả URL cho caller mở popup). Đổi block `onSuccess`:

```ts
export function useConnectChannel() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (provider: string) => {
            const { data } = await api!.post<{ data: { auth_url: string; provider: string } }>(`/channel-accounts/${provider}/connect`);
            return data.data;
        },
        // mở popup do caller xử lý (ChannelsPage) — không redirect ở đây nữa.
    });
}
```

(Xoá dòng `onSuccess: ({ auth_url }) => { window.location.href = auth_url; },`.)

- [ ] **Step 2: Tách logic xử lý query-param thành hàm tái dùng + mở popup trong ChannelsPage**

Trong `app/resources/js/pages/ChannelsPage.tsx`:

(a) Thêm import ở đầu file:
```ts
import { openOAuthPopup } from '@/lib/oauthPopup';
```

(b) Tách thân `useEffect` (dòng ~127-166) thành 1 hàm `applyConnectResult` dùng `useCallback`, và gọi lại từ `useEffect` mount. Thay nguyên khối `useEffect(() => { … }, [])` bằng:

```tsx
    const applyConnectResult = useCallback((p: URLSearchParams) => {
        const connected = p.get('connected');
        const err = p.get('error');
        const ttCode = p.get('tt_code');
        const lzCode = p.get('lz_code');
        const lzMsg = p.get('lz_msg');
        const errDesc = p.get('error_description');
        if (connected) {
            message.success(`Đã kết nối gian hàng ${CHANNEL_META[connected]?.name ?? connected}! Đơn 90 ngày gần đây đang được tải về.`);
            params.delete('connected'); setParams(params, { replace: true });
            resyncPoll.start(120_000);
        } else if (err) {
            const base = (err === 'lazada_api_error' && lzCode && LAZADA_CODE_GUIDE[lzCode])
                || PROVIDER_ERROR_PREFIXES[err]
                || CALLBACK_ERRORS[err]
                || 'Có lỗi khi kết nối gian hàng.';
            const detail = [
                ttCode ? `TikTok code ${ttCode}` : null,
                lzCode && !LAZADA_CODE_GUIDE[lzCode] ? `Lazada code ${lzCode}` : null,
                lzMsg ? `chi tiết: ${lzMsg}` : null,
                errDesc ? `chi tiết: ${errDesc}` : null,
            ].filter(Boolean).join(' · ');
            if (err === 'lazada_api_error' && lzCode === 'AppWhiteIpLimit') {
                setIpModal({ lzCode, guide: LAZADA_CODE_GUIDE[lzCode], detail });
            } else if (err === 'lazada_api_error' && lzCode && LAZADA_CODE_GUIDE[lzCode]) {
                Modal.error({ title: `Lazada báo lỗi: ${lzCode}`, content: <div style={{ whiteSpace: 'pre-line' }}>{base}{detail ? `\n\n${detail}` : ''}</div>, width: 640 });
            } else {
                message.error({ content: detail ? `${base} (${detail})` : base, duration: 15 });
            }
            ['error', 'tt_code', 'lz_code', 'lz_msg', 'error_description'].forEach((k) => params.delete(k));
            setParams(params, { replace: true });
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        applyConnectResult(params);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
```

(Bảo đảm `useCallback` đã có trong import `react` ở đầu file; nếu chưa, thêm vào.)

(c) Đổi handler nút kết nối (dòng ~187) sang mở popup. Tìm `onClick={() => connect.mutate(p.code)}` và thay bằng `onClick={() => handleConnectChannel(p.code)}`. Thêm hàm `handleConnectChannel` gần các handler khác:

```tsx
    const handleConnectChannel = (provider: string) => {
        connect.mutate(provider, {
            onSuccess: async ({ auth_url }) => {
                const res = await openOAuthPopup(auth_url);
                if (res.status === 'done' && res.redirect) {
                    applyConnectResult(new URL(res.redirect, window.location.origin).searchParams);
                    refetch();
                }
            },
            onError: () => { /* connect.isError đã hiển thị Alert sẵn có */ },
        });
    };
```

(`refetch` đã có sẵn trong component — dùng cho danh sách gian hàng. Nếu tên khác, dùng đúng tên hàm refetch hiện có.)

- [ ] **Step 3: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/lib/channels.tsx app/resources/js/pages/ChannelsPage.tsx
git commit -m "feat(oauth-ui): kết nối marketplace bằng popup tự đóng"
```

---

## Task 5: Dùng popup cho kết nối Facebook (MessagingChannelsPage)

**Files:**
- Modify: `app/resources/js/pages/MessagingChannelsPage.tsx:42-66`

- [ ] **Step 1: Mở popup thay vì redirect**

Trong `app/resources/js/pages/MessagingChannelsPage.tsx`:

(a) Thêm import:
```ts
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useQueryClient } from '@tanstack/react-query';
```
Và trong component: `const qc = useQueryClient();`

(b) Tách thân `useEffect` (dòng ~42-53) thành hàm `applyFbResult`, gọi từ mount:

```tsx
    const applyFbResult = (p: URLSearchParams) => {
        const connected = p.get('connected');
        const err = p.get('error');
        if (connected === 'facebook_page') {
            message.success('Đã kết nối Facebook Page!');
            params.delete('connected'); setParams(params, { replace: true });
            qc.invalidateQueries({ queryKey: ['messaging', 'channels'] });
        } else if (err) {
            message.error({ content: FB_ERRORS[err] ?? 'Bạn đã huỷ hoặc Facebook từ chối cấp quyền.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
    };

    useEffect(() => {
        applyFbResult(params);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
```

> Lưu ý: kiểm tra queryKey thật của `useMessagingChannels` trong `lib/messagingConfig.tsx` và dùng đúng key đó cho `invalidateQueries` (ví dụ `['messaging','channels', tenantId]` → có thể dùng prefix `['messaging','channels']`).

(c) Đổi `handleConnect` (dòng ~55-58) và `handleReconnect` (dòng ~60-66) sang popup:

```tsx
    const handleConnect = () => connectFb.mutate(undefined, {
        onSuccess: async (d) => {
            const res = await openOAuthPopup(d.authorize_url);
            if (res.status === 'done' && res.redirect) {
                applyFbResult(new URL(res.redirect, window.location.origin).searchParams);
            }
        },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật facebook_page.')),
    });

    const handleReconnect = (id: number) => {
        setReconnectingId(id);
        connectFb.mutate(undefined, {
            onSuccess: async (d) => {
                const res = await openOAuthPopup(d.authorize_url);
                setReconnectingId(null);
                if (res.status === 'done' && res.redirect) {
                    applyFbResult(new URL(res.redirect, window.location.origin).searchParams);
                }
            },
            onError: (e) => { setReconnectingId(null); message.error(errorMessage(e, 'Không khởi tạo được kết nối.')); },
        });
    };
```

- [ ] **Step 2: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/pages/MessagingChannelsPage.tsx
git commit -m "feat(oauth-ui): kết nối/kết nối lại Facebook bằng popup tự đóng"
```

---

## Task 6: Helper định dạng thời gian + giờ từng tin nhắn (thread)

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx` (import dayjs; thêm helpers module-scope; render giờ trong bong bóng `:693-724`)

- [ ] **Step 1: Thêm import dayjs + helpers module-scope**

Trong `MessagingPage.tsx`:

(a) Thêm vào cụm import đầu file:
```ts
import dayjs from 'dayjs';
```

(b) Thêm 2 hàm module-scope (đặt cạnh `LinkifiedText`, ngoài component):
```tsx
/** Giờ hiển thị cho 1 tin trong thread: cùng ngày → HH:mm; khác ngày → DD/MM HH:mm. */
function fmtMsgTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso);
    return d.isSame(dayjs(), 'day') ? d.format('HH:mm') : d.format('DD/MM HH:mm');
}

/** Giờ gọn cho danh sách hội thoại: <60' → "x phút"; hôm nay → HH:mm; hôm qua → "Hôm qua"; còn lại → DD/MM. */
function fmtListTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso);
    const now = dayjs();
    const diffMin = now.diff(d, 'minute');
    if (diffMin < 1) return 'vừa xong';
    if (diffMin < 60) return `${diffMin} phút`;
    if (d.isSame(now, 'day')) return d.format('HH:mm');
    if (d.isSame(now.subtract(1, 'day'), 'day')) return 'Hôm qua';
    if (d.isSame(now, 'year')) return d.format('DD/MM');
    return d.format('DD/MM/YY');
}
```

- [ ] **Step 2: Render giờ trong bong bóng tin**

Trong khối render bong bóng (`:716-724`), thay block trạng thái outbound + thêm giờ. Tìm:
```tsx
                                                {m.direction === 'outbound' && (
                                                    <div style={{ fontSize: 10, opacity: 0.8, textAlign: 'right' }}>
                                                        {DELIVERY_STATUS_LABEL[m.delivery_status ?? ''] ?? m.delivery_status}
                                                    </div>
                                                )}
```
Thay bằng (giờ cho cả 2 chiều; outbound kèm trạng thái):
```tsx
                                                <div style={{ fontSize: 10, opacity: 0.6, textAlign: m.direction === 'outbound' ? 'right' : 'left', marginTop: 2 }}>
                                                    {fmtMsgTime(m.sent_at ?? m.created_at)}
                                                    {m.direction === 'outbound' && (
                                                        <> · {DELIVERY_STATUS_LABEL[m.delivery_status ?? ''] ?? m.delivery_status}</>
                                                    )}
                                                </div>
```

- [ ] **Step 3: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): hiển thị giờ từng tin nhắn trong thread"
```

---

## Task 7: Giờ tin cuối ở danh sách hội thoại

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx:514-521` (hàng tên trong list)

- [ ] **Step 1: Thêm nhãn giờ căn phải hàng tên**

Trong khối `title` của `List.Item.Meta` (hàng tên + menu, `:517-531`), thêm nhãn giờ trước/sau Dropdown. Tìm block:
```tsx
                                                        <Dropdown
                                                            trigger={['click']}
                                                            menu={{
                                                                items: convMenuItems(c),
                                                                onClick: ({ key, domEvent }) => { domEvent.stopPropagation(); onConvAction(key, c); },
                                                            }}
                                                        >
                                                            <Button type="text" size="small" icon={<MoreOutlined />} onClick={(e) => e.stopPropagation()} />
                                                        </Dropdown>
```
Thêm NGAY TRƯỚC `<Dropdown` một nhãn giờ:
```tsx
                                                        {c.last_message_at && (
                                                            <Text type="secondary" style={{ fontSize: 11, whiteSpace: 'nowrap', flexShrink: 0 }}>
                                                                {fmtListTime(c.last_message_at)}
                                                            </Text>
                                                        )}
```
(`fmtListTime` đã định nghĩa ở Task 6.)

- [ ] **Step 2: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): hiển thị giờ tin cuối ở danh sách hội thoại"
```

---

## Task 8: Icon comment & messenger to, dễ nhìn

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx:504-512` (Badge góc avatar)

- [ ] **Step 1: Tăng cỡ icon badge + chỉnh offset**

Tìm khối Badge (`:504-510`):
```tsx
                                                <Badge
                                                    count={c.thread_type === 'comment'
                                                        ? <CommentOutlined style={{ fontSize: 10, color: '#1677ff', background: '#fff', borderRadius: '50%', padding: 1 }} />
                                                        : <MessageOutlined style={{ fontSize: 10, color: '#52c41a', background: '#fff', borderRadius: '50%', padding: 1 }} />
                                                    }
                                                    offset={[-2, 28]}
                                                >
```
Thay bằng (icon 15px, padding 2, offset hơi lùi để cân với avatar 40):
```tsx
                                                <Badge
                                                    count={c.thread_type === 'comment'
                                                        ? <CommentOutlined style={{ fontSize: 15, color: '#1677ff', background: '#fff', borderRadius: '50%', padding: 2, boxShadow: '0 0 0 1px #fff' }} />
                                                        : <MessageOutlined style={{ fontSize: 15, color: '#52c41a', background: '#fff', borderRadius: '50%', padding: 2, boxShadow: '0 0 0 1px #fff' }} />
                                                    }
                                                    offset={[-4, 30]}
                                                >
```

- [ ] **Step 2: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 3: Kiểm thủ công**

Mở Hộp thư (board Facebook) → badge comment/messenger ở góc avatar to rõ, không tràn/khuất. (Cần app chạy; nếu không chạy được, xác nhận bằng review thị giác cỡ 15px hợp lý.)

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): icon comment/messenger to & rõ hơn ở danh sách"
```

---

## Task 9: Chip số điện thoại trong nội dung tin nhắn

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx` (thay `LinkifiedText` bằng `MessageBody`; cập nhật chỗ dùng `:716`)

- [ ] **Step 1: Thay `LinkifiedText` bằng `MessageBody` (URL + sđt)**

Trong `MessagingPage.tsx`, thay nguyên hàm `LinkifiedText` (`:36-58`) bằng cụm sau (dùng regex test có neo `^…$` để tránh lỗi lastIndex của global regex):

```tsx
const URL_SPLIT_RE = /(https?:\/\/[^\s]+)/g;
const URL_TEST_RE = /^https?:\/\/[^\s]+$/;
// SĐT VN: tiền tố +84 hoặc 0, cho phép . - và khoảng trắng giữa các nhóm số.
const PHONE_SPLIT_RE = /((?:\+84|0)\d[\d .-]{7,12}\d)/g;
const PHONE_TEST_RE = /^(?:\+84|0)\d[\d .-]{7,12}\d$/;

/** Chip sđt: bấm để copy (đã bỏ ký tự ngăn cách). */
function PhoneChip({ value }: { value: string }) {
    const { message } = App.useApp();
    const normalized = value.replace(/[ .-]/g, '');
    return (
        <Tag
            color="green"
            icon={<PhoneOutlined />}
            style={{ cursor: 'pointer', marginInline: 2 }}
            onClick={(e) => {
                e.stopPropagation();
                void navigator.clipboard?.writeText(normalized);
                message.success('Đã copy số điện thoại');
            }}
        >
            {value.trim()}
        </Tag>
    );
}

/** Render 1 đoạn text: tách sđt thành chip. */
function renderPhones(text: string, keyPrefix: string) {
    return text.split(PHONE_SPLIT_RE).map((part, i) =>
        PHONE_TEST_RE.test(part.trim())
            ? <PhoneChip key={`${keyPrefix}-p${i}`} value={part} />
            : <span key={`${keyPrefix}-t${i}`}>{part}</span>,
    );
}

/** Render nội dung tin: URL → link; sđt → chip màu (bấm copy); còn lại giữ nguyên. */
function MessageBody({ text }: { text: string }) {
    const parts = text.split(URL_SPLIT_RE);
    return (
        <>
            {parts.map((part, i) =>
                URL_TEST_RE.test(part) ? (
                    <a key={`u${i}`} href={part} target="_blank" rel="noreferrer" style={{ color: 'inherit', textDecoration: 'underline' }}>
                        {part}
                    </a>
                ) : (
                    <span key={`s${i}`}>{renderPhones(part, `s${i}`)}</span>
                ),
            )}
        </>
    );
}
```

- [ ] **Step 2: Cập nhật chỗ dùng trong bong bóng**

Tại `:716`, đổi:
```tsx
                                                {m.body != null && <div style={{ whiteSpace: 'pre-wrap' }}><LinkifiedText text={m.body} /></div>}
```
thành:
```tsx
                                                {m.body != null && <div style={{ whiteSpace: 'pre-wrap' }}><MessageBody text={m.body} /></div>}
```
(Tìm toàn file các chỗ còn dùng `<LinkifiedText` — thay hết bằng `<MessageBody`. Bảo đảm `App`, `Tag`, `PhoneOutlined` đã được import — đều đã có sẵn ở đầu file.)

- [ ] **Step 3: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS (không còn tham chiếu `LinkifiedText`).

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): tô màu chip & copy số điện thoại trong nội dung tin"
```

---

## Task 10: Backend — bổ sung field `shares` cho `fetchMessages` (sửa tin rỗng)

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php:469-544`
- Test: `app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php`

- [ ] **Step 1: Viết test FAIL cho `shares`**

Thêm vào `FacebookBackfillConnectorTest.php` (cuối class, trước `}` đóng):

```php
    public function test_fetch_messages_shares_edge_sets_body_when_text_empty(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 't_shares',
                'messages' => ['data' => [[
                    'id' => 'm_shares',
                    'message' => '',
                    'created_time' => '2026-05-20T13:00:00+0000',
                    'from' => ['id' => 'PSID_999', 'name' => 'A'],
                    'shares' => ['data' => [[
                        'name' => 'Bài viết hay',
                        'link' => 'https://www.facebook.com/post/xyz',
                    ]]],
                ]]],
            ], 200),
        ]);

        $page = $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_shares', 'pageSize' => 50]);

        $this->assertCount(1, $page->items);
        $msg = $page->items[0];
        $this->assertSame('text', $msg->kind->value);
        $this->assertSame('Bài viết hay https://www.facebook.com/post/xyz', $msg->body);
    }

    public function test_fetch_messages_graph_fields_include_shares(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 't_x', 'messages' => ['data' => []]], 200)]);

        $this->connector()->fetchMessages($this->auth(), 'PSID_999', ['thread_id' => 't_x', 'pageSize' => 20]);

        Http::assertSent(fn ($r) => str_contains(urldecode($r->url()), 'shares'));
    }
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit --filter shares tests/Unit/Messaging/FacebookBackfillConnectorTest.php`
Expected: FAIL (field `shares` chưa được request; body rỗng).

- [ ] **Step 3: Thêm `shares` vào fields + xử lý body**

Trong `FacebookPageConnector::fetchMessages` (`:477-480`), đổi chuỗi `fields`:
```php
        $res = Http::timeout(30)->get($this->graphUrl($threadId), [
            'fields' => "messages.limit({$limit}){id,message,created_time,from,sticker,shares{link,name,description},attachments{mime_type,name,image_data,video_data,file_url,type,title,url}}",
            'access_token' => $auth->accessToken,
        ]);
```

Sau khối xử lý `attachments` và TRƯỚC dòng `if ($body === null && $shareUrl !== null)` (`:522-526`), thêm trích `shares`:
```php
            // Shared post/link nằm ở edge `shares` (KHÁC `attachments`) — nguồn gây
            // tin rỗng khi `message` trống. Lấy name/description + link làm body.
            if ($shareUrl === null) {
                foreach ((array) ($row['shares']['data'] ?? []) as $share) {
                    $link = (string) ($share['link'] ?? '');
                    if ($link === '') {
                        continue;
                    }
                    $label = (string) ($share['name'] ?? $share['description'] ?? '');
                    $shareUrl = $label !== '' ? $label.' '.$link : $link;
                    break;
                }
            }
```

(Dòng `if ($body === null && $shareUrl !== null) { $body = $shareUrl; }` sẵn có sẽ gán body. `kind` vẫn = Text vì không tạo attachment.)

- [ ] **Step 4: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Unit/Messaging/FacebookBackfillConnectorTest.php`
Expected: PASS toàn bộ (kể cả test cũ).

- [ ] **Step 5: Lint backend**

Run: `cd app && vendor/bin/pint app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php && vendor/bin/phpstan analyse --no-progress`
Expected: không lỗi mới.

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php
git commit -m "fix(messaging): backfill lấy field `shares` của Facebook — hết tin rỗng do bài chia sẻ"
```

---

## Task 11: Frontend — placeholder rõ ràng cho tin không có text

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx:79-86` (`KIND_LABEL`) và `:717-719`

- [ ] **Step 1: Đổi nhãn `KIND_LABEL`**

Tìm (`:79-86`):
```tsx
    const KIND_LABEL: Record<string, string> = {
        text: 'Tin không có nội dung',
        image: 'Hình ảnh',
        video: 'Video',
        file: 'Tệp đính kèm',
        template: 'Mẫu tin',
        system: 'Tin hệ thống',
    };
```
Thay `text` thành nhãn rõ hơn (chỉ xuất hiện khi body=null VÀ không có attachment — tức tin không hỗ trợ hiển thị):
```tsx
    const KIND_LABEL: Record<string, string> = {
        text: 'Tin nhắn không hỗ trợ hiển thị',
        image: 'Hình ảnh',
        video: 'Video',
        file: 'Tệp đính kèm',
        sticker: 'Sticker',
        template: 'Mẫu tin',
        system: 'Tin hệ thống',
    };
```

- [ ] **Step 2: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "fix(messaging-ui): placeholder rõ ràng cho tin không có nội dung text"
```

---

## Task 12: Backend — thêm `HUMAN_AGENT` vào allowedTags

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php:716-723`
- Test: `app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php`

- [ ] **Step 1: Viết test FAIL**

Thêm vào `FacebookBackfillConnectorTest.php`:
```php
    public function test_outbound_window_allows_human_agent_tag(): void
    {
        $policy = $this->connector()->outboundWindow();
        $this->assertContains('HUMAN_AGENT', $policy->allowedTags);
        $this->assertSame(24, $policy->freeWindowHours);
        $this->assertTrue($policy->requiresTag);
    }
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit --filter human_agent tests/Unit/Messaging/FacebookBackfillConnectorTest.php`
Expected: FAIL (`HUMAN_AGENT` chưa có trong allowedTags).

- [ ] **Step 3: Thêm `HUMAN_AGENT`**

Trong `outboundWindow()` (`:716-723`):
```php
    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        return new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['HUMAN_AGENT', 'CONFIRMED_EVENT_UPDATE', 'POST_PURCHASE_UPDATE', 'ACCOUNT_UPDATE'],
        );
    }
```

- [ ] **Step 4: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Unit/Messaging/FacebookBackfillConnectorTest.php tests/Unit/Messaging/OutboundWindowGuardTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php app/tests/Unit/Messaging/FacebookBackfillConnectorTest.php
git commit -m "feat(messaging): cho phép thẻ HUMAN_AGENT (cửa sổ 7 ngày cho nhân viên)"
```

---

## Task 13: Backend — ingestion dùng `sent_at` cho mốc thời gian hội thoại

**Files:**
- Modify: `app/app/Modules/Messaging/Services/MessageIngestionService.php:182-216`
- Test: Create `app/tests/Feature/Messaging/MessagingIngestionTimestampTest.php`

- [ ] **Step 1: Viết test FAIL**

Create `app/tests/Feature/Messaging/MessagingIngestionTimestampTest.php`:
```php
<?php

namespace Tests\Feature\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `last_inbound_at`/`last_message_at` phải phản ánh giờ buyer NHẮN THẬT (sent_at),
 * không phải giờ ingest (created_at) — để OutboundWindowGuard tính đúng cửa sổ 24h.
 */
class MessagingIngestionTimestampTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_uses_sent_at_for_conversation_timestamps(): void
    {
        $tenant = Tenant::create(['name' => 'TS']);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_ts_1',
            'shop_name' => 'TS Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        $sentAt = CarbonImmutable::now()->subDays(3)->startOfMinute();

        $dto = new MessageDTO(
            externalConversationId: 'PSID_TS',
            externalMessageId: 'm_ts_1',
            buyerExternalId: 'PSID_TS',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'tin cũ 3 ngày',
            sentAt: $sentAt,
        );

        $res = app(MessageIngestionService::class)->ingest($account, $dto);
        $conv = $res['conversation'];

        $this->assertNotNull($conv->last_inbound_at);
        $this->assertSame($sentAt->toDateTimeString(), $conv->last_inbound_at->toDateTimeString());
        $this->assertSame($sentAt->toDateTimeString(), $conv->last_message_at->toDateTimeString());
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/MessagingIngestionTimestampTest.php`
Expected: FAIL (`last_inbound_at` đang = `created_at` ≈ now, không phải 3 ngày trước).

- [ ] **Step 3: Sửa `updateConversationOnNewMessage`**

Trong `MessageIngestionService.php:182-216`, thêm biến mốc thời gian ở đầu hàm và dùng nó thay cho `$message->created_at`:

Tìm đầu hàm:
```php
    private function updateConversationOnNewMessage(Conversation $conversation, Message $message): void
    {
        $preview = $message->body !== null
            ? Str::limit(preg_replace('/\s+/', ' ', $message->body), 197)
            : '['.$message->kind.']';

        $conversation->message_count++;
        $conversation->last_message_at = $message->created_at;
```
Thay bằng:
```php
    private function updateConversationOnNewMessage(Conversation $conversation, Message $message): void
    {
        $preview = $message->body !== null
            ? Str::limit(preg_replace('/\s+/', ' ', $message->body), 197)
            : '['.$message->kind.']';

        // Mốc thời gian hội thoại theo giờ tin NHẮN THẬT (sent_at từ sàn/FB), fallback
        // created_at (giờ ingest) khi thiếu — để window guard 24h tính đúng cho tin backfill.
        $occurredAt = $message->sent_at ?? $message->created_at;

        $conversation->message_count++;
        $conversation->last_message_at = $occurredAt;
```

Trong nhánh inbound, đổi `$conversation->last_inbound_at = $message->created_at;` → `$conversation->last_inbound_at = $occurredAt;`

Trong nhánh else (outbound), đổi `$conversation->last_outbound_at = $message->created_at;` → `$conversation->last_outbound_at = $occurredAt;`

- [ ] **Step 4: Chạy test để xác nhận PASS (+ không vỡ test ingestion cũ)**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging/MessagingIngestionTimestampTest.php tests/Feature/Messaging/MessagingWebhookIngestTest.php tests/Feature/Messaging/MessagingBackfillTest.php`
Expected: PASS.

- [ ] **Step 5: Lint backend**

Run: `cd app && vendor/bin/pint app/app/Modules/Messaging/Services/MessageIngestionService.php && vendor/bin/phpstan analyse --no-progress`
Expected: không lỗi mới.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Services/MessageIngestionService.php app/tests/Feature/Messaging/MessagingIngestionTimestampTest.php
git commit -m "fix(messaging): mốc thời gian hội thoại dùng sent_at — window guard tính đúng 24h"
```

---

## Task 14: Frontend — `useSendText` nhận `message_tag`

**Files:**
- Modify: `app/resources/js/lib/messaging.tsx:137-150` (`useSendText`)
- Modify: `app/resources/js/pages/MessagingPage.tsx:194-201` (`handleSend` truyền object)

- [ ] **Step 1: Đổi `useSendText` nhận object**

Trong `lib/messaging.tsx`, đổi `useSendText`:
```ts
export function useSendText(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { body: string; message_tag?: string }) => {
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/messages`,
                { body: input.body, message_tag: input.message_tag },
            );
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}
```

- [ ] **Step 2: Cập nhật `handleSend` truyền object (chưa kèm tag)**

Trong `MessagingPage.tsx:194-201`, đổi:
```tsx
    const handleSend = () => {
        const body = draft.trim();
        if (!body || !activeId) return;
        sendText.mutate({ body }, {
            onSuccess: () => setDraft(''),
            onError: (e) => message.error(errorMessage(e, 'Không gửi được tin.')),
        });
    };
```

- [ ] **Step 3: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/lib/messaging.tsx app/resources/js/pages/MessagingPage.tsx
git commit -m "refactor(messaging-ui): useSendText nhận message_tag (chuẩn bị bộ chọn thẻ)"
```

---

## Task 15: Frontend — bộ chọn thẻ khi gửi ngoài cửa sổ 24h

**Files:**
- Modify: `app/resources/js/pages/MessagingPage.tsx` (helper `isOutsideWindow`, state `msgTag`, picker trong composer, `handleSend` truyền tag)

- [ ] **Step 1: Thêm helper + hằng số thẻ (module-scope)**

Trong `MessagingPage.tsx`, cạnh các helper khác (ngoài component), thêm:
```tsx
/** Hội thoại Facebook đã quá cửa sổ 24h kể từ tin buyer gần nhất? */
function isOutsideWindow(lastInboundAt: string | null): boolean {
    if (!lastInboundAt) return true;
    return dayjs().diff(dayjs(lastInboundAt), 'hour') >= 24;
}

/** Thẻ tin nhắn Facebook ngoài 24h (mặc định HUMAN_AGENT cho trả lời của nhân viên). */
const FB_MESSAGE_TAGS: Array<{ label: string; value: string }> = [
    { label: 'Nhân viên (7 ngày)', value: 'HUMAN_AGENT' },
    { label: 'Xác nhận sự kiện', value: 'CONFIRMED_EVENT_UPDATE' },
    { label: 'Sau mua hàng', value: 'POST_PURCHASE_UPDATE' },
    { label: 'Cập nhật tài khoản', value: 'ACCOUNT_UPDATE' },
];
```

- [ ] **Step 2: Thêm state + cờ cần thẻ trong component**

Cạnh các `useState` khác (vd sau `:100` `const [draft, setDraft] = useState('')`), thêm:
```tsx
    const [msgTag, setMsgTag] = useState<string>('HUMAN_AGENT');
```

Sau khi có `active` (sau `:180`), thêm cờ:
```tsx
    const needsTag = active?.provider === 'facebook_page'
        && active?.thread_type === 'message'
        && !active?.blocked_at
        && isOutsideWindow(active?.last_inbound_at ?? null);
```

- [ ] **Step 3: `handleSend` kèm thẻ khi cần**

Đổi `handleSend` (đã chỉnh ở Task 14):
```tsx
    const handleSend = () => {
        const body = draft.trim();
        if (!body || !activeId) return;
        sendText.mutate({ body, message_tag: needsTag ? msgTag : undefined }, {
            onSuccess: () => setDraft(''),
            onError: (e) => message.error(errorMessage(e, 'Không gửi được tin.')),
        });
    };
```

- [ ] **Step 4: Hiện picker trong composer tin nhắn (KHÔNG phải composer comment)**

Trong nhánh composer tin nhắn, NGAY TRƯỚC `<Popover` bọc `<Input.TextArea>` có placeholder bắt đầu `"Nhập tin nhắn…"` (`:822`), chèn:
```tsx
                            {needsTag && (
                                <div style={{ marginBottom: 8 }}>
                                    <Text type="secondary" style={{ fontSize: 12, display: 'block', marginBottom: 4 }}>
                                        Quá 24h từ tin cuối của khách — chọn loại thẻ tin nhắn để gửi (Facebook yêu cầu):
                                    </Text>
                                    <Radio.Group
                                        size="small"
                                        optionType="button"
                                        buttonStyle="solid"
                                        options={FB_MESSAGE_TAGS}
                                        value={msgTag}
                                        onChange={(e) => setMsgTag(e.target.value)}
                                    />
                                </div>
                            )}
```
(`Radio` và `Text` đã được import sẵn ở đầu file.)

- [ ] **Step 5: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 6: Kiểm thủ công**

Mở 1 hội thoại Facebook có tin khách >24h → thấy bộ chọn thẻ (mặc định "Nhân viên (7 ngày)"); gửi → request kèm `message_tag`. Hội thoại trong 24h → KHÔNG hiện picker, gửi bình thường.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/pages/MessagingPage.tsx
git commit -m "feat(messaging-ui): bộ chọn thẻ khi gửi tin Facebook ngoài cửa sổ 24h"
```

---

## Task 16: Hồi quy tổng thể

- [ ] **Step 1: Chạy toàn bộ test BE liên quan messaging/channels**

Run: `cd app && vendor/bin/phpunit tests/Feature/Messaging tests/Unit/Messaging tests/Feature/Channels`
Expected: PASS toàn bộ.

- [ ] **Step 2: PHPStan + Pint toàn dự án (phần đã đụng)**

Run: `cd app && vendor/bin/pint --test && vendor/bin/phpstan analyse --no-progress`
Expected: không lỗi.

- [ ] **Step 3: FE typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 4: Báo cáo kết quả**

Tổng hợp: phần nào đã verify bằng test/tooling; phần nào chỉ kiểm tĩnh (luồng OAuth/gửi tin Facebook live cần credentials thật — không E2E được trong môi trường dev).

---

## Self-review (đã rà)

**Spec coverage:**
- #1 popup (mọi kênh) → Task 1–5 (view + 2 controller + helper + 2 trang). ✓
- #2 giờ tin nhắn → Task 6. ✓
- #3 giờ tin cuối ở list → Task 7. ✓
- #4 icon to → Task 8. ✓
- #5 chip sđt trong nội dung tin → Task 9. ✓
- #6 tin rỗng → Task 10 (backend `shares`) + Task 11 (placeholder FE). ✓
- #7 gửi thiếu thẻ → Task 12 (HUMAN_AGENT) + Task 13 (sent_at) + Task 14 (hook) + Task 15 (picker). ✓

**Type/contract nhất quán:** `openOAuthPopup` trả `OAuthPopupOutcome {status, redirect?}` (Task 3) — dùng đúng ở Task 4/5. View post `{source:'cmb-oauth', redirect}` (Task 1/2) khớp listener (Task 3). `useSendText` đổi sang object `{body, message_tag?}` (Task 14) — `handleSend` gọi đúng (Task 14/15). `fmtMsgTime`/`fmtListTime`/`isOutsideWindow`/`MessageBody`/`PhoneChip` định nghĩa trước khi dùng.

**Placeholder:** không còn TODO/TBD; mọi step code đều có code thật.
