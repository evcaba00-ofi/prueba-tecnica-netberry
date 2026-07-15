<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['PHP', 'Javascript', 'CSS'] as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
