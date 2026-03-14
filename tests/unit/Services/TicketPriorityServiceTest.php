<?php

namespace Tests\Unit\Services;

use App\Models\TicketPriorityModel;
use App\Services\TicketPriorityService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketPriorityServiceTest extends CIUnitTestCase
{
    private TicketPriorityService $service;
    private TicketPriorityModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TicketPriorityModel();
        $this->service = new TicketPriorityService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete priorities - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreatePriorityWithValidData(): void
    {
        $data = [
            'name' => 'high_' . uniqid(),
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreatePriorityWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [];

        $this->service->create($data, 1);
    }

    public function testUpdatePriorityWithValidData(): void
    {
        $created = $this->service->create(['name' => 'old_priority_' . uniqid()], 1);

        $newName = 'new_priority_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentPriority(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingPriority(): void
    {
        $created = $this->service->create(['name' => 'delete_priority_' . uniqid()], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentPriority(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingPriority(): void
    {
        $name = 'find_priority_' . uniqid();
        $created = $this->service->create(['name' => $name], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentPriority(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdatePriorityAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'priority_' . uniqid()], 1);

        $newName = 'updated_priority_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
