<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Tambahkan untuk debugging
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private $rowCount = 0; // Untuk melacak baris yang diproses
    private $importedCount = 0; // Untuk melacak baris yang berhasil diimpor

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $this->rowCount++;
        Log::info("Processing row #{$this->rowCount}: " . json_encode($row));

        // Skenario 1: Nama kolom di Excel tidak cocok dengan key di $row
        // Misal di Excel 'Nama Lengkap', tapi di sini $row['nama']
        // Pastikan key di $row (yang diambil dari WithHeadingRow) sudah benar.
        // Dump $row untuk melihat key yang tersedia:
        // Log::info("Available keys in row: " . implode(', ', array_keys($row)));

        // Skenario 2: Kondisi skip selalu terpenuhi
        $existingUser = User::where('email', $row['email'] ?? null)->first(); // Tambahkan null coalesce untuk email
        if ($existingUser) {
            Log::info("Skipping row #{$this->rowCount} for email {$row['email']}: User already exists.");
            return null; // Skip baris ini jika email sudah ada
        }

        // Skenario 3: Data yang dibutuhkan tidak ada atau kosong, menyebabkan validasi gagal secara diam-diam
        // atau membuat objek User tidak valid.
        if (empty($row['nama']) || empty($row['email']) || empty($row['role']) || empty($row['password'])) {
            Log::warning("Skipping row #{$this->rowCount} due to missing required fields: " . json_encode($row));
            // Anda bisa menambahkan ini ke SkipsFailures jika ingin melaporkannya sebagai failure
            // $this->onFailure(new Failure($this->rowCount, 'required_fields', ['Nama, email, role, atau password kosong'], $row));
            return null;
        }

        // Pastikan role di-handle dengan benar
        $role = strtolower($row['role'] ?? '');
        if (!in_array($role, ['pegawai', 'pimpinan'])) {
            Log::warning("Skipping row #{$this->rowCount} due to invalid role '{$role}': " . json_encode($row));
            // $this->onFailure(new Failure($this->rowCount, 'invalid_role', ["Role '{$role}' tidak valid"], $row));
            return null;
        }

        try {
            $user = new User([
                'name'     => $row['nama'],
                'email'    => $row['email'],
                'role'     => $role,
                'password' => Hash::make($row['password']),
                'email_verified_at' => isset($row['email_verified_at']) && !empty($row['email_verified_at'])
                                        ? \Carbon\Carbon::parse($row['email_verified_at'])->toDateTimeString()
                                        : null,
            ]);
            Log::info("Successfully created User object for row #{$this->rowCount}: " . $user->email);
            $this->importedCount++;
            return $user; // Mengembalikan objek User agar Maatwebsite menyimpannya
        } catch (\Exception $e) {
            Log::error("Error creating User object for row #{$this->rowCount}: " . $e->getMessage() . " Data: " . json_encode($row));
            // Anda bisa menambahkan ini ke SkipsFailures
            // $this->onFailure(new Failure($this->rowCount, 'creation_error', [$e->getMessage()], $row));
            return null; // Gagal membuat objek, skip
        }
    }

    public function rules(): array
    {
        return [
            // Gunakan *. untuk validasi setiap item dalam array/collection
            // Jika WithHeadingRow digunakan, validasi berdasarkan nama heading
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email', // Ini akan mencegah duplikasi
            'role' => 'required|string|in:pegawai,pimpinan,Pegawai,Pimpinan', // Validasi nilai role
            'password' => 'required|string|min:8',
            'email_verified_at' => 'nullable|date_format:Y-m-d H:i:s,d/m/Y H:i:s,Y-m-d,d/m/Y',
        ];
    }

    // ... (customValidationMessages dan onFailure jika perlu) ...

    // Opsional: Method untuk mendapatkan statistik setelah impor
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}