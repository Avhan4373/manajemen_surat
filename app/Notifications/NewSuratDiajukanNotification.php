<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Surat;
use App\Filament\Resources\SuratResource;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action as FilamentAction;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Forms; // Import Forms
use App\Notifications\SuratStatusUpdatedNotification; // Untuk notifikasi ke pegawai




class NewSuratDiajukanNotification extends Notification
{
    use Queueable;
    public Surat $surat;

    public function __construct(Surat $surat)
    {
        $this->surat = $surat;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Kirim via database untuk Filament
    }
      // Untuk Filament Database Notification
      public function toDatabase(object $notifiable): array
      {
        return FilamentNotification::make()
        ->title('Pengajuan Surat Baru')
        ->icon('heroicon-o-envelope')
        ->body("Surat ". ucfirst($this->surat->jenis_surat) ." baru dari {$this->surat->pegawai->name} menunggu persetujuan Anda.")
        ->actions([
            FilamentAction::make('lihat_dan_tindak_lanjuti') // Nama action yang jelas
                ->label('Lihat & Tindak Lanjuti')
                // Arahkan ke halaman edit surat tersebut
                ->url(SuratResource::getUrl('edit', ['record' => $this->surat->id]))
                ->markAsRead(), // Tandai sebagai sudah dibaca saat diklik
        ])
        ->getDatabaseMessage();
}

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
