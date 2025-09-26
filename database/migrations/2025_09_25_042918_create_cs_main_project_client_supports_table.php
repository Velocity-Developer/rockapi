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
        Schema::create('cs_main_project_client_supports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cs_main_project_id');
            $table->string('layanan');
            $table->timestamp('tanggal');
            $table->timestamps();

            //index
            $table->index('cs_main_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_main_project_client_supports');
    }
};
