<?php

namespace Tests\Unit\Services;

use App\Models\UserModel;
use App\Models\DepartmentModel;
use App\Services\UserService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class UserServiceTest extends CIUnitTestCase
{
    private UserService $service;
    private UserModel $model;
    private DepartmentModel $departmentModel;
    private int $testDepartmentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserModel();
        $this->departmentModel = new DepartmentModel();
        $this->service = new UserService($this->model);

        // Create a test department for FK references
        $this->testDepartmentId = $this->departmentModel->insert([
            'name' => 'TestDept_' . uniqid(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test data - delete only test-created records
        // Get test user IDs
        $testUserIds = $this->model->db->table('users')
            ->select('id')
            ->where("email LIKE '%@example.com' OR email LIKE '%@test.com'")
            ->where('id >', 1)
            ->get()->getResultArray();

        if (!empty($testUserIds)) {
            $userIds = array_column($testUserIds, 'id');
            $this->model->db->table('user_roles')->whereIn('user_id', $userIds)->delete();
            $this->model->db->table('users')->whereIn('id', $userIds)->delete();
        }

        $this->model->db->table('departments')
            ->where("name LIKE 'TestDept_%'")
            ->delete();
    }

    public function testCreateUserWithValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'securepassword123',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals($data['email'], $result['email']);
        $this->assertNotEquals('securepassword123', $result['password']);
        $this->assertTrue(password_verify('securepassword123', $result['password']));
    }

    public function testCreateUserPasswordIsHashed(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'plaintext',
        ];

        $result = $this->service->create($data, 1);

        $this->assertNotEquals('plaintext', $result['password']);
        $this->assertTrue(password_verify('plaintext', $result['password']));
    }

    public function testCreateUserWithoutPassword(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'No Password User',
            'email' => 'nopass_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
        ];

        $this->service->create($data, 1);
    }

    public function testCreateUserWithMissingEmail(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'Missing Email',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'password123',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateUserPassword(): void
    {
        $created = $this->service->create([
            'name' => 'User',
            'email' => 'user_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'oldpassword',
        ], 1);

        $updated = $this->service->update($created['id'], [
            'email' => 'user_updated_' . uniqid() . '@example.com',
            'password' => 'newpassword123',
        ], 1);

        $this->assertTrue(password_verify('newpassword123', $updated['password']));
        $this->assertFalse(password_verify('oldpassword', $updated['password']));
    }

    public function testUpdateUserRemovesPasswordFieldIfEmpty(): void
    {
        $created = $this->service->create([
            'name' => 'User',
            'email' => 'user_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'original',
        ], 1);

        $updated = $this->service->update($created['id'], [
            'name' => 'Updated Name',
            'email' => 'user_updated2_' . uniqid() . '@example.com',
        ], 1);

        // Password should remain unchanged
        $this->assertTrue(password_verify('original', $updated['password']));
        $this->assertEquals('Updated Name', $updated['name']);
    }

    public function testUpdateNonExistentUser(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteUser(): void
    {
        $created = $this->service->create([
            'name' => 'To Delete',
            'email' => 'delete_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'password123',
        ], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testFindUser(): void
    {
        $created = $this->service->create([
            'name' => 'Find Me',
            'email' => 'findme_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'password123',
        ], 1);

        $found = $this->service->findOrFail($created['id']);

        $this->assertEquals('Find Me', $found['name']);
        $this->assertEquals($created['id'], $found['id']);
    }
}
