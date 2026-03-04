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
        Schema::create('whmcs_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->string('email')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whmcs_users');
    }
};
