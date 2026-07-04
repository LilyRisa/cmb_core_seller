<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function stt(): void
    {
        AiProvider::query()->create(['code'=>'groq','adapter'=>'openai_compatible','is_active'=>true,'role'=>'transcription','api_key'=>'k','base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo']);
    }

    public function test_index_lists_only_transcription_role(): void
    {
        $this->stt();
        AiProvider::query()->create(['code'=>'chatx','adapter'=>'openai_compatible','is_active'=>true,'role'=>'chat','base_url'=>'https://h','default_model'=>'m']);
        $res = $this->actingAs(AdminUser::factory()->create(),'admin_web')->getJson('/api/v1/admin/ai-transcription')->assertOk()->json('data');
        $this->assertSame(['groq'], collect($res['providers'])->pluck('code')->all());
    }

    public function test_test_persists_and_put_requires_verified(): void
    {
        $this->stt();
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin,'admin_web')->putJson('/api/v1/admin/ai-transcription',['provider_code'=>'groq'])->assertStatus(422);
        Http::fake(['api.groq.com/*' => Http::response(['text'=>'ok'],200)]);
        $this->actingAs($admin,'admin_web')->postJson('/api/v1/admin/ai-transcription/test',['provider_code'=>'groq'])->assertOk()->assertJsonPath('data.ok',true);
        $this->assertTrue(AiProvider::find('groq')->transcription_verified);
        $this->actingAs($admin,'admin_web')->putJson('/api/v1/admin/ai-transcription',['provider_code'=>'groq'])->assertOk();
        $this->assertSame('groq', system_setting('messaging.transcription.provider_code'));
    }

    public function test_requires_admin(): void
    {
        $this->getJson('/api/v1/admin/ai-transcription')->assertStatus(401);
    }
}
