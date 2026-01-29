<?php

namespace Database\Seeders;

use App\Models\TodoAssignment;
use App\Models\TodoCategory;
use App\Models\TodoList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $categories = TodoCategory::all()->keyBy('name');

        // Get users
        $users = User::all();
        $adminUser = User::find(1);
        $ownerUser = User::find(2);

        // Define date range: 1 month ago to 2 weeks ahead
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now()->addWeeks(2);

        // Sample todo data with realistic tasks
        $todoData = [
            [
                'title' => 'Setup development environment for new project',
                'description' => 'Install Laravel, Node.js, and configure database connections for the upcoming client project',
                'category' => 'Pengembangan',
                'priority' => TodoList::PRIORITY_HIGH,
                'status' => TodoList::STATUS_COMPLETED,
                'assigned_to' => ['user' => [10]], // webdeveloper
                'created_by' => 2, // owner
                'is_private' => false,
                'days_from_start' => -25, // 25 days ago
                'due_days' => 5, // 5 days to complete
            ],
            [
                'title' => 'Review and approve Q3 financial reports',
                'description' => 'Check all financial statements, expense reports, and revenue calculations for Q3 2024',
                'category' => 'Keuangan',
                'priority' => TodoList::PRIORITY_URGENT,
                'status' => TodoList::STATUS_IN_PROGRESS,
                'assigned_to' => ['user' => [6]], // finance
                'created_by' => 2, // owner
                'is_private' => true,
                'days_from_start' => -5, // 5 days ago
                'due_days' => 2, // 2 days from creation
            ],
            [
                'title' => 'Customer support ticket response optimization',
                'description' => 'Implement new template responses and improve ticket resolution time by 30%',
                'category' => 'Layanan Pelanggan',
                'priority' => TodoList::PRIORITY_MEDIUM,
                'status' => TodoList::STATUS_ASSIGNED,
                'assigned_to' => ['role' => [7]], // support
                'created_by' => 1, // admin
                'is_private' => false,
                'days_from_start' => -10, // 10 days ago
                'due_days' => 7, // 7 days to complete
            ],
            [
                'title' => 'Server maintenance and security updates',
                'description' => 'Apply security patches, update SSL certificates, and perform server backups',
                'category' => 'Infrastruktur',
                'priority' => TodoList::PRIORITY_HIGH,
                'status' => TodoList::STATUS_PENDING,
                'assigned_to' => ['user' => [4], 'role' => [10]], // manager_project + webdeveloper
                'created_by' => 1, // admin
                'is_private' => false,
                'days_from_start' => -3, // 3 days ago
                'due_days' => 3, // 3 days to complete
            ],
            [
                'title' => 'Create marketing campaign for new product launch',
                'description' => 'Design social media assets, write copy, and schedule posts for product launch',
                'category' => 'Pemasaran',
                'priority' => TodoList::PRIORITY_URGENT,
                'status' => TodoList::STATUS_IN_PROGRESS,
                'assigned_to' => ['user' => [5], 'role' => [9]], // manager_advertising + advertising
                'created_by' => 2, // owner
                'is_private' => false,
                'days_from_start' => -7, // 7 days ago
                'due_days' => 10, // 10 days to complete
            ],
            [
                'title' => 'Weekly team meeting preparation',
                'description' => 'Prepare agenda, gather progress reports, and book meeting room for weekly sync',
                'category' => 'Administrasi',
                'priority' => TodoList::PRIORITY_MEDIUM,
                'status' => TodoList::STATUS_COMPLETED,
                'assigned_to' => ['user' => [4]], // manager_project
                'created_by' => 4, // manager_project
                'is_private' => false,
                'days_from_start' => -20, // 20 days ago
                'due_days' => 1, // 1 day to complete
            ],
            [
                'title' => 'Database performance optimization',
                'description' => 'Analyze slow queries, add indexes, and optimize database performance for main application',
                'category' => 'Pengembangan',
                'priority' => TodoList::PRIORITY_HIGH,
                'status' => TodoList::STATUS_ASSIGNED,
                'assigned_to' => ['user' => [10]], // webdeveloper
                'created_by' => 1, // admin
                'is_private' => false,
                'days_from_start' => -14, // 14 days ago
                'due_days' => 14, // 14 days to complete
            ],
            [
                'title' => 'Client presentation for project proposal',
                'description' => 'Create slides, demo, and prepare presentation materials for new client meeting',
                'category' => 'Manajemen Proyek',
                'priority' => TodoList::PRIORITY_URGENT,
                'status' => TodoList::STATUS_IN_PROGRESS,
                'assigned_to' => ['user' => [2], 'role' => [4]], // owner + manager_project
                'created_by' => 2, // owner
                'is_private' => false,
                'days_from_start' => -2, // 2 days ago
                'due_days' => 4, // 4 days to complete
            ],
            [
                'title' => 'Update documentation for API endpoints',
                'description' => 'Document all new API endpoints, update examples, and ensure accuracy',
                'category' => 'Pengembangan',
                'priority' => TodoList::PRIORITY_LOW,
                'status' => TodoList::STATUS_PENDING,
                'assigned_to' => ['user' => [10]], // webdeveloper
                'created_by' => 1, // admin
                'is_private' => false,
                'days_from_start' => 0, // today
                'due_days' => 21, // 21 days to complete
            ],
            [
                'title' => 'Budget planning for next quarter',
                'description' => 'Analyze current spending, forecast expenses, and create budget proposal for Q1 2025',
                'category' => 'Keuangan',
                'priority' => TodoList::PRIORITY_HIGH,
                'status' => TodoList::STATUS_PENDING,
                'assigned_to' => ['user' => [6], 'role' => [2]], // finance + owner
                'created_by' => 2, // owner
                'is_private' => true,
                'days_from_start' => 3, // 3 days from now
                'due_days' => 14, // 14 days to complete
            ],
            [
                'title' => 'Security audit and penetration testing',
                'description' => 'Conduct comprehensive security audit, identify vulnerabilities, and implement fixes',
                'category' => 'Infrastruktur',
                'priority' => TodoList::PRIORITY_URGENT,
                'status' => TodoList::STATUS_ASSIGNED,
                'assigned_to' => ['role' => [1, 10]], // admin + webdeveloper
                'created_by' => 1, // admin
                'is_private' => true,
                'days_from_start' => 7, // 7 days from now
                'due_days' => 10, // 10 days to complete
            ],
            [
                'title' => 'Employee performance reviews',
                'description' => 'Complete performance evaluations, schedule 1-on-1 meetings, and provide feedback',
                'category' => 'Administrasi',
                'priority' => TodoList::PRIORITY_MEDIUM,
                'status' => TodoList::STATUS_PENDING,
                'assigned_to' => ['user' => [2, 4, 5]], // owner, manager_project, manager_advertising
                'created_by' => 2, // owner
                'is_private' => true,
                'days_from_start' => 10, // 10 days from now
                'due_days' => 7, // 7 days to complete
            ],
            [
                'title' => 'Content creation for company blog',
                'description' => 'Write 4 blog posts about industry trends and company achievements',
                'category' => 'Pemasaran',
                'priority' => TodoList::PRIORITY_LOW,
                'status' => TodoList::STATUS_COMPLETED,
                'assigned_to' => ['user' => [9]], // advertising
                'created_by' => 5, // manager_advertising
                'is_private' => false,
                'days_from_start' => -28, // 28 days ago
                'due_days' => 20, // 20 days to complete
            ],
            [
                'title' => 'Implement automated testing suite',
                'description' => 'Set up unit tests, integration tests, and CI/CD pipeline for automated testing',
                'category' => 'Pengembangan',
                'priority' => TodoList::PRIORITY_MEDIUM,
                'status' => TodoList::STATUS_IN_PROGRESS,
                'assigned_to' => ['user' => [10]], // webdeveloper
                'created_by' => 1, // admin
                'is_private' => false,
                'days_from_start' => -12, // 12 days ago
                'due_days' => 18, // 18 days to complete
            ],
            [
                'title' => 'Customer feedback analysis and action plan',
                'description' => 'Analyze customer survey results, identify improvement areas, and create action plan',
                'category' => 'Layanan Pelanggan',
                'priority' => TodoList::PRIORITY_MEDIUM,
                'status' => TodoList::STATUS_ASSIGNED,
                'assigned_to' => ['role' => [7, 12]], // support + customer_service
                'created_by' => 1, // admin
                'is_private' => false,
                'days_from_start' => -8, // 8 days ago
                'due_days' => 12, // 12 days to complete
            ],
            [
                'title' => 'Mobile app UX improvements',
                'description' => 'Redesign user interface, improve navigation, and enhance user experience',
                'category' => 'Pengembangan',
                'priority' => TodoList::PRIORITY_HIGH,
                'status' => TodoList::STATUS_PENDING,
                'assigned_to' => ['user' => [10]], // webdeveloper
                'created_by' => 4, // manager_project
                'is_private' => false,
                'days_from_start' => 5, // 5 days from now
                'due_days' => 25, // 25 days to complete
            ],
            [
                'title' => 'Prepare tax documentation',
                'description' => 'Gather all financial documents, receipts, and prepare tax filing paperwork',
                'category' => 'Keuangan',
                'priority' => TodoList::PRIORITY_URGENT,
                'status' => TodoList::STATUS_IN_PROGRESS,
                'assigned_to' => ['user' => [6]], // finance
                'created_by' => 2, // owner
                'is_private' => true,
                'days_from_start' => -15, // 15 days ago
                'due_days' => 5, // 5 days to complete
            ],
            [
                'title' => 'Team building event planning',
                'description' => 'Organize quarterly team building activity, book venue, and coordinate logistics',
                'category' => 'Administrasi',
                'priority' => TodoList::PRIORITY_LOW,
                'status' => TodoList::STATUS_PENDING,
                'assigned_to' => ['user' => [4]], // manager_project
                'created_by' => 2, // owner
                'is_private' => false,
                'days_from_start' => 14, // 14 days from now
                'due_days' => 21, // 21 days to complete
            ],
            [
                'title' => 'Competitor analysis report',
                'description' => 'Research competitors, analyze their strategies, and create comprehensive report',
                'category' => 'Pemasaran',
                'priority' => TodoList::PRIORITY_MEDIUM,
                'status' => TodoList::STATUS_COMPLETED,
                'assigned_to' => ['user' => [5]], // manager_advertising
                'created_by' => 5, // manager_advertising
                'is_private' => false,
                'days_from_start' => -22, // 22 days ago
                'due_days' => 15, // 15 days to complete
            ],
        ];

        foreach ($todoData as $data) {
            // Calculate dates
            $createdAt = $startDate->copy()->addDays($data['days_from_start']);
            $dueDate = $createdAt->copy()->addDays($data['due_days']);

            // Skip if due date is beyond our range
            if ($dueDate->gt($endDate)) {
                continue;
            }

            // Create todo
            $todo = TodoList::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'created_by' => $data['created_by'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'due_date' => $dueDate->format('Y-m-d'),
                'category_id' => $categories[$data['category']]->id ?? null,
                'is_private' => $data['is_private'],
                'notes' => $this->generateRandomNotes(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create assignments
            if (isset($data['assigned_to'])) {
                foreach ($data['assigned_to'] as $type => $ids) {
                    foreach ($ids as $id) {
                        TodoAssignment::create([
                            'todo_id' => $todo->id,
                            'assignable_type' => $type === 'user' ? 'App\\Models\\User' : 'Spatie\\Permission\\Models\\Role',
                            'assignable_id' => $id,
                            'assigned_by' => $data['created_by'],
                            'assigned_at' => $createdAt,
                            'status' => $this->getAssignmentStatus($data['status'], $createdAt),
                        ]);
                    }
                }
            }
        }

        $this->command->info('Todo items seeded successfully!');
        $this->command->info('Created '.count($todoData).' todo items with assignments.');
    }

    private function generateRandomNotes(): ?string
    {
        $notes = [
            null,
            'Please prioritize this task as it affects multiple departments.',
            'Coordinate with the team before proceeding.',
            'Check dependencies before starting this task.',
            'Review documentation for detailed requirements.',
            'Update progress in daily stand-up meetings.',
            'Ensure quality standards are met.',
            'Test thoroughly before deployment.',
            'Follow company guidelines and best practices.',
            'Escalate any blockers immediately.',
        ];

        return $notes[array_rand($notes)];
    }

    private function getAssignmentStatus(string $todoStatus, Carbon $createdAt): string
    {
        // If todo is completed, assignments should be completed too
        if ($todoStatus === TodoList::STATUS_COMPLETED) {
            return TodoAssignment::STATUS_COMPLETED;
        }

        // If todo is in progress, some assignments might be in progress
        if ($todoStatus === TodoList::STATUS_IN_PROGRESS) {
            return rand(0, 1) ? TodoAssignment::STATUS_IN_PROGRESS : TodoAssignment::STATUS_ASSIGNED;
        }

        // For other statuses, use assigned as default
        return TodoAssignment::STATUS_ASSIGNED;
    }
}
