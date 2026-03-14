<?php

namespace Tests\Unit\Services;

use App\Models\TicketCategoryModel;
use App\Services\TicketCategoryService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketCategoryServiceTest extends CIUnitTestCase
{
    private TicketCategoryService $service;
    private TicketCategoryModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TicketCategoryModel();
        $this->service = new TicketCategoryService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete categories - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreateCategoryWithValidData(): void
    {
        $data = [
            'name' => 'bug_' . uniqid(),
            'description' => 'Bug reports',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals('Bug reports', $result['description']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateCategoryWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'description' => 'Missing name field',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateCategoryWithValidData(): void
    {
        $created = $this->service->create(['name' => 'old_cat_' . uniqid()], 1);

        $newName = 'new_cat_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentCategory(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingCategory(): void
    {
        $created = $this->service->create(['name' => 'delete_cat_' . uniqid()], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentCategory(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingCategory(): void
    {
        $name = 'find_cat_' . uniqid();
        $created = $this->service->create(['name' => $name], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentCategory(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateCategoryAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'cat_' . uniqid(), 'description' => 'Original'], 1);

        $newName = 'updated_cat_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals('Original', $result['description']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
