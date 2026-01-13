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
            $table->string('nama')->nullable()->change();
            $table->text('ai_result')->nullable()->change();
            $table->string('no_whatsapp')->nullable()->change();
            $table->string('jenis_website')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_forms', function (Blueprint $table) {
            $table->string('nama')->nullable(false)->change();
            $table->text('ai_result')->nullable(false)->change();
            $table->string('no_whatsapp')->nullable(false)->change();
            $table->string('jenis_website')->nullable(false)->change();
        });
    }
};
