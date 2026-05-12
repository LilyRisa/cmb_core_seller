<?php

namespace Tests\Feature;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploads_an_image_and_returns_a_public_url(): void
    {
        Storage::fake('public');   // MEDIA_DISK falls back to "public" outside production
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $res = $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/media/image', ['image' => UploadedFile::fake()->image('a.jpg', 100, 100), 'folder' => 'order-items'])
            ->assertOk();

        $path = $res->json('data.path');
        $this->assertNotNull($path);
        $this->assertStringContainsString("tenants/{$tenant->getKey()}/order-items/", $path);
        $this->assertNotNull($res->json('data.url'));
        Storage::disk('public')->assertExists($path);

        // non-image rejected
        $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/media/image', ['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertStatus(422);
    }

    public function test_viewer_cannot_upload(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);

        $this->actingAs($viewer)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/media/image', ['image' => UploadedFile::fake()->image('a.jpg')])->assertForbidden();
    }
}
