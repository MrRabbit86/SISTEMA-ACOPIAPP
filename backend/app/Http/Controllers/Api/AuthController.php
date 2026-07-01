<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $esCiudadano = Rol::from($validated['rol']) === Rol::CIUDADANO;

        $user = DB::transaction(function () use ($validated, $esCiudadano): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'rol' => Rol::from($validated['rol']),
                'telefono' => $validated['telefono'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'estado' => $esCiudadano ? EstadoUsuario::APROBADO : EstadoUsuario::PENDIENTE,
            ]);

            if ($user->esCiudadano()) {
                $user->ciudadano()->create([
                    'ci' => $validated['ci'] ?? null,
                    'zona' => $validated['zona'] ?? null,
                ]);
            } else {
                $user->empresa()->create([
                    'nit' => $validated['nit'],
                    'razon_social' => $validated['razon_social'],
                    'direccion' => $validated['direccion'] ?? null,
                ]);
            }

            return $user;
        });

        return response()->json([
            'message' => $esCiudadano
                ? 'Registro exitoso. Ya puede iniciar sesión.'
                : 'Registro exitoso. Su cuenta está pendiente de aprobación por un administrador.',
            'usuario' => new UsuarioResource($user->load(['ciudadano', 'empresa'])),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales proporcionadas son incorrectas.',
            ]);
        }

        if ($user->estado === EstadoUsuario::PENDIENTE) {
            return response()->json([
                'message' => 'Su cuenta está pendiente de aprobación por un administrador.',
            ], 403);
        }

        if ($user->estado === EstadoUsuario::RECHAZADO) {
            return response()->json([
                'message' => 'Su cuenta fue rechazada. Comuníquese con el administrador.',
            ], 403);
        }

        $token = $user->createToken('app-movil')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'token_type' => 'Bearer',
            'usuario' => new UsuarioResource($user->load(['ciudadano', 'empresa'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => new UsuarioResource($request->user()->load(['ciudadano', 'empresa'])),
        ]);
    }
}