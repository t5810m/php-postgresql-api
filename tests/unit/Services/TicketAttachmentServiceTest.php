<?php

namespace Tests\Unit\Services;

use App\Models\TicketAttachmentModel;
use App\Models\TicketModel;
use App\Services\TicketAttachmentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketAttachmentServiceTest extends CIUnitTestCase
{
    private TicketAttachmentService $service;
    private TicketAttachmentModel $model;
    private TicketModel $ticketModel;
    private int $testTicketId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TicketAttachmentModel();
        $this->ticketModel = new TicketModel();
        $this->service = new TicketAttachmentService($this->model);

        // Ensure system user and basic data exist
        $this->model->db->query("INSERT INTO users (id, name, email, password, created_by, updated_by)
            VALUES (1, 'System', 'system@system.com', '', 1, 1) ON CONFLICT (id) DO NOTHING");

        // Create a test ticket (with required FK fields)
        $this->testTicketId = $this->ticketModel->insert([
            'subject' => 'Test Ticket for Attachments',
            'description' => 'Test',
            'submitted_by' => 1,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->model->db->table('ticket_attachments')->where('ticket_id', $this->testTicketId)->delete();
        $this->ticketModel->db->table('tickets')->where('id', $this->testTicketId)->delete();
    }

    public function testCreateAttachmentWithValidData(): void
    {
        $data = [
            'ticket_id' => $this->testTicketId,
            'file_name' => 'document_' . uniqid() . '.pdf',
            'file_path' => '/uploads/ticket_' . uniqid() . '.pdf',
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['file_name'], $result['file_name']);
        $this->assertEquals($data['file_path'], $result['file_path']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateAttachmentWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'file_name' => 'test.pdf',
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateAttachmentWithValidData(): void
    {
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'file_name' => 'old_' . uniqid() . '.pdf',
            'file_path' => '/uploads/old_' . uniqid() . '.pdf',
        ], 1);

        $newName = 'new_' . uniqid() . '.pdf';
        $result = $this->service->update($created['id'], ['file_name' => $newName], 1);

        $this->assertEquals($newName, $result['file_name']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentAttachment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['file_name' => 'test.pdf'], 1);
    }

    public function testDeleteExistingAttachment(): void
    {
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'file_name' => 'delete_' . uniqid() . '.pdf',
            'file_path' => '/uploads/delete_' . uniqid() . '.pdf',
        ], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentAttachment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingAttachment(): void
    {
        $fileName = 'find_' . uniqid() . '.pdf';
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'file_name' => $fileName,
            'file_path' => '/uploads/find_' . uniqid() . '.pdf',
        ], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($fileName, $result['file_name']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentAttachment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateAttachmentAllowsOptionalFields(): void
    {
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'file_name' => 'test_' . uniqid() . '.pdf',
            'file_path' => '/uploads/original_' . uniqid() . '.pdf',
        ], 1);

        $newFileName = 'updated_' . uniqid() . '.pdf';
        $result = $this->service->update($created['id'], ['file_name' => $newFileName], 2);

        $this->assertEquals($newFileName, $result['file_name']);
        $this->assertEquals($created['file_path'], $result['file_path']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
