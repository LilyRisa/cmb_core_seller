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

class AiImageReplyTest extends TestCase
{
    use RefreshDatabase;

    private function seedConv(): Conversation
    {
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();
        Storage::fake(config('messaging.media_disk', 'local'));

        $tenant = Tenant::create(['name' => 'ImgShop']);
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

        return Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'manual',
            'external_conversation_id' => 'c', 'buyer_external_id' => 'b', 'buyer_name' => 'Khách',
            'status' => Conversation::STATUS_OPEN, 'last_inbound_at' => now(),
        ]);
    }

    public function test_image_request_matched_sends_image_message(): void
    {
        $conv = $this->seedConv();

        $intent = Mockery::mock(IntentClassifier::class);
        $intent->shouldReceive('classify')->andReturn(new IntentDTO(intent: 'image_request', confidence: 0.95));
        $intent->shouldReceive('shouldEscalate')->andReturn(false);
        $this->app->instance(IntentClassifier::class, $intent);

        $visual = Mockery::mock(VisualItemSearch::class);
        $visual->shouldReceive('findByName')->andReturn(
            VisualMatchResult::matched(new VisualItemCandidate(itemId: 5, name: 'Áo thun', description: null, attributes: [], confidence: 1.0)),
        );
        $visual->shouldReceive('imagesForItem')->andReturn([new VisualItemImage('image/jpeg', 'IMG')]);
        $this->app->instance(VisualItemSearch::class, $visual);

        $result = app(AiSuggestionService::class)->autoRespond($conv, 'cho em xin ảnh áo thun');

        $this->assertSame('sent', $result['action']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => 'image',
            'sent_by_ai' => 1,
        ]);
    }

    public function test_image_request_via_keyword_heuristic_when_classifier_says_other(): void
    {
        // Prod: MiniMax classify NHẦM image_request thành "other" ⇒ AI từ chối gửi ảnh.
        // Heuristic từ khoá phải bắt được "gửi cho tôi hình ảnh …" dù classifier trả "other".
        $conv = $this->seedConv();

        $intent = Mockery::mock(IntentClassifier::class);
        $intent->shouldReceive('classify')->andReturn(new IntentDTO(intent: 'other', confidence: 0.7));
        $intent->shouldReceive('shouldEscalate')->andReturn(false);
        $this->app->instance(IntentClassifier::class, $intent);

        $visual = Mockery::mock(VisualItemSearch::class);
        $visual->shouldReceive('findByName')->andReturn(
            VisualMatchResult::matched(new VisualItemCandidate(itemId: 5, name: 'Áo thun', description: null, attributes: [], confidence: 1.0)),
        );
        $visual->shouldReceive('imagesForItem')->andReturn([new VisualItemImage('image/jpeg', 'IMG')]);
        $this->app->instance(VisualItemSearch::class, $visual);

        $result = app(AiSuggestionService::class)->autoRespond($conv, 'gửi cho tôi hình ảnh áo thun với');

        $this->assertSame('sent', $result['action']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => 'image', 'sent_by_ai' => 1,
        ]);
    }

    public function test_non_image_message_not_treated_as_image_request(): void
    {
        // "other" + không có từ khoá ảnh ⇒ KHÔNG vào nhánh gửi ảnh (tránh sai dương).
        $conv = $this->seedConv();

        $intent = Mockery::mock(IntentClassifier::class);
        $intent->shouldReceive('classify')->andReturn(new IntentDTO(intent: 'other', confidence: 0.7));
        $intent->shouldReceive('shouldEscalate')->andReturn(false);
        $this->app->instance(IntentClassifier::class, $intent);

        $visual = Mockery::mock(VisualItemSearch::class);
        $visual->shouldReceive('findByName')->never();
        $this->app->instance(VisualItemSearch::class, $visual);

        $result = app(AiSuggestionService::class)->autoRespond($conv, 'sản phẩm này giá bao nhiêu vậy shop');

        $this->assertSame('sent', $result['action']);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_OUTBOUND, 'kind' => 'image',
        ]);
    }

    public function test_image_request_unresolved_falls_through_to_text(): void
    {
        $conv = $this->seedConv();

        $intent = Mockery::mock(IntentClassifier::class);
        $intent->shouldReceive('classify')->andReturn(new IntentDTO(intent: 'image_request', confidence: 0.9));
        $intent->shouldReceive('shouldEscalate')->andReturn(false);
        $this->app->instance(IntentClassifier::class, $intent);

        $visual = Mockery::mock(VisualItemSearch::class);
        $visual->shouldReceive('findByName')->andReturn(VisualMatchResult::notFound());
        // imagesForItem should NOT be called when not matched — no expectation set.
        $this->app->instance(VisualItemSearch::class, $visual);

        // manual provider generates a text reply → action 'sent' with a text message (kind=text), NOT image.
        $result = app(AiSuggestionService::class)->autoRespond($conv, 'cho xin ảnh với');

        $this->assertSame('sent', $result['action']);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conv->id, 'direction' => Message::DIRECTION_OUTBOUND, 'kind' => 'image',
        ]);
    }
}
