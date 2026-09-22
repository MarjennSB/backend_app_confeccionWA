<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Color;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'WHITE', 'abbreviation' => 'WHIT'],
            ['name' => 'NAVY', 'abbreviation' => 'NAVY'],
            ['name' => 'PALMETTO', 'abbreviation' => 'PALM'],
            ['name' => 'PURE BLACK', 'abbreviation' => 'PURE'],
            ['name' => 'TRUE NAVY', 'abbreviation' => 'TRUE'],
            ['name' => 'CLASSIC NAVY', 'abbreviation' => 'CLASSIC'],
            ['name' => 'SOAPSTONE', 'abbreviation' => 'SOAPSTONE'],
            ['name' => 'BRIGHT SAPPHIRE', 'abbreviation' => 'BRIGHT'],
            ['name' => 'BLACK BEAUTY', 'abbreviation' => 'BLKBEAUTY'],
            ['name' => 'CRIMSON', 'abbreviation' => 'CRIMSON'],
            ['name' => 'GRAPE BAY WHITE', 'abbreviation' => 'GRAPEBAY'],
        ];
        foreach ($colors as $color) {
            Color::firstOrCreate(
                ['name' => $color['name']],
                ['abbreviation' => $color['abbreviation']]
            );
        }
    }
}