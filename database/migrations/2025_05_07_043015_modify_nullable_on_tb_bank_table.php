<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah Kolom 'jenis' jadi nullable
     */
    public function up(): void
    {
        Schema::table('tb_bank', function (Blueprint $table) {
            $table->text('jenis')->nullable()->change();
            $table->text('id_webhost')->nullable()->change();
            $table->text('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_bank', function (Blueprint $table) {
            $table->text('jenis')->nullable(false)->change();
            $table->text('id_webhost')->nullable(false)->change();
            $table->text('status')->nullable(false)->change();
        });
    }
};
