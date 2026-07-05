<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AiImageSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_attaches_product_images_when_image_requested(): void
    {
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();
        Storage::fake(config('messaging.media_disk', 'local'));

        $tenant = Tenant::create(['name' => 'ImgSuggestShop']);
        $plan = Plan::query()->where('code', Plan::CODE_BUSINESS)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 's', 'shop_name' => 's', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'manual',
            'external_conversation_id' => 'c', 'buyer_external_id' => 'b', 'buyer_name' => 'Khách',
            'status' => Conversation::STATUS_OPEN, 'last_inbound_at' => now(),
        ]);
        Message::query()->create([
            'tenant_id' => $tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_INBOUND, 'kind' => Message::KIND_TEXT,
            'body' => 'cho xin ảnh áo thun', 'external_message_id' => 'm1',
        ]);

        $intent = Mockery::mock(IntentClassifier::class);
        $intent->shouldReceive('classify')->andReturn(new IntentDTO(intent: 'image_request', confidence: 0.95));
        $this->app->instance(IntentClassifier::class, $intent);

        $visual = Mockery::mock(VisualItemSearch::class);
        $visual->shouldReceive('findByName')->andReturn(
            VisualMatchResult::matched(new VisualItemCandidate(itemId: 5, name: 'Áo thun', description: null, attributes: [], confidence: 1.0)),
        );
        $visual->shouldReceive('imagesForItem')->andReturn([new VisualItemImage('image/jpeg', 'IMG')]);
        $this->app->instance(VisualItemSearch::class, $visual);

        $draft = app(AiSuggestionService::class)->suggest($conv, 1);

        $this->assertNotEmpty($draft->suggested_attachments);
        $this->assertSame('image', $draft->suggested_attachments[0]['kind']);
        $this->assertArrayHasKey('storage_path', $draft->suggested_attachments[0]);
    }
}
