<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Contracts\AudioTranscriber;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Super-admin chọn provider STT (role=transcription, phải verified). `/api/v1/admin/ai-transcription`. */
class AdminTranscriptionController extends Controller
{
    private const KEY = 'messaging.transcription.provider_code';

    public function __construct(private AiAssistantRegistry $registry, private SystemSettingService $settings) {}

    public function index(): JsonResponse
    {
        $providers = AiProvider::query()->where('role', 'transcription')->orderBy('sort_order')->orderBy('code')->get()
            ->map(fn (AiProvider $p) => [
                'code' => $p->code, 'display_name' => $p->display_name, 'default_model' => $p->default_model,
                'is_active' => (bool) $p->is_active, 'transcription_verified' => $p->transcription_verified,
                'transcription_verified_at' => $p->transcription_verified_at?->toIso8601String(),
                'transcription_verify_error' => $p->transcription_verify_error,
            ])->values()->all();

        return response()->json(['data' => [
            'selected_provider_code' => (string) system_setting(self::KEY, '') ?: null,
            'providers' => $providers,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));
        if ($code !== '') {
            if (! in_array($code, $this->registry->activeProviders('transcription'), true)) {
                return response()->json(['error' => ['code' => 'PROVIDER_NOT_ACTIVE', 'message' => 'Provider không tồn tại hoặc chưa bật.']], 422);
            }
            if (AiProvider::find($code)?->transcription_verified !== true) {
                return response()->json(['error' => ['code' => 'PROVIDER_NOT_VERIFIED', 'message' => 'Provider chưa xác minh STT — hãy Thử transcribe tới khi thành công.']], 422);
            }
        }
        $this->settings->set(self::KEY, $code, Auth::guard('admin_web')->id());
        AuditLog::record('messaging.transcription.provider_set', null, ['provider_code' => $code]);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function test(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));
        if ($code === '') {
            return response()->json(['data' => ['ok' => false, 'reason' => 'no_provider', 'message' => 'Chưa chọn provider.']]);
        }
        try {
            $connector = $this->registry->for($code);
            if (! $connector instanceof AudioTranscriber) {
                AiProvider::whereKey($code)->update(['transcription_verified' => false, 'transcription_verify_error' => 'connector không hỗ trợ STT']);

                return response()->json(['data' => ['ok' => false, 'reason' => 'unsupported', 'message' => 'Provider không hỗ trợ STT.']]);
            }
            $wav = base64_decode('UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAIA+AAACABAAZGF0YQAAAAA=');
            $out = $connector->transcribeAudio(new AiContext(tenantId: 0, providerCode: $code, meta: ['mode' => 'transcription_test']), $wav, 'audio/wav', 'test.wav');
            AiProvider::whereKey($code)->update(['transcription_verified' => true, 'transcription_verified_at' => now(), 'transcription_verify_error' => null]);

            return response()->json(['data' => ['ok' => true, 'text' => Str::limit($out, 120)]]);
        } catch (\Throwable $e) {
            AiProvider::whereKey($code)->update(['transcription_verified' => false, 'transcription_verify_error' => Str::limit($e->getMessage(), 240)]);

            return response()->json(['data' => ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)]]);
        }
    }
}
