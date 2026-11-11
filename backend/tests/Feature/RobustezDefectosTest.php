<?php

namespace Tests\Feature;

use App\Enums\EstadoOferta;
use App\Enums\EstadoUsuario;
use App\Models\CategoriaMaterial;
use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobustezDefectosTest extends TestCase
{
    use RefreshDatabase;

    public function test_d01_fechas_invalidas_en_reportes_devuelven_422_y_nunca_500(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes?desde=no-fecha')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['desde']);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes/pdf?desde=no-fecha')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['desde']);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes?desde=2026-11-10&hasta=2026-11-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hasta']);

        $this->actingAs($empresa->usuario)
            ->get('/reportes?desde=no-fecha')
            ->assertStatus(422);

        $this->actingAs($empresa->usuario)
            ->get('/reportes/pdf?desde=2026-11-10&hasta=2026-11-01')
            ->assertStatus(422);
    }

    public function test_d02_monto_que_desborda_decimal_se_rechaza_con_422(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 999999,
                'precio_acordado_kg' => 999999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['peso_real_kg']);

        $this->actingAs($empresa->usuario)
            ->post("/ofertas/{$oferta->id}/transaccion", [
                'peso_real_kg' => 999999,
                'precio_acordado_kg' => 999999,
            ])
            ->assertSessionHasErrors(['peso_real_kg']);

        $this->assertDatabaseCount('transacciones', 0);
        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => EstadoOferta::PENDIENTE->value,
        ]);
    }

    public function test_d03_cabeceras_de_seguridad_presentes_en_login_y_panel(): void
    {
        $admin = User::factory()->administrador()->create();

        foreach ([$this->get('/login'), $this->actingAs($admin)->get('/admin/usuarios')] as $respuesta) {
            $respuesta->assertOk()
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

            $this->assertNotEmpty($respuesta->headers->get('Content-Security-Policy'));
        }
    }

    public function test_d04_rechaza_mas_de_dos_decimales_y_mantiene_exactitud(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 2.375,
                'precio_acordado_kg' => 3.50,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['peso_real_kg']);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 2.38,
                'precio_acordado_kg' => 3.555,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['precio_acordado_kg']);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 2.38,
                'precio_acordado_kg' => 3.50,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.monto_total', 8.33);
    }

    public function test_d05_zona_horaria_configurada_en_america_la_paz(): void
    {
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('-04:00', config('database.connections.mysql.timezone'));
    }

    public function test_d06_cancelar_oferta_ya_comprada_devuelve_422(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
            ])->assertStatus(201);

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/cancelar")
            ->assertStatus(422);

        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => EstadoOferta::COMPLETADA->value,
        ]);
    }

    public function test_d07_cuenta_rechazada_con_sesion_o_token_activo_recibe_403(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $token = $empresa->usuario->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $empresa->usuario->update(['estado' => EstadoUsuario::RECHAZADO]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(403);
        $this->withToken($token)->getJson('/api/ofertas')->assertStatus(403);

        $this->actingAs($empresa->usuario->fresh())
            ->get('/dashboard')
            ->assertStatus(403);

        $this->actingAs($empresa->usuario->fresh())
            ->get('/ofertas')
            ->assertStatus(403);
    }

    public function test_d08_coma_decimal_se_normaliza_en_compras_y_ofertas(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $ofertaResp = $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => '4,25',
                'latitud' => '-17,40',
                'longitud' => '-66,15',
            ]);

        $ofertaResp->assertStatus(201);
        $this->assertEquals(4.25, (float) $ofertaResp->json('data.cantidad_estimada_kg'));

        $empresa = EmpresaAcopiadora::factory()->create();
        $ofertaId = $ofertaResp->json('data.id');

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $ofertaId,
                'peso_real_kg' => '2,5',
                'precio_acordado_kg' => '3,5',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.peso_real_kg', 2.5)
            ->assertJsonPath('data.precio_acordado_kg', 3.5)
            ->assertJsonPath('data.monto_total', 8.75);
    }

    public function test_mensajes_de_validacion_en_espanol(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $respuesta = $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
                'precio_acordado_kg' => 'abc',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['precio_acordado_kg']);

        $this->assertSame(
            'El campo precio acordado (Bs/kg) debe ser un número.',
            $respuesta->json('errors.precio_acordado_kg.0'),
        );
    }

    public function test_m02_pagina_offline_y_version_service_worker_disponibles(): void
    {
        $this->assertFileExists(public_path('offline.html'));
        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('reciclaje-v4', $sw);
        $this->assertStringContainsString('/offline.html', $sw);
    }
}
