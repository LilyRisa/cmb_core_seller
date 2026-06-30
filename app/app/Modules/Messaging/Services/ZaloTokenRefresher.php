<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloApiException;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZaloTokenRefresher
{
    public function __construct(private MessagingRegistry $registry) {}

    public function refresh(ChannelAccount $account): bool
    {
        if (! $account->refresh_token || ! $this->registry->has('zalo_oa')) {
            return false;
        }

        $lock = Cache::lock('channel-token-refresh:'.$account->getKey(), 30);
        if (! $lock->get()) {
            $account->refresh();

            return $account->status === ChannelAccount::STATUS_ACTIVE;
        }

        try {
            $account->refresh(); // re-read inside lock (sibling may have rotated)
            $token = $this->registry->for('zalo_oa')->refreshToken((string) $account->refresh_token);
            $account->forceFill([
                'access_token' => $token->accessToken,
                'refresh_token' => $token->refreshToken ?: $account->refresh_token,
                'token_expires_at' => $token->expiresAt,
                'status' => ChannelAccount::STATUS_ACTIVE,
            ])->save();
        } catch (ZaloApiException $e) {
            // Token bị thu hồi dứt khoát (-124) → expired; lỗi tạm thời giữ active.
            if (in_array($e->zaloError, [-124, -1001], true)) {
                $account->forceFill(['status' => ChannelAccount::STATUS_EXPIRED])->save();
            } else {
                Log::warning('messaging.zalo.refresh_transient', ['account' => $account->getKey(), 'error' => $e->zaloError]);
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('messaging.zalo.refresh_failed', ['account' => $account->getKey(), 'error' => $e->getMessage()]);

            return false;
        } finally {
            $lock->release();
        }

        return true;
    }
}
