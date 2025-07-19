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
        Schema::create('server_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->integer('bandwidth')->nullable();
            $table->string('email_daily_limit')->nullable();
            $table->string('inode')->nullable();
            $table->integer('quota')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_packages');
    }
};
