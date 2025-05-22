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
        Schema::create('cs_main_project_karyawan', function (Blueprint $table) {
            $table->id();
            $table->integer('cs_main_project_id');
            $table->integer('karyawan_id');
            $table->unsignedInteger('porsi')->nullable(); // dalam persen
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_main_project_karyawan');
    }
};
