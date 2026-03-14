<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameTicketsUserIdToSubmittedBy extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE tickets RENAME COLUMN user_id TO submitted_by');
        $this->db->query('ALTER INDEX idx_tickets_user_id RENAME TO idx_tickets_submitted_by');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE tickets RENAME COLUMN submitted_by TO user_id');
        $this->db->query('ALTER INDEX idx_tickets_submitted_by RENAME TO idx_tickets_user_id');
    }
}
