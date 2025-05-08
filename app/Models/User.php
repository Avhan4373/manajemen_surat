<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

       // Method untuk Filament Panel Authorization
       public function canAccessPanel(Panel $panel): bool
       {
           // Untuk saat ini, semua user yang terautentikasi bisa akses panel admin
           // Nantinya bisa ditambahkan logika berdasarkan role jika ada panel berbeda
           return true;
       }

       // Relasi ke surat yang diajukan
    public function suratsDiajukan()
    {
        return $this->hasMany(Surat::class, 'user_id');
    }

    // Relasi ke surat yang disetujui/ditolak
    public function suratsDitangani()
    {
        return $this->hasMany(Surat::class, 'approver_id');
    }

    // Helper
    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }
}
