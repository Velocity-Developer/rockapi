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
        Schema::create('server_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->boolean('cron')->nullable();
            $table->string('domain')->nullable();
            $table->string('domains')->nullable();
            $table->string('ip')->nullable();
            $table->string('lets_encrypt')->nullable();
            $table->string('memory_high')->nullable();
            $table->string('memory_low')->nullable();
            $table->string('name')->nullable();
            $table->string('ns1')->nullable();
            $table->string('ns2')->nullable();
            $table->string('package')->nullable();
            $table->foreignId('server_package_id')->nullable()->constrained('server_packages')->onDelete('set null');
            $table->boolean('php')->nullable();
            $table->boolean('spam')->nullable();
            $table->boolean('ssh')->nullable();
            $table->boolean('ssl')->nullable();
            $table->boolean('suspended')->nullable();
            $table->string('user_type')->nullable();
            $table->string('users')->nullable();
            $table->boolean('wordpress')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_users');
    }
};
