<?php

namespace Tests\Feature;

use App\Models\Ciudadano;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_login_se_muestra(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar sesión');
    }

    public function test_pagina_login_muestra_formulario_de_registro(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Registrarse')
            ->assertSee('Tipo de cuenta');
    }

    public function test_ciudadano_se_registra_desde_el_login(): void
    {
        $this->post('/login/registrar', [
            'tipo' => 'ciudadano',
            'name' => 'Luis Pérez',
            'email' => 'luis@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'ci' => '7654321',
            'zona' => 'Calacala',
            'telefono' => '71234567',
            'direccion' => 'Av. América',
        ])->assertRedirect(route('mis-ofertas'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'luis@example.com',
            'rol' => 'ciudadano',
            'estado' => 'aprobado',
        ]);
        $this->assertDatabaseHas('ciudadanos', ['ci' => '7654321', 'zona' => 'Calacala']);
    }

    public function test_registro_web_no_acepta_email_duplicado(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $this->post('/login/registrar', [
            'tipo' => 'ciudadano',
            'name' => 'Luis Pérez',
            'email' => 'existente@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'ci' => '7654321',
            'zona' => 'Calacala',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registro_web_requiere_ci_y_zona(): void
    {
        $this->post('/login/registrar', [
            'tipo' => 'ciudadano',
            'name' => 'Luis Pérez',
            'email' => 'luis@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertSessionHasErrors(['ci', 'zona']);

        $this->assertGuest();
    }

    public function test_empresa_se_registra_desde_el_login_y_queda_pendiente(): void
    {
        $this->post('/login/registrar', [
            'tipo' => 'empresa',
            'name' => 'Reciclajes Andinos',
            'email' => 'empresa@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'nit' => '1023467',
            'razon_social' => 'Reciclajes Andinos S.R.L.',
            'telefono' => '71234567',
            'direccion' => 'Av. Blanco Galindo km 7',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'empresa@example.com',
            'rol' => 'empresa',
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('empresas_acopiadoras', [
            'nit' => '1023467',
            'razon_social' => 'Reciclajes Andinos S.R.L.',
        ]);
    }

    public function test_registro_web_empresa_requiere_nit_y_razon_social(): void
    {
        $this->post('/login/registrar', [
            'tipo' => 'empresa',
            'name' => 'Reciclajes Andinos',
            'email' => 'empresa@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertSessionHasErrors(['nit', 'razon_social']);

        $this->assertGuest();
    }

    public function test_raiz_redirige_a_login_sin_autenticar(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_usuario_pendiente_no_ingresa_al_panel(): void
    {
        User::factory()->pendiente()->create([
            'email' => 'pendiente@example.com',
            'password' => 'secret1234',
        ]);

        $this->post('/login', [
            'email' => 'pendiente@example.com',
            'password' => 'secret1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_con_credenciales_incorrectas_falla(): void
    {
        $this->post('/login', [
            'email' => 'no@existe.com',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_ingresa_y_accede_a_gestion(): void
    {
        User::factory()->administrador()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->get('/admin/usuarios')->assertOk();
        $this->get('/ofertas')->assertOk();
        $this->get('/admin/categorias')->assertOk();
    }

    public function test_ciudadano_no_accede_a_panel_empresa(): void
    {
        $ciudadano = Ciudadano::factory()->create();

        $this->actingAs($ciudadano->usuario)
            ->get('/ofertas')
            ->assertForbidden();

        $this->actingAs($ciudadano->usuario)
            ->get('/admin/usuarios')
            ->assertForbidden();
    }

    public function test_empresa_accede_a_mapa_y_listado(): void
    {
        $empresa = User::factory()->empresa()->create();

        $this->actingAs($empresa)->get('/ofertas')->assertOk();
        $this->actingAs($empresa)->get('/ofertas/listado')->assertOk();
    }

    public function test_enlace_detalle_oferta_apunta_al_panel_web(): void
    {
        $oferta = Oferta::factory()->create();

        $path = parse_url(route('ofertas.show', $oferta), PHP_URL_PATH);

        $this->assertSame("/ofertas/{$oferta->id}", $path);
        $this->assertStringNotContainsString('/api/', $path);
    }

    public function test_empresa_ve_listado_de_transacciones(): void
    {
        $empresa = User::factory()->empresa()->create();

        $this->actingAs($empresa)
            ->get('/transacciones')
            ->assertOk()
            ->assertSee('Transacciones');
    }

    public function test_ciudadano_no_accede_a_transacciones(): void
    {
        $ciudadano = Ciudadano::factory()->create();

        $this->actingAs($ciudadano->usuario)
            ->get('/transacciones')
            ->assertForbidden();
    }

    public function test_empresa_ve_pagina_de_reportes(): void
    {
        $empresa = User::factory()->empresa()->create();

        $this->actingAs($empresa)
            ->get('/reportes')
            ->assertOk()
            ->assertSee('Reportes y estadísticas');
    }

    public function test_ciudadano_no_accede_a_reportes(): void
    {
        $ciudadano = Ciudadano::factory()->create();

        $this->actingAs($ciudadano->usuario)
            ->get('/reportes')
            ->assertForbidden();
    }

    public function test_empresa_descarga_comprobante_desde_panel(): void
    {
        $empresa = User::factory()->empresa()->create();
        $transaccion = Transaccion::factory()->create(['empresa_id' => $empresa->id]);

        $this->actingAs($empresa)
            ->get("/comprobantes/{$transaccion->id}")
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('comprobantes', ['transaccion_id' => $transaccion->id]);
    }

    public function test_empresa_registra_compra_desde_panel(): void
    {
        $empresa = User::factory()->empresa()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($empresa)
            ->post("/ofertas/{$oferta->id}/transaccion", [
                'peso_real_kg' => 33.5,
            ])
            ->assertRedirect(route('transacciones.index'));

        $this->assertDatabaseHas('transacciones', [
            'oferta_id' => $oferta->id,
            'empresa_id' => $empresa->id,
            'peso_real_kg' => '33.50',
            'estado' => 'completada',
        ]);

        $this->assertDatabaseHas('ofertas', [
            'id' => $oferta->id,
            'estado' => 'completada',
        ]);
    }

    public function test_ciudadano_ve_sus_ofertas(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        Oferta::factory()->create(['ciudadano_id' => $ciudadano->id]);

        $this->actingAs($ciudadano->usuario)
            ->get('/mis-ofertas')
            ->assertOk()
            ->assertSee('Mis ofertas');
    }

    public function test_ciudadano_publica_oferta_desde_el_panel(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = \App\Models\CategoriaMaterial::factory()->create();

        $this->actingAs($ciudadano->usuario)
            ->post('/mis-ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 22.5,
                'descripcion' => 'Papel de oficina',
                'modalidad_entrega' => 'recojo_domicilio',
                'latitud' => -17.3932,
                'longitud' => -66.1561,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ofertas', [
            'ciudadano_id' => $ciudadano->id,
            'descripcion' => 'Papel de oficina',
            'modalidad_entrega' => 'recojo_domicilio',
            'estado' => 'pendiente',
        ]);
    }

    public function test_ciudadano_publica_por_panel_sin_gps_fuera_de_zona_sur(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $categoria = \App\Models\CategoriaMaterial::factory()->create();

        $this->actingAs($ciudadano->usuario)
            ->post('/mis-ofertas', [
                'categoria_id' => $categoria->id,
                'cantidad_estimada_kg' => 10,
                'latitud' => -18.0,
                'longitud' => -66.1561,
            ])
            ->assertSessionHasErrors('latitud');
    }

    public function test_ciudadano_ve_su_venta_y_descarga_su_comprobante(): void
    {
        $ciudadano = Ciudadano::factory()->create();
        $empresa = User::factory()->empresa()->create();
        $oferta = Oferta::factory()->create([
            'ciudadano_id' => $ciudadano->id,
            'cantidad_estimada_kg' => 10,
        ]);
        $transaccion = Transaccion::factory()->create([
            'oferta_id' => $oferta->id,
            'empresa_id' => $empresa->id,
            'peso_real_kg' => 10,
        ]);

        $this->actingAs($ciudadano->usuario)
            ->get('/mis-ofertas')
            ->assertOk()
            ->assertSee('Descargar comprobante')
            ->assertSee($empresa->name);

        $this->actingAs($ciudadano->usuario)
            ->get("/comprobantes/{$transaccion->id}")
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_aprueba_cuenta_desde_panel(): void
    {
        $admin = User::factory()->administrador()->create();
        $pendiente = User::factory()->pendiente()->create();

        $this->actingAs($admin)
            ->post("/admin/usuarios/{$pendiente->id}/aprobar")
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $pendiente->id, 'estado' => 'aprobado']);
    }

    public function test_segunda_compra_de_la_misma_oferta_muestra_error_amigable(): void
    {
        $empresaA = User::factory()->empresa()->create();
        $empresaB = User::factory()->empresa()->create();
        $oferta = Oferta::factory()->create();

        $this->actingAs($empresaA)
            ->post("/ofertas/{$oferta->id}/transaccion", ['peso_real_kg' => 10])
            ->assertRedirect(route('transacciones.index'));

        $this->actingAs($empresaB)
            ->post("/ofertas/{$oferta->id}/transaccion", ['peso_real_kg' => 10])
            ->assertRedirect()
            ->assertSessionHasErrors('oferta');

        $this->assertDatabaseCount('transacciones', 1);
        $this->assertDatabaseHas('ofertas', ['id' => $oferta->id, 'estado' => 'completada']);
    }

    public function test_login_web_tiene_limite_de_intentos(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'fuerza@bruta.com',
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'fuerza@bruta.com',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }
}