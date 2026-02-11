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
        Schema::table('rekap_forms', function (Blueprint $table) {
            //tambah
            $table->string('status_analisa', 50)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_forms', function (Blueprint $table) {
            $table->dropColumn('status_analisa');
        });
    }
};
