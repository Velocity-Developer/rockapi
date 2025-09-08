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
            $table->string('telepon_klien')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('pajak')->nullable();
            $table->decimal('nominal_pajak', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamp('tanggal');
            $table->timestamp('jatuh_tempo')->nullable();
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
