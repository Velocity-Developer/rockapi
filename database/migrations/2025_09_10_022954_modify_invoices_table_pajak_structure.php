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
        Schema::table('invoices', function (Blueprint $table) {
            // Ubah kolom pajak menjadi boolean
            $table->boolean('pajak')->default(false)->change();
            // Tambahkan kolom nama_pajak
            $table->string('nama_pajak')->nullable()->after('pajak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Kembalikan kolom pajak ke string
            $table->string('pajak')->nullable()->change();
            // Hapus kolom nama_pajak
            $table->dropColumn('nama_pajak');
        });
    }
};
