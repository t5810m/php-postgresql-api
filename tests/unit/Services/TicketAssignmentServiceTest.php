<?php

namespace Tests\Unit\Services;

use App\Models\TicketAssignmentModel;
use App\Models\TicketModel;
use App\Models\TicketHistoryModel;
use App\Models\DepartmentModel;
use App\Models\UserModel;
use App\Services\TicketAssignmentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketAssignmentServiceTest extends CIUnitTestCase
{
    private TicketAssignmentService $service;
    private TicketAssignmentModel $assignmentModel;
    private TicketModel $ticketModel;
    private TicketHistoryModel $historyModel;
    private int $testUserId;
    private int $testDepartmentId;
    private int $testUser2Id;
    private int $testUser3Id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentModel = new TicketAssignmentModel();
        $this->ticketModel = new TicketModel();
        $this->historyModel = new TicketHistoryModel();
        $this->service = new TicketAssignmentService(
            $this->assignmentModel,
            $this->ticketModel,
            $this->historyModel
        );

        // Create test dependencies
        $deptModel = new DepartmentModel();
        $this->testDepartmentId = $deptModel->insert([
            'name' => 'TestDept_' . uniqid(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $userModel = new UserModel();
        $this->testUserId = $userModel->insert([
            'name' => 'Test User',
            'email' => 'testuser_' . uniqid() . '@test.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Create additional test users for assignment tests
        $this->testUser2Id = $userModel->insert([
            'name' => 'Test User 2',
            'email' => 'testuser2_' . uniqid() . '@test.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $this->testUser3Id = $userModel->insert([
            'name' => 'Test User 3',
            'email' => 'testuser3_' . uniqid() . '@test.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'phone' => '1234567890',
            'department_id' => $this->testDepartmentId,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Find tickets created by test users
        $ticketIds = $this->ticketModel->db->table('tickets')
            ->whereIn('submitted_by', [$this->testUserId, $this->testUser2Id, $this->testUser3Id])
            ->select('id')->get()->getResultArray();
        $ids = array_column($ticketIds, 'id');

        if (!empty($ids)) {
            $this->ticketModel->db->table('ticket_assignments')->whereIn('ticket_id', $ids)->delete();
            $this->ticketModel->db->table('ticket_comments')->whereIn('ticket_id', $ids)->delete();
            $this->ticketModel->db->table('ticket_attachments')->whereIn('ticket_id', $ids)->delete();
            $this->ticketModel->db->table('ticket_history')->whereIn('ticket_id', $ids)->delete();
            $this->ticketModel->db->table('tickets')->whereIn('id', $ids)->delete();
        }
        $this->ticketModel->db->table('users')->where("email LIKE '%@test.com%'")->delete();
        $this->ticketModel->db->table('departments')->where("name LIKE 'TestDept_%'")->delete();
    }

    private function createTicket(): int
    {
        return $this->ticketModel->insert([
            'subject' => 'Test Ticket',
            'description' => 'Test',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);
    }

    public function testCreateAssignmentWithValidData(): void
    {
        $ticketId = $this->createTicket();

        $data = [
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ];

        $result = $this->service->create($data, $this->testUserId);

        $this->assertIsArray($result);
        $this->assertEquals($ticketId, $result['ticket_id']);
        $this->assertEquals($this->testUser2Id, $result['assigned_to_id']);
    }

    public function testCreateAssignmentSyncsTicketAssignedToId(): void
    {
        $ticketId = $this->createTicket();

        $data = [
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ];

        $this->service->create($data, $this->testUserId);

        $ticket = $this->ticketModel->find($ticketId);

        $this->assertEquals($this->testUser2Id, $ticket['assigned_to_id']);
    }

    public function testCreateAssignmentRecordsHistory(): void
    {
        $ticketId = $this->createTicket();

        $data = [
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ];

        $this->service->create($data, $this->testUserId);

        $history = $this->historyModel->where('ticket_id', $ticketId)
            ->where('action', 'ticket_assigned')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('ticket_assigned', $history['action']);
        $this->assertEquals($this->testUserId, $history['user_id']);
    }

    public function testCreateAssignmentWithMissingTicketId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'assigned_to_id' => $this->testUser2Id,
        ];

        $this->service->create($data, $this->testUserId);
    }

    public function testUpdateAssignmentChangesAssignee(): void
    {
        $ticketId = $this->createTicket();

        $created = $this->service->create([
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ], $this->testUserId);

        $updated = $this->service->update($created['id'], [
            'assigned_to_id' => $this->testUser3Id,
        ], $this->testUserId);

        $this->assertEquals($this->testUser3Id, $updated['assigned_to_id']);

        $ticket = $this->ticketModel->find($ticketId);
        $this->assertEquals($this->testUser3Id, $ticket['assigned_to_id']);
    }

    public function testUpdateAssignmentWithoutChangingAssigneeDoesNotSyncTicket(): void
    {
        $ticketId = $this->createTicket();

        $created = $this->service->create([
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ], $this->testUserId);

        // Update without changing assigned_to_id
        $this->service->update($created['id'], [], $this->testUserId);

        $ticket = $this->ticketModel->find($ticketId);
        $this->assertEquals($this->testUser2Id, $ticket['assigned_to_id']);
    }

    public function testUpdateNonExistentAssignment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['assigned_to_id' => $this->testUser2Id], 1);
    }

    public function testDeleteAssignmentRemovesTicketAssignment(): void
    {
        $ticketId = $this->createTicket();

        $created = $this->service->create([
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ], $this->testUserId);

        $this->service->delete($created['id']);

        $ticket = $this->ticketModel->find($ticketId);
        $this->assertNull($ticket['assigned_to_id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testFindAssignment(): void
    {
        $ticketId = $this->createTicket();

        $created = $this->service->create([
            'ticket_id' => $ticketId,
            'assigned_to_id' => $this->testUser2Id,
        ], $this->testUserId);

        $found = $this->service->findOrFail($created['id']);

        $this->assertEquals($ticketId, $found['ticket_id']);
        $this->assertEquals($this->testUser2Id, $found['assigned_to_id']);
    }

    public function testFindNonExistentAssignment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testDeleteNonExistentAssignment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }
}
