<?php

namespace Tests\Feature;

use App\Models\Ciudadano;
use App\Models\Comprobante;
use App\Models\EmpresaAcopiadora;
use App\Models\Transaccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprobanteTest extends TestCase
{
    use RefreshDatabase;

    public function test_empresa_descarga_comprobante_de_su_transaccion(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $transaccion = Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}/comprobante");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('comprobantes', [
            'transaccion_id' => $transaccion->id,
            'numero_comprobante' => 'CMP-'.now()->year.'-0001',
        ]);
    }

    public function test_segunda_descarga_reutiliza_el_mismo_comprobante(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $transaccion = Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertStatus(200);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertStatus(200);

        $this->assertSame(1, Comprobante::query()->count());
    }

    public function test_comprobantes_son_correlativos(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();

        $t1 = Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);
        $t2 = Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$t1->id}/comprobante")
            ->assertStatus(200);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$t2->id}/comprobante")
            ->assertStatus(200);

        $numeros = Comprobante::query()->orderBy('id')->pluck('numero_comprobante')->all();
        $this->assertSame(['CMP-'.now()->year.'-0001', 'CMP-'.now()->year.'-0002'], $numeros);
    }

    public function test_ciudadano_no_puede_descargar_comprobante(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $transaccion = Transaccion::factory()->create();

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertForbidden();
    }

    public function test_empresa_ajena_no_puede_descargar_comprobante(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $transaccion = Transaccion::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertForbidden();
    }

    public function test_admin_puede_descargar_cualquier_comprobante(): void
    {
        $transaccion = Transaccion::factory()->create();
        $admin = \App\Models\User::factory()->administrador()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/transacciones/{$transaccion->id}/comprobante")
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}