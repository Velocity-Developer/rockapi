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
            $table->string('status_project')->nullable()->after('status_multi'); // tambah kolom status_project
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_wm_project', function (Blueprint $table) {
            $table->dropColumn('status_project');
        });
    }
};
