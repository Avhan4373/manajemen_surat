<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratResource\Pages;
use App\Filament\Resources\SuratResource\RelationManagers;
use App\Models\Surat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification; // Tambahkan ini di atas class
use App\Notifications\SuratStatusUpdatedNotification; // Tambahkan ini
use Illuminate\Support\Facades\Notification as LaravelNotification; // Alias jika diperlukan
use Filament\Notifications\Notification as FilamentUINotification; // Untuk notifikasi UI Filament

class SuratResource extends Resource
{
    protected static ?string $model = Surat::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('jenis_surat')
                        ->options([
                            'izin' => 'Surat Izin',
                            'tugas' => 'Surat Tugas',
                        ])
                        ->required()
                        ->disabled(fn (string $operation): bool => $operation !== 'create' && $user->isPimpinan()) // Pimpinan tidak bisa edit jenis surat
                        ->visible(fn (string $operation): bool => $operation === 'create' || $user->isPegawai()),
                        Forms\Components\DatePicker::make('tanggal_mulai')
                    ->required()
                    ->native(false)
                    ->disabled(fn (string $operation): bool => $operation !== 'create' && $user->isPimpinan())
                    ->visible(fn (string $operation): bool => $operation === 'create' || $user->isPegawai()),

                Forms\Components\DatePicker::make('tanggal_selesai')
                    ->required()
                    ->native(false)
                    ->minDate(fn (Forms\Get $get) => $get('tanggal_mulai')) // Tanggal selesai tidak boleh sebelum tanggal mulai
                    ->disabled(fn (string $operation): bool => $operation !== 'create' && $user->isPimpinan())
                    ->visible(fn (string $operation): bool => $operation === 'create' || $user->isPegawai()),
                    Forms\Components\Textarea::make('uraian')
                    ->required()
                    ->columnSpanFull()
                    ->disabled(fn (string $operation): bool => $operation !== 'create' && $user->isPimpinan())
                    ->visible(fn (string $operation): bool => $operation === 'create' || $user->isPegawai()),

