<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DbIntegrity extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:integrity';
    protected $description = 'Check database integrity - read-only checks for foreign key orphans and sync issues';

    public function run(array $params): int
    {
        $db = \Config\Database::connect();
        $hasIssues = false;

        try {
            CLI::write("\nRunning database integrity checks...\n");

            // Check 1: Row count overview
            $this->checkRowCounts($db, $hasIssues);

            // Check 2: Tickets with missing category_id
            $this->checkTicketCategories($db, $hasIssues);

            // Check 3: Tickets with missing priority_id
            $this->checkTicketPriorities($db, $hasIssues);

            // Check 4: Tickets with missing status_id
            $this->checkTicketStatuses($db, $hasIssues);

            // Check 5: Sync drift between ticket_assignments and tickets
            $this->checkAssignmentSync($db, $hasIssues);

            // Check 6: Orphaned ticket_attachments
            $this->checkOrphanedAttachments($db, $hasIssues);

            // Check 7: Orphaned ticket_comments
            $this->checkOrphanedComments($db, $hasIssues);

            // Check 8: Orphaned ticket_history
            $this->checkOrphanedHistory($db, $hasIssues);

            CLI::write("");
            if (!$hasIssues) {
                CLI::write(CLI::color('OK', 'green') . ' All integrity checks passed');
                return 0;
            } else {
                CLI::write(CLI::color('FAIL', 'red') . ' Some integrity issues detected');
                return 1;
            }
        } catch (\Exception $e) {
            CLI::error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function checkRowCounts($db, &$hasIssues): void
    {
        CLI::write(CLI::color('1. Row count overview', 'yellow'));

        $tables = [
            'users', 'roles', 'user_roles', 'permissions', 'role_permissions',
            'departments', 'locations', 'ticket_categories', 'ticket_priorities',
            'ticket_statuses', 'tickets', 'ticket_assignments', 'ticket_comments',
            'ticket_attachments', 'ticket_history'
        ];

        foreach ($tables as $table) {
            $count = $db->table($table)->countAllResults();
            CLI::write("  {$table}: {$count} rows");
        }
        CLI::write(CLI::color('OK', 'green') . " Row counts retrieved\n");
    }

    private function checkTicketCategories($db, &$hasIssues): void
    {
        CLI::write(CLI::color('2. Checking tickets with missing category_id', 'yellow'));

        $orphans = $db->query("
            SELECT id, subject FROM tickets
            WHERE category_id IS NULL OR category_id NOT IN (SELECT id FROM ticket_categories)
        ")->getResultArray();

        if (count($orphans) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($orphans) . " tickets with invalid category_id:");
            foreach ($orphans as $row) {
                CLI::write("    Ticket ID: {$row['id']}, Subject: {$row['subject']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " All tickets have valid category_id\n");
        }
    }

    private function checkTicketPriorities($db, &$hasIssues): void
    {
        CLI::write(CLI::color('3. Checking tickets with missing priority_id', 'yellow'));

        $orphans = $db->query("
            SELECT id, subject FROM tickets
            WHERE priority_id IS NULL OR priority_id NOT IN (SELECT id FROM ticket_priorities)
        ")->getResultArray();

        if (count($orphans) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($orphans) . " tickets with invalid priority_id:");
            foreach ($orphans as $row) {
                CLI::write("    Ticket ID: {$row['id']}, Subject: {$row['subject']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " All tickets have valid priority_id\n");
        }
    }

    private function checkTicketStatuses($db, &$hasIssues): void
    {
        CLI::write(CLI::color('4. Checking tickets with missing status_id', 'yellow'));

        $orphans = $db->query("
            SELECT id, subject FROM tickets
            WHERE status_id IS NULL OR status_id NOT IN (SELECT id FROM ticket_statuses)
        ")->getResultArray();

        if (count($orphans) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($orphans) . " tickets with invalid status_id:");
            foreach ($orphans as $row) {
                CLI::write("    Ticket ID: {$row['id']}, Subject: {$row['subject']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " All tickets have valid status_id\n");
        }
    }

    private function checkAssignmentSync($db, &$hasIssues): void
    {
        CLI::write(CLI::color('5. Checking assignment sync (ticket_assignments vs tickets.assigned_to_id)', 'yellow'));

        $drift = $db->query("
            SELECT t.id, t.assigned_to_id as ticket_assigned_to,
                   MAX(ta.assigned_to_id) as latest_assignment
            FROM tickets t
            LEFT JOIN ticket_assignments ta ON t.id = ta.ticket_id
            GROUP BY t.id, t.assigned_to_id
            HAVING COALESCE(t.assigned_to_id, 0) != COALESCE(MAX(ta.assigned_to_id), 0)
        ")->getResultArray();

        if (count($drift) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($drift) . " tickets with assignment sync drift:");
            foreach ($drift as $row) {
                CLI::write("    Ticket ID: {$row['id']}, tickets.assigned_to_id: {$row['ticket_assigned_to']}, latest assignment: {$row['latest_assignment']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " All assignments synchronized\n");
        }
    }

    private function checkOrphanedAttachments($db, &$hasIssues): void
    {
        CLI::write(CLI::color('6. Checking orphaned ticket_attachments', 'yellow'));

        $orphans = $db->query("
            SELECT id, ticket_id FROM ticket_attachments
            WHERE ticket_id NOT IN (SELECT id FROM tickets)
        ")->getResultArray();

        if (count($orphans) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($orphans) . " orphaned attachments:");
            foreach ($orphans as $row) {
                CLI::write("    Attachment ID: {$row['id']}, references deleted ticket ID: {$row['ticket_id']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " No orphaned attachments\n");
        }
    }

    private function checkOrphanedComments($db, &$hasIssues): void
    {
        CLI::write(CLI::color('7. Checking orphaned ticket_comments', 'yellow'));

        $orphans = $db->query("
            SELECT id, ticket_id FROM ticket_comments
            WHERE ticket_id NOT IN (SELECT id FROM tickets)
        ")->getResultArray();

        if (count($orphans) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($orphans) . " orphaned comments:");
            foreach ($orphans as $row) {
                CLI::write("    Comment ID: {$row['id']}, references deleted ticket ID: {$row['ticket_id']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " No orphaned comments\n");
        }
    }

    private function checkOrphanedHistory($db, &$hasIssues): void
    {
        CLI::write(CLI::color('8. Checking orphaned ticket_history', 'yellow'));

        $orphans = $db->query("
            SELECT id, ticket_id FROM ticket_history
            WHERE ticket_id NOT IN (SELECT id FROM tickets)
        ")->getResultArray();

        if (count($orphans) > 0) {
            CLI::write(CLI::color('FAIL', 'red') . " Found " . count($orphans) . " orphaned history records:");
            foreach ($orphans as $row) {
                CLI::write("    History ID: {$row['id']}, references deleted ticket ID: {$row['ticket_id']}");
            }
            $hasIssues = true;
        } else {
            CLI::write(CLI::color('OK', 'green') . " No orphaned history records\n");
        }
    }
}
