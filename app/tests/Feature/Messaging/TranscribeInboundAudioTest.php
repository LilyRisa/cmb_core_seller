<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\TranscribeInboundAudio;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\MediaRelayService;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TranscribeInboundAudioTest extends TestCase
{
    use RefreshDatabase;

    public int $recorded = 0;

    private function fakeCredits(bool $canUse = true): void
    {
        $test = $this;
        $this->app->instance(AiCreditMeter::class, new class($test, $canUse) implements AiCreditMeter
        {
            public function __construct(private $t, private bool $can) {}

            public function aiEnabled(int $x): bool
            {
                return true;
            }

            public function canUse(int $x, int $n = 1): bool
            {
                return $this->can;
            }

            public function consume(int $x, int $n = 1): void {}

            public function record(int $x, int $n = 1): void
            {
                $this->t->recorded += $n;
            }

            public function grantPurchase(int $x, int $a): int
            {
                return $a;
            }

            public function summary(int $x): array
            {
                return ['enabled' => true, 'unlimited' => true, 'monthly_allowance' => 0, 'period_used' => 0, 'purchased_balance' => 0, 'available' => null];
            }
        });
    }

    private function makeAudioAttachment(int $tenantId): MessageAttachment
    {
        Storage::fake('local');
        config()->set('messaging.media_disk', 'local');
        $conv = Conversation::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'external_conversation_id' => 'c1', 'buyer_external_id' => 'buyer1',
        ]);
        $msg = Message::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'conversation_id' => $conv->id, 'direction' => 'inbound',
            'kind' => 'audio', 'external_message_id' => 'm1',
        ]);
        $path = "tenants/{$tenantId}/messaging/voice.mp3";
        Storage::disk('local')->put($path, 'RAWAUDIO');

        return MessageAttachment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'message_id' => $msg->id, 'kind' => 'audio',
            'mime' => 'audio/mpeg', 'status' => 'downloaded', 'storage_path' => $path, 'filename' => 'voice.mp3',
        ]);
    }

    public function test_transcribes_and_saves_and_records_credit(): void
    {
        $tenant = Tenant::factory()->create();
        $this->fakeCredits();
        AiProvider::query()->create(['code' => 'groq', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'transcription', 'api_key' => 'gsk', 'base_url' => 'https://api.groq.com/openai/v1', 'default_model' => 'whisper-large-v3-turbo']);
        app(SystemSettingService::class)->set('messaging.transcription.provider_code', 'groq');
        Http::fake(['api.groq.com/*' => Http::response(['text' => 'cho em hỏi giá'], 200)]);

        $att = $this->makeAudioAttachment($tenant->id);
        (new TranscribeInboundAudio($att->id))->handle();

        $this->assertSame('cho em hỏi giá', $att->fresh()->transcript);
        $this->assertSame(1, $this->recorded);
    }

    public function test_noop_when_provider_unset(): void
    {
        $tenant = Tenant::factory()->create();
        $this->fakeCredits();
        Http::fake();
        $att = $this->makeAudioAttachment($tenant->id);
        (new TranscribeInboundAudio($att->id))->handle();

        $this->assertNull($att->fresh()->transcript);
        Http::assertNothingSent();
    }

    public function test_throws_on_api_error_for_retry(): void
    {
        $tenant = Tenant::factory()->create();
        $this->fakeCredits();
        AiProvider::query()->create(['code' => 'groq', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'transcription', 'api_key' => 'gsk', 'base_url' => 'https://api.groq.com/openai/v1', 'default_model' => 'whisper-large-v3-turbo']);
        app(SystemSettingService::class)->set('messaging.transcription.provider_code', 'groq');
        Http::fake(['api.groq.com/*' => Http::response('err', 500)]);

        $att = $this->makeAudioAttachment($tenant->id);
        $this->expectException(TranscriptionFailed::class);
        (new TranscribeInboundAudio($att->id))->handle();
    }

    public function test_download_job_dispatches_transcription_for_audio(): void
    {
        Queue::fake();
        $tenant = Tenant::factory()->create();
        $att = $this->makeAudioAttachment($tenant->id);

        (new DownloadInboundMedia($att->id))
            ->handle(app(MediaRelayService::class));

        Queue::assertPushed(TranscribeInboundAudio::class);
    }
}
