<?php

namespace CMBcoreSeller\Modules\Tenancy\Support;

use CMBcoreSeller\Modules\Tenancy\Enums\Role;

/**
 * Default roles seeded into every tenant. Bridges the legacy {@see Role} enum to
 * concrete permission lists stored on the `roles` table, so:
 *  - existing members keep identical permissions after migration, and
 *  - newly created tenants get the same starter set (owner can rename/edit/delete
 *    any preset except the built-in owner role).
 *
 * Owner is special (is_owner ⇒ bypasses every check). Admin = every assignable
 * permission (i.e. all but owner-only). The rest mirror Role::permissions() verbatim.
 */
final class RolePresets
{
    /** Enum cases seeded as editable presets, in display order. */
    private const PRESET_ROLES = [
        Role::Admin,
        Role::StaffOrder,
        Role::StaffWarehouse,
        Role::StaffCs,
        Role::Accountant,
        Role::Viewer,
    ];

    /**
     * @return list<array{key:string, name:string, is_owner:bool, permissions:list<string>}>
     */
    public static function defaults(): array
    {
        $out = [[
            'key' => Role::Owner->value,
            'name' => Role::Owner->label(),
            'is_owner' => true,
            'permissions' => ['*'],
        ]];

        foreach (self::PRESET_ROLES as $role) {
            $out[] = [
                'key' => $role->value,
                'name' => $role->label(),
                'is_owner' => false,
                'permissions' => self::permissionsFor($role),
            ];
        }

        return $out;
    }

    /**
     * Concrete permission list for a preset role (no '*' / '!' tokens, except owner).
     *
     * @return list<string>
     */
    public static function permissionsFor(Role $role): array
    {
        if ($role === Role::Owner) {
            return ['*'];
        }

        // Admin had ['*', '!owner-only…'] ⇒ every assignable permission.
        if ($role === Role::Admin) {
            return PermissionCatalog::assignable();
        }

        // Staff/viewer presets already list explicit grants.
        return array_values(array_filter(
            $role->permissions(),
            static fn (string $p): bool => $p !== '*' && ! str_starts_with($p, '!'),
        ));
    }
}
