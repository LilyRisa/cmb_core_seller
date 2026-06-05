<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\AudienceTemplateRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AudienceTemplateResource;
use CMBcoreSeller\Modules\Marketing\Models\AudienceTemplate;
use CMBcoreSeller\Modules\Marketing\Services\AudienceTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Tenant-scoped detailed-targeting templates (saved interests/behaviours/
 * demographics sets — include/narrow/exclude). Read = marketing.view;
 * write = marketing.ads.create. Lookups tenant-scoped via the model global scope.
 */
class AudienceTemplateController extends Controller
{
    public function __construct(private AudienceTemplateService $service) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('marketing.view');

        return AudienceTemplateResource::collection($this->service->list());
    }

    public function store(AudienceTemplateRequest $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $template = $this->service->create(
            $request->user()?->id,
            (string) $request->validated('name'),
            (array) $request->validated('payload'),
        );

        return (new AudienceTemplateResource($template))->response()->setStatusCode(201);
    }

    public function destroy(AudienceTemplate $template): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $this->service->delete($template);

        return response()->json(null, 204);
    }
}
