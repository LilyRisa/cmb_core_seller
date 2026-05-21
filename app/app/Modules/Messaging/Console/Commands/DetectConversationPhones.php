<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\PhoneDetector;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/** Quét tin nhắn các hội thoại chưa gắn cờ SĐT và set has_phone/detected_phone (idempotent). */
class DetectConversationPhones extends Command
{
    protected $signature = 'messaging:detect-phones';

    protected $description = 'Nhận diện SĐT trong hội thoại hiện có (backfill cờ has_phone).';

    public function handle(PhoneDetector $phones): int
    {
        Conversation::withoutGlobalScope(TenantScope::class)
            ->where('has_phone', false)
            ->orderBy('id')
            ->chunkById(200, function ($conversations) use ($phones) {
                foreach ($conversations as $conv) {
                    $phone = null;
                    Message::withoutGlobalScope(TenantScope::class)
                        ->where('conversation_id', $conv->id)
                        ->whereNotNull('body')
                        ->orderBy('id')
                        ->each(function ($m) use ($phones, &$phone) {
                            $found = $phones->firstPhone($m->body);
                            if ($found !== null) {
                                $phone = $found;

                                return false;
                            }

                            return true;
                        });
                    if ($phone !== null) {
                        $conv->forceFill(['has_phone' => true, 'detected_phone' => $phone])->save();
                    }
                }
            });

        return self::SUCCESS;
    }
}
