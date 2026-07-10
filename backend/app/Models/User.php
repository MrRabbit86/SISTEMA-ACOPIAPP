<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'rol', 'telefono', 'direccion', 'estado'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function ciudadano(): HasOne
    {
        return $this->hasOne(Ciudadano::class, 'usuario_id');
    }

    public function empresa(): HasOne
    {
        return $this->hasOne(EmpresaAcopiadora::class, 'usuario_id');
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::ADMIN;
    }

    public function esCiudadano(): bool
    {
        return $this->rol === Rol::CIUDADANO;
    }

    public function esEmpresa(): bool
    {
        return $this->rol === Rol::EMPRESA;
    }

    public function estaAprobado(): bool
    {
        return $this->estado === EstadoUsuario::APROBADO;
    }

    public function perfil(): ?string
    {
        return match ($this->rol) {
            Rol::CIUDADANO => $this->ciudadano?->zona,
            Rol::EMPRESA => $this->empresa?->razon_social,
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
            'estado' => EstadoUsuario::class,
        ];
    }
}