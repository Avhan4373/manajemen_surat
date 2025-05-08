<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Surat extends Model
{
    /** @use HasFactory<\Database\Factories\SuratFactory> */
    use HasFactory;
    protected $fillable = [
        'user_id',
        'approver_id',
        'jenis_surat',
        'tanggal_mulai',
        'tanggal_selesai',
        'uraian',
        'lampiran',
        'tanggal_pengajuan',
        'status',
        'catatan_pimpinan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_pengajuan' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pimpinan()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Accessor untuk mendapatkan URL lampiran
    public function getLampiranUrlAttribute()
    {
        if ($this->lampiran) {
            return Storage::url($this->lampiran);
        }
        return null;
    }
}
