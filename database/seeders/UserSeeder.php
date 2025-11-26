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
            'lab_id'       => null,
        ]);

        // Admin Lab RDBI
        User::create([
            'name'         => 'Admin Lab RDBI',
            'email'        => 'admin.rdbi@gmail.com',
            'password'     => Hash::make('password'),
            'role'         => 'admin_lab',
            'phone_number' => '082233445566',
            'lab_id'       => 1,
        ]);

        // Admin Lab SD
        User::create([
            'name'         => 'Admin Lab SD',
            'email'        => 'admin.lsd@gmail.com',
            'password'     => Hash::make('password123'),
            'role'         => 'admin_lab',
            'phone_number' => '083344556677',
            'lab_id'       => 2, // sesuaikan dengan tabel labs
        ]);

            // Admin Lab SE
        User::create([
            'name'         => 'Admin Lab SE',
            'email'        => 'admin.se@gmail.com',
            'password'     => Hash::make('password'),
            'role'         => 'admin_lab',
            'phone_number' => '082233445567',
            'lab_id'       => 3,
        ]);

        // Admin Lab SD
        User::create([
            'name'         => 'Admin Lab TKITI',
            'email'        => 'admin.tkiti@gmail.com',
            'password'     => Hash::make('password123'),
            'role'         => 'admin_lab',
            'phone_number' => '083344556556',
            'lab_id'       => 4, 
        ]);

        // Dosen
        User::create([
            'name'         => 'John Doe',
            'email'        => 'johndoe@gmail.com',
            'password'     => Hash::make('password'),
            'role'         => 'dosen',
            'phone_number' => '081998877665',
            'lab_id'       => null, // Dosen tidak memiliki lab
        ]);
    }
}