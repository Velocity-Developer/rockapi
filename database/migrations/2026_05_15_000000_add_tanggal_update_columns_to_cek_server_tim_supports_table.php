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
        Schema::table('cek_server_tim_supports', function (Blueprint $table) {
            $table->dateTime('tanggal_update_kapasitas_ssh')->nullable()->after('kapasitas_ssh');
            $table->dateTime('tanggal_update_cek_error_idrac')->nullable()->after('cek_error_idrac');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cek_server_tim_supports', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_update_kapasitas_ssh',
                'tanggal_update_cek_error_idrac',
            ]);
        });
    }
};
