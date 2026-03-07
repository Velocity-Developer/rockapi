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
        Schema::create('whmcs_hostings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('whmcs_id')->nullable();
            $table->bigInteger('whmcs_userid')->nullable();
            $table->string('domain')->nullable();
            $table->date('nextduedate')->nullable();
            $table->string('billingcycle')->nullable();
            $table->string('domainstatus')->nullable();
            $table->string('package_name')->nullable();
            $table->string('package_servertype')->nullable();
            $table->string('package_name_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whmcs_hostings');
    }
};
