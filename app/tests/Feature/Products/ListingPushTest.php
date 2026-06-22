<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDetailDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingEditDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingStatusDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Products\Jobs\PrepareListingVideoJob;
use CMBcoreSeller\Modules\Products\Jobs\PushListingJob;
use CMBcoreSeller\Modules\Products\Jobs\RefreshListingQcStatus;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\Product;
use CMBcoreSeller\Modules\Products\Models\ProductPushBatch;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Products\Services\ListingPushService;
use CMBcoreSeller\Modules\Products\Services\MediaPrepService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ListingPushTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $accountId;

    private ListingDraft $draft;

    protected function setUp(): void
    {
        parent::setUp();

        // MediaPrepService tải ảnh nguồn để kiểm tra kích thước trước khi upload — chặn
        // mọi HTTP ngoài trong test (không chạm mạng thật).
        Http::fake();

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $product = Product::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Áo thun cotton',
        ]);
        $product->skus()->create([
            'tenant_id' => $this->tenant->getKey(),
            'sku_code' => 'SKU-001',
            'name' => 'Áo thun cotton - M',
            'base_unit' => 'cái',
            'cost_price' => 20000,
            'cost_method' => Sku::COST_AVERAGE,
            'ref_sale_price' => 35000,
            'attributes' => ['size' => 'M'],
        ]);

        $account = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada',
            'external_shop_id' => 'shop',
            'shop_name' => 'Shop',
            'shop_region' => 'VN',
            'status' => 'active',
            'access_token' => 'tok',
        ]);
        $this->accountId = (int) $account->getKey();

        $service = app(ListingDraftService::class);
        $draft = $service->createDraft((int) $product->getKey(), $this->accountId, 'lazada');
        $this->draft = $service->update((int) $draft->getKey(), [
            'category_id' => '3',
            'brand_id' => '40516',
            'media_refs' => [['ref' => 'https://cdn/x.jpg', 'kind' => 'cdn_url']],
            'skus' => [[
                'seller_sku' => 'S1',
                'price' => 35000,
                'stock' => 3,
                'sale_props' => [],
                'package_weight' => 0.5,
                'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]],
        ]);

        $this->assertSame(ListingDraft::STATUS_READY, $this->draft->status);
    }

    private function bindPublisher(ProductPublishingConnector $pub): void
    {
        $reg = new PublisherRegistry($this->app);
        $cls = get_class($pub);
        $reg->register('lazada', $cls);
        $this->app->instance($cls, $pub);
        $this->app->instance(PublisherRegistry::class, $reg);
    }

    public function test_enqueues_a_push_batch_for_a_ready_listing(): void
    {
        Queue::fake();

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/listings/{$this->draft->getKey()}/push");

        $res->assertOk();
        $this->assertNotNull($res->json('data.batch_id'));

        Queue::assertPushedOn('listings', PushListingJob::class);

        $batch = ProductPushBatch::find($res->json('data.batch_id'));
        $this->assertSame(1, (int) $batch->total);
        $this->assertSame(ListingDraft::STATUS_PUSHING, $this->draft->fresh()->status);
    }

    public function test_job_pushes_via_publisher_and_marks_success(): void
    {
        $this->bindPublisher(new FakePushPublisher(new ListingResultDTO('LZ-999', [], 'PENDING')));

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        (new PushListingJob((int) $row->getKey()))->handle(
            app(PublisherRegistry::class),
            app(MediaPrepService::class),
            app(ListingDraftService::class),
            app(CurrentTenant::class),
        );

        // Sàn xét duyệt: rawStatus 'PENDING' ⇒ nháp ở trạng thái 'reviewing' (chưa live).
        $fresh = $this->draft->fresh();
        $this->assertSame(ListingDraft::STATUS_REVIEWING, $fresh->status);
        $this->assertSame('LZ-999', $fresh->external_item_id);
        $this->assertSame('PENDING', $fresh->raw_qc_status);

        $this->assertSame('success', $row->fresh()->status);
        $batch->refresh();
        $this->assertSame(1, (int) $batch->succeeded);
        $this->assertSame('done', $batch->status);
    }

    public function test_images_are_uploaded_to_marketplace_before_create(): void
    {
        // Sàn (TikTok/Shopee) chỉ nhận ref ảnh do API upload trả về, KHÔNG nhận URL CDN
        // ngoài. PushListingJob phải upload media_refs nguồn rồi truyền ref đã upload vào DTO.
        $pub = new FakePushPublisher(new ListingResultDTO('LZ-1', [], 'PENDING'));
        $this->bindPublisher($pub);

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        (new PushListingJob((int) $row->getKey()))->handle(
            app(PublisherRegistry::class),
            app(MediaPrepService::class),
            app(ListingDraftService::class),
            app(CurrentTenant::class),
        );

        $this->assertNotNull($pub->lastDraft);
        $this->assertSame('uploaded:'.md5('https://cdn/x.jpg'), $pub->lastDraft->media[0]->ref);
        // media_refs nguồn của nháp KHÔNG bị ghi đè (FE còn hiển thị ảnh).
        $this->assertSame('https://cdn/x.jpg', $this->draft->fresh()->media_refs[0]['ref']);
    }

    public function test_refresh_qc_status_moves_reviewing_draft_to_live(): void
    {
        // FakePushPublisher::getListingStatus trả 'live' ⇒ nháp đang duyệt → live.
        $this->bindPublisher(new FakePushPublisher(new ListingResultDTO('LZ-1', [], 'PENDING')));
        $this->draft->update([
            'status' => ListingDraft::STATUS_REVIEWING,
            'external_item_id' => 'LZ-1',
            'raw_qc_status' => 'PENDING',
        ]);

        (new RefreshListingQcStatus($this->accountId))->handle(app(PublisherRegistry::class));

        $this->assertSame(ListingDraft::STATUS_LIVE, $this->draft->fresh()->status);
    }

    public function test_job_marks_failed_on_api_exception_without_throwing(): void
    {
        $this->bindPublisher(new FakePushPublisher(null, new MarketplaceApiException('boom', 'lazada')));

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        (new PushListingJob((int) $row->getKey()))->handle(
            app(PublisherRegistry::class),
            app(MediaPrepService::class),
            app(ListingDraftService::class),
            app(CurrentTenant::class),
        );

        $fresh = $this->draft->fresh();
        $this->assertSame(ListingDraft::STATUS_FAILED, $fresh->status);
        $this->assertNotEmpty($fresh->last_error);

        $this->assertSame('failed', $row->fresh()->status);
    }

    public function test_prepare_video_job_resolves_id_then_dispatches_push(): void
    {
        $this->bindPublisher(new FakePushPublisher(new ListingResultDTO('LZ-1', [], 'PENDING')));
        $this->draft->update(['attributes' => array_merge($this->draft->attributes ?? [], ['video_url' => 'https://cdn/v.mp4'])]);

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        $job = new PrepareListingVideoJob((int) $row->getKey());
        // Nhịp 1: upload xong → lưu pending, release để poll (release no-op khi gọi handle trực tiếp).
        $job->handle(app(PublisherRegistry::class), app(ListingDraftService::class), app(CurrentTenant::class));
        $this->assertSame('V1', $this->draft->fresh()->attributes['video_pending_id'] ?? null);
        // Nhịp 2: trạng thái 'ready' → lưu video_external_id + dispatch đăng thật.
        $job->handle(app(PublisherRegistry::class), app(ListingDraftService::class), app(CurrentTenant::class));

        $this->assertSame('V1', $this->draft->fresh()->attributes['video_external_id'] ?? null);
        Queue::assertPushed(PushListingJob::class);
    }

    public function test_push_job_succeeds_when_worker_has_no_current_tenant(): void
    {
        // Reproduces the production failure: the queue worker runs the job WITHOUT a
        // request-bound tenant, so the global TenantScope hid the ProductPushJob row
        // (tenant_id=0) and findOrFail threw ModelNotFoundException → instant FAIL.
        $this->bindPublisher(new FakePushPublisher(new ListingResultDTO('LZ-777', [], 'PENDING')));

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        // Simulate the worker: clear the tenant the HTTP request had set.
        app(CurrentTenant::class)->clear();

        (new PushListingJob((int) $row->getKey()))->handle(
            app(PublisherRegistry::class),
            app(MediaPrepService::class),
            app(ListingDraftService::class),
            app(CurrentTenant::class),
        );

        // The job must restore "no tenant" after running (runAs restores previous).
        $this->assertNull(app(CurrentTenant::class)->id());

        // Re-establish tenant context to read the results back.
        app(CurrentTenant::class)->set($this->tenant);
        $this->assertSame('success', $row->fresh()->status);
        $this->assertSame('LZ-777', $this->draft->fresh()->external_item_id);
        $this->assertSame(ListingDraft::STATUS_REVIEWING, $this->draft->fresh()->status);
    }

    public function test_prepare_video_job_resolves_tenant_when_worker_has_no_current_tenant(): void
    {
        $this->bindPublisher(new FakePushPublisher(new ListingResultDTO('LZ-1', [], 'PENDING')));
        $this->draft->update(['attributes' => array_merge($this->draft->attributes ?? [], ['video_url' => 'https://cdn/v.mp4'])]);

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        app(CurrentTenant::class)->clear();

        $job = new PrepareListingVideoJob((int) $row->getKey());
        $job->handle(app(PublisherRegistry::class), app(ListingDraftService::class), app(CurrentTenant::class));

        $this->assertNull(app(CurrentTenant::class)->id());

        app(CurrentTenant::class)->set($this->tenant);
        $this->assertSame('V1', $this->draft->fresh()->attributes['video_pending_id'] ?? null);
    }

    public function test_job_is_idempotent_when_external_item_id_present(): void
    {
        // A publisher that throws if createListing is ever called — proving the
        // idempotency guard skips re-creation when the item already exists.
        $this->bindPublisher(new FakePushPublisher(null, new \RuntimeException('createListing must not be called')));

        $this->draft->update(['external_item_id' => 'EXISTING-1']);

        Queue::fake();
        $batch = app(ListingPushService::class)->push([(int) $this->draft->getKey()], (int) $this->owner->getKey());
        $row = $batch->jobs()->first();

        (new PushListingJob((int) $row->getKey()))->handle(
            app(PublisherRegistry::class),
            app(MediaPrepService::class),
            app(ListingDraftService::class),
            app(CurrentTenant::class),
        );

        $fresh = $this->draft->fresh();
        $this->assertSame(ListingDraft::STATUS_LIVE, $fresh->status);
        $this->assertSame('EXISTING-1', $fresh->external_item_id);
        $this->assertSame('success', $row->fresh()->status);
    }
}

final class FakePushPublisher implements ProductPublishingConnector
{
    public ?ListingDraftDTO $lastDraft = null;

    public function __construct(
        private ?ListingResultDTO $result = null,
        private ?\Throwable $throw = null,
    ) {}

    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        return [];
    }

    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        return [];
    }

    public function getBrands(AuthContext $auth, string $categoryId, ?string $keyword = null, int $limit = 50): array
    {
        return [];
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        // Giả lập upload: trả ref "đã upload" để phân biệt với URL nguồn.
        return new MediaRefDTO('uploaded:'.md5($imageUrlOrPath), 'uri');
    }

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        $this->lastDraft = $draft;
        if ($this->throw !== null) {
            throw $this->throw;
        }

        return $this->result ?? new ListingResultDTO('X', [], '');
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        return new ListingStatusDTO($externalItemId, 'live', '');
    }

    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        throw new \RuntimeException('not used');
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        return [];
    }

    public function startVideoUpload(AuthContext $auth, ListingDraftDTO $draft): string
    {
        return 'V1';
    }

    public function videoUploadStatus(AuthContext $auth, string $videoId): string
    {
        return 'ready';
    }
}
