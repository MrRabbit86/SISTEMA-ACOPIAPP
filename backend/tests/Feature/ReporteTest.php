<?php

namespace Tests\Feature;

use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\Oferta;
use App\Models\Reporte;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_empresa_ve_resumen_solo_de_sus_transacciones(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id, 'peso_real_kg' => 10]);
        Transaccion::factory()->create();

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes')
            ->assertOk();

        $this->assertSame(1, $response->json('transacciones.total'));
        $this->assertCount(1, $response->json('por_categoria'));

        $this->assertSame(1, Reporte::query()->where('tipo', 'resumen')->count());
    }

    public function test_admin_ve_resumen_general(): void
    {
        Transaccion::factory()->create(['peso_real_kg' => 5]);
        Transaccion::factory()->create(['peso_real_kg' => 5]);
        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reportes')
            ->assertOk();

        $this->assertSame(2, $response->json('transacciones.total'));
        $this->assertEquals(10.0, $response->json('transacciones.peso_total_kg'));
    }

    public function test_resumen_incluye_montos_por_categoria(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();
        Transaccion::factory()->create([
            'empresa_id' => $empresa->usuario->id,
            'oferta_id' => $oferta->id,
            'peso_real_kg' => 20,
            'precio_acordado_kg' => 2,
            'monto_total' => 40,
        ]);

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes')
            ->assertOk();

        $categoria = $response->json('por_categoria')[0];
        $this->assertSame($oferta->categoria->nombre, $categoria['material']);
        $this->assertEquals(40.0, $categoria['monto_total']);
    }

    public function test_filtro_por_rango_de_fechas(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $t = Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);
        $t->update(['fecha_transaccion' => Carbon::parse('2026-01-15')]);
        Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes?desde=2026-01-01&hasta=2026-01-31')
            ->assertOk();

        $this->assertSame(1, $response->json('transacciones.total'));
    }

    public function test_empresa_exporta_reporte_pdf(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        Transaccion::factory()->create(['empresa_id' => $empresa->usuario->id]);

        $response = $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/reportes/pdf')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(1, Reporte::query()->where('tipo', 'resumen_pdf')->count());
    }

    public function test_ciudadano_no_genera_reportes(): void
    {
        $ciudadano = Ciudadano::factory()->create();

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->getJson('/api/reportes')
            ->assertForbidden();
    }
}