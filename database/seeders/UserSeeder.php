<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Jurusan DSI
        User::create([
            'name'         => 'Admin DSI',
            'email'        => 'admin_dsi@gmail.com',
            'password'     => Hash::make('password'),
            'role'         => 'admin_jurusan',
            'phone_number' => '081234567890',
            'lab_id'       => null, // Tidak terkait lab
        ]);

        // Admin Lab contoh (isi sesuai kebutuhan)
        User::create([
            'name'         => 'Admin Lab Komputer',
            'email'        => 'admin_lab@gmail.com',
            'password'     => Hash::make('password'),
            'role'         => 'admin_lab',
            'phone_number' => '082233445566',
            'lab_id'       => 1, // sesuaikan dengan tabel labs
        ]);
    }
}