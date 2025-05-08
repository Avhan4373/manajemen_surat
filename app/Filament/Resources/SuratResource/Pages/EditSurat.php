<?php

namespace App\Filament\Resources\SuratResource\Pages;

use App\Filament\Resources\SuratResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use Filament\Forms; // Tambahkan ini
use Illuminate\Support\Facades\Notification as LaravelNotification; // Tambahkan ini
use Filament\Notifications\Notification as FilamentUINotification; // Tambahkan ini
use App\Notifications\SuratStatusUpdatedNotification; // Untuk notifikasi ke pegawai
use App\Models\User; // Untuk mendapatkan user yang mengajukan surat
use App\Notifications\NewSuratDiajukanNotification; // Tambahkan ini



class EditSurat extends EditRecord
{
    protected static string $resource = SuratResource::class;

      // Otorisasi: Hanya Pimpinan yang boleh mengedit (untuk menyetujui/menolak)
    // atau Pegawai yang mengajukan jika statusnya masih memungkinkan (misal, sebelum diajukan - tapi ini sudah ditangani di create)
    // Untuk kasus ini, setelah diajukan, hanya Pimpinan yang bisa "edit" untuk memberi keputusan.
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        $user = Auth::user();
        $record = $this->getRecord(); // Dapatkan record Surat saat ini

        // Jika user adalah pegawai, mereka tidak bisa mengedit setelah diajukan (form fields sudah di-disable)
        // Kecuali jika Anda ingin mereka bisa membatalkan, dll. (tidak diminta saat ini)
        // Pimpinan bisa "mengedit" untuk memberi keputusan jika statusnya 'menunggu_persetujuan'
        abort_if(
            !$user->isPimpinan() && $user->id !== $record->user_id,
            403,
            'Anda tidak memiliki izin untuk mengakses halaman ini.'
        );

        // Pegawai hanya boleh melihat (form fields disabled), tidak boleh ada action setujui/tolak
        // Pimpinan boleh melakukan action jika status menunggu persetujuan
    }
    protected function getHeaderActions(): array
    {
        $actions = [];
        $user = Auth::user();
        $surat = $this->getRecord(); // Dapatkan model Surat saat ini

        // Hanya tampilkan action Setujui/Tolak untuk Pimpinan dan jika status masih 'menunggu_persetujuan'
        if ($user->isPimpinan() && $surat instanceof Surat && $surat->status === 'menunggu_persetujuan') {
            $actions = [
                Actions\Action::make('setujui_surat')
                    ->label('Setujui Surat Ini')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Surat')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui surat ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->form([
                        Forms\Components\Textarea::make('catatan_pimpinan_approve')
                            ->label('Catatan Persetujuan (Opsional)')
                    ])
                    ->action(function (array $data) use ($surat) {
                        $surat->update([
                            'status' => 'disetujui',
                            'approver_id' => Auth::id(),
                            'catatan_pimpinan' => $data['catatan_pimpinan_approve'],
                        ]);

                        FilamentUINotification::make()
                            ->title('Surat berhasil disetujui')
                            ->success()
                            ->send();

                        // Kirim Notifikasi Database ke Pegawai
                        $pegawai = $surat->pegawai;
                        if ($pegawai) {
                            LaravelNotification::send($pegawai, new SuratStatusUpdatedNotification($surat));
                        }
                        // Redirect atau refresh mungkin diperlukan, atau Filament akan otomatis refresh data
                        $this->refreshFormData([
                            'status',
                            'catatan_pimpinan',
                            // 'pimpinan' // jika ingin refresh data pimpinan
                        ]);
                        $this->dispatch('refreshPage'); // Event untuk merefresh komponen lain jika perlu

                    }),

                Actions\Action::make('tolak_surat')
                    ->label('Tolak Surat Ini')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Surat')
                    ->modalDescription('Apakah Anda yakin ingin menolak surat ini?')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->form([
                        Forms\Components\Textarea::make('catatan_pimpinan_reject')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($surat) {
                        $surat->update([
                            'status' => 'ditolak',
                            'approver_id' => Auth::id(),
                            'catatan_pimpinan' => $data['catatan_pimpinan_reject'],
                        ]);

                        FilamentUINotification::make()
                            ->title('Surat berhasil ditolak')
                            ->success() // atau ->warning()
                            ->send();

                        // Kirim Notifikasi Database ke Pegawai
                        $pegawai = $surat->pegawai;
                        if ($pegawai) {
                            LaravelNotification::send($pegawai, new SuratStatusUpdatedNotification($surat));
                        }
                        $this->refreshFormData([
                            'status',
                            'catatan_pimpinan',
                        ]);
                         $this->dispatch('refreshPage');
                    }),
            ];
        }

        // Tombol Cetak PDF untuk semua yang bisa akses halaman edit JIKA surat sudah disetujui
        if ($surat instanceof Surat && $surat->status === 'disetujui') {
            // Pastikan user yang login adalah pegawai ybs atau pimpinan
            if ($user->id === $surat->user_id || $user->isPimpinan()) {
                $actions[] = Actions\Action::make('cetak_pdf_edit_page')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (): string => route('surat.pdf', $surat))
                    ->openUrlInNewTab();
            }
        }

        // Selalu tambahkan tombol Simpan standar jika ada perubahan pada form (meskipun field pimpinan mungkin satu-satunya yang bisa diubah)
        // $actions[] = Actions\SaveAction::make(); // Tombol Save standar, jika Pimpinan bisa edit field di form

        // Jika Pimpinan tidak seharusnya bisa edit field di form sama sekali (hanya memberi catatan via modal)
        // maka SaveAction bisa di-disable atau disembunyikan.
        // Atau, kita bisa menghapus tombol save bawaan dan hanya mengandalkan action Setujui/Tolak.

        return $actions;
    }

    // Kita bisa menonaktifkan tombol "Save" bawaan jika semua aksi melalui modal Setujui/Tolak
    // protected function getFormActions(): array
    // {
    //     return [];
    // }

    // Atau jika pimpinan masih bisa edit field 'catatan_pimpinan' langsung di form:
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Jika ada field di form utama yang diisi oleh Pimpinan sebelum Save (misal 'catatan_pimpinan' jika tidak via modal)
        // $data['approver_id'] = Auth::id(); // Jika status diubah melalui field di form, bukan action modal
        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        // Jika menggunakan SaveAction standar
        return 'Perubahan pada surat berhasil disimpan';
    }

    // Redirect ke halaman index setelah Save (jika menggunakan SaveAction standar)
    // atau setelah aksi Setujui/Tolak jika tidak ada redirect di dalam action itu sendiri.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
