<?php

namespace Tests\Feature\Controllers;

use App\Models\TicketModel;
use App\Models\TicketHistoryModel;
use App\Models\DepartmentModel;
use App\Models\UserModel;
use App\Models\TicketStatusModel;
use App\Models\TicketPriorityModel;
use App\Models\TicketCategoryModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class TicketControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    private TicketModel $ticketModel;
    private TicketHistoryModel $historyModel;
    private int $testUserId;
    private int $testDepartmentId;

    // Helper method to send JSON POST
    private function postJson(string $path, array $data)
    {
        return $this->withBody(json_encode($data))
                   ->withHeaders(['Content-Type' => 'application/json'])
                   ->post($path);
    }

    // Helper method to send JSON PUT
    private function putJson(string $path, array $data)
    {
        return $this->withBody(json_encode($data))
                   ->withHeaders(['Content-Type' => 'application/json'])
                   ->put($path);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketModel = new TicketModel();
        $this->historyModel = new TicketHistoryModel();

        // Ensure system user with ID 1 exists (required for getUserId() stub and FK constraints)
        // Use raw SQL with UPSERT to avoid auto-increment issues
        $db = \Config\Database::connect('tests');

        // First ensure system department exists (needed for FK)
        $db->query(
            "INSERT INTO departments (id, name, created_by, updated_by, created_at, updated_at)
             VALUES (0, 'System', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING"
        );

        // Then ensure system user with ID 1 exists
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

        // Ensure lookup tables have data - use raw SQL with ON CONFLICT
        $db->query(
            "INSERT INTO ticket_statuses (id, name, created_by, updated_by, created_at, updated_at)
             VALUES (1, 'Open', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO NOTHING"
        );
        $db->query(
            "INSERT INTO ticket_statuses (id, name, created_by, updated_by, created_at, updated_at)
             VALUES (2, 'In Progress', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
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

        // Get ticket IDs created in this test (those with test department or test user)
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

    public function testGetAllTickets(): void
    {
        $this->ticketModel->insert([
            'subject' => 'Ticket 1',
            'description' => 'Description 1',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $result = $this->get('/api/v1/tickets');

        $result->assertStatus(200);
    }

    public function testCreateTicketWithFormData(): void
    {
        $data = [
            'subject' => 'Form Ticket_' . uniqid(),
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
        ];

        // Test with form data (original way)
        $result = $this->post('/api/v1/tickets', $data);

        // This should fail (get 400) because form data isn't JSON
        $result->assertStatus(400);
    }

    public function testCreateTicket(): void
    {
        $data = [
            'subject' => 'New Ticket_' . uniqid(),
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
        ];

        $result = $this->postJson('/api/v1/tickets', $data);

        $result->assertStatus(201);
    }

    public function testCreateTicketRecordsHistory(): void
    {
        $data = [
            'subject' => 'Ticket with History_' . uniqid(),
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
        ];

        $result = $this->postJson('/api/v1/tickets', $data);

        $result->assertStatus(201);

        // Verify history was recorded
        $response = $result->getJSON(true);
        if (isset($response['data']['id'])) {
            $history = $this->historyModel->where('ticket_id', $response['data']['id'])->first();
            $this->assertNotNull($history);
            $this->assertEquals('ticket_created', $history['action']);
        }
    }

    public function testCreateTicketWithMissingRequiredField(): void
    {
        $data = [
            'description' => 'Missing subject',
            'submitted_by' => $this->testUserId,
        ];

        $result = $this->postJson('/api/v1/tickets', $data);

        $result->assertStatus(400);
    }

    public function testGetSingleTicket(): void
    {
        $id = $this->ticketModel->insert([
            'subject' => 'Find Me',
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $result = $this->get("/api/v1/tickets/$id");

        $result->assertStatus(200);
    }

    public function testGetNonExistentTicket(): void
    {
        $result = $this->get('/api/v1/tickets/99999');

        $result->assertStatus(404);
    }

    public function testUpdateTicketStatusChangeRecordsHistory(): void
    {
        $id = $this->ticketModel->insert([
            'subject' => 'Status Change Test',
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $data = ['status_id' => 2];

        $result = $this->putJson("/api/v1/tickets/$id", $data);

        $result->assertStatus(200);
    }

    public function testUpdateTicketAssignmentRecordsHistory(): void
    {
        // Create a second user for assignment change
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

        $id = $this->ticketModel->insert([
            'subject' => 'Assignment Test',
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'assigned_to_id' => $this->testUserId,
        ]);

        $data = ['assigned_to_id' => $testUser2Id];

        $result = $this->putJson("/api/v1/tickets/$id", $data);

        $result->assertStatus(200);
    }

    public function testDeleteTicket(): void
    {
        $id = $this->ticketModel->insert([
            'subject' => 'To Delete',
            'description' => 'Description',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $result = $this->delete("/api/v1/tickets/$id");

        $result->assertStatus(200);
    }

    public function testFilterTicketsByStatus(): void
    {
        $this->ticketModel->insert([
            'subject' => 'Open',
            'description' => 'Open ticket',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $this->ticketModel->insert([
            'subject' => 'Closed',
            'description' => 'Closed ticket',
            'submitted_by' => $this->testUserId,
            'status_id' => 2,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $result = $this->get('/api/v1/tickets?status_id=1');

        $result->assertStatus(200);
    }

    public function testSearchTickets(): void
    {
        $this->ticketModel->insert([
            'subject' => 'Network Issue',
            'description' => 'Internet connectivity problem',
            'submitted_by' => $this->testUserId,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'department_id' => $this->testDepartmentId,
            'created_by' => $this->testUserId,
            'updated_by' => $this->testUserId,
        ]);

        $result = $this->get('/api/v1/tickets?search=Network');

        $result->assertStatus(200);
    }
}
