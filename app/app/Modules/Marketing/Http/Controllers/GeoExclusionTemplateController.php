<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\GeoExclusionTemplateRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\GeoExclusionTemplateResource;
use CMBcoreSeller\Modules\Marketing\Models\GeoExclusionTemplate;
use CMBcoreSeller\Modules\Marketing\Services\GeoExclusionTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Tenant-scoped geo exclusion templates (saved sets of excluded locations).
 * Read = marketing.view; write = marketing.ads.create — same policy as the
 * other Marketing controllers. Lookups are tenant-scoped via the model global
 * scope (cross-tenant ⇒ 404).
 */
class GeoExclusionTemplateController extends Controller
{
    public function __construct(private GeoExclusionTemplateService $service) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('marketing.view');

        return GeoExclusionTemplateResource::collection($this->service->list());
    }

    public function store(GeoExclusionTemplateRequest $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $template = $this->service->create(
            $request->user()?->id,
            (string) $request->validated('name'),
            (array) $request->validated('payload'),
        );

        return (new GeoExclusionTemplateResource($template))->response()->setStatusCode(201);
    }

    public function destroy(GeoExclusionTemplate $template): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $this->service->delete($template);

        return response()->json(null, 204);
    }
}
