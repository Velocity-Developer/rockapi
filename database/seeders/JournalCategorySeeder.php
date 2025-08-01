<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JournalCategory;

class JournalCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Project',
            'Pengembangan',
            'Desain',
            'Support',
            'Training',
            'Konsep',
            'Lainnya',
        ];

        foreach ($categories as $category) {
            JournalCategory::create([
                'name' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
