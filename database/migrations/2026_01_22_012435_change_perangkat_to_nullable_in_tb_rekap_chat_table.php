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
        Schema::table('tb_rekap_chat', function (Blueprint $table) {
            // jadikan nullable
            $table->string('perangkat')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_rekap_chat', function (Blueprint $table) {
            // kembalikan ke non-nullable
            $table->string('perangkat')->nullable(false)->change();
        });
    }
};
