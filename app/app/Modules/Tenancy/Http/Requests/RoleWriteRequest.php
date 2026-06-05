<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Requests;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate create/update of a custom tenant role (SPEC 0031). Permissions must be
 * assignable (owner-only abilities are rejected). Name unique within the tenant.
 */
class RoleWriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller enforces `team.manage` via Gate::authorize
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        $roleId = $this->route('role'); // raw id on update, null on store

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->where('tenant_id', $tenantId)->ignore($roleId)],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::assignable())],
        ];
    }
}
