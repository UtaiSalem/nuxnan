<?php

namespace Tests\Feature;

use App\Models\AcademyPermission;
use Tests\TestCase;

class GuardianPermissionKeysTest extends TestCase
{
    public function test_all_permissions_have_guardian_keys(): void
    {
        $keys = array_column(AcademyPermission::getAllPermissions(), 'name');

        $this->assertContains('guardians.view', $keys);
        $this->assertContains('guardians.manage', $keys);
        $this->assertContains('guardians.sensitive.view', $keys);
        $this->assertContains('guardians.sensitive.manage', $keys);
        $this->assertContains('guardians.appoint', $keys);
    }

    public function test_department_delegable_keys_contain_guardian_keys(): void
    {
        $keys = AcademyPermission::departmentDelegableKeys();

        $this->assertContains('guardians.view', $keys);
        $this->assertContains('guardians.manage', $keys);
        $this->assertContains('guardians.sensitive.view', $keys);
        $this->assertContains('guardians.sensitive.manage', $keys);
        $this->assertContains('guardians.appoint', $keys);
    }

    public function test_non_delegable_keys_for_guardians_is_empty(): void
    {
        $result = AcademyPermission::nonDelegableDepartmentKeys([
            'guardians.view',
            'guardians.sensitive.manage',
        ]);

        $this->assertEmpty($result);
    }

    public function test_non_delegable_keys_still_restricts_roles_and_groups(): void
    {
        $result = AcademyPermission::nonDelegableDepartmentKeys([
            'roles.manage',
            'groups.manage',
            'members.manage',
        ]);

        $this->assertCount(3, $result);
        $this->assertContains('roles.manage', $result);
        $this->assertContains('groups.manage', $result);
        $this->assertContains('members.manage', $result);
    }
}
