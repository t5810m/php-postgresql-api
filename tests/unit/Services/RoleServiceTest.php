<?php

namespace Tests\Unit\Services;

use App\Models\RoleModel;
use App\Services\RoleService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class RoleServiceTest extends CIUnitTestCase
{
    private RoleService $service;
    private RoleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new RoleModel();
        $this->service = new RoleService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete roles - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreateRoleWithValidData(): void
    {
        $data = [
            'name' => 'admin_' . uniqid(),
            'description' => 'Administrator role',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals('Administrator role', $result['description']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateRoleWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'description' => 'Missing name field',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateRoleWithValidData(): void
    {
        $created = $this->service->create(['name' => 'old_role_' . uniqid()], 1);

        $newName = 'new_role_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentRole(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingRole(): void
    {
        $created = $this->service->create(['name' => 'delete_role_' . uniqid()], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentRole(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingRole(): void
    {
        $name = 'find_role_' . uniqid();
        $created = $this->service->create(['name' => $name], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentRole(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateRoleAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'role_' . uniqid(), 'description' => 'Original'], 1);

        $newName = 'updated_role_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals('Original', $result['description']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
