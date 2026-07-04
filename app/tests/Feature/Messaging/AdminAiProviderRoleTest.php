<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiProviderRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_role_and_present_exposes_verified(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web')->postJson('/api/v1/admin/ai-providers', [
            'code'=>'groq','adapter'=>'openai_compatible','role'=>'transcription',
            'base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo',
        ])->assertCreated()->assertJsonPath('data.role','transcription');

        AiProvider::query()->whereKey('groq')->update(['vision_verified'=>false,'vision_verify_error'=>'no image']);
        $row = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/ai-providers')->json('data');
        $g = collect($row)->firstWhere('code','groq');
        $this->assertSame('transcription',$g['role']);
        $this->assertFalse($g['vision_verified']);
    }

    public function test_store_rejects_bad_role(): void
    {
        $this->actingAs(AdminUser::factory()->create(),'admin_web')->postJson('/api/v1/admin/ai-providers', [
            'code'=>'x','adapter'=>'openai_compatible','role'=>'bogus','base_url'=>'https://h','default_model'=>'m',
        ])->assertStatus(422);
    }
}
