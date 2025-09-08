<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Webhost;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dapatkan beberapa webhost untuk digunakan dalam invoice
        $webhosts = Webhost::take(5)->get();

        if ($webhosts->isEmpty()) {
            $this->command->info('Tidak ada data webhost. Silakan jalankan WebhostSeeder terlebih dahulu.');
            return;
        }

        // Status yang mungkin untuk invoice
        $statuses = ['pending', 'lunas', 'batal'];

        // Jenis item yang mungkin
        $jenis_items = ['hosting', 'domain', 'maintenance', 'development', 'support'];

        // Buat 20 invoice contoh
        for ($i = 1; $i <= 20; $i++) {
            $webhost = $webhosts->random();
            $status = $statuses[array_rand($statuses)];
            $tanggal = Carbon::now()->subDays(rand(1, 60));
            $tanggal_bayar = $status === 'lunas' ? Carbon::parse($tanggal)->addDays(rand(1, 15)) : null;

            $invoice = Invoice::create([
                'unit' => 'vd',
                'nama_klien' => 'Klien ' . $webhost->nama_web,
                'alamat_klien' => 'Alamat contoh Klien ' . $i,
                'telepon_klien' => '0812' . rand(1000000, 9999999),
                'note' => 'Catatan untuk invoice #' . $i,
                'status' => $status,
                'subtotal' => 0,
                'pajak' => null,
                'nominal_pajak' => 0,
                'total' => 0,
                'tanggal' => $tanggal,
                'jatuh_tempo' => Carbon::parse($tanggal)->addDays(rand(3, 14)),
                'tanggal_bayar' => $tanggal_bayar,
            ]);

            // Buat 1-5 item untuk setiap invoice
            $item_count = rand(1, 5);
            for ($j = 1; $j <= $item_count; $j++) {
                $jenis = $jenis_items[array_rand($jenis_items)];
                $harga = rand(50000, 2000000);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'webhost_id' => $webhost->id_webhost,
                    'nama' => ucfirst($jenis) . ' ' . $webhost->nama_web,
                    'jenis' => $jenis,
                    'harga' => $harga,
                ]);
            }

            // Update subtotal/total berdasarkan items
            $subtotal = InvoiceItem::where('invoice_id', $invoice->id)->sum('harga');
            $nominal_pajak = 0;
            $total = $subtotal + $nominal_pajak;
            $invoice->update([
                'subtotal' => $subtotal,
                'nominal_pajak' => $nominal_pajak,
                'total' => $total,
            ]);
        }

        $this->command->info('20 invoice dengan item berhasil dibuat!');
    }
}
