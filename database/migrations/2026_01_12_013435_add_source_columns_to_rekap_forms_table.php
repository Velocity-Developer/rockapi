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
            $table->string('source', 50)
                ->nullable()
                ->after('id'); // sesuaikan posisi kolom

            $table->string('source_id', 100)
                ->nullable()
                ->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_forms', function (Blueprint $table) {
            //
            $table->dropColumn(['source', 'source_id']);
        });
    }
};
