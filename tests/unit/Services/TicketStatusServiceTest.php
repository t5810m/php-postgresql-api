<?php

namespace Tests\Unit\Services;

use App\Models\TicketStatusModel;
use App\Services\TicketStatusService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketStatusServiceTest extends CIUnitTestCase
{
    private TicketStatusService $service;
    private TicketStatusModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TicketStatusModel();
        $this->service = new TicketStatusService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete statuses - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreateStatusWithValidData(): void
    {
        $data = [
            'name' => 'open_' . uniqid(),
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateStatusWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [];

        $this->service->create($data, 1);
    }

    public function testUpdateStatusWithValidData(): void
    {
        $created = $this->service->create(['name' => 'old_status_' . uniqid()], 1);

        $newName = 'new_status_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentStatus(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingStatus(): void
    {
        $created = $this->service->create(['name' => 'delete_status_' . uniqid()], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentStatus(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingStatus(): void
    {
        $name = 'find_status_' . uniqid();
        $created = $this->service->create(['name' => $name], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentStatus(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateStatusAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'status_' . uniqid()], 1);

        $newName = 'updated_status_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
