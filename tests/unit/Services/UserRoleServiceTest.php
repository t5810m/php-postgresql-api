<?php

namespace Tests\Unit\Services;

use App\Models\UserRoleModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Services\UserRoleService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class UserRoleServiceTest extends CIUnitTestCase
{
    private UserRoleService $service;
    private UserRoleModel $model;
    private UserModel $userModel;
    private RoleModel $roleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserRoleModel();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->service = new UserRoleService($this->model);

        // Ensure system user exists
        $this->model->db->query("INSERT INTO users (id, name, email, password, created_by, updated_by)
            VALUES (1, 'System', 'system@system.com', '', 1, 1) ON CONFLICT (id) DO NOTHING");

        // Disable FK constraints for this test
        $this->model->db->query("SET session_replication_role = REPLICA");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->model->db->table('user_roles')->where('id IS NOT NULL')->delete();
        $this->userModel->db->table('users')->where("email LIKE 'test_%@example.com'")->delete();
        $this->roleModel->db->table('roles')->where("name LIKE 'test_role_%'")->delete();

        // Re-enable FK constraints
        $this->model->db->query("SET session_replication_role = DEFAULT");
    }

    public function testCreateUserRoleWithValidData(): void
    {
        // Create user and role first
        $userName = 'test_user_' . uniqid();
        $roleName = 'test_role_' . uniqid();
        $user = $this->userModel->insert([
            'name' => $userName,
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $role = $this->roleModel->insert([
            'name' => $roleName,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $data = [
            'user_id' => $user,
            'role_id' => $role,
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($user, $result['user_id']);
        $this->assertEquals($role, $result['role_id']);
        $this->assertEquals($userName, $result['user_name']);
        $this->assertEquals($roleName, $result['role_name']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateUserRoleWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'user_id' => 1,
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateUserRoleWithValidData(): void
    {
        $user1 = $this->userModel->insert([
            'name' => 'user1_' . uniqid(),
            'email' => 'user1_' . uniqid() . '@example.com',
            'password' => 'password',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $user2 = $this->userModel->insert([
            'name' => 'user2_' . uniqid(),
            'email' => 'user2_' . uniqid() . '@example.com',
            'password' => 'password',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $role = $this->roleModel->insert([
            'name' => 'role_' . uniqid(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $created = $this->service->create(['user_id' => $user1, 'role_id' => $role], 1);

        $result = $this->service->update($created['id'], ['user_id' => $user2], 1);

        $this->assertEquals($user2, $result['user_id']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentUserRole(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['user_id' => 1], 1);
    }

    public function testDeleteExistingUserRole(): void
    {
        $user = $this->userModel->insert([
            'name' => 'user_' . uniqid(),
            'email' => 'user_' . uniqid() . '@example.com',
            'password' => 'password',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $role = $this->roleModel->insert([
            'name' => 'role_' . uniqid(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $created = $this->service->create(['user_id' => $user, 'role_id' => $role], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentUserRole(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingUserRole(): void
    {
        $userName = 'user_' . uniqid();
        $roleName = 'role_' . uniqid();
        $user = $this->userModel->insert([
            'name' => $userName,
            'email' => 'user_' . uniqid() . '@example.com',
            'password' => 'password',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        $role = $this->roleModel->insert([
            'name' => $roleName,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $created = $this->service->create(['user_id' => $user, 'role_id' => $role], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($user, $result['user_id']);
        $this->assertEquals($role, $result['role_id']);
        $this->assertEquals($userName, $result['user_name']);
        $this->assertEquals($roleName, $result['role_name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentUserRole(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }
}
