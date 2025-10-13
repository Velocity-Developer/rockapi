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
        Schema::create('todo_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained('todo_lists')->onDelete('cascade');
            $table->string('assignable_type'); // 'user' or 'role'
            $table->unsignedBigInteger('assignable_id'); // user_id or role_id
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->default(now());
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('assigned'); // assigned, in_progress, completed, declined
            $table->timestamps();

            $table->index(['todo_id', 'assignable_type', 'assignable_id']);
            $table->index(['assignable_type', 'assignable_id', 'status']);
            $table->index(['assigned_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_assignments');
    }
};
