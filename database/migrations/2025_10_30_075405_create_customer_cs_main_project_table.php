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
        Schema::create('customer_cs_main_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->integer('cs_main_project_id'); // tanpa constraint karena table legacy
            $table->timestamps();

            // Composite unique key
            $table->unique(['customer_id', 'cs_main_project_id']);

            // Index untuk performance
            $table->index('cs_main_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_cs_main_project');
    }
};
