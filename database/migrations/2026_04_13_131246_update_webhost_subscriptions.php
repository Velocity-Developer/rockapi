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
            // Tambah kolom baru
            $table->date('nextduedate')->nullable()->after('end_date');

            // Hapus kolom yang tidak diperlukan
            $table->dropColumn([
                'is_whmcs_mismatch',
                'description',
                'nominal',
                'provider_status',
                'provider_expiry_date',
                'renewed_from_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhost_subscriptions', function (Blueprint $table) {
            // Rollback: hapus kolom baru
            $table->dropColumn('nextduedate');

            // Rollback: tambahkan kolom yang dihapus
            $table->string('description')->nullable()->after('nominal');
            $table->string('nominal')->nullable()->after('description');
            $table->string('provider_status')->nullable()->after('nominal');
            $table->date('provider_expiry_date')->nullable()->after('provider_status');
            $table->boolean('is_whmcs_mismatch')->default(false)->after('provider_expiry_date');
            $table->date('renewed_from_date')->nullable()->after('is_whmcs_mismatch');
        });
    }
};
