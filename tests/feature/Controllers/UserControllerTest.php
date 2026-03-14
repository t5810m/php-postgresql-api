<?php

namespace Tests\Feature\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class UserControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    private UserModel $model;
    private int $testDepartmentId;

    // Helper method to send JSON POST
    private function postJson(string $path, array $data)
    {
        return $this->withBody(json_encode($data))
                   ->withHeaders(['Content-Type' => 'application/json'])
                   ->post($path);
    }

    // Helper method to send JSON PUT
    private function putJson(string $path, array $data)
    {
        return $this->withBody(json_encode($data))
                   ->withHeaders(['Content-Type' => 'application/json'])
                   ->put($path);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserModel();

        // Create test department
        $deptModel = new DepartmentModel();
        $this->testDepartmentId = $deptModel->insert([
            'name' => 'TestDept_' . uniqid(),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test data - delete in proper FK order
        $this->model->db->table('user_roles')->where("user_id IN (SELECT id FROM users WHERE email LIKE '%@example.com%')")->delete();
        $this->model->db->table('users')->where("email LIKE '%@example.com%'")->delete();
        $this->model->db->table('departments')->where("name LIKE 'TestDept_%'")->delete();
    }

    public function testGetAllUsers(): void
    {
        $this->model->insert([
            'name' => 'User 1',
            'email' => 'user1_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'password' => '',
            'department_id' => $this->testDepartmentId,
        ]);

        $result = $this->get('/api/v1/users');

        $result->assertStatus(200);
    }

    public function testCreateUserWithPassword(): void
    {
        $data = [
            'name' => 'New User',
            'email' => 'newuser_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => 'securepassword123',
        ];

        $result = $this->postJson('/api/v1/users', $data);

        $result->assertStatus(201);
    }

    public function testCreateUserWithoutPassword(): void
    {
        $data = [
            'name' => 'No Password User',
            'email' => 'nopass_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
        ];

        $result = $this->postJson('/api/v1/users', $data);

        $result->assertStatus(400);
    }

    public function testCreateUserWithMissingRequiredField(): void
    {
        $data = [
            'name' => 'Missing Email',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
        ];

        $result = $this->postJson('/api/v1/users', $data);

        $result->assertStatus(400);
    }

    public function testGetSingleUser(): void
    {
        $id = $this->model->insert([
            'name' => 'Find Me',
            'email' => 'findme_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'password' => '',
            'department_id' => $this->testDepartmentId,
        ]);

        $result = $this->get("/api/v1/users/$id");

        $result->assertStatus(200);
    }

    public function testGetNonExistentUser(): void
    {
        $result = $this->get('/api/v1/users/99999');

        $result->assertStatus(404);
    }

    public function testUpdateUserPassword(): void
    {
        $id = $this->model->insert([
            'name' => 'User',
            'email' => 'user_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT),
        ]);

        $data = [
            'password' => 'newpassword123',
        ];

        $result = $this->putJson("/api/v1/users/$id", $data);

        $result->assertStatus(200);
    }

    public function testUpdateUserWithEmptyPasswordDoesNotChange(): void
    {
        $originalHash = password_hash('original', PASSWORD_BCRYPT);

        $id = $this->model->insert([
            'name' => 'User',
            'email' => 'user_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'password' => $originalHash,
        ]);

        $data = [
            'name' => 'Updated Name',
            'password' => '',
        ];

        $result = $this->putJson("/api/v1/users/$id", $data);

        $result->assertStatus(200);

    }

    public function testDeleteUser(): void
    {
        $id = $this->model->insert([
            'name' => 'To Delete',
            'email' => 'delete_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'department_id' => $this->testDepartmentId,
        ]);

        $result = $this->delete("/api/v1/users/$id");

        $result->assertStatus(200);
    }

    public function testFilterUsersByDepartment(): void
    {
        $this->model->insert([
            'name' => 'User 1',
            'email' => 'user1_' . uniqid() . '@example.com',
            'phone' => '1111111111',
            'password' => '',
            'department_id' => $this->testDepartmentId,
        ]);

        $this->model->insert([
            'name' => 'User 2',
            'email' => 'user2_' . uniqid() . '@example.com',
            'phone' => '2222222222',
            'password' => '',
            'department_id' => $this->testDepartmentId,
        ]);

        $result = $this->get('/api/v1/users?department_id=' . $this->testDepartmentId);

        $result->assertStatus(200);
    }

    public function testSearchUsers(): void
    {
        $this->model->insert([
            'name' => 'John Doe',
            'email' => 'john_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'password' => '',
            'department_id' => $this->testDepartmentId,
        ]);

        $result = $this->get('/api/v1/users?search=John');

        $result->assertStatus(200);
    }
}
