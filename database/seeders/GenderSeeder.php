<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gender;

class GenderSeeder extends Seeder
{
    public function run(): void
    {

        $genders = [
            ['name' => 'Masculino', 'abbreviation' => 'M', 'is_active' => true],
            ['name' => 'Femenino', 'abbreviation' => 'F', 'is_active' => true],
        ];


        foreach ($genders as $gender) {
            Gender::create($gender);
        }
    }
}