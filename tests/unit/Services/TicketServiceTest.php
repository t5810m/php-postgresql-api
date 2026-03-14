<?php

namespace Tests\Unit\Services;

use App\Models\TicketModel;
use App\Models\TicketHistoryModel;
use App\Models\DepartmentModel;
use App\Models\UserModel;
use App\Services\TicketService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketServiceTest extends CIUnitTestCase
{
    private TicketService $service;
    private TicketModel $ticketModel;
    private TicketHistoryModel $historyModel;
    private int $testUserId;
    private int $testDepartmentId;
    private int $testStatusId = 1;
    private int $testPriorityId = 1;
    private int $testCategoryId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketModel = new TicketModel();
        $this->historyModel = new TicketHistoryModel();
        $this->service = new TicketService($this->ticketModel, $this->historyModel);

        // Ensure system user with ID 1 exists
        $db = \Config\Database::connect('tests');
        $db->query(
            "INSERT INTO users (id, name, email, password, phone, department_id, created_by, updated_by, created_at, updated_at)
             VALUES (1, 'System User', 'system@localhost', ?, '0000000000', 0, 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING",
            [password_hash('password123', PASSWORD_BCRYPT)]
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

        // Ensure lookup tables have required data - use raw SQL with ON CONFLICT
        $db->query(
            "INSERT INTO ticket_statuses (id, name, created_by, updated_by, created_at, updated_at)
             VALUES (1, 'Open', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING"
        );
        $db->query(
            "INSERT INTO ticket_statuses (id, name, created_by, updated_by, created_at, updated_at)
             VALUES (2, 'Closed', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING"
        );
        $db->query(
            "INSERT INTO ticket_priorities (id, name, created_by, updated_by, created_at, updated_at)
             VALUES (1, 'Low', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING"
        );
        $db->query(
            "INSERT INTO ticket_categories (id, name, description, created_by, updated_by, created_at, updated_at)
             VALUES (1, 'Hardware', 'Hardware issues', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING"
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test data - delete in proper FK order
        // IMPORTANT: Only delete records we created, not system seed data

        // Get ticket IDs created in this test
        $testTicketIds = $this->ticketModel->db->table('tickets')
            ->where('department_id', $this->testDepartmentId)
            ->orWhere('submitted_by', $this->testUserId)
            ->select('id')
            ->get()
            ->getResultArray();
        $ticketIds = array_column($testTicketIds, 'id');

        // Delete dependent records for these tickets
        if (!empty($ticketIds)) {
            $this->ticketModel->db->table('ticket_assignments')->whereIn('ticket_id', $ticketIds)->delete();
            $this->ticketModel->db->table('ticket_comments')->whereIn('ticket_id', $ticketIds)->delete();
            $this->ticketModel->db->table('ticket_attachments')->whereIn('ticket_id', $ticketIds)->delete();
            $this->ticketModel->db->table('ticket_history')->whereIn('ticket_id', $ticketIds)->delete();
            $this->ticketModel->db->table('tickets')->whereIn('id', $ticketIds)->delete();
        }

        // Delete test users (those with @test.com email)
        $this->ticketModel->db->table('users')->where("email LIKE '%@test.com%'")->delete();

        // Delete test departments (those with TestDept_ prefix)
        $this->ticketModel->db->table('departments')->where("name LIKE '%TestDept_%'")->delete();
    }

    public function testCreateTicketWithValidData(): void
    {
        $data = [
            'subject' => 'Network Issue',
            'description' => 'Internet is down',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
        ];

        $result = $this->service->create($data, $this->testUserId);

        $this->assertIsArray($result);
        $this->assertEquals('Network Issue', $result['subject']);
        $this->assertEquals($this->testUserId, $result['created_by']);
    }

    public function testCreateTicketRecordsHistory(): void
    {
        $data = [
            'subject' => 'Test Ticket',
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
        ];

        $created = $this->service->create($data, $this->testUserId);

        $history = $this->historyModel->where('ticket_id', $created['id'])->first();

        $this->assertNotNull($history);
        $this->assertEquals('ticket_created', $history['action']);
        $this->assertEquals($this->testUserId, $history['user_id']);
    }

    public function testCreateTicketWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'description' => 'Missing subject',
            'submitted_by' => $this->testUserId,
        ];

        $this->service->create($data, $this->testUserId);
    }

    public function testUpdateTicketStatusChangeRecordsHistory(): void
    {
        $created = $this->service->create([
            'subject' => 'Status Change Test',
            'description' => 'Test',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
        ], $this->testUserId);

        $this->service->update($created['id'], [
            'status_id' => 2,
        ], $this->testUserId);

        $history = $this->historyModel->where('ticket_id', $created['id'])
            ->where('action', 'status_changed')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($this->testUserId, $history['user_id']);
    }

    public function testUpdateTicketAssignmentChangeRecordsHistory(): void
    {
        // Create second user for assignment
        $userModel = new UserModel();
        $testUser2Id = $userModel->insert([
            'name' => 'Test User 2',
            'email' => 'testuser2_' . uniqid() . '@test.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'phone' => '9876543210',
            'department_id' => $this->testDepartmentId,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $created = $this->service->create([
            'subject' => 'Assignment Test',
            'description' => 'Test',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
            'assigned_to_id' => $this->testUserId,
        ], $this->testUserId);

        $this->service->update($created['id'], [
            'assigned_to_id' => $testUser2Id,
        ], $this->testUserId);

        $history = $this->historyModel->where('ticket_id', $created['id'])
            ->where('action', 'reassigned')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('reassigned', $history['action']);
        $this->assertEquals($this->testUserId, $history['user_id']);
    }

    public function testUpdateTicketWithoutStatusChangeDoesNotRecordStatusHistory(): void
    {
        $created = $this->service->create([
            'subject' => 'No Status Change',
            'description' => 'Test',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
        ], $this->testUserId);

        $this->service->update($created['id'], [
            'subject' => 'Updated Subject',
        ], $this->testUserId);

        $statusHistories = $this->historyModel->where('ticket_id', $created['id'])
            ->where('action', 'status_changed')
            ->findAll();

        $this->assertCount(0, $statusHistories);
    }

    public function testUpdateNonExistentTicket(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['subject' => 'Test'], 1);
    }

    public function testDeleteTicket(): void
    {
        $created = $this->service->create([
            'subject' => 'To Delete',
            'description' => 'Test',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
        ], $this->testUserId);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testFindTicket(): void
    {
        $created = $this->service->create([
            'subject' => 'Find Me',
            'description' => 'Test',
            'submitted_by' => $this->testUserId,
            'status_id' => $this->testStatusId,
            'priority_id' => $this->testPriorityId,
            'category_id' => $this->testCategoryId,
            'department_id' => $this->testDepartmentId,
        ], $this->testUserId);

        $found = $this->service->findOrFail($created['id']);

        $this->assertEquals('Find Me', $found['subject']);
        $this->assertEquals($created['id'], $found['id']);
    }
}
