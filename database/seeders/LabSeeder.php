<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lab;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        Lab::create([
            'name' => 'Lab Komputer 1',
            'description' => 'Laboratorium Komputer untuk praktikum dasar.',
        ]);

        Lab::create([
            'name' => 'Lab Komputer 2',
            'description' => 'Laboratorium Komputer untuk praktikum lanjutan.',
        ]);

        Lab::create([
            'name' => 'Lab Jaringan',
            'description' => 'Laboratorium khusus jaringan dan infrastruktur TI.',
        ]);
    }
}