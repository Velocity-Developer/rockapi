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
        Schema::table('webhost_subscriptions', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->date('paid_at')->nullable()->after('payment_status');

            $table->index('payment_status');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhost_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['paid_at']);

            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
