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
        Schema::table('tb_clientsupport', function (Blueprint $table) {
            $table->integer('export')->nullable()->after('update_web');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_clientsupport', function (Blueprint $table) {
            $table->dropColumn('export');
        });
    }
};
