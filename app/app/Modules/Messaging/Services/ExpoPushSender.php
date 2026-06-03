<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gửi Expo Push notification qua Expo Push HTTP API v2 — SPEC 0029.
 *
 * Batch endpoint: POST https://exp.host/--/api/v2/push/send
 * Mỗi message → response `{ status: 'ok'|'error', details?: { error: string } }`.
 *
 * Xử lý lỗi (best-effort, không bao giờ ném ra ngoài làm vỡ digest):
 *   - DeviceNotRegistered  ⇒ xoá MobileDevice row + return false.
 *   - lỗi khác / HTTP fail ⇒ log + return false (giữ row).
 *
 * Tách hoàn toàn khỏi {@see WebPushSender} (VAPID) — gate riêng bằng
 * `config('services.expo.enabled')`.
 */
class ExpoPushSender implements ExpoPushSenderContract
{
    public function isConfigured(): bool
    {
        return (bool) config('services.expo.enabled', false);
    }

    /**
     * @param  array<string, mixed>  $payload  vd ['title'=>..,'body'=>..,'data'=>[..]]
     */
    public function send(MobileDevice $device, array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $message = [
            'to' => $device->expo_push_token,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'sound' => 'default',
            'data' => $payload['data'] ?? [],
        ];

        try {
            $request = Http::asJson()->acceptJson();

            $accessToken = (string) config('services.expo.access_token', '');
            if ($accessToken !== '') {
                $request = $request->withToken($accessToken);
            }

            $response = $request->post(
                (string) config('services.expo.url', 'https://exp.host/--/api/v2/push/send'),
                [$message],
            );

            if (! $response->successful()) {
                Log::warning('messaging.expo_push.http_failed', [
                    'device_id' => $device->getKey(),
                    'status' => $response->status(),
                ]);

                return false;
            }

            /** @var array<int, array<string, mixed>> $tickets */
            $tickets = (array) $response->json('data', []);
            $ticket = $tickets[0] ?? null;

            if (is_array($ticket) && ($ticket['status'] ?? null) === 'error') {
                $error = $ticket['details']['error'] ?? null;

                if ($error === 'DeviceNotRegistered') {
                    $device->delete();
                } else {
                    Log::warning('messaging.expo_push.ticket_error', [
                        'device_id' => $device->getKey(),
                        'error' => $error,
                    ]);
                }

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('messaging.expo_push.exception', [
                'device_id' => $device->getKey(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
