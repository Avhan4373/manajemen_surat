<?php

namespace App\Filament\Resources\SuratResource\Pages;

use App\Filament\Resources\SuratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use App\Models\Surat;
use App\Models\User;  // Tambahkan ini
use Filament\Forms; // Tambahkan ini
use Illuminate\Support\Facades\Gate; // Jika menggunakan Gate untuk otorisasi
use Barryvdh\DomPDF\Facade\Pdf; // Tambahkan ini
use Carbon\Carbon; // Tambahkan ini

class ListSurats extends ListRecords
{
    protected static string $resource = SuratResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        $user = Auth::user();
        // Tombol create hanya untuk pegawai
        // Tombol create hanya untuk pegawai
        if ($user->isPegawai()) {
            $actions[] = Actions\CreateAction::make();
        }

        // Tombol Download Laporan
        // Semua user terautentikasi bisa men-download laporan (sesuaikan jika perlu)
        $actions[] = Actions\Action::make('downloadLaporanSurat')
            ->label('Download Laporan Surat')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->form([
                Forms\Components\DatePicker::make('tanggal_mulai_laporan')
                    ->label('Tanggal Mulai Laporan')
                    ->required()
                    ->default(now()->startOfMonth()) // Default awal bulan ini
                    ->native(false),
                Forms\Components\DatePicker::make('tanggal_selesai_laporan')
                    ->label('Tanggal Selesai Laporan')
                    ->required()
                    ->default(now()->endOfMonth()) // Default akhir bulan ini
                    ->minDate(fn (Forms\Get $get) => $get('tanggal_mulai_laporan'))
                    ->native(false),
                Forms\Components\Select::make('pegawai_laporan')
                    ->label('Pegawai (Kosongkan untuk semua)')
                    ->options(User::where('role', 'pegawai')->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn () => Auth::user()->isPimpinan()), // Hanya Pimpinan yang bisa filter per pegawai
            ])
            ->action(function (array $data) {
                $tanggalMulai = Carbon::parse($data['tanggal_mulai_laporan'])->startOfDay();
                $tanggalSelesai = Carbon::parse($data['tanggal_selesai_laporan'])->endOfDay();
                $pegawaiId = $data['pegawai_laporan'] ?? null;
                $user = Auth::user();

                $query = Surat::query()
                    ->with('pegawai', 'pimpinan') // Eager load relasi
                    ->whereBetween('tanggal_pengajuan', [$tanggalMulai, $tanggalSelesai]);

                if ($user->isPegawai()) {
                    $query->where('user_id', $user->id);
                } elseif ($pegawaiId && $user->isPimpinan()) {
                    $query->where('user_id', $pegawaiId);
                }
                // Jika pimpinan dan $pegawaiId kosong, maka ambil semua

                $surats = $query->orderBy('tanggal_pengajuan', 'desc')->get();

                if ($surats->isEmpty()) {
                    \Filament\Notifications\Notification::make()
                        ->title('Tidak Ada Data')
                        ->body('Tidak ada data surat ditemukan untuk rentang tanggal dan filter yang dipilih.')
                        ->warning()
                        ->send();
                    return;
                }

                $namaPegawaiFilter = ($pegawaiId && $user->isPimpinan()) ? User::find($pegawaiId)?->name : 'Semua Pegawai';
                if ($user->isPegawai()) {
                    $namaPegawaiFilter = $user->name;
                }


                // Data untuk view PDF
                $pdfData = [
                    'surats' => $surats,
                    'tanggalMulai' => $tanggalMulai,
                    'tanggalSelesai' => $tanggalSelesai,
                    'currentUser' => $user,
                    'namaPegawaiFilter' => $namaPegawaiFilter, // Nama pegawai yang difilter (jika ada)
                ];

                // Generate PDF
                $pdf = Pdf::loadView('pdf.laporan_surat', $pdfData);
                $fileName = 'laporan-surat-' . $tanggalMulai->format('Ymd') . '-' . $tanggalSelesai->format('Ymd') . '.pdf';

                // Mengirimkan PDF sebagai stream response untuk di-download
                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $fileName);

            });


        return $actions;
    }
}
