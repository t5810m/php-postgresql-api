<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;
use Faker\Generator;

class DatabaseSeeder extends Seeder
{
    private Generator $faker;

    public function run(): void
    {
        $this->faker = Factory::create();

        // Seed in dependency order (respect foreign keys)
        $this->seedRoles();
        $this->seedPermissions();
        $this->seedRolePermissions();
        $this->seedDepartments();
        $this->seedLocations();
        $this->seedUsers();
        $this->seedUserRoles();
        $this->seedTicketCategories();
        $this->seedTicketPriorities();
        $this->seedTicketStatuses();
        $this->seedTickets();
        $this->seedTicketAssignments();
        $this->seedTicketComments();
        $this->seedTicketAttachments();
        $this->seedTicketHistory();

        echo "Database seeding completed successfully!\n";
    }

    private function seedRoles(): void
    {
        echo "Seeding roles...";
        $roles = [
            ['name' => 'Company IT Manager', 'description' => 'Manages entire IT operations for the company'],
            ['name' => 'IT Department Manager', 'description' => 'Manages specific IT department'],
            ['name' => 'IT Team Member', 'description' => 'Resolves tickets and supports users'],
            ['name' => 'End User', 'description' => 'Creates and views their own tickets'],
        ];
        $baseTime = strtotime('2026-03-05 11:00:00');

        foreach ($roles as $index => $role) {
            $timestamp = $baseTime + ($index * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('roles')->insert([
                'name' => $role['name'],
                'description' => $role['description'],
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedPermissions(): void
    {
        echo "Seeding permissions...";
        $permissions = [
            ['name' => 'view_dashboard', 'description' => 'View dashboard'],
            ['name' => 'manage_users', 'description' => 'Create, edit, delete users'],
            ['name' => 'manage_roles', 'description' => 'Create, edit, delete roles'],
            ['name' => 'manage_tickets', 'description' => 'View all tickets'],
            ['name' => 'create_ticket', 'description' => 'Create a new ticket'],
            ['name' => 'edit_ticket', 'description' => 'Edit ticket details'],
            ['name' => 'assign_ticket', 'description' => 'Assign ticket to team member'],
            ['name' => 'close_ticket', 'description' => 'Close or resolve ticket'],
            ['name' => 'view_reports', 'description' => 'View system reports'],
            ['name' => 'manage_settings', 'description' => 'Manage system settings'],
        ];
        $baseTime = strtotime('2026-03-05 10:00:00');

        foreach ($permissions as $index => $permission) {
            $timestamp = $baseTime + ($index * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('permissions')->insert([
                'name' => $permission['name'],
                'description' => $permission['description'],
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedRolePermissions(): void
    {
        echo "Seeding role permissions...";
        $rolesRaw = $this->db->table('roles')->select('id, name')->get()->getResultArray();
        $permissionsRaw = $this->db->table('permissions')->select('id, name')->get()->getResultArray();

        // Build name-keyed maps
        $rolesByName = array_column($rolesRaw, 'id', 'name');
        $permissions = array_column($permissionsRaw, 'id', 'name');

        // Company IT Manager gets all permissions
        if (isset($rolesByName['Company IT Manager'])) {
            foreach ($permissions as $permName => $permId) {
                $this->db->table('role_permissions')->insert([
                    'role_id' => $rolesByName['Company IT Manager'],
                    'permission_id' => $permId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 1,
                ]);
                usleep(50000);
            }
        }

        // IT Department Manager gets most permissions except user management
        if (isset($rolesByName['IT Department Manager'])) {
            foreach ($permissions as $permName => $permId) {
                if ($permName === 'manage_users' || $permName === 'manage_roles') {
                    continue;
                }
                $this->db->table('role_permissions')->insert([
                    'role_id' => $rolesByName['IT Department Manager'],
                    'permission_id' => $permId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 1,
                ]);
                usleep(50000);
            }
        }

        // IT Team Member gets ticket-related permissions
        if (isset($rolesByName['IT Team Member'])) {
            $ticketPermNames = ['manage_tickets', 'create_ticket', 'edit_ticket', 'assign_ticket', 'close_ticket'];
            foreach ($ticketPermNames as $permName) {
                if (!isset($permissions[$permName])) {
                    continue;
                }
                $this->db->table('role_permissions')->insert([
                    'role_id' => $rolesByName['IT Team Member'],
                    'permission_id' => $permissions[$permName],
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 1,
                ]);
                usleep(50000);
            }
        }

        // End User gets basic permissions
        if (isset($rolesByName['End User'])) {
            $endUserPermNames = ['view_dashboard', 'create_ticket'];
            foreach ($endUserPermNames as $permName) {
                if (!isset($permissions[$permName])) {
                    continue;
                }
                $this->db->table('role_permissions')->insert([
                    'role_id' => $rolesByName['End User'],
                    'permission_id' => $permissions[$permName],
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 1,
                ]);
                usleep(50000);
            }
        }
        echo " done\n";
    }

    private function seedDepartments(): void
    {
        echo "Seeding departments...";
        $departments = ['IT Operations', 'Infrastructure', 'Applications', 'Network Security', 'Help Desk'];
        $baseTime = strtotime('2026-03-05 12:00:00');

        foreach ($departments as $index => $dept) {
            $timestamp = $baseTime + ($index * 60); // Increment by 60 seconds per record
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('departments')->insert([
                'name' => $dept,
                'description' => "The $dept department",
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedLocations(): void
    {
        echo "Seeding locations...";
        $locations = [
            ['name' => 'Milan Office', 'country' => 'Italy'],
            ['name' => 'Rome Office', 'country' => 'Italy'],
            ['name' => 'Brussels Office', 'country' => 'Belgium'],
            ['name' => 'Antwerp Office', 'country' => 'Belgium'],
            ['name' => 'Berlin Office', 'country' => 'Germany'],
            ['name' => 'Munich Office', 'country' => 'Germany'],
        ];
        $baseTime = strtotime('2026-03-05 13:00:00');

        foreach ($locations as $index => $location) {
            $timestamp = $baseTime + ($index * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('locations')->insert([
                'name' => $location['name'],
                'country' => $location['country'],
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedUsers(): void
    {
        echo "Seeding users...";
        $departments = $this->db->table('departments')->select('id')->get()->getResultArray();
        $locations = $this->db->table('locations')->select('id')->get()->getResultArray();

        for ($i = 0; $i < 50; $i++) {
            $this->db->table('users')->insert([
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->email(),
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'phone' => $this->faker->phoneNumber(),
                'department_id' => $departments[array_rand($departments)]['id'],
                'location_id' => $locations[array_rand($locations)]['id'],
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => 1,
            ]);
            usleep(50000);
        }
        echo " done\n";
    }

    private function seedUserRoles(): void
    {
        echo "Seeding user roles...";
        $users = $this->db->table('users')->select('id')->get()->getResultArray();
        $roles = $this->db->table('roles')->select('id')->get()->getResultArray();

        foreach ($users as $user) {
            $role = $roles[array_rand($roles)];
            $this->db->table('user_roles')->insert([
                'user_id' => $user['id'],
                'role_id' => $role['id'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => 1,
            ]);
            usleep(20000);
        }
        echo " done\n";
    }

    private function seedTicketCategories(): void
    {
        echo "Seeding ticket categories...";
        $categories = [
            ['name' => 'Hardware', 'description' => 'Hardware issues and repairs'],
            ['name' => 'Software', 'description' => 'Software installation and issues'],
            ['name' => 'Network', 'description' => 'Network connectivity issues'],
            ['name' => 'Email', 'description' => 'Email account issues'],
            ['name' => 'Access', 'description' => 'User access and permissions'],
            ['name' => 'Data Recovery', 'description' => 'Data recovery and backup issues'],
        ];
        $baseTime = strtotime('2026-03-05 14:00:00');

        foreach ($categories as $index => $category) {
            $timestamp = $baseTime + ($index * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('ticket_categories')->insert([
                'name' => $category['name'],
                'description' => $category['description'],
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedTicketPriorities(): void
    {
        echo "Seeding ticket priorities...";
        $priorities = [
            ['name' => 'Low'],
            ['name' => 'Medium'],
            ['name' => 'High'],
            ['name' => 'Critical'],
        ];
        $baseTime = strtotime('2026-03-05 15:00:00');

        foreach ($priorities as $index => $priority) {
            $timestamp = $baseTime + ($index * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('ticket_priorities')->insert([
                'name' => $priority['name'],
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedTicketStatuses(): void
    {
        echo "Seeding ticket statuses...";
        $statuses = [
            ['name' => 'Open'],
            ['name' => 'In Progress'],
            ['name' => 'On Hold'],
            ['name' => 'Pending Approval'],
            ['name' => 'Resolved'],
            ['name' => 'Closed'],
            ['name' => 'Reopened'],
        ];
        $baseTime = strtotime('2026-03-05 16:00:00');

        foreach ($statuses as $index => $status) {
            $timestamp = $baseTime + ($index * 60);
            $createdAt = date('Y-m-d H:i:s', $timestamp);
            $this->db->table('ticket_statuses')->insert([
                'name' => $status['name'],
                'created_at' => $createdAt,
                'created_by' => 1,
                'updated_at' => $createdAt,
                'updated_by' => 1,
            ]);
        }
        echo " done\n";
    }

    private function seedTickets(): void
    {
        echo "Seeding tickets...";
        $users = $this->db->table('users')->select('id')->get()->getResultArray();
        $categories = $this->db->table('ticket_categories')->select('id')->get()->getResultArray();
        $priorities = $this->db->table('ticket_priorities')->select('id')->get()->getResultArray();
        $statuses = $this->db->table('ticket_statuses')->select('id')->get()->getResultArray();
        $departments = $this->db->table('departments')->select('id')->get()->getResultArray();
        $locations = $this->db->table('locations')->select('id')->get()->getResultArray();

        for ($i = 0; $i < 100; $i++) {
            $createdBy = $users[array_rand($users)]['id'];
            $this->db->table('tickets')->insert([
                'subject' => $this->faker->sentence(4),
                'description' => $this->faker->paragraph(),
                'submitted_by' => $createdBy,
                'category_id' => $categories[array_rand($categories)]['id'],
                'priority_id' => $priorities[array_rand($priorities)]['id'],
                'status_id' => $statuses[array_rand($statuses)]['id'],
                'assigned_to_id' => array_rand([null, $users[array_rand($users)]['id']]) ? null : $users[array_rand($users)]['id'],
                'department_id' => $departments[array_rand($departments)]['id'],
                'location_id' => $locations[array_rand($locations)]['id'],
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => 1,
            ]);
            usleep(20000);
        }
        echo " done\n";
    }

    private function seedTicketAssignments(): void
    {
        echo "Seeding ticket assignments...";
        $tickets = $this->db->table('tickets')->select('id, created_at')->get()->getResultArray();
        $users = $this->db->table('users')->select('id')->get()->getResultArray();

        foreach ($tickets as $ticket) {
            if (rand(0, 1)) {
                $ticketTime = strtotime($ticket['created_at']);
                $now = time();
                $randomTime = rand($ticketTime, $now);
                $createdAt = date('Y-m-d H:i:s', $randomTime);

                $this->db->table('ticket_assignments')->insert([
                    'ticket_id' => $ticket['id'],
                    'assigned_to_id' => $users[array_rand($users)]['id'],
                    'assigned_at' => $createdAt,
                    'created_by' => 1,
                    'created_at' => $createdAt,
                    'updated_by' => 1,
                    'updated_at' => $createdAt,
                ]);
                usleep(10000);
            }
        }
        echo " done\n";
    }

    private function seedTicketComments(): void
    {
        echo "Seeding ticket comments...";
        $tickets = $this->db->table('tickets')->select('id, created_at')->get()->getResultArray();
        $users = $this->db->table('users')->select('id')->get()->getResultArray();

        foreach ($tickets as $ticket) {
            for ($i = 0; $i < rand(0, 5); $i++) {
                $userId = $users[array_rand($users)]['id'];
                $ticketTime = strtotime($ticket['created_at']);
                $now = time();
                $randomTime = rand($ticketTime, $now);
                $createdAt = date('Y-m-d H:i:s', $randomTime);

                $this->db->table('ticket_comments')->insert([
                    'ticket_id' => $ticket['id'],
                    'user_id' => $userId,
                    'comment' => $this->faker->paragraph(),
                    'created_by' => $userId,
                    'created_at' => $createdAt,
                    'updated_by' => 1,
                    'updated_at' => $createdAt,
                ]);
                usleep(10000);
            }
        }
        echo " done\n";
    }

    private function seedTicketAttachments(): void
    {
        echo "Seeding ticket attachments...";
        $tickets = $this->db->table('tickets')->select('id, created_at')->get()->getResultArray();
        $userIds = array_column($this->db->table('users')->select('id')->get()->getResultArray(), 'id');

        foreach ($tickets as $ticket) {
            if (rand(0, 1)) {
                $userId = $userIds[array_rand($userIds)];
                $ticketTime = strtotime($ticket['created_at']);
                $now = time();
                $randomTime = rand($ticketTime, $now);
                $createdAt = date('Y-m-d H:i:s', $randomTime);

                $this->db->table('ticket_attachments')->insert([
                    'ticket_id' => $ticket['id'],
                    'file_name' => $this->faker->word() . '.pdf',
                    'file_path' => '/uploads/' . $this->faker->uuid() . '.pdf',
                    'created_by' => $userId,
                    'created_at' => $createdAt,
                    'updated_by' => $userId,
                    'updated_at' => $createdAt,
                ]);
                usleep(10000);
            }
        }
        echo " done\n";
    }

    private function seedTicketHistory(): void
    {
        echo "Seeding ticket history...";
        $tickets = $this->db->table('tickets')->select('id, created_at')->get()->getResultArray();
        $users = $this->db->table('users')->select('id')->get()->getResultArray();

        foreach ($tickets as $ticket) {
            for ($i = 0; $i < rand(1, 4); $i++) {
                $userId = $users[array_rand($users)]['id'];
                $ticketTime = strtotime($ticket['created_at']);
                $now = time();
                $randomTime = rand($ticketTime, $now);
                $createdAt = date('Y-m-d H:i:s', $randomTime);

                $this->db->table('ticket_history')->insert([
                    'ticket_id' => $ticket['id'],
                    'action' => $this->faker->randomElement(['ticket_created', 'status_changed', 'reassigned', 'ticket_assigned', 'ticket_closed']),
                    'user_id' => $userId,
                    'details' => $this->faker->sentence(),
                    'created_by' => $userId,
                    'created_at' => $createdAt,
                    'updated_by' => 1,
                    'updated_at' => $createdAt,
                ]);
                usleep(10000);
            }
        }
        echo " done\n";
    }
}
