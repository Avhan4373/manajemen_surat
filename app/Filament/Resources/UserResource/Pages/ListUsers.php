<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportFilteredUsers')
            ->label('Ekspor Hasil Filter Tabel')
            ->action(function () {
                // $this merujuk ke instance ListUsers (Livewire component)
                $queryBuilder = $this->getFilteredTableQuery(); // Dapatkan query builder yang sudah terfilter
                // Anda perlu memodifikasi UsersExport untuk menerima QueryBuilder
                // atau mengeksekusi query dan mengirimkan collection
                // $dataToExport = $queryBuilder->get();
                // return Excel::download(new UsersExportFromCollection($dataToExport), '...');
                // Ini contoh, implementasi detailnya akan bergantung bagaimana UsersExport dirancang
            // Actions\CreateAction::make(),
            }),
        ];
    }
}
