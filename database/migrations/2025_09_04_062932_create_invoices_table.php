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
            $table->string('nama_klien')->nullable();
            $table->text('alamat_klien')->nullable();
            $table->integer('webhost_id')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('tanggal');
            $table->timestamp('tanggal_bayar')->nullable();
            $table->timestamps();
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
