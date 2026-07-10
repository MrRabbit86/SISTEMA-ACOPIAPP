<?php

namespace App\Support;

/**
 * Contenedor de resultado de una operación de negocio.
 *
 * Patrón Result: evita usar excepciones como control de flujo y
 * centraliza la decisión de éxito/error de los servicios.
 */
final class Result
{
    private function __construct(
        private readonly mixed $valor,
        private readonly ?string $mensaje,
        private readonly ?string $codigo,
    ) {
    }

    public static function ok(mixed $valor = null): self
    {
        return new self($valor, null, null);
    }

    public static function fail(string $mensaje, string $codigo = 'error', mixed $valor = null): self
    {
        return new self($valor, $mensaje, $codigo);
    }

    public function bien(): bool
    {
        return $this->mensaje === null;
    }

    public function mal(): bool
    {
        return ! $this->bien();
    }

    public function valor(): mixed
    {
        return $this->valor;
    }

    public function mensaje(): ?string
    {
        return $this->mensaje;
    }

    public function codigo(): ?string
    {
        return $this->codigo;
    }
}