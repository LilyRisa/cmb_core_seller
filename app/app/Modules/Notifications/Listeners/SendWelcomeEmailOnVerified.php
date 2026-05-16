<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listen `Illuminate\Auth\Events\Verified` ⇒ gửi WelcomeNotification (SPEC 0022 §3.1).
 *
 * Idempotent — Laravel chỉ fire Verified một lần (markEmailAsVerified() no-op
 * khi đã verified), nên không lo gửi welcome trùng.
 */
class SendWelcomeEmailOnVerified implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(Verified $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $user->notify(new WelcomeNotification);
        }
    }
}
