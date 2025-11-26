<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lab;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        Lab::create([
            'name' => 'Lab RDBI',
            'description' => 'Berkaitan dengan data mining, bussines intelligence, machine learning, dan sistem informasi geografis.',
        ]);

        Lab::create([
            'name' => 'Lab SD',
            'description' => 'Berkaitan dengan pengembangan perangkat lunak dan sistem informasi.',
        ]);

         Lab::create([
            'name' => 'Lab SE',
            'description' => 'Berkaitan dengan sistem enterprise dan rekayasa perangkat lunak.',
        ]);

         Lab::create([
            'name' => 'Lab TKITI',
            'description' => 'Berkiatan dengan teknologi komunikasi dan informatika.',
       ]);
    }
}