<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

class ShippingLabelTemplateService
{
    public function setDefault(int $tenantId, int $templateId): ShippingLabelTemplate
    {
        return DB::transaction(function () use ($tenantId, $templateId) {
            $tpl = ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('id', $templateId)->firstOrFail();
            ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('id', '<>', $templateId)
                ->where('is_default', true)->update(['is_default' => false]);
            $tpl->update(['is_default' => true]);

            return $tpl;
        });
    }

    public function duplicate(int $tenantId, int $sourceId, ?int $createdBy): ShippingLabelTemplate
    {
        $src = ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('id', $sourceId)->firstOrFail();
        $name = $this->uniqueName($tenantId, $src->name.' (copy)');

        return ShippingLabelTemplate::create([
            'tenant_id' => $tenantId, 'name' => $name,
            'paper' => $src->paper, 'paper_w_mm' => $src->paper_w_mm, 'paper_h_mm' => $src->paper_h_mm,
            'schema_version' => $src->schema_version, 'schema' => $src->schema,
            'is_default' => false, 'created_by' => $createdBy,
        ]);
    }

    private function uniqueName(int $tenantId, string $base): string
    {
        $name = $base;
        $i = 1;
        while (ShippingLabelTemplate::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('name', $name)->exists()) {
            $i++;
            $name = $base.' '.$i;
        }

        return $name;
    }
}
