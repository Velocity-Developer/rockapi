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
        Schema::create('whmcs_domains', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('whmcs_id')->nullable();
            $table->string('domain');
            $table->date('expirydate')->nullable();
            $table->date('registrationdate')->nullable();
            $table->date('nextduedate')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('registrar')->nullable();
            $table->string('user_email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whmcs_domains');
    }
};
