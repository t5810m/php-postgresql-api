<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DbClean extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:clean';
    protected $description = 'Truncate all tables in the database';

    public function run(array $params): int
    {
        $db = \Config\Database::connect();

        try {
            // Disable foreign key checks
            $db->query('SET session_replication_role = replica;');

            // Get all tables
            $tables = $db->query("
                SELECT tablename FROM pg_tables
                WHERE schemaname = 'public'
                ORDER BY tablename
            ")->getResultArray();

            foreach ($tables as $table) {
                $tableName = $table['tablename'];
                CLI::write("Truncating table: {$tableName}");
                $db->query("TRUNCATE TABLE \"$tableName\" RESTART IDENTITY CASCADE");
            }

            // Re-enable foreign key checks
            $db->query('SET session_replication_role = default;');

            CLI::write(CLI::color('All tables cleared successfully!', 'green'));
        } catch (\Exception $e) {
            CLI::error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
