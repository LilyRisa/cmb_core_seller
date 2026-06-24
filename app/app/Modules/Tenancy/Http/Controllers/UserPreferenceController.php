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
        return response()->json(['data' => UserPreferenceService::shape($this->prefs->all((int) $request->user()->getKey()))]);
    }

    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $saved = $this->prefs->putMany((int) $request->user()->getKey(), $request->validated());

        return response()->json(['data' => UserPreferenceService::shape($saved)]);
    }
}