                    //     Forms\Components\FileUpload::make('lampiran')
                    //     ->disk('public') // Simpan ke public disk
                    //     ->directory('lampiran_surat')
                    //     ->visibility('public') // Pastikan file bisa diakses publik
                    //     ->downloadable()
                    //     ->openable()
                    //     ->disabled(fn (string $operation): bool => $operation !== 'create' && $user->isPimpinan())
                    // ->visible(fn (string $operation): bool => $operation === 'create' || $user->isPegawai()),
                    ]),
                     // Field ini hanya untuk pimpinan saat menyetujui/menolak
                Forms\Components\Textarea::make('catatan_pimpinan')
                ->label('Catatan/Keterangan Keputusan')
                ->columnSpanFull()
                ->disabled() // Selalu disabled di form utama, diisi via modal action
                ->visible(fn (string $operation, ?Surat $record): bool => $record !== null && $record->status !== 'menunggu_persetujuan'), // Tampil jika sudah ada 

            // Field ini hanya ditampilkan, tidak bisa diedit oleh pegawai
            Forms\Components\TextInput::make('status')
                ->label('Status Saat Ini')
                ->disabled()
                ->formatStateUsing(fn (?string $state): string => $state ? str_replace('_', ' ', ucfirst($state)) : '-')
                ->visible(fn (string $operation, ?Surat $record): bool => ($operation === 'edit' || $operation === 'view') && $record !== null),
                Forms\Components\Placeholder::make('tanggal_pengajuan_info')
                ->label('Tanggal Pengajuan')
                ->content(fn (?Surat $record): string => $record?->tanggal_pengajuan?->translatedFormat('d F Y H:i') ?? '-')
                ->visible(fn (string $operation, ?Surat $record): bool => ($operation === 'edit' || $operation === 'view') && $record !== null),

            Forms\Components\Placeholder::make('pemohon_info')
                ->label('Diajukan Oleh')
                ->content(fn (?Surat $record): string => $record?->pegawai?->name ?? '-')
                ->visible(fn (string $operation, ?Surat $record): bool => ($operation === 'edit' || $operation === 'view') && $record !== null && $user->isPimpinan()),

            Forms\Components\Placeholder::make('disetujui_oleh_info')
                ->label('Disetujui/Ditolak Oleh')
                ->content(fn (?Surat $record): string => $record?->pimpinan?->name ?? '-')
                ->visible(fn (string $operation, ?Surat $record): bool => ($operation === 'edit' || $operation === 'view') && $record !== null && $record?->status !== 'menunggu_persetujuan'),
            ]);
            
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->visible($user->isPimpinan()), // Hanya pimpinan yang bisa lihat kolom ini
                    Tables\Columns\TextColumn::make('jenis_surat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'izin' => 'warning',
                        'tugas' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->dateTime('d M Y H:i')
                    ->label('Tgl. Pengajuan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu_persetujuan' => 'gray',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->searchable(),
                    Tables\Columns\TextColumn::make('pimpinan.name')
                    ->label('Pimpinan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($user->isPimpinan()), // Hanya pimpinan yang bisa lihat kolom ini
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu_persetujuan' => 'Menunggu Persetujuan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('jenis_surat')
                ->options([
                    'izin' => 'Izin',
                    'tugas' => 'Tugas',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    // Pimpinan hanya bisa edit jika status 'menunggu_persetujuan' untuk memberi catatan
                    // Pegawai tidak bisa edit setelah diajukan (asumsi)
                    ->visible(fn (Surat $record): bool => Auth::user()->isPimpinan() && $record->status === 'menunggu_persetujuan'),

                // Aksi untuk Pimpinan
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('catatan_pimpinan_approve')
                            ->label('Catatan (Opsional)')
                    ])
                    ->action(function (Surat $record, array $data) {
                        $record->update([
                            'status' => 'disetujui',
                            'approver_id' => Auth::id(),
                            'catatan_pimpinan' => $data['catatan_pimpinan_approve'],
                        ]);
                        Notification::make()
                        ->title('Surat berhasil disetujui')
                        ->success()
                        ->send();
                        // TODO: Tambahkan notifikasi
                         // Kirim Notifikasi Database ke Pegawai
                            $pegawai = $record->pegawai;
                            if ($pegawai) {
                                LaravelNotification::send($pegawai, new SuratStatusUpdatedNotification($record));
                            }
                    })
                    ->visible(fn (Surat $record): bool => Auth::user()->isPimpinan() && $record->status === 'menunggu_persetujuan'),

                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('catatan_pimpinan_reject')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (Surat $record, array $data) {
                        $record->update([
                            'status' => 'ditolak',
                            'approver_id' => Auth::id(),
                            'catatan_pimpinan' => $data['catatan_pimpinan_reject'],
                        ]);
                        // TODO: Tambahkan notifikasi
                        Notification::make()
                        ->title('Surat berhasil ditolak')
                        ->success() // atau ->warning()
                        ->send();
                        // Kirim Notifikasi Database ke Pegawai
                            $pegawai = $record->pegawai;
                            if ($pegawai) {
                                LaravelNotification::send($pegawai, new SuratStatusUpdatedNotification($record));
                            }
                
                    })
                    ->visible(fn (Surat $record): bool => Auth::user()->isPimpinan() && $record->status === 'menunggu_persetujuan'),

                // Aksi Cetak PDF
                Tables\Actions\Action::make('cetakPdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Surat $record): string => route('surat.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Surat $record): bool => $record->status === 'disetujui'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

     // Modifikasi Eloquent Query berdasarkan role
     public static function getEloquentQuery(): Builder
     {
         $user = Auth::user();
         if ($user->isPegawai()) {
             return parent::getEloquentQuery()->where('user_id', $user->id)->orderBy('tanggal_pengajuan', 'desc');
         }
         // Pimpinan bisa melihat semua
         return parent::getEloquentQuery()->orderBy('tanggal_pengajuan', 'desc');
     }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurats::route('/'),
            'create' => Pages\CreateSurat::route('/create'),
            'view' => Pages\ViewSurat::route('/{record}'),
            'edit' => Pages\EditSurat::route('/{record}/edit'),
        ];
    }

     // Pegawai tidak bisa menghapus surat yang sudah diajukan (asumsi)
    // Pimpinan bisa menghapus (jika diperlukan, bisa disesuaikan)
    public static function canDelete(Model $record): bool
    {
        return Auth::user()->isPimpinan();
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->isPimpinan();
    }
}
