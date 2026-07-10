<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Models\User;
use App\Support\Result;

class UsuarioService
{
    public function aprobar(User $user): Result
    {
        if ($user->estado !== EstadoUsuario::PENDIENTE) {
            return Result::fail('Solo se pueden aprobar cuentas pendientes.', 'estado_invalido');
        }

        $user->update(['estado' => EstadoUsuario::APROBADO]);

        return Result::ok($user);
    }

    public function rechazar(User $user): Result
    {
        if ($user->estado !== EstadoUsuario::PENDIENTE) {
            return Result::fail('Solo se pueden rechazar cuentas pendientes.', 'estado_invalido');
        }

        $user->update(['estado' => EstadoUsuario::RECHAZADO]);

        return Result::ok($user);
    }
}