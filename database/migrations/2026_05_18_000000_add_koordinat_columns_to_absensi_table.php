<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->string('koordinat_masuk')->nullable()->after('jam_masuk');
            $table->string('koordinat_pulang')->nullable()->after('jam_pulang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn([
                'koordinat_masuk',
                'koordinat_pulang',
            ]);
        });
    }
};
