<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Fulfillment\Http\Resources\ShippingLabelTemplateResource;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelRenderer;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\SampleDataFactory;
use CMBcoreSeller\Modules\Fulfillment\Services\ShippingLabelTemplateService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingLabelTemplateController extends Controller
{
    private const PRESET_DIMS = [
        'A4' => [210, 297], 'A5' => [148, 210], 'A6' => [105, 148],
        '100x150mm' => [100, 150], '80mm' => [80, 0],
    ];

    public function index(Request $request, CurrentTenant $tenant): JsonResponse
    {
        $items = ShippingLabelTemplate::query()
            ->where('tenant_id', $tenant->id())
            ->orderByDesc('is_default')->orderBy('name')->get();

        return response()->json(['data' => ShippingLabelTemplateResource::collection($items)]);
    }

    public function show(Request $request, int $id, CurrentTenant $tenant): JsonResponse
    {
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)]);
    }

    public function store(Request $request, CurrentTenant $tenant, FieldTypeRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $data = $this->validatePayload($request, $registry);
        $tpl = ShippingLabelTemplate::create([
            'tenant_id' => $tenant->id(),
            'name' => $data['name'], 'paper' => $data['paper'],
            'paper_w_mm' => $data['paper_w_mm'], 'paper_h_mm' => $data['paper_h_mm'],
            'schema_version' => 1, 'schema' => ['fields' => $data['schema']['fields']],
            'is_default' => false, 'created_by' => $request->user()->getKey(),
        ]);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)], 201);
    }

    public function update(Request $request, int $id, CurrentTenant $tenant, FieldTypeRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        $data = $this->validatePayload($request, $registry, $tpl->id);
        $tpl->update([
            'name' => $data['name'], 'paper' => $data['paper'],
            'paper_w_mm' => $data['paper_w_mm'], 'paper_h_mm' => $data['paper_h_mm'],
            'schema' => ['fields' => $data['schema']['fields']],
        ]);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl->fresh())]);
    }

    public function destroy(Request $request, int $id, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        $tpl->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function setDefault(Request $request, int $id, CurrentTenant $tenant, ShippingLabelTemplateService $service): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = $service->setDefault($tenant->id(), $id);

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)]);
    }

    public function duplicate(Request $request, int $id, CurrentTenant $tenant, ShippingLabelTemplateService $service): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $tpl = $service->duplicate($tenant->id(), $id, $request->user()->getKey());

        return response()->json(['data' => new ShippingLabelTemplateResource($tpl)], 201);
    }

    public function preview(Request $request, int $id, CurrentTenant $tenant, LabelRenderer $renderer, GotenbergClient $gotenberg, MediaUploader $media, SampleDataFactory $factory): JsonResponse
    {
        abort_unless($request->user()?->can('fulfillment.print'), 403, 'Bạn không có quyền.');
        $this->rateLimit($request);
        $tpl = ShippingLabelTemplate::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        $profile = (string) $request->input('sample_profile', 'one_item_short_address');
        abort_unless(in_array($profile, SampleDataFactory::PROFILES, true), 422, 'sample_profile không hợp lệ.');
        $bytes = $gotenberg->htmlToLabelPdf($renderer->renderSample($profile, $tpl, $factory));
        $stored = $media->storeBytes($bytes, $tenant->id(), 'print', 'preview-'.Str::ulid(), 'pdf');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }

    public function previewInline(Request $request, CurrentTenant $tenant, LabelRenderer $renderer, GotenbergClient $gotenberg, MediaUploader $media, SampleDataFactory $factory, FieldTypeRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('tenant.settings'), 403, 'Bạn không có quyền.');
        $this->rateLimit($request);
        $data = $this->validatePayload($request, $registry, null, requireName: false);
        $profile = (string) $request->input('sample_profile', 'one_item_short_address');
        abort_unless(in_array($profile, SampleDataFactory::PROFILES, true), 422, 'sample_profile không hợp lệ.');
        $tpl = new ShippingLabelTemplate([
            'tenant_id' => $tenant->id(),
            'name' => $data['name'] ?? 'preview',
            'paper' => $data['paper'], 'paper_w_mm' => $data['paper_w_mm'], 'paper_h_mm' => $data['paper_h_mm'],
            'schema' => ['fields' => $data['schema']['fields']], 'schema_version' => 1, 'is_default' => false,
        ]);
        $bytes = $gotenberg->htmlToLabelPdf($renderer->renderSample($profile, $tpl, $factory));
        $stored = $media->storeBytes($bytes, $tenant->id(), 'print', 'preview-'.Str::ulid(), 'pdf');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }

    private function rateLimit(Request $request): void
    {
        $key = 'label-preview:'.$request->user()->getKey();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Quá nhiều lượt preview. Thử lại sau 1 phút.');
        }
        RateLimiter::hit($key, 60);
    }

    /**
     * @return array{name:?string,paper:string,paper_w_mm:int,paper_h_mm:int,schema:array<string,mixed>}
     */
    private function validatePayload(Request $request, FieldTypeRegistry $registry, ?int $excludeId = null, bool $requireName = true): array
    {
        $data = $request->validate([
            'name' => [$requireName ? 'required' : 'nullable', 'string', 'max:120'],
            'paper' => ['required', Rule::in(array_merge(array_keys(self::PRESET_DIMS), ['custom']))],
            'paper_w_mm' => ['required', 'integer', 'min:30', 'max:420'],
            'paper_h_mm' => ['required', 'integer', 'min:0', 'max:1200'],
            'schema' => ['required', 'array'],
            'schema.fields' => ['required', 'array', 'max:100'],
            'schema.fields.*.id' => ['required', 'string', 'max:32'],
            'schema.fields.*.type' => ['required', Rule::in($registry->keys())],
            'schema.fields.*.x' => ['required', 'numeric', 'min:0'],
            'schema.fields.*.y' => ['required', 'numeric', 'min:0'],
            'schema.fields.*.w' => ['required', 'numeric', 'min:1'],
            'schema.fields.*.h' => ['required', 'numeric', 'min:1'],
            'schema.fields.*.rotation' => ['nullable', 'numeric'],
        ]);
        if ($requireName) {
            $exists = ShippingLabelTemplate::query()
                ->where('tenant_id', app(CurrentTenant::class)->id())
                ->where('name', $data['name'])
                ->when($excludeId, fn ($q, $id) => $q->where('id', '<>', $id))
                ->exists();
            abort_if($exists, 422, 'Đã có template trùng tên trong shop.');
        }
        // Use the raw request fields for validateProps so field-type-specific props
        // (e.g. TextField's `text`, `style`) that are not in the shared validation rules
        // are not stripped from the data by $request->validate().
        // Also preserve the full field data (including type-specific props) for storage.
        $rawFields = $request->input('schema.fields', []);
        $fullFields = [];
        foreach ($data['schema']['fields'] as $i => $field) {
            $rawField = array_merge($rawFields[$i] ?? [], $field);
            $type = $registry->get($field['type']);
            if (! $type) {
                $fullFields[] = $rawField;

                continue;
            }
            try {
                $type->validateProps($rawField);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([
                    "schema.fields.{$i}" => $e->errors(),
                ]);
            }
            if (($field['x'] + $field['w']) > $data['paper_w_mm']) {
                throw ValidationException::withMessages([
                    "schema.fields.{$i}.w" => "Field '{$field['id']}' vượt chiều rộng giấy.",
                ]);
            }
            if ($data['paper_h_mm'] > 0 && ($field['y'] + $field['h']) > $data['paper_h_mm']) {
                throw ValidationException::withMessages([
                    "schema.fields.{$i}.h" => "Field '{$field['id']}' vượt chiều cao giấy.",
                ]);
            }
            $fullFields[] = $rawField;
        }
        $data['schema']['fields'] = $fullFields;

        return $data;
    }
}
