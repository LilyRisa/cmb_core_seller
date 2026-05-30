<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Support\Services\HelpAssistant;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Trợ lý hỏi-đáp về cách dùng hệ thống (tab "Hỏi AI" của widget trợ giúp).
 * Không gate theo gói — trợ giúp dùng được cho mọi gói. Không bao giờ 500 (service suy biến).
 */
class HelpAssistantController extends Controller
{
    public function __construct(private HelpAssistant $assistant) {}

    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
        ]);

        $result = $this->assistant->ask(
            (string) $data['question'],
            array_values($data['history'] ?? []),
            app(CurrentTenant::class)->id(),
        );

        return response()->json(['data' => $result]);
    }
}
