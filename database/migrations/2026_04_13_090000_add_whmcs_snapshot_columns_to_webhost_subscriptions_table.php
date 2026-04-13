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
            $table->string('provider_status')->nullable()->after('payment_status');
            $table->date('provider_expiry_date')->nullable()->after('provider_status');
            $table->boolean('is_whmcs_mismatch')->default(false)->after('provider_expiry_date');

            $table->index('provider_status');
            $table->index('provider_expiry_date');
            $table->index('is_whmcs_mismatch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhost_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['provider_status']);
            $table->dropIndex(['provider_expiry_date']);
            $table->dropIndex(['is_whmcs_mismatch']);

            $table->dropColumn([
                'provider_status',
                'provider_expiry_date',
                'is_whmcs_mismatch',
            ]);
        });
    }
};
