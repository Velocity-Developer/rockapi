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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->string('unit')->nullable();
            $table->string('nama')->nullable();
            $table->unsignedBigInteger('webhost_id')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('tanggal');
            $table->timestamp('tanggal_bayar')->nullable();
            $table->timestamps();

            $table->foreign('webhost_id')->references('id_webhost')->on('tb_webhost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
