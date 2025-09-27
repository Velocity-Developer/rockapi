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
        Schema::create('cs_main_project_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cs_main_project_id');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('jenis_project')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_main_project_infos');
    }
};
