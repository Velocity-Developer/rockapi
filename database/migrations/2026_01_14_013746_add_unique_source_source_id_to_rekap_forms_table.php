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
            $table->unique(['source', 'source_id'], 'rekap_forms_source_source_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_forms', function (Blueprint $table) {
            $table->dropUnique('rekap_forms_source_source_id_unique');
        });
    }
};
