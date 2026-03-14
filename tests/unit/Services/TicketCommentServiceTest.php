<?php

namespace Tests\Unit\Services;

use App\Models\TicketCommentModel;
use App\Models\TicketModel;
use App\Models\UserModel;
use App\Services\TicketCommentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

class TicketCommentServiceTest extends CIUnitTestCase
{
    private TicketCommentService $service;
    private TicketCommentModel $model;
    private TicketModel $ticketModel;
    private UserModel $userModel;
    private int $testTicketId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TicketCommentModel();
        $this->ticketModel = new TicketModel();
        $this->userModel = new UserModel();
        $this->service = new TicketCommentService($this->model);

        // Ensure system user exists
        $this->model->db->query("INSERT INTO users (id, name, email, password, created_by, updated_by)
            VALUES (1, 'System', 'system@system.com', '', 1, 1) ON CONFLICT (id) DO NOTHING");

        // Create a test ticket (with required FK fields)
        $this->testTicketId = $this->ticketModel->insert([
            'subject' => 'Test Ticket for Comments',
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
        $this->model->db->table('ticket_comments')->where('ticket_id', $this->testTicketId)->delete();
        $this->ticketModel->db->table('tickets')->where('id', $this->testTicketId)->delete();
    }

    public function testCreateCommentWithValidData(): void
    {
        $data = [
            'ticket_id' => $this->testTicketId,
            'user_id' => 1,
            'comment' => 'This is a test comment_' . uniqid(),
        ];

        $result = $this->service->create($data, 1);

        $this->assertIsArray($result);
        $this->assertEquals($data['comment'], $result['comment']);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testCreateCommentWithMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'ticket_id' => $this->testTicketId,
            'user_id' => 1,
        ];

        $this->service->create($data, 1);
    }

    public function testUpdateCommentWithValidData(): void
    {
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'user_id' => 1,
            'comment' => 'Old comment_' . uniqid(),
        ], 1);

        $newComment = 'Updated comment_' . uniqid();
        $result = $this->service->update($created['id'], ['comment' => $newComment], 1);

        $this->assertEquals($newComment, $result['comment']);
        $this->assertEquals(1, $result['updated_by']);
    }

    public function testUpdateNonExistentComment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(99999, ['comment' => 'Test'], 1);
    }

    public function testDeleteExistingComment(): void
    {
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'user_id' => 1,
            'comment' => 'Delete me_' . uniqid(),
        ], 1);

        $this->service->delete($created['id']);

        $this->expectException(NotFoundException::class);
        $this->service->findOrFail($created['id']);
    }

    public function testDeleteNonExistentComment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(99999);
    }

    public function testFindExistingComment(): void
    {
        $commentText = 'Find me_' . uniqid();
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'user_id' => 1,
            'comment' => $commentText,
        ], 1);

        $result = $this->service->findOrFail($created['id']);

        $this->assertEquals($commentText, $result['comment']);
        $this->assertEquals($created['id'], $result['id']);
    }

    public function testFindNonExistentComment(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->findOrFail(99999);
    }

    public function testPartialUpdateCommentAllowsOptionalFields(): void
    {
        $created = $this->service->create([
            'ticket_id' => $this->testTicketId,
            'user_id' => 1,
            'comment' => 'Original comment_' . uniqid(),
        ], 1);

        $newComment = 'Updated comment_' . uniqid();
        $result = $this->service->update($created['id'], ['comment' => $newComment], 2);

        $this->assertEquals($newComment, $result['comment']);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals(2, $result['updated_by']);
    }
}
