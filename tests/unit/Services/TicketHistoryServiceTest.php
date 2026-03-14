<?php

namespace Tests\Unit\Services;

use App\Models\TicketHistoryModel;
use App\Models\TicketModel;
use App\Services\TicketHistoryService;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketHistoryServiceTest extends CIUnitTestCase
{
    private TicketHistoryService $service;
    private TicketHistoryModel $historyModel;
    private TicketModel $ticketModel;
    private int $testTicketId;
    private int $testHistoryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->historyModel = new TicketHistoryModel();
        $this->ticketModel  = new TicketModel();
        $this->service      = new TicketHistoryService();

        // Ensure system user exists
        $this->historyModel->db->query(
            "INSERT INTO users (id, name, email, password, created_by, updated_by)
             VALUES (1, 'System', 'system@system.com', '', 1, 1) ON CONFLICT (id) DO NOTHING"
        );

        // Create a test ticket
        $this->testTicketId = $this->ticketModel->insert([
            'subject'     => 'Test Ticket for History',
            'description' => 'Test',
            'submitted_by' => 1,
            'status_id'   => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'created_by'  => 1,
            'updated_by'  => 1,
        ]);

        // Insert a history entry directly (bypassing service - history is system-written)
        $this->testHistoryId = $this->historyModel->insert([
            'ticket_id'  => $this->testTicketId,
            'action'     => 'ticket_created',
            'user_id'    => 1,
            'details'    => 'Test history entry',
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->historyModel->db->table('ticket_history')->where('ticket_id', $this->testTicketId)->delete();
        $this->ticketModel->db->table('tickets')->where('id', $this->testTicketId)->delete();
    }

    public function testFindExistingHistory(): void
    {
        $result = $this->service->findOrFail($this->testHistoryId);

        $this->assertIsArray($result);
        $this->assertEquals($this->testHistoryId, $result['id']);
        $this->assertEquals('ticket_created', $result['action']);
    }

    public function testFindNonExistentHistoryThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testFindHistoryIncludesTicketSubject(): void
    {
        $result = $this->service->findOrFail($this->testHistoryId);

        $this->assertArrayHasKey('ticket_subject', $result);
        $this->assertEquals('Test Ticket for History', $result['ticket_subject']);
    }

    public function testFindHistoryIncludesUserName(): void
    {
        $result = $this->service->findOrFail($this->testHistoryId);

        $this->assertArrayHasKey('user_name', $result);
        $this->assertEquals('System', $result['user_name']);
    }

    public function testFindHistoryWithNullUserIdDoesNotFail(): void
    {
        $historyId = $this->historyModel->insert([
            'ticket_id'  => $this->testTicketId,
            'action'     => 'no_user_action',
            'user_id'    => null,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $result = $this->service->findOrFail($historyId);

        // user_name is not added when user_id is null
        $this->assertArrayNotHasKey('user_name', $result);
    }
}
