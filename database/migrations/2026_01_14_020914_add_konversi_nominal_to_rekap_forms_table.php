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
        Schema::table('rekap_forms', function (Blueprint $table) {

            $table->string('kategori_konversi_nominal', 225)
                ->nullable()
                ->after('cek_konversi_ads');

            $table
                ->boolean('cek_konversi_nominal')
                ->nullable()
                ->default(false)
                ->after('cek_konversi_ads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_forms', function (Blueprint $table) {
            //
        });
    }
};
