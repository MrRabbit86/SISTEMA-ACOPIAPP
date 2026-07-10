<?php

namespace Tests\Unit;

use App\Support\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function test_ok_marca_resultado_exitoso(): void
    {
        $resultado = Result::ok(42);

        $this->assertTrue($resultado->bien());
        $this->assertFalse($resultado->mal());
        $this->assertSame(42, $resultado->valor());
        $this->assertNull($resultado->mensaje());
        $this->assertNull($resultado->codigo());
    }

    public function test_fail_marca_resultado_fallido_con_mensaje_y_codigo(): void
    {
        $resultado = Result::fail('La oferta no está disponible.', 'oferta_no_disponible');

        $this->assertTrue($resultado->mal());
        $this->assertFalse($resultado->bien());
        $this->assertSame('La oferta no está disponible.', $resultado->mensaje());
        $this->assertSame('oferta_no_disponible', $resultado->codigo());
    }

    public function test_fail_puede_transportar_valor_adicional(): void
    {
        $valor = ['id' => 7];
        $resultado = Result::fail('Algo falló', 'error_x', $valor);

        $this->assertSame($valor, $resultado->valor());
    }
}