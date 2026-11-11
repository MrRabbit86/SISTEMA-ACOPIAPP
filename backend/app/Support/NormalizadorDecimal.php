<?php

namespace App\Support;

final class NormalizadorDecimal
{
    /**
     * Reemplaza comas decimales por puntos en los campos indicados antes de validar.
     *
     * @param  array<string, mixed>  $entrada
     * @param  array<int, string>  $campos
     * @return array<string, mixed>
     */
    public static function normalizar(array $entrada, array $campos): array
    {
        $resultado = [];

        foreach ($campos as $campo) {
            if (! array_key_exists($campo, $entrada) || ! is_string($entrada[$campo])) {
                continue;
            }

            $resultado[$campo] = str_replace(',', '.', trim($entrada[$campo]));
        }

        return $resultado;
    }
}
