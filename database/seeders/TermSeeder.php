<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create default terms
        Term::create([
            'name' => 'Uncategorized',
            'slug' => 'uncategorized',
            'taxonomy' => 'category',
        ]);
        Term::create([
            'name' => 'Blog',
            'slug' => 'blog',
            'taxonomy' => 'category',
        ]);

        Term::create([
            'name' => 'story',
            'slug' => 'story',
            'taxonomy' => 'tag',
        ]);
        Term::create([
            'name' => 'Velocity Developer',
            'slug' => 'velocity-developer',
            'taxonomy' => 'tag',
        ]);
    }
}
