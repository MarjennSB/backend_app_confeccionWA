<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['name' => 'DNI', 'abbreviation' => 'DNI', 'min_length' => 8, 'max_length' => 8, 'is_active' => true],
            ['name' => 'Carné de extranjería', 'abbreviation' => 'CE', 'min_length' => 9, 'max_length' => 12, 'is_active' => true],
            ['name' => 'Pasaporte', 'abbreviation' => 'PAS', 'min_length' => 6, 'max_length' => 12, 'is_active' => true],
            ['name' => 'RUC', 'abbreviation' => 'RUC', 'min_length' => 11, 'max_length' => 11, 'is_active' => true],
        ];

        foreach ($tipos as $tipo) {
            DocumentType::create($tipo);
        }
    }
}