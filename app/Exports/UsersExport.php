<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// Hapus: use Maatwebsite\Excel\Concerns\Exportable;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    // Hapus: use Exportable;

    protected $selectedIds;
    protected $filters;

    public function __construct($selectedIds = null, $filters = [])
    {
        $this->selectedIds = $selectedIds;
        $this->filters = $filters;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = User::query();

        if (!empty($this->selectedIds)) {
            return $query->whereIn('id', $this->selectedIds)->get();
        }

        if (isset($this->filters['role']) && !empty($this->filters['role'])) {
            $query->where('role', $this->filters['role']);
        }
        // Anda bisa menambahkan filter lain di sini berdasarkan array $this->filters
        // if (isset($this->filters['search']) && !empty($this->filters['search'])) {
        //     $query->where(function ($q) {
        //         $q->where('name', 'like', '%' . $this->filters['search'] . '%')
        //           ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
        //     });
        // }


        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Email',
            'Role',
            'Password',
            'Email Verified At',
            'Created At',
            'Updated At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            ucfirst($user->role),
            $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null,
            $user->created_at->format('Y-m-d H:i:s'),
            $user->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}