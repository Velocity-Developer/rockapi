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
        Schema::create('follow_up_perpanjang', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(0);
            $table->datetime('tanggal');
            $table->foreignId('whmcs_user_id')->nullable()->constrained();
            $table->BigInteger('whmcs_domain_id')->nullable();
            $table->BigInteger('whmcs_hosting_id')->nullable();
            $table->BigInteger('webhost_id')->nullable();
            $table->BigInteger('user_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->text('alasan')->nullable();
            $table->timestamps();

            //index
            $table->index('whmcs_user_id');
            $table->index('webhost_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_perpanjang');
    }
};
