<?php

namespace App\Filament\Resources\SuratResource\Pages;

use App\Filament\Resources\SuratResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Notifications\NewSuratDiajukanNotification; // Tambahkan ini
use Illuminate\Support\Facades\Notification as LaravelNotification;
use App\Models\User;



class CreateSurat extends CreateRecord
{
    protected static string $resource = SuratResource::class;
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        abort_if(!Auth::user()->isPegawai(), 403, 'Anda tidak memiliki izin untuk membuat surat.');
    }
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Surat berhasil diajukan';
    }

     // Method ini dipanggil setelah record berhasil dibuat
     protected function afterCreate(): void
     {
         // Ambil semua user dengan role pimpinan
         $pimpinans = User::where('role', 'pimpinan')->get();
         $surat = $this->record; // Record Surat yang baru saja dibuat
 
         // Kirim notifikasi ke setiap pimpinan
         if ($pimpinans->isNotEmpty() && $surat) {
             LaravelNotification::send($pimpinans, new NewSuratDiajukanNotification($surat));
         }
     }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['tanggal_pengajuan'] = now();
        $data['status'] = 'menunggu_persetujuan'; // Default status
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
