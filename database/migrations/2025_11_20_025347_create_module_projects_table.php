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
        Schema::create('module_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version');
            $table->string('github_url')->nullable();
            $table->string('download_url')->nullable();
            $table->enum('type', ['theme', 'plugin', 'child_theme']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_projects');
    }
};
