<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * MessageResource phải luôn trả `download_url` render được cho media inbound —
 * kể cả khi relay (DownloadInboundMedia) chưa chạy / thất bại (storage_path null).
 *
 * Bug: hình ảnh / video / sticker hiển thị thành link "Tệp đính kèm" vì
 * `temporaryUrl()` trả null khi attachment còn pending ⇒ FE rơi xuống nhánh <a>.
 * Fix: fallback về `external_url` (URL CDN sàn) khi chưa có signed storage URL.
 *
 * SPEC-0024 §6.4 / §8.5 (không lộ storage_path raw — external_url là URL nguồn).
 */
class MessageResourceMediaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'MediaResourceTenant']);
        app(CurrentTenant::class)->set($this->tenant);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1',
            'shop_name' => 'FB Page',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        $this->conversation = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page',
            'external_conversation_id' => 'PSID_1',
            'buyer_external_id' => 'PSID_1',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now(),
        ]);
    }

    private function makeImageMessage(): Message
    {
        return Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conversation->getKey(),
            'external_message_id' => 'MID_IMG',
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_IMAGE,
            'body' => null,
            'attachments_count' => 1,
            'delivery_status' => Message::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    private function resourceArray(Message $message): array
    {
        return (new MessageResource($message->load('attachments')))->toArray(Request::create('/'));
    }

    /** Nút bấm (template/quick-reply) trong meta phải lộ ra `buttons` cho FE. */
    public function test_buttons_in_meta_are_exposed(): void
    {
        $message = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conversation->getKey(),
            'external_message_id' => 'MID_BTN',
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Bạn cần hỗ trợ gì?',
            'attachments_count' => 0,
            'delivery_status' => Message::STATUS_SENT,
            'sent_at' => now(),
            'meta' => ['buttons' => [
                ['title' => 'Mua hàng'],
                ['title' => ''], // rỗng → bị loại
                ['title' => 'Xem sản phẩm', 'url' => 'https://shop.vn/sp'],
            ]],
        ]);

        $data = (new MessageResource($message))->toArray(Request::create('/'));

        $this->assertCount(2, $data['buttons'], 'nút rỗng tên bị loại');
        $this->assertSame('Mua hàng', $data['buttons'][0]['title']);
        $this->assertSame('https://shop.vn/sp', $data['buttons'][1]['url']);
    }

    /** Pending (chưa relay) ⇒ download_url fallback về external_url để FE render ảnh. */
    public function test_pending_image_attachment_exposes_external_url_as_download_url(): void
    {
        $message = $this->makeImageMessage();
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'message_id' => $message->getKey(),
            'kind' => MessageAttachment::KIND_IMAGE,
            'mime' => 'image/jpeg',
            'external_url' => 'https://scontent.fbcdn.net/v/abc.jpg',
            'storage_path' => null,
            'status' => MessageAttachment::STATUS_PENDING,
        ]);

        $data = $this->resourceArray($message);

        $this->assertCount(1, $data['attachments']);
        $this->assertSame(
            'https://scontent.fbcdn.net/v/abc.jpg',
            $data['attachments'][0]['download_url'],
            'download_url phải fallback về external_url khi attachment còn pending',
        );
    }

    /** Relay thất bại (failed, không có storage_path) vẫn còn external_url để render. */
    public function test_failed_attachment_still_exposes_external_url(): void
    {
        $message = $this->makeImageMessage();
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'message_id' => $message->getKey(),
            'kind' => MessageAttachment::KIND_VIDEO,
            'mime' => 'video/mp4',
            'external_url' => 'https://video.fbcdn.net/v/clip.mp4',
            'storage_path' => null,
            'status' => MessageAttachment::STATUS_FAILED,
        ]);

        $data = $this->resourceArray($message);

        $this->assertSame(
            'https://video.fbcdn.net/v/clip.mp4',
            $data['attachments'][0]['download_url'],
        );
    }

    /** Đã relay (downloaded) ⇒ ưu tiên signed storage URL, KHÔNG dùng external_url. */
    public function test_downloaded_attachment_prefers_storage_url(): void
    {
        Storage::fake(config('messaging.media_disk'));
        $path = "tenants/{$this->tenant->getKey()}/messaging/2026/05/{$this->conversation->getKey()}/abc.jpg";
        Storage::disk(config('messaging.media_disk'))->put($path, 'fake-bytes');

        $message = $this->makeImageMessage();
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'message_id' => $message->getKey(),
            'kind' => MessageAttachment::KIND_IMAGE,
            'mime' => 'image/jpeg',
            'external_url' => 'https://scontent.fbcdn.net/v/abc.jpg',
            'storage_path' => $path,
            'status' => MessageAttachment::STATUS_DOWNLOADED,
        ]);

        $data = $this->resourceArray($message);

        $url = $data['attachments'][0]['download_url'];
        $this->assertNotNull($url);
        $this->assertStringNotContainsString('scontent.fbcdn.net', (string) $url, 'Đã relay thì không dùng external_url');
        $this->assertStringContainsString('abc.jpg', (string) $url, 'Phải trỏ tới file storage đã relay');
    }
}
