<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\Http\Requests\UpdatePreferencesRequest;
use CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function __construct(private readonly UserPreferenceService $prefs) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->shape($this->prefs->all((int) $request->user()->getKey()))]);
    }

    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $saved = $this->prefs->putMany((int) $request->user()->getKey(), $request->validated());

        return response()->json(['data' => $this->shape($saved)]);
    }

    /**
     * @param  array<string,mixed>  $all
     * @return array<string,mixed>
     */
    private function shape(array $all): array
    {
        return [
            'ui_shell' => $all['ui_shell'] ?? 'v1',
            'ui_open_tabs' => $all['ui_open_tabs'] ?? [],
            'ui_active_tab' => $all['ui_active_tab'] ?? null,
        ];
    }
}
