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
        Schema::table('tb_wm_project', function (Blueprint $table) {
            $table->string('user_id')->nullable()->after('id_karyawan'); // tambah kolom user_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_wm_project', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
