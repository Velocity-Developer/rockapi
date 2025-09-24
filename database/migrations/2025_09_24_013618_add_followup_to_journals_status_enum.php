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
        Schema::table('journals', function (Blueprint $table) {
            // Mengubah enum status untuk menambahkan 'followup'
            $table->enum('status', ['ongoing', 'completed', 'cancelled', 'archived', 'followup'])->default('ongoing')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            // Mengembalikan enum status ke kondisi semula
            $table->enum('status', ['ongoing', 'completed', 'cancelled', 'archived'])->default('ongoing')->change();
        });
    }
};
