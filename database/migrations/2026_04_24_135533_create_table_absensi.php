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
        Schema::create('absensi_shift', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->time('masuk');
            $table->time('pulang');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');
            $table->unsignedBigInteger('absensi_shift_id')->nullable();
            $table->enum('status', [
                'Hadir',
                'Terlambat',
                'Izin',
                'Sakit',
                'Cuti',
                'Alpha',
                'Libur',
                'Setengah Hari'
            ])->default('Hadir');
            $table->text('catatan')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Real absensi
            |--------------------------------------------------------------------------
            */
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_pulang')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Perhitungan otomatis
            |--------------------------------------------------------------------------
            */
            $table->integer('detik_telat')->default(0);
            $table->integer('detik_pulang_cepat')->default(0);
            $table->integer('detik_kurang')->default(0);
            $table->integer('detik_lebih')->default(0);
            $table->integer('total_detik_kerja')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Snapshot shift saat absen
            |--------------------------------------------------------------------------
            */
            $table->string('nama_shift')->nullable();
            $table->time('jadwal_masuk')->nullable();
            $table->time('jadwal_pulang')->nullable();

            $table->timestamps();
            $table->index('tanggal');
            $table->index('status');
            $table->index('absensi_shift_id');
        });

        Schema::create('absensi_revisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');
            $table->integer('detik')->default(0);
            $table->enum('jenis', [
                'Tambah',
                'Kurang',
            ])->default('Tambah');
            $table->string('sumber')->nullable(); //['Tambah Jam Pulang','Masuk Lebih Awal','Kerja Saat Istirahat','Lembur','Penyesuaian Admin']
            $table->unsignedBigInteger('approve_user_id')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_shift');
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('absensi_revisi');
    }
};
