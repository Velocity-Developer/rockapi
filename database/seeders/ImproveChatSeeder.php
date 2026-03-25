<?php

namespace Database\Seeders;

use App\Models\ImproveChat;
use App\Models\User;
use Illuminate\Database\Seeder;

class ImproveChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found, skipping ImproveChat seeding.');
            return;
        }

        $kategoris = ['CS', 'Revisi', 'Support', 'AM', 'Ads', 'PM'];
        $masukkans = [
            'Fokus pada respons cepat dan ramah.',
            'Pastikan setiap jawaban memberi kepastian langkah berikutnya (misalnya: “Tim kami akan update dalam 1x24 jam”).',
            'Hindari bahasa teknis yang membingungkan klien.',
            'Tegaskan timeline revisi agar klien tahu kapan hasil akan diberikan',
            'Komunikasi harus transparan: jika butuh waktu lebih lama, sampaikan dengan jujur.',
            'Tunjukkan kepedulian terhadap hasil campaign, bukan hanya proses',
            'Dorong klien untuk memberi masukan agar strategi bisa lebih tepat sasaran. Gunakan bahasa yang mudah dipahami, bukan jargon iklan.'
        ];

        foreach ($users->take(5) as $user) {
            foreach (range(1, 3) as $index) {
                ImproveChat::create([
                    'nohp' => '08' . rand(100000000, 999999999),
                    'kategori' => $kategoris[array_rand($kategoris)],
                    'masukkan' => $masukkans[array_rand($masukkans)],
                    'user_id' => $user->id,
                ]);
            }
        }

        $this->command->info('ImproveChat seeded successfully!');
    }
}
