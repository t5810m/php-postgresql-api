<?php

namespace Tests\Unit\Services;

use App\Models\LocationModel;
use App\Services\LocationService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class LocationServiceTest extends CIUnitTestCase
{
    private LocationService $service;
    private LocationModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new LocationModel();
        $this->service = new LocationService($this->model);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Don't delete locations - they're shared lookup tables with no test-specific data
        // Teardown is only needed for non-lookup tables
    }

    public function testCreateLocationWithValidData(): void
    {
        $data = [
            'name' => 'New York_' . uniqid(),
            'country' => 'United States',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals('United States', $result['country']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateLocationWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'country' => 'Missing name field',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateLocationWithValidData(): void
    {
        $created = $this->service->create(['name' => 'Old_' . uniqid(), 'country' => 'Old Country'], 1);

        $newName = 'New_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 1);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentLocation(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['name' => 'Test'], 1);
    }

    public function testDeleteExistingLocation(): void
    {
        $created = $this->service->create(['name' => 'ToDelete_' . uniqid(), 'country' => 'Test'], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentLocation(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingLocation(): void
    {
        $name = 'FindMe_' . uniqid();
        $created = $this->service->create(['name' => $name, 'country' => 'Test'], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($name, $result['name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentLocation(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateLocationAllowsOptionalFields(): void
    {
        $created = $this->service->create(['name' => 'Test_' . uniqid(), 'country' => 'Original'], 1);

        $newName = 'Updated_' . uniqid();
        $result = $this->service->update($created['id'], ['name' => $newName], 2);

        $this->assertEquals($newName, $result['name']);
        $this->assertEquals('Original', $result['country']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
