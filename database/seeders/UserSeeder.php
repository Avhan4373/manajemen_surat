<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Pimpinan A',
            'email' => 'pimpinan@example.com',
            'password' => Hash::make('password'),
            'role' => 'pimpinan',
        ]);

        User::create([
            'name' => 'Pegawai Budi',
            'email' => 'budi@example.com',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
        ]);

        User::create([
            'name' => 'Pegawai Ani',
            'email' => 'ani@example.com',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
        ]);
    
    }
}
