<?php

namespace Tests\Unit\Services;

use App\Models\PermissionModel;
use App\Services\PermissionService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class PermissionServiceTest extends CIUnitTestCase
{
    private PermissionService $service;
    private PermissionModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PermissionModel();
        $this->service = new PermissionService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete permissions - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreatePermissionWithValidData(): void
    {
        $data = [
            'name' => 'create_ticket_' . uniqid(),
            'description' => 'Permission to create tickets',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals('Permission to create tickets', $result['description']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreatePermissionWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'description' => 'Missing name field',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdatePermissionWithValidData(): void
    {
        $created = $this->service->create(['name' => 'old_perm_' . uniqid()], 1);

        $newName = 'new_perm_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentPermission(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingPermission(): void
    {
        $created = $this->service->create(['name' => 'delete_me_' . uniqid()], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentPermission(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingPermission(): void
    {
        $name = 'find_perm_' . uniqid();
        $created = $this->service->create(['name' => $name], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentPermission(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdatePermissionAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'perm_' . uniqid(), 'description' => 'Original'], 1);

        $newName = 'updated_perm_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals('Original', $result['description']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
