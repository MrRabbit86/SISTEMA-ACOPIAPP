<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lista_usuarios_pendientes(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->pendiente()->create();
        User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/usuarios?estado=pendiente')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('pendiente', $response->json('data.0.estado'));
    }

    public function test_no_admin_no_puede_listar_usuarios(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/usuarios')
            ->assertStatus(403);
    }

    public function test_admin_aprueba_cuenta_pendiente(): void
    {
        $admin = User::factory()->administrador()->create();
        $pendiente = User::factory()->pendiente()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/usuarios/{$pendiente->id}/aprobar")
            ->assertOk()
            ->assertJsonPath('usuario.estado', 'aprobado');

        $this->assertDatabaseHas('users', ['id' => $pendiente->id, 'estado' => 'aprobado']);
    }

    public function test_admin_rechaza_cuenta_pendiente(): void
    {
        $admin = User::factory()->administrador()->create();
        $pendiente = User::factory()->pendiente()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/usuarios/{$pendiente->id}/rechazar")
            ->assertOk()
            ->assertJsonPath('usuario.estado', 'rechazado');

        $this->assertDatabaseHas('users', ['id' => $pendiente->id, 'estado' => 'rechazado']);
    }

    public function test_admin_no_puede_aprobar_cuenta_ya_aprobada(): void
    {
        $admin = User::factory()->administrador()->create();
        $aprobado = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/usuarios/{$aprobado->id}/aprobar")
            ->assertStatus(422);
    }
}