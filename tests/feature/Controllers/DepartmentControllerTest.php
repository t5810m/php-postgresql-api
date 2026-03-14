<?php

namespace Tests\Feature\Controllers;

use App\Models\DepartmentModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class DepartmentControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    private DepartmentModel $model;

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
        $this->model = new DepartmentModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test data - delete in proper FK order
        $this->model->db->table('departments')->where('id IS NOT NULL')->delete();
    }

    public function testGetAllDepartments(): void
    {
        // Create test data
        $this->model->insert(['name' => 'Department 1']);
        $this->model->insert(['name' => 'Department 2']);

        $result = $this->get('/api/v1/departments');

        $this->assertTrue($result->isOK());
    }

    public function testGetSingleDepartment(): void
    {
        $id = $this->model->insert([
            'name' => 'Test Department',
        ]);

        $result = $this->get("/api/v1/departments/$id");

        $this->assertTrue($result->isOK());
    }

    public function testGetNonExistentDepartment(): void
    {
        $result = $this->get('/api/v1/departments/99999');

        $result->assertStatus(404);
    }

    public function testCreateDepartmentWithValidData(): void
    {
        $data = [
            'name' => 'New Department_' . uniqid(),
            'description' => 'A new department',
        ];

        $result = $this->postJson('/api/v1/departments', $data);

        $result->assertStatus(201);
    }

    public function testCreateDepartmentWithMissingRequiredField(): void
    {
        $data = [
            'description' => 'Missing name',
        ];

        $result = $this->postJson('/api/v1/departments', $data);

        $result->assertStatus(400);
    }

    public function testUpdateDepartmentWithValidData(): void
    {
        $id = $this->model->insert([
            'name' => 'Original Name_' . uniqid(),
        ]);

        $newName = 'Updated Name_' . uniqid();
        $data = [
            'name' => $newName,
        ];

        $result = $this->putJson("/api/v1/departments/$id", $data);

        $result->assertStatus(200);

        // Verify name was actually updated in database
        $updated = $this->model->find($id);
        $this->assertEquals($newName, $updated['name']);
    }

    public function testUpdateNonExistentDepartment(): void
    {
        $data = [
            'name' => 'Updated',
        ];

        $result = $this->putJson('/api/v1/departments/99999', $data);

        $result->assertStatus(404);
    }

    public function testDeleteDepartment(): void
    {
        $id = $this->model->insert([
            'name' => 'To Delete',
        ]);

        $result = $this->delete("/api/v1/departments/$id");

        $this->assertTrue($result->isOK());

        // Verify deletion
        $this->assertNull($this->model->find($id));
    }

    public function testDeleteNonExistentDepartment(): void
    {
        $result = $this->delete('/api/v1/departments/99999');

        $result->assertStatus(404);
    }

    public function testSearchDepartments(): void
    {
        $this->model->insert(['name' => 'Engineering']);
        $this->model->insert(['name' => 'Sales']);
        $this->model->insert(['name' => 'Marketing']);

        $result = $this->get('/api/v1/departments?search=Engineering');

        $this->assertTrue($result->isOK());
    }

    public function testPaginationParameters(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->model->insert([
                'name' => "Department $i",
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }

        $result = $this->get('/api/v1/departments?page=2&per_page=10');

        $this->assertTrue($result->isOK());
    }
}
