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
        Schema::table('whmcs_hostings', function (Blueprint $table) {
            $table->string('username')->nullable()->after('domainstatus');
            $table->bigInteger('diskusage')->nullable()->after('username');
            $table->bigInteger('disklimit')->nullable()->after('diskusage');
            $table->bigInteger('bwusage')->nullable()->after('disklimit');
            $table->bigInteger('bwlimit')->nullable()->after('bwusage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whmcs_hostings', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'diskusage',
                'disklimit',
                'bwusage',
                'bwlimit',
            ]);
        });
    }
};
