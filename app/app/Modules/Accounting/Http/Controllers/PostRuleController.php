<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Http\Resources\AccountingPostRuleResource;
use CMBcoreSeller\Modules\Accounting\Models\AccountingPostRule;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PostRuleController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly PostRuleResolver $resolver,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $rows = AccountingPostRule::query()
            ->where('tenant_id', $this->tenant->id())
            ->orderBy('event_key')
            ->get();

        return AccountingPostRuleResource::collection($rows);
    }

    public function update(Request $request, string $eventKey): AccountingPostRuleResource
    {
        $request->validate([
            'debit_account_code' => 'required|string|max:16',
            'credit_account_code' => 'required|string|max:16',
            'is_enabled' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
        ]);
        $tenantId = (int) $this->tenant->id();

        foreach (['debit_account_code', 'credit_account_code'] as $field) {
            $code = (string) $request->input($field);
            $acc = ChartAccount::query()->where('tenant_id', $tenantId)->where('code', $code)->first();
            if (! $acc) {
                throw AccountingException::accountNotFound($code);
            }
            if (! $acc->is_postable) {
                throw AccountingException::accountNotPostable($code);
            }
        }

        $rule = AccountingPostRule::query()
            ->where('tenant_id', $tenantId)
            ->where('event_key', $eventKey)
            ->firstOrFail();
        $rule->fill([
            'debit_account_code' => $request->string('debit_account_code'),
            'credit_account_code' => $request->string('credit_account_code'),
            'is_enabled' => $request->boolean('is_enabled', true),
            'notes' => $request->input('notes'),
            'updated_by' => (int) $request->user()->getKey(),
        ])->save();

        $this->resolver->forget($tenantId);

        return new AccountingPostRuleResource($rule);
    }
}
