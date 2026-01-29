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
        Schema::table('cs_main_project_infos', function (Blueprint $table) {
            $table->integer('waktu_plus')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cs_main_project_infos', function (Blueprint $table) {
            $table->dropColumn('waktu_plus');
        });
    }
};
