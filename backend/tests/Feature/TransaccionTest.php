<?php

namespace Tests\Feature;

use App\Enums\EstadoOferta;
use App\Models\CategoriaMaterial;
use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_empresa_registra_transaccion_y_completa_oferta(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 80.5,
            ]);

        $this->assertSame(
            round(80.5 * (float) $oferta->categoria->precio_referencia_kg, 2),
            round($response->json('data.monto_total'), 2),
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'completada')
            ->assertJsonPath('data.oferta.estado', 'completada')
            ->assertJsonPath('data.oferta.material', $oferta->categoria->nombre)
            ->assertJsonPath('data.empresa.name', $empresa->usuario->name);

        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => EstadoOferta::COMPLETADA->value,
        ]);

        $this->assertDatabaseHas('transacciones', [
            'oferta_id' => $oferta->id,
            'empresa_id' => $empresa->usuario->id,
            'peso_real_kg' => '80.50',
            'estado' => 'completada',
        ]);
    }

    public function test_monto_usar_precio_acordado_cuando_se_envia(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 40,
                'precio_acordado_kg' => 1.75,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.monto_total', 70)
            ->assertJsonPath('data.precio_acordado_kg', 1.75);
    }

    public function test_precio_por_defecto_es_el_de_referencia_de_la_categoria(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $categoria = CategoriaMaterial::factory()->create(['precio_referencia_kg' => 2.5]);
        $oferta = Oferta::factory()->create(['categoria_id' => $categoria->id]);

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.monto_total', 25)
            ->assertJsonPath('data.precio_acordado_kg', 2.5);
    }

    public function test_ciudadano_recibe_puntos_verdes_al_venderse_su_oferta(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $ciudadano = Ciudadano::factory()->create();
        $oferta = Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 30,
            ])->assertStatus(201);

        $this->assertDatabaseHas('ciudadanos', ['id' => $ciudadano->id, 'puntos' => 300]);
        $this->assertDatabaseHas('transacciones', [
            'oferta_id' => $oferta->id,
            'puntos_otorgados' => 300,
        ]);
    }

    public function test_ciudadano_no_puede_registrar_transaccion(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
            ])->assertStatus(403);
    }

    public function test_oferta_completada_no_permite_nueva_transaccion(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->completada()->create();
        Transaccion::factory()->create([
            'oferta_id' => $oferta->id,
            'empresa_id' => $empresa->usuario->id,
        ]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
            ])->assertStatus(422);
    }

    public function test_doble_compra_de_la_misma_oferta_no_duplica_transaccion(): void
    {
        $empresaA = EmpresaAcopiadora::factory()->create();
        $empresaB = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($empresaA->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
            ])->assertStatus(201);

        $this->actingAs($empresaB->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 20,
            ])->assertStatus(422)
            ->assertJsonPath('message', 'La oferta ya fue adquirida o no está disponible para registrar una compra.');

        $this->assertDatabaseCount('transacciones', 1);
        $this->assertDatabaseHas('ofertas', ['id' => $oferta->id, 'estado' => 'completada']);
    }

    public function test_oferta_cancelada_no_permite_transaccion(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->cancelada()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 10,
            ])->assertStatus(422);
    }

    public function test_peso_invalido_se_rechaza(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 0,
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['peso_real_kg']);
    }

    public function test_empresa_solo_ve_sus_transacciones(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);
        Transaccion::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/transacciones')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_ve_todas_las_transacciones(): void
    {
        $admin = User::factory()->administrador()->create();
        Transaccion::factory()->create();
        Transaccion::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/transacciones')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_transaccion_ajena_denegada(): void
    {
        $empresaA = EmpresaAcopiadora::factory()->create();
        $empresaB = EmpresaAcopiadora::factory()->create();
        $transaccion = Transaccion::factory()->create(['empresa_id' => $empresaA->usuario->id]);

        $this->actingAs($empresaB->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}")
            ->assertStatus(403);
    }

    public function test_show_transaccion_propia_ok(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $transaccion = Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $transaccion->id);
    }

    public function test_ciudadano_no_accede_al_listado(): void
    {
        $ciudadano = Ciudadano::factory()->create();

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->getJson('/api/transacciones')
            ->assertStatus(403);
    }

    public function test_ciudadano_consulta_sus_ventas(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $otroCiudadano = Ciudadano::factory()->create();
        Transaccion::factory()->create(['oferta_id' => Oferta::factory()->create(['ciudadano_id' => $ciudadano->id])->id]);
        Transaccion::factory()->create(['oferta_id' => Oferta::factory()->create(['ciudadano_id' => $otroCiudadano->id])->id]);

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->getJson('/api/mis-transacciones')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_empresa_no_accede_a_mis_transacciones(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/mis-transacciones')
            ->assertStatus(403);
    }

    public function test_ciudadano_dueno_descarga_su_comprobante(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $oferta = Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);
        $transaccion = Transaccion::factory()->create(['oferta_id' => $oferta->id]);

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->get("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_ciudadano_no_descarga_comprobante_ajeno(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $otroCiudadano = Ciudadano::factory()->create();
        $oferta = Oferta::factory()->create(['ciudadano_id' => $otroCiudadano->id]);
        $transaccion = Transaccion::factory()->create(['oferta_id' => $oferta->id]);

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->get("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertStatus(403);
    }
}