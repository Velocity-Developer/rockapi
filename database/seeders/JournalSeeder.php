<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Journal;
use App\Models\JournalCategory;
use App\Models\Webhost;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        //dapatkan semua category
        $categories = JournalCategory::all();

        //looping user
        foreach ($categories as $category) {
            $role = $category->role;

            //get user by role
            $users = User::role($role)->get();

            foreach ($users as $user) {

                //get random webhost
                $webhost = Webhost::inRandomOrder()->first();

                //get date, kemarin sampai 1 bulan lalu
                $random_day_subs = rand(2, 30);
                $start = $faker->dateTimeBetween('-' . $random_day_subs . ' days', 'now');
                $end = $faker->dateTimeBetween($start, 'now');

                //create journal
                Journal::create([
                    'user_id'               => $user->id,
                    'journal_category_id'   => $category->id,
                    'title'                 => $faker->sentence(10),
                    'description'           => $faker->paragraph(10),
                    'start'                 => $start,
                    'end'                   => $end,
                    'status'                => $faker->randomElement(['ongoing', 'completed', 'cancelled', 'archived']),
                    'webhost_id'            => $webhost->id_webhost,
                ]);

                $this->command->info('Create Journal: ' . $user->name . ' - ' . $category->name);
            }
        }
    }
}
