<?php

namespace Tests\Unit\Services;

use App\Models\DepartmentModel;
use App\Services\DepartmentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class DepartmentServiceTest extends CIUnitTestCase
{
    private DepartmentService $service;
    private DepartmentModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new DepartmentModel();
        $this->service = new DepartmentService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete departments - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreateDepartmentWithValidData(): void
    {
        $data = [
            'name' => 'Engineering_' . uniqid(),
            'description' => 'Engineering Department',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals('Engineering Department', $result['description']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateDepartmentWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'description' => 'Missing name field',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateDepartmentWithValidData(): void
    {
        // Create first
        $created = $this->service->create(['name' => 'Old_' . uniqid()], 1);

        // Update
        $newName = 'New_' . uniqid() . '_updated';
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentDepartment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingDepartment(): void
    {
        $created = $this->service->create(['name' => 'ToDelete_' . uniqid()], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentDepartment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingDepartment(): void
    {
        $name = 'FindMe_' . uniqid();
        $created = $this->service->create(['name' => $name], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentDepartment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateDepartmentAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'Test_' . uniqid(), 'description' => 'Original'], 1);

        // Update only name, description should remain
        $newName = 'Updated_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals('Original', $result['description']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
