<?php

namespace Database\Seeders;

use App\Models\JournalCategory;
use Illuminate\Database\Seeder;

class JournalCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Project',
                'description' => 'Project',
                'icon' => '🔧',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Pengembangan',
                'description' => 'Pengembangan',
                'icon' => '🧪',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Desain',
                'description' => 'Desain',
                'icon' => '🎨',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Support',
                'description' => 'Support',
                'icon' => '🤝',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Training',
                'description' => 'Training',
                'icon' => '🎓',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Konsep',
                'description' => 'Konsep',
                'icon' => '💡',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Lainnya',
                'description' => 'Lainnya',
                'icon' => '🔍',
                'role' => 'webdeveloper',
            ],
            [
                'name' => 'Panduan',
                'description' => 'Panduan penggunaan website klien',
                'icon' => '📣',
                'role' => 'support',
            ],
            [
                'name' => 'Konsultasi Update',
                'description' => 'Konsultasi update website klien',
                'icon' => '📝',
                'role' => 'support',
            ],
            [
                'name' => 'Pengerjaan Update',
                'description' => 'Pengerjaan update website klien',
                'icon' => '📝',
                'role' => 'support',
            ],
            [
                'name' => 'Trouble',
                'description' => 'Layanan kendala website klien',
                'icon' => '🕷',
                'role' => 'support',
            ],
            [
                'name' => 'Lain-lain',
                'description' => 'Layanan support lainnya',
                'icon' => '🦾',
                'role' => 'support',
            ],
        ];

        foreach ($categories as $category) {
            // create or update
            JournalCategory::updateOrCreate(
                [
                    'name' => $category['name'],
                    'role' => $category['role'],
                ],
                [
                    'description' => $category['description'],
                    'icon' => $category['icon'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
