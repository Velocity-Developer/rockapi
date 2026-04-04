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
        Schema::table('whmcs_users', function (Blueprint $table) {
            $table->unsignedBigInteger('webhost_id')->nullable()->after('whmcs_id');
        });

        Schema::table('whmcs_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('webhost_id')->nullable()->after('whmcs_userid');
        });

        Schema::table('whmcs_hostings', function (Blueprint $table) {
            $table->unsignedBigInteger('webhost_id')->nullable()->after('whmcs_userid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whmcs_hostings', function (Blueprint $table) {
            $table->dropColumn('webhost_id');
        });

        Schema::table('whmcs_domains', function (Blueprint $table) {
            $table->dropColumn('webhost_id');
        });

        Schema::table('whmcs_users', function (Blueprint $table) {
            $table->dropColumn('webhost_id');
        });
    }
};
