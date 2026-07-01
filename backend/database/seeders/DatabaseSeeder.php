<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\CategoriaMaterial;
use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategoriaMaterialSeeder::class,
            UserSeeder::class,
        ]);
    }
}