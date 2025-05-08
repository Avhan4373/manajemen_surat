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


class SuratStatusUpdatedNotification extends Notification
{
    use Queueable;

    public Surat $surat;

    public function __construct(Surat $surat)
    {
        $this->surat = $surat;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusText = str_replace('_', ' ', ucfirst($this->surat->status));
        $title = "Status Surat Anda Diperbarui";
        $body = "Pengajuan surat {$this->surat->jenis_surat} Anda tanggal {$this->surat->tanggal_mulai->format('d M Y')} telah **{$statusText}**.";

        if ($this->surat->status === 'ditolak' && $this->surat->catatan_pimpinan) {
            $body .= " Alasan: " . $this->surat->catatan_pimpinan;
        } elseif ($this->surat->status === 'disetujui' && $this->surat->catatan_pimpinan) {
            $body .= " Catatan: " . $this->surat->catatan_pimpinan;
        }
        $url = SuratResource::getUrl('edit', ['record' => $this->surat->id]); // Default ke edit
        if ($notifiable->isPegawai()) {
             // Jika halaman 'view' sudah didefinisikan dan ingin digunakan untuk pegawai:
             // $url = SuratResource::getUrl('view', ['record' => $this->surat->id]);
             // Untuk saat ini, kita tetap ke 'edit' karena fieldnya sudah di-disable untuk pegawai
             $url = SuratResource::getUrl('edit', ['record' => $this->surat->id]);
        }

        return FilamentNotification::make()
            ->title($title)
            ->icon($this->surat->status === 'disetujui' ? 'heroicon-o-check-circle' : ($this->surat->status === 'ditolak' ? 'heroicon-o-x-circle' : 'heroicon-o-information-circle'))
            ->body($body)
            ->actions([
                FilamentAction::make('view')
                    ->label('Lihat Detail Surat')
                    // Arahkan ke halaman view surat
                    ->url(SuratResource::getUrl('view', ['record' => $this->surat->id]))
                    ->markAsRead(),
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
