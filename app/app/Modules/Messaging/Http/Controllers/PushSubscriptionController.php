<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Web Push subscription cho thông báo tin nhắn mới (tab đóng/ẩn).
 *
 * - GET    push/public-key  → VAPID public key (FE subscribe).
 * - POST   push/subscribe   → lưu/cập nhật subscription (endpoint unique).
 * - POST   push/heartbeat   → cập nhật last_seen_at (tab visible) ⇒ digest bỏ qua.
 * - DELETE push/subscribe   → xoá subscription.
 */
class PushSubscriptionController extends Controller
{
    public function publicKey(WebPushSender $sender): JsonResponse
    {
        return response()->json(['data' => ['public_key' => $sender->publicKey()]]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $sub = PushSubscription::query()->firstOrNew(['endpoint' => $data['endpoint']]);
        $isNew = ! $sub->exists;
        $sub->fill([
            'tenant_id' => app(CurrentTenant::class)->id(),
            'user_id' => $request->user()->id,
            'p256dh' => $data['keys']['p256dh'],
            'auth' => $data['keys']['auth'],
            'last_seen_at' => now(),
        ]);
        if ($isNew) {
            // Baseline: chỉ gom inbound MỚI sau khi đăng ký (tránh push dồn lịch sử).
            $sub->last_notified_at = now();
        }
        $sub->save();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $endpoint = (string) $request->input('endpoint', '');
        if ($endpoint !== '') {
            PushSubscription::query()
                ->where('endpoint', $endpoint)
                ->where('user_id', $request->user()->id)
                ->update(['last_seen_at' => now()]);
        }

        return response()->json(['data' => ['ok' => true]]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint', '');
        if ($endpoint !== '') {
            PushSubscription::query()->where('endpoint', $endpoint)->delete();
        }

        return response()->json(['data' => ['ok' => true]]);
    }
}
