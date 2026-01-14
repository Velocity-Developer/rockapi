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
        Schema::create('rekap_forms_log_konversi', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rekap_form_id')->required();
            $table->string('kirim_konversi_id')->nullable();
            $table->string('jobid')->nullable();
            $table->string('conversion_action_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_forms_log_konversi');
    }
};
