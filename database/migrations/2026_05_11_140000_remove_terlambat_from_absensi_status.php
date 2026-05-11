<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('absensi')
            ->where('status', 'Terlambat')
            ->update(['status' => 'Hadir']);

        DB::statement("ALTER TABLE absensi MODIFY status ENUM('Hadir','Izin','Sakit','Cuti','Alpha','Libur','Setengah Hari') NOT NULL DEFAULT 'Hadir'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE absensi MODIFY status ENUM('Hadir','Terlambat','Izin','Sakit','Cuti','Alpha','Libur','Setengah Hari') NOT NULL DEFAULT 'Hadir'");
    }
};
