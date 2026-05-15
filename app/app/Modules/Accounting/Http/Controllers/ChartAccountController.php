<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Http\Resources\ChartAccountResource;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ChartAccountController extends Controller
{
    public function __construct(private readonly CurrentTenant $tenant) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $q = ChartAccount::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('sort_order')->orderBy('code');
        if ($t = $request->query('type')) {
            $q->where('type', $t);
        }
        if ($request->boolean('active_only', false)) {
            $q->where('is_active', true);
        }
        if ($s = $request->query('q')) {
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%$s%")->orWhere('name', 'like', "%$s%");
            });
        }

        return ChartAccountResource::collection($q->get());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:16|regex:/^[A-Za-z0-9_-]+$/',
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', ChartAccount::TYPES),
            'parent_code' => 'nullable|string|max:16',
            'normal_balance' => 'required|in:debit,credit',
            'is_postable' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
            'description' => 'nullable|string|max:500',
        ]);
        $tenantId = (int) $this->tenant->id();
        if (ChartAccount::query()->where('tenant_id', $tenantId)->where('code', $request->string('code'))->exists()) {
            throw AccountingException::invalidLines("Tài khoản {$request->string('code')} đã tồn tại.");
        }
        $parentId = null;
        if ($pc = $request->string('parent_code')->toString()) {
            $parent = ChartAccount::query()->where('tenant_id', $tenantId)->where('code', $pc)->first();
            if (! $parent) {
                throw AccountingException::accountNotFound($pc);
            }
            $parentId = $parent->id;
        }
        $row = ChartAccount::query()->create([
            'tenant_id' => $tenantId,
            'code' => $request->string('code'),
            'name' => $request->string('name'),
            'type' => $request->string('type'),
            'parent_id' => $parentId,
            'normal_balance' => $request->string('normal_balance'),
            'is_postable' => $request->boolean('is_postable', true),
            'is_active' => true,
            'vas_template' => 'custom',
            'sort_order' => $request->integer('sort_order', 0),
            'description' => $request->input('description'),
        ]);

        return (new ChartAccountResource($row))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): ChartAccountResource
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'is_postable' => 'sometimes|boolean',
            'description' => 'nullable|string|max:500',
        ]);
        $tenantId = (int) $this->tenant->id();
        /** @var ChartAccount $row */
        $row = ChartAccount::query()->where('tenant_id', $tenantId)->findOrFail($id);
        $row->fill($request->only(['name', 'sort_order', 'is_active', 'is_postable', 'description']))->save();

        return new ChartAccountResource($row);
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) $this->tenant->id();
        /** @var ChartAccount $row */
        $row = ChartAccount::query()->where('tenant_id', $tenantId)->findOrFail($id);
        // Chặn xoá nếu có phát sinh.
        $hasLines = JournalLine::query()->where('tenant_id', $tenantId)->where('account_id', $row->id)->exists();
        if ($hasLines) {
            throw AccountingException::accountInUse($row->code);
        }
        // Chặn xoá nếu có TK con.
        if (ChartAccount::query()->where('tenant_id', $tenantId)->where('parent_id', $row->id)->exists()) {
            throw AccountingException::accountInUse($row->code);
        }
        $row->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
