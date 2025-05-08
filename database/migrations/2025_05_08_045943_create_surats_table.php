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
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->comment('Pegawai yang mengajukan');
            $table->foreignId('approver_id')->nullable()->constrained('users')->comment('Pimpinan yang menyetujui/menolak');
            $table->enum('jenis_surat', ['izin', 'tugas']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('uraian');
            $table->string('lampiran')->nullable();
            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->enum('status', ['menunggu_persetujuan', 'disetujui', 'ditolak'])->default('menunggu_persetujuan');
            $table->text('catatan_pimpinan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
