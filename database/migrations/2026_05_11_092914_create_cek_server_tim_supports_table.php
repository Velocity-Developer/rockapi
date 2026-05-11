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
        Schema::create('cek_server_tim_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained('servers')->onDelete('set null');
            $table->datetime('hapus_backup_admin')->nullable();
            $table->string('kapasitas_ssh')->nullable();
            $table->boolean('cek_error_idrac')->nullable();
            $table->text('error_idrac')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cek_server_tim_supports');
    }
};
