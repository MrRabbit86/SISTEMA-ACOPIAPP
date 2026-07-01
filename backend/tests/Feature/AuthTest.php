<?php

namespace Tests\Feature;

use App\Models\Ciudadano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_ciudadano_queda_activo_de_inmediato(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ana García',
            'email' => 'ana@example.com',
            'password' => 'secret1234',
            'rol' => 'ciudadano',
            'ci' => '9876543',
            'zona' => 'Valle Hermoso',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('usuario.rol', 'ciudadano')
            ->assertJsonPath('usuario.estado', 'aprobado')
            ->assertJsonPath('message', 'Registro exitoso. Ya puede iniciar sesión.')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'rol' => 'ciudadano']);
        $this->assertDatabaseHas('ciudadanos', ['ci' => '9876543']);
    }

    public function test_registro_empresa_requiere_nit_y_razon_social(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Eco Servicios',
            'email' => 'eco@example.com',
            'password' => 'secret1234',
            'rol' => 'empresa',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nit', 'razon_social']);
    }

    public function test_registro_empresa_completo_crea_perfil(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Eco Servicios',
            'email' => 'eco@example.com',
            'password' => 'secret1234',
            'rol' => 'empresa',
            'nit' => '1020304050',
            'razon_social' => 'Eco Servicios S.R.L.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('usuario.rol', 'empresa')
            ->assertJsonPath('usuario.nit', '1020304050')
            ->assertJsonPath('usuario.estado', 'pendiente');

        $this->assertDatabaseHas('empresas_acopiadoras', ['nit' => '1020304050']);
    }

    public function test_login_de_cuenta_pendiente_es_rechazado(): void
    {
        User::factory()->pendiente()->create([
            'email' => 'pendiente@example.com',
            'password' => 'secret1234',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'pendiente@example.com',
            'password' => 'secret1234',
        ])
            ->assertStatus(403)
            ->assertJsonMissingPath('token');
    }

    public function test_login_de_cuenta_rechazada_es_rechazado(): void
    {
        User::factory()->create([
            'email' => 'rechazada@example.com',
            'password' => 'secret1234',
            'estado' => 'rechazado',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'rechazada@example.com',
            'password' => 'secret1234',
        ])->assertStatus(403);
    }

    public function test_login_aprobado_devuelve_token(): void
    {
        User::factory()->create([
            'email' => 'aprobado@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'aprobado@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'rol']]);

        $this->assertNotNull($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_con_credenciales_incorrectas_falla(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'no@existe.com',
            'password' => 'incorrecta',
        ])->assertStatus(422);
    }

    public function test_me_devuelve_usuario_autenticado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('usuario.id', $user->id);
    }

    public function test_logout_revoca_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_ciudadano_aprobado_puede_acceder_con_token(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $token = $ciudadano->usuario->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('usuario.id', $ciudadano->usuario_id);
    }

    public function test_login_api_tiene_limite_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'fuerza@bruta.com',
                'password' => 'incorrecta',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'fuerza@bruta.com',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }
}