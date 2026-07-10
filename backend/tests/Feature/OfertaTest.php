<?php

namespace Tests\Feature;

use App\Models\CategoriaMaterial;
use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfertaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ciudadano_puede_publicar_oferta(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $response = $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 50.5,
                'latitud' => -17.3932,
                'longitud' => -66.1561,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.categoria.nombre', $categoria->nombre)
            ->assertJsonPath('data.modalidad_entrega', 'entrega_punto_verde');

        $this->assertDatabaseHas('ofertas', [
            'ciudadano_id' => $ciudadano->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_ciudadano_publica_con_descripcion_y_modalidad(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $response = $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 40,
                'descripcion' => 'Botellas de plástico limpias',
                'modalidad_entrega' => 'recojo_domicilio',
                'latitud' => -17.3932,
                'longitud' => -66.1561,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.descripcion', 'Botellas de plástico limpias')
            ->assertJsonPath('data.modalidad_entrega', 'recojo_domicilio')
            ->assertJsonPath('data.modalidad_label', 'Recojo a domicilio');

        $this->assertDatabaseHas('ofertas', [
            'categoria_id' => $categoria->id,
            'modalidad_entrega' => 'recojo_domicilio',
            'descripcion' => 'Botellas de plástico limpias',
        ]);
    }

    public function test_ciudadano_publica_oferta_con_foto(): void
    {
        Storage::fake('public');

        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $response = $this->actingAs($ciudadano->usuario, 'sanctum')
            ->post('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 15,
                'latitud' => -17.4000,
                'longitud' => -66.1600,
                'foto' => UploadedFile::fake()->image('material.jpg', 100, 100),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['foto_url']]);

        $oferta = Oferta::query()->latest('id')->first();

        $this->assertNotNull($oferta->foto_url);
        $this->assertStringContainsString('/storage/', $response->json('data.foto_url'));
        Storage::disk('public')->assertExists($oferta->foto_url);
    }

    public function test_empresa_no_puede_publicar_oferta(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 50,
                'latitud' => -17.3932,
                'longitud' => -66.1561,
            ])->assertStatus(403);
    }

    public function test_oferta_con_gps_fuera_de_zona_sur_se_rechaza(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 50,
                'latitud' => -18.0000,
                'longitud' => -66.1561,
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['latitud']);
    }

    public function test_oferta_duplicada_en_24_horas_se_rechaza(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'categoria_id' => $categoria->id,
        ]);

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 25,
                'latitud' => -17.3932,
                'longitud' => -66.1561,
            ])->assertStatus(422);
    }

    public function test_lista_ofertas_filtra_por_categoria(): void
    {
        $c1 = CategoriaMaterial::factory()->create();
        $c2 = CategoriaMaterial::factory()->create();
        Oferta::factory()->create(['categoria_id' => $c1->id]);
        Oferta::factory()->create(['categoria_id' => $c2->id]);

        $empresa = EmpresaAcopiadora::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson("/api/ofertas?categoria_id={$c1->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_lista_ofertas_filtra_por_radio(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $puntoCentral = [-17.3932, -66.1561];

        Oferta::factory()->create([
            'latitud' => -17.3932,
            'longitud' => -66.1561,
        ]);
        Oferta::factory()->create([
            'latitud' => -17.4800,
            'longitud' => -66.2000,
        ]);

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/ofertas?lat='.$puntoCentral[0].'&lng='.$puntoCentral[1].'&radio=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_dueno_puede_cancelar_oferta_pendiente(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $oferta = Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/cancelar")
            ->assertOk()
            ->assertJsonPath('data.estado', 'cancelada');

        $this->assertDatabaseHas('ofertas', ['id' => $oferta->id, 'estado' => 'cancelada']);
    }

    public function test_otro_usuario_no_puede_cancelar_oferta(): void
    {
        $dueno = Ciudadano::factory()->create();
        $otro = Ciudadano::factory()->create();
        $oferta = Oferta::factory()->create(['ciudadano_id' => $dueno->id]);

        $this->actingAs($otro->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/cancelar")
            ->assertStatus(403);
    }

    public function test_no_autenticado_no_puede_listar_ofertas(): void
    {
        $this->getJson('/api/ofertas')->assertStatus(401);
    }

    public function test_ciudadano_consulta_sus_ofertas(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);
        Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);
        Oferta::factory()->create();

        $this->actingAs($ciudadano->usuario, 'sanctum')
            ->getJson('/api/ofertas/mias')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_empresa_no_accede_a_mis_ofertas(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/ofertas/mias')
            ->assertStatus(403);
    }

    public function test_lista_api_excluye_ofertas_no_disponibles_por_defecto(): void
    {
        Oferta::factory()->create();
        Oferta::factory()->completada()->create();
        Oferta::factory()->cancelada()->create();

        $empresa = EmpresaAcopiadora::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/ofertas')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_lista_api_filtra_estados_concretos(): void
    {
        Oferta::factory()->create();
        Oferta::factory()->completada()->create();

        $empresa = EmpresaAcopiadora::factory()->create();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/ofertas?estado=completada')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}