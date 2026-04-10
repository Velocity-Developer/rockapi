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
        Schema::create('webhost_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhost_id');
            $table->unsignedBigInteger('cs_main_project_id')->nullable();
            $table->unsignedBigInteger('parent_subscription_id')->nullable();
            $table->string('source_type')->default('csmainproject');
            $table->string('service_type')->default('hosting');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('renewed_from_date')->nullable();
            $table->string('status')->default('active');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('webhost_id');
            $table->index('cs_main_project_id');
            $table->index('parent_subscription_id');
            $table->index(['webhost_id', 'service_type']);
            $table->index(['webhost_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhost_subscriptions');
    }
};
