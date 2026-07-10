<?php

namespace Database\Seeders;

use App\Models\CategoriaMaterial;
use Illuminate\Database\Seeder;

class CategoriaMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Plástico PET', 'descripcion' => 'Botellas y envases de tereftalato de polietileno', 'precio_referencia_kg' => 2.50],
            ['nombre' => 'Cartón', 'descripcion' => 'Cajas y embalajes de cartón corrugado', 'precio_referencia_kg' => 1.20],
            ['nombre' => 'Vidrio', 'descripcion' => 'Botellas y envases de vidrio', 'precio_referencia_kg' => 0.80],
            ['nombre' => 'Papel', 'descripcion' => 'Papel blanco y mixto', 'precio_referencia_kg' => 1.50],
            ['nombre' => 'Latas de aluminio', 'descripcion' => 'Latas de bebidas de aluminio', 'precio_referencia_kg' => 8.00],
            ['nombre' => 'Metales', 'descripcion' => 'Chatarra y metales ferrosos y no ferrosos', 'precio_referencia_kg' => 3.00],
        ];

        foreach ($categorias as $categoria) {
            CategoriaMaterial::query()->updateOrCreate(
                ['nombre' => $categoria['nombre']],
                $categoria,
            );
        }
    }
}