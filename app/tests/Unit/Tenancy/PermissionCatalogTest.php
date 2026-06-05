<?php

namespace Tests\Unit\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Support\PermissionCatalog;
use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_keys_are_unique(): void
    {
        $all = PermissionCatalog::all();
        $this->assertSame(array_values(array_unique($all)), $all, 'Duplicate permission keys in the catalog.');
    }

    public function test_owner_only_subset_of_all_and_excluded_from_assignable(): void
    {
        foreach (PermissionCatalog::OWNER_ONLY as $perm) {
            $this->assertContains($perm, PermissionCatalog::all());
            $this->assertNotContains($perm, PermissionCatalog::assignable());
            $this->assertFalse(PermissionCatalog::isAssignable($perm));
        }
    }

    public function test_groups_shape(): void
    {
        foreach (PermissionCatalog::groups() as $group) {
            $this->assertArrayHasKey('key', $group);
            $this->assertArrayHasKey('label', $group);
            $this->assertNotEmpty($group['permissions']);
            foreach ($group['permissions'] as $perm) {
                $this->assertArrayHasKey('key', $perm);
                $this->assertArrayHasKey('label', $perm);
                $this->assertContains($perm['type'], ['view', 'action']);
            }
        }
    }

    public function test_team_manage_is_assignable(): void
    {
        $this->assertTrue(PermissionCatalog::isAssignable('team.manage'));
    }
}
