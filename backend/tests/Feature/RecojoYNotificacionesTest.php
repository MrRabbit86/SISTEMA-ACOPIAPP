<?php

namespace Tests\Feature;

use App\Enums\EstadoOferta;
use App\Enums\EstadoUsuario;
use App\Models\CategoriaMaterial;
use App\Models\Ciudadano;
use App\Models\EmpresaAcopiadora;
use App\Models\Oferta;
use App\Notifications\NotificacionSistema;
use App\Support\ContactoTelefono;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecojoYNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_publicar_oferta_genera_pin_y_notifica_a_empresas_aprobadas(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = CategoriaMaterial::factory()->create(['nombre' => 'Cartón']);
        $empresaAprobada = EmpresaAcopiadora::factory()->create();
        $empresaPendiente = EmpresaAcopiadora::factory()->create();
        $empresaPendiente->usuario->update(['estado' => EstadoUsuario::PENDIENTE]);

        $response = $this->actingAs($ciudadano->usuario, 'sanctum')
            ->postJson('/api/ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 30,
                'latitud' => -17.4200,
                'longitud' => -66.1700,
            ]);

        $response->assertStatus(201);
        $pin = $response->json('data.codigo_confirmacion');
        $this->assertMatchesRegularExpression('/^\d{4}$/', (string) $pin);

        // El PIN se oculta a las empresas en la API
        $ofertaId = $response->json('data.id');
        $this->actingAs($empresaAprobada->usuario, 'sanctum')
            ->getJson("/api/ofertas/{$ofertaId}")
            ->assertOk()
            ->assertJsonMissingPath('data.codigo_confirmacion');

        // Notifica en BD solo a empresas aprobadas
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $empresaAprobada->usuario->id,
            'type' => NotificacionSistema::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $empresaPendiente->usuario->id,
        ]);
    }

    public function test_reserva_temporal_oculta_oferta_a_otras_empresas_y_notifica_ciudadano(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $empresa1 = EmpresaAcopiadora::factory()->create();
        $empresa2 = EmpresaAcopiadora::factory()->create();
        $categoria = CategoriaMaterial::factory()->create();

        $oferta = Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'categoria_id' => $categoria->id,
            'latitud' => -17.4200,
            'longitud' => -66.1700,
        ]);

        // Empresa 1 reserva por API
        $res = $this->actingAs($empresa1->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/reservar");

        $res->assertOk()
            ->assertJsonPath('data.estado', EstadoOferta::EN_PROCESO->value)
            ->assertJsonPath('data.empresa_reservada.id', $empresa1->usuario->id);

        // Empresa 1 sigue viendo su propia reserva en el listado
        $idsEmpresa1 = collect($this->actingAs($empresa1->usuario, 'sanctum')
            ->getJson('/api/ofertas')
            ->assertOk()
            ->json('data'))->pluck('id');
        $this->assertTrue($idsEmpresa1->contains($oferta->id));

        // Empresa 2 NO la ve en disponibles (/api/ofertas ni /ofertas/listado)
        $idsEmpresa2 = collect($this->actingAs($empresa2->usuario, 'sanctum')
            ->getJson('/api/ofertas')
            ->assertOk()
            ->json('data'))->pluck('id');
        $this->assertFalse($idsEmpresa2->contains($oferta->id));

        $this->actingAs($empresa2->usuario)
            ->get('/ofertas/listado')
            ->assertOk()
            ->assertDontSee("Oferta #{$oferta->id}");

        // Empresa 2 no puede reservar ni comprar una oferta reservada por Empresa 1
        $this->actingAs($empresa2->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/reservar")
            ->assertStatus(422);

        $this->actingAs($empresa2->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 25,
            ])
            ->assertStatus(422);

        // Ciudadano recibió notificación
        $this->assertSame(1, $ciudadano->usuario->unreadNotifications()->count());
        $this->assertSame('recojo_reservado', $ciudadano->usuario->notifications()->first()->data['tipo']);
    }

    public function test_reserva_expirada_se_libera_automaticamente(): void
    {
        $empresa1 = EmpresaAcopiadora::factory()->create();
        $empresa2 = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create([
            'estado' => EstadoOferta::EN_PROCESO,
            'empresa_reservada_id' => $empresa1->usuario->id,
            'reservada_hasta' => now()->subMinute(),
        ]);

        // Al consultar ofertas disponibles, la reserva vencida vuelve a pendiente
        $idsEmpresa2 = collect($this->actingAs($empresa2->usuario, 'sanctum')
            ->getJson('/api/ofertas')
            ->assertOk()
            ->json('data'))->pluck('id');

        $this->assertTrue($idsEmpresa2->contains($oferta->id));
        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => EstadoOferta::PENDIENTE->value,
            'empresa_reservada_id' => null,
            'reservada_hasta' => null,
        ]);
    }

    public function test_liberar_oferta_por_motivos_actualiza_estado_y_notifica_ciudadano(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'estado' => EstadoOferta::PENDIENTE,
        ]);

        // Reserva y libera por desacuerdo de precio -> vuelve a pendiente
        $this->actingAs($empresa->usuario)->post("/ofertas/{$oferta->id}/reservar")->assertRedirect();

        $this->actingAs($empresa->usuario)
            ->post("/ofertas/{$oferta->id}/liberar", [
                'motivo' => 'desacuerdo_precio',
                'nota' => 'Diferencia en calidad',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => EstadoOferta::PENDIENTE->value,
            'empresa_reservada_id' => null,
        ]);

        // Reserva y reporta material no apto -> cancelada con motivo
        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/reservar")
            ->assertOk();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/liberar", [
                'motivo' => 'material_no_apto',
                'nota' => 'Material húmedo',
            ])
            ->assertOk()
            ->assertJsonPath('data.estado', EstadoOferta::CANCELADA->value);

        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => EstadoOferta::CANCELADA->value,
        ]);
        $this->assertStringContainsString('Material no apto', (string) $oferta->fresh()->motivo_cierre);
    }

    public function test_compra_valida_pin_y_notifica_a_ciudadano_y_empresa(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'codigo_confirmacion' => '4821',
        ]);

        // PIN incorrecto falla con 422
        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 20,
                'codigo_confirmacion' => '9999',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['codigo_confirmacion']);

        // PIN correcto completa la compra y notifica a ambos
        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 20,
                'codigo_confirmacion' => '4821',
            ])
            ->assertStatus(201);

        $this->assertSame('compra_confirmada', $ciudadano->usuario->notifications()->latest()->first()->data['tipo']);
        $this->assertSame('compra_registrada', $empresa->usuario->notifications()->latest()->first()->data['tipo']);
    }

    public function test_endpoints_web_y_api_de_notificaciones_funcionan_y_marcan_leidas(): void
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $empresa->usuario->notify(new NotificacionSistema(
            titulo: 'Nueva oferta',
            mensaje: 'Cartón publicado',
            url: '/ofertas',
            tipo: 'nueva_oferta',
        ));

        // API
        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonPath('no_leidas', 1)
            ->assertJsonPath('data.0.titulo', 'Nueva oferta');

        // Web recientes
        $this->actingAs($empresa->usuario)
            ->getJson('/notificaciones/recientes')
            ->assertOk()
            ->assertJsonPath('no_leidas', 1);

        // Marcar todas como leídas por Web
        $this->actingAs($empresa->usuario)
            ->postJson('/notificaciones/leer-todas')
            ->assertOk();

        $this->actingAs($empresa->usuario, 'sanctum')
            ->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonPath('no_leidas', 0);
    }

    public function test_empresa_puede_notificar_llegada_al_punto_y_otra_empresa_no_puede(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $empresa1 = EmpresaAcopiadora::factory()->create();
        $empresa2 = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'codigo_confirmacion' => '1234',
        ]);

        // Pendiente aún no permite marcar llegada
        $this->actingAs($empresa1->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/llegada")
            ->assertStatus(422);

        // Empresa 1 reserva y luego notifica llegada por Web y API
        $this->actingAs($empresa1->usuario)->post("/ofertas/{$oferta->id}/reservar")->assertRedirect();

        // Empresa 2 no puede notificar llegada en una reserva ajena
        $this->actingAs($empresa2->usuario, 'sanctum')
            ->postJson("/api/ofertas/{$oferta->id}/llegada")
            ->assertStatus(422);

        $this->actingAs($empresa1->usuario)
            ->post("/ofertas/{$oferta->id}/llegada")
            ->assertRedirect();

        $this->assertNotNull($oferta->fresh()->llegada_en);
        $tipos = $ciudadano->usuario->notifications()->pluck('data')->pluck('tipo');
        $this->assertTrue($tipos->contains('recojo_reservado'));
        $this->assertTrue($tipos->contains('empresa_en_punto'));
    }

    public function test_normalizacion_whatsapp_y_botones_contacto_en_vistas_solo_durante_reserva(): void
    {
        $this->assertSame('59175951188', ContactoTelefono::numeroWhatsappBolivia('75951188'));
        $this->assertSame('59160709411', ContactoTelefono::numeroWhatsappBolivia('+591 60709411'));
        $this->assertNull(ContactoTelefono::numeroWhatsappBolivia('4220001'));
        $this->assertSame('tel:+5914220001', ContactoTelefono::urlLlamada('4220001'));

        $ciudadano = Ciudadano::factory()->create();
        $ciudadano->usuario->update(['telefono' => '60709411']);
        $empresa = EmpresaAcopiadora::factory()->create();
        $empresa->usuario->update(['telefono' => '75951188']);

        $oferta = Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'codigo_confirmacion' => '5544',
        ]);

        // Empresa ve enlace Google Maps, WhatsApp al ciudadano y botón Reservar
        $this->actingAs($empresa->usuario)
            ->get("/ofertas/{$oferta->id}")
            ->assertOk()
            ->assertSee('https://wa.me/59160709411', false)
            ->assertSee('https://www.google.com/maps/search/?api=1', false);

        // Empresa reserva y notifica llegada
        $this->actingAs($empresa->usuario)->post("/ofertas/{$oferta->id}/reservar")->assertRedirect();
        $this->actingAs($empresa->usuario)
            ->get("/ofertas/{$oferta->id}")
            ->assertOk()
            ->assertSee('Ya estoy en el punto');

        $this->actingAs($empresa->usuario)->post("/ofertas/{$oferta->id}/llegada")->assertRedirect();

        // Ciudadano en /mis-ofertas ve aviso de llegada y botón WhatsApp a la empresa
        $this->actingAs($ciudadano->usuario)
            ->get('/mis-ofertas')
            ->assertOk()
            ->assertSee('https://wa.me/59175951188', false)
            ->assertSee('ya está en tu punto');

        // Al completarse la compra, el botón WhatsApp desaparece de /mis-ofertas por privacidad
        $this->actingAs($empresa->usuario, 'sanctum')
            ->postJson('/api/transacciones', [
                'oferta_id' => $oferta->id,
                'peso_real_kg' => 15,
                'codigo_confirmacion' => '5544',
            ])
            ->assertStatus(201);

        $this->actingAs($ciudadano->usuario)
            ->get('/mis-ofertas')
            ->assertOk()
            ->assertDontSee('https://wa.me/59175951188', false);
    }
}
