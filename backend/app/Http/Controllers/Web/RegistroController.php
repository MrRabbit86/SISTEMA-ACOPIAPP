<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroWebRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistroController extends Controller
{
    public function store(RegistroWebRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $esEmpresa = $validated['tipo'] === 'empresa';

        $user = DB::transaction(function () use ($validated, $esEmpresa): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'rol' => $esEmpresa ? Rol::EMPRESA : Rol::CIUDADANO,
                'telefono' => $validated['telefono'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'estado' => $esEmpresa ? EstadoUsuario::PENDIENTE : EstadoUsuario::APROBADO,
            ]);

            if ($esEmpresa) {
                $user->empresa()->create([
                    'nit' => $validated['nit'],
                    'razon_social' => $validated['razon_social'],
                    'direccion' => $validated['direccion'] ?? null,
                ]);
            } else {
                $user->ciudadano()->create([
                    'ci' => $validated['ci'],
                    'zona' => $validated['zona'],
                ]);
            }

            return $user;
        });

        if ($esEmpresa) {
            return redirect()->route('login')->with(
                'status',
                'Registro exitoso. Su cuenta está pendiente de aprobación por un administrador.',
            );
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('mis-ofertas');
    }
}