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
        Schema::table('whmcs_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_userid')->nullable()->after('whmcs_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whmcs_domains', function (Blueprint $table) {
            $table->dropColumn('whmcs_userid');
        });
    }
};
