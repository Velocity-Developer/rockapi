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
                'name' => 'Pengembangan',
                'color' => '#3b82f6', // blue
                'icon' => '💻',
                'description' => 'Tugas programming, code review, dan aktivitas pengembangan',
                'is_active' => true
            ],
            [
                'name' => 'Administrasi',
                'color' => '#6b7280', // gray
                'icon' => '⚙️',
                'description' => 'Tugas administrasi, dokumentasi, dan aktivitas organisasi',
                'is_active' => true
            ],
            [
                'name' => 'Layanan Pelanggan',
                'color' => '#10b981', // green
                'icon' => '🎧',
                'description' => 'Layanan pelanggan, tiket support, dan komunikasi klien',
                'is_active' => true
            ],
            [
                'name' => 'Infrastruktur',
                'color' => '#f59e0b', // amber
                'icon' => '🖥️',
                'description' => 'Manajemen server, deployment, dan pemeliharaan sistem',
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Proyek',
                'color' => '#8b5cf6', // purple
                'icon' => '📊',
                'description' => 'Perencanaan proyek, koordinasi, dan tugas manajemen',
                'is_active' => true
            ],
            [
                'name' => 'Keuangan',
                'color' => '#ef4444', // red
                'icon' => '💰',
                'description' => 'Tugas keuangan, invoicing, dan manajemen anggaran',
                'is_active' => true
            ],
            [
                'name' => 'Pemasaran',
                'color' => '#ec4899', // pink
                'icon' => '📢',
                'description' => 'Campaign pemasaran, pembuatan konten, dan aktivitas promosi',
                'is_active' => true
            ],
            [
                'name' => 'Pribadi',
                'color' => '#06b6d4', // cyan
                'icon' => '👤',
                'description' => 'Tugas pribadi dan tujuan personal',
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
