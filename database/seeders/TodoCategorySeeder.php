<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TodoCategory;

class TodoCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Development',
                'color' => '#3b82f6', // blue
                'icon' => 'pi pi-code',
                'description' => 'Programming tasks, code reviews, and development related activities',
                'is_active' => true
            ],
            [
                'name' => 'Administration',
                'color' => '#6b7280', // gray
                'icon' => 'pi pi-cog',
                'description' => 'Administrative tasks, documentation, and organizational activities',
                'is_active' => true
            ],
            [
                'name' => 'Customer Support',
                'color' => '#10b981', // green
                'icon' => 'pi pi-users',
                'description' => 'Customer service, support tickets, and client communication',
                'is_active' => true
            ],
            [
                'name' => 'Infrastructure',
                'color' => '#f59e0b', // amber
                'icon' => 'pi pi-server',
                'description' => 'Server management, deployment, and system maintenance',
                'is_active' => true
            ],
            [
                'name' => 'Project Management',
                'color' => '#8b5cf6', // purple
                'icon' => 'pi pi-chart-bar',
                'description' => 'Project planning, coordination, and management tasks',
                'is_active' => true
            ],
            [
                'name' => 'Finance',
                'color' => '#ef4444', // red
                'icon' => 'pi pi-dollar',
                'description' => 'Financial tasks, invoicing, and budget management',
                'is_active' => true
            ],
            [
                'name' => 'Marketing',
                'color' => '#ec4899', // pink
                'icon' => 'pi pi-bullseye',
                'description' => 'Marketing campaigns, content creation, and promotional activities',
                'is_active' => true
            ],
            [
                'name' => 'Personal',
                'color' => '#06b6d4', // cyan
                'icon' => 'pi pi-user',
                'description' => 'Personal tasks and goals',
                'is_active' => true
            ]
        ];

        foreach ($categories as $category) {
            TodoCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
