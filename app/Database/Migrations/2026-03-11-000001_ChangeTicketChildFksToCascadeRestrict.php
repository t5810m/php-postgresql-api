<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeTicketChildFksToCascadeRestrict extends Migration
{
    private array $tables = [
        'ticket_assignments',
        'ticket_attachments',
        'ticket_comments',
        'ticket_history',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $constraintName = $table . '_ticket_id_fkey';

            $this->db->query("
                ALTER TABLE {$table}
                DROP CONSTRAINT {$constraintName},
                ADD CONSTRAINT {$constraintName}
                    FOREIGN KEY (ticket_id)
                    REFERENCES tickets(id)
                    ON DELETE RESTRICT
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $constraintName = $table . '_ticket_id_fkey';

            $this->db->query("
                ALTER TABLE {$table}
                DROP CONSTRAINT {$constraintName},
                ADD CONSTRAINT {$constraintName}
                    FOREIGN KEY (ticket_id)
                    REFERENCES tickets(id)
                    ON DELETE CASCADE
            ");
        }
    }
}
