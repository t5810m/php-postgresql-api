<?php

namespace Tests\Unit\Services;

use App\Models\RolePermissionModel;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Services\RolePermissionService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

class RolePermissionServiceTest extends CIUnitTestCase
{
    private RolePermissionService $service;
    private RolePermissionModel $model;
    private RoleModel $roleModel;
    private PermissionModel $permissionModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new RolePermissionModel();
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->service = new RolePermissionService($this->model);

        // Ensure system user exists
        $this->model->db->query("INSERT INTO users (id, name, email, password, created_by, updated_by)
            VALUES (1, 'System', 'system@system.com', '', 1, 1) ON CONFLICT (id) DO NOTHING");

        // Disable FK constraints for this test
        $this->model->db->query("SET session_replication_role = REPLICA");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->model->db->table('role_permissions')->where('id IS NOT NULL')->delete();
        $this->roleModel->db->table('roles')->where("name LIKE 'test_role_%'")->delete();
        $this->permissionModel->db->table('permissions')->where("name LIKE 'test_perm_%'")->delete();

        // Re-enable FK constraints
        $this->model->db->query("SET session_replication_role = DEFAULT");
    }

    public function testCreateRolePermissionWithValidData(): void
    {
        // Create role and permission first
        $roleName = 'test_role_' . uniqid();
        $permName = 'test_perm_' . uniqid();

        $role = $this->roleModel->insert([
            'name' => $roleName,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $permission = $this->permissionModel->insert([
            'name' => $permName,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $data = [
            'role_id' => $role,
            'permission_id' => $permission,
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($role, $result['role_id']);
        $this->assertEquals($permission, $result['permission_id']);
        $this->assertEquals($roleName, $result['role_name']);
        $this->assertEquals($permName, $result['permission_name']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateRolePermissionWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'role_id' => 1,
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateRolePermissionWithValidData(): void
    {
        $role1Name = 'role1_' . uniqid();
        $role2Name = 'role2_' . uniqid();
        $permName = 'perm_' . uniqid();

        $role1 = $this->roleModel->insert(['name' => $role1Name, 'created_by' => 1, 'updated_by' => 1]);
        $role2 = $this->roleModel->insert(['name' => $role2Name, 'created_by' => 1, 'updated_by' => 1]);
        $perm = $this->permissionModel->insert(['name' => $permName, 'created_by' => 1, 'updated_by' => 1]);

        $created = $this->service->create(['role_id' => $role1, 'permission_id' => $perm], 1);

        $result = $this->service->update($created['id'], ['role_id' => $role2], 1);

        $this->assertEquals($role2, $result['role_id']);
        $this->assertEquals($role2Name, $result['role_name']);
        $this->assertEquals($permName, $result['permission_name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentRolePermission(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['role_id' => 1], 1);
    }

    public function testDeleteExistingRolePermission(): void
    {
        $role = $this->roleModel->insert(['name' => 'role_' . uniqid(), 'created_by' => 1, 'updated_by' => 1]);
        $perm = $this->permissionModel->insert(['name' => 'perm_' . uniqid(), 'created_by' => 1, 'updated_by' => 1]);

        $created = $this->service->create(['role_id' => $role, 'permission_id' => $perm], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentRolePermission(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingRolePermission(): void
    {
        $roleName = 'role_' . uniqid();
        $permName = 'perm_' . uniqid();

        $role = $this->roleModel->insert(['name' => $roleName, 'created_by' => 1, 'updated_by' => 1]);
        $perm = $this->permissionModel->insert(['name' => $permName, 'created_by' => 1, 'updated_by' => 1]);

        $created = $this->service->create(['role_id' => $role, 'permission_id' => $perm], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($role, $result['role_id']);
        $this->assertEquals($perm, $result['permission_id']);
        $this->assertEquals($roleName, $result['role_name']);
        $this->assertEquals($permName, $result['permission_name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentRolePermission(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }
}
