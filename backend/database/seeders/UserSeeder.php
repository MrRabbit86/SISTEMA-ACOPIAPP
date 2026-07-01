<?php

namespace Database\Seeders;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@reciclaje.bo'],
            [
                'name' => 'Administrador del Sistema',
                'password' => 'admin123456',
                'rol' => Rol::ADMIN->value,
                'telefono' => '4220000',
                'estado' => EstadoUsuario::APROBADO->value,
            ],
        );
        $admin->save();

        $ciudadano = User::query()->updateOrCreate(
            ['email' => 'ciudadano@reciclaje.bo'],
            [
                'name' => 'Juan Perez',
                'password' => 'ciudadano123',
                'rol' => Rol::CIUDADANO->value,
                'telefono' => '70000001',
                'direccion' => 'Av. Melchor Perez de Olguin',
                'estado' => EstadoUsuario::APROBADO->value,
            ],
        );

        Ciudadano::query()->updateOrCreate(
            ['usuario_id' => $ciudadano->id],
            ['ci' => '12345678', 'zona' => 'Zona Sur'],
        );

        $empresa = User::query()->updateOrCreate(
            ['email' => 'empresa@reciclaje.bo'],
            [
                'name' => 'Empresa Recicla Sur SRL',
                'password' => 'empresa123456',
                'rol' => Rol::EMPRESA->value,
                'telefono' => '4220001',
                'direccion' => 'Av. Blanco Galindo km 5',
                'estado' => EstadoUsuario::APROBADO->value,
            ],
        );

        EmpresaAcopiadora::query()->updateOrCreate(
            ['usuario_id' => $empresa->id],
            [
                'nit' => '1020304012',
                'razon_social' => 'Recicla Sur S.R.L.',
                'direccion' => 'Av. Blanco Galindo km 5',
            ],
        );

        $pendiente = User::query()->updateOrCreate(
            ['email' => 'pendiente@reciclaje.bo'],
            [
                'name' => 'Maria Lopez',
                'password' => 'pendiente123',
                'rol' => Rol::CIUDADANO->value,
                'telefono' => '70000002',
                'estado' => EstadoUsuario::PENDIENTE->value,
            ],
        );

        Ciudadano::query()->updateOrCreate(
            ['usuario_id' => $pendiente->id],
            ['ci' => '87654321', 'zona' => 'Valle Hermoso'],
        );
    }
}