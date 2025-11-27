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
        Schema::create('rekap_forms', function (Blueprint $table) {
            $table->mediumIncrements('id'); // mengikuti mediumint dan auto increment
            $table->string('nama', 255);
            $table->string('no_whatsapp', 20);
            $table->string('jenis_website', 255);
            $table->string('ai_result', 255);
            $table->string('via', 11)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('greeting', 255)->nullable();
            $table->string('status', 20)->nullable();
            $table->dateTime('created_at')->nullable(); // sesuai struktur asli
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_forms');
    }
};
