<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
// ... other use statements
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table; // Pastikan ini di-import
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;      // Pastikan namespace ini benar
use App\Exports\UsersExport;      // Pastikan namespace ini benar
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')
                    ->options([
                        'pegawai' => 'Pegawai',
                        'pimpinan' => 'Pimpinan',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->mutateDehydratedStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->maxLength(255)
                    ->visibleOn(['create', 'edit'])
                    ->placeholder(fn (string $operation): string => $operation === 'edit' ? 'Isi untuk ganti password' : ''),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At (Opsional)')
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pimpinan' => 'success',
                        'pegawai' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'pegawai' => 'Pegawai',
                        'pimpinan' => 'Pimpinan',
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                TableAction::make('importUsers')
                    ->label('Impor')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('attachment')
                            ->label('File Excel (.xlsx, .xls, .csv)')
                            ->required()
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                            ->disk('local')
                            ->directory('imports'),
                    ])
                    ->action(function (array $data): void {
                        $attachmentPath = $data['attachment']; // Ini adalah path relatif dari root disk 'local'

    // Dapatkan path absolut ke file
    // Disk 'local' memiliki root di storage_path('app')
    $absoluteFilePath = storage_path('app/' . $attachmentPath);

    // Logging untuk debugging
    \Illuminate\Support\Facades\Log::info('Import Action Triggered.');
    \Illuminate\Support\Facades\Log::info('Attachment path from FileUpload: ' . $attachmentPath);
    \Illuminate\Support\Facades\Log::info('Attempting to read file from absolute path: ' . $absoluteFilePath);

    if (!file_exists($absoluteFilePath)) {
        \Illuminate\Support\Facades\Log::error('File does not exist at the absolute path.');
        \Filament\Notifications\Notification::make()
            ->title('Gagal Impor')
            ->danger()
            ->body("File tidak ditemukan di server: " . $absoluteFilePath)
            ->persistent()
            ->send();
        return;
    }

    if (!is_readable($absoluteFilePath)) {
        \Illuminate\Support\Facades\Log::error('File is not readable at the absolute path.');
        \Filament\Notifications\Notification::make()
            ->title('Gagal Impor')
            ->danger()
            ->body("File tidak dapat dibaca oleh server: " . $absoluteFilePath . ". Periksa izin file.")
            ->persistent()
            ->send();
        return;
    }

    try {
        $importer = new \App\Imports\UsersImport; // Pastikan namespace benar
        Excel::import($importer, $absoluteFilePath);
    
        $rowCount = $importer->getRowCount(); // Jika Anda menambahkan method ini
        $importedCount = $importer->getImportedCount(); // Jika Anda menambahkan method ini
    
        \Filament\Notifications\Notification::make()
            ->title('Impor Selesai')
            ->body("Proses impor selesai. Baris diproses: {$rowCount}. Baris berhasil diimpor: {$importedCount}.")
            ->success() // Ubah ke warning jika importedCount == 0 && rowCount > 0
            ->send();
    
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        $errorMessages = [];
        foreach ($failures as $failure) {
            $errorMessages[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors()) . " (Nilai: " . implode(', ', $failure->values()) . ")";
        }
        \Filament\Notifications\Notification::make()
            ->title('Gagal Impor: Terdapat Kesalahan Validasi')
            ->danger()
            ->body(implode("\n", $errorMessages))
            ->persistent()
            ->send();
        \Illuminate\Support\Facades\Log::warning('Import validation errors: ' . implode("\n", $errorMessages));
    } catch (\Exception $e) {
        \Filament\Notifications\Notification::make()
            ->title('Gagal Impor')
            ->danger()
            ->body('Terjadi kesalahan umum saat mengimpor data: ' . $e->getMessage())
            ->persistent()
            ->send();
        \Illuminate\Support\Facades\Log::error('General import exception: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
    } finally {
        // Hapus file setelah impor (baik berhasil maupun gagal)
        if (file_exists($absoluteFilePath)) {
            unlink($absoluteFilePath);
            \Illuminate\Support\Facades\Log::info('Temporary import file deleted: ' . $absoluteFilePath);
        }
    }
})
                    ->color('primary'),

                TableAction::make('exportAllUsers')
                    ->label('Ekspor')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([ // Form untuk filter ekspor
                        Forms\Components\Select::make('export_role_filter')
                            ->label('Filter Role (Opsional)')
                            ->options([
                                'pegawai' => 'Pegawai',
                                'pimpinan' => 'Pimpinan',
                            ])
                            ->placeholder('Semua Role'),
                        // Tambahkan field filter lain di sini jika perlu
                        // Forms\Components\TextInput::make('export_search_term')
                        //     ->label('Kata Kunci Pencarian (Opsional)'),
                    ])
                    ->action(function (array $data) { // $data berasal dari form di atas
                        $filtersForExport = [];
                        if (!empty($data['export_role_filter'])) {
                            $filtersForExport['role'] = $data['export_role_filter'];
                        }
                        // if (!empty($data['export_search_term'])) {
                        //     $filtersForExport['search'] = $data['export_search_term'];
                        // }

                        // Teruskan filter ini ke class UsersExport
                        return Excel::download(new UsersExport(null, $filtersForExport), 'daftar_users_'.date('Y-m-d_H-i-s').'.csv');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('exportSelectedUsers')
                        ->label('Ekspor User Terpilih')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (EloquentCollection $records) {
                            $selectedIds = $records->pluck('id')->toArray();
                            return Excel::download(new UsersExport($selectedIds, []), 'users_terpilih_'.date('Y-m-d_H-i-s').'.xlsx');
                            // Untuk ekspor terpilih, kita tidak perlu $filters tambahan, jadi kirim array kosong
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}