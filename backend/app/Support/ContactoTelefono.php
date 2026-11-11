<?php

namespace App\Support;

final class ContactoTelefono
{
    public static function soloDigitos(?string $telefono): string
    {
        return preg_replace('/\D+/', '', (string) $telefono) ?? '';
    }

    public static function numeroWhatsappBolivia(?string $telefono): ?string
    {
        $digitos = self::soloDigitos($telefono);

        if (preg_match('/^[67]\d{7}$/', $digitos) === 1) {
            return '591'.$digitos;
        }

        if (preg_match('/^591([67]\d{7})$/', $digitos, $coincidencias) === 1) {
            return '591'.$coincidencias[1];
        }

        return null;
    }

    public static function esCelularBolivia(?string $telefono): bool
    {
        return self::numeroWhatsappBolivia($telefono) !== null;
    }

    public static function urlWhatsapp(?string $telefono, string $mensaje = ''): ?string
    {
        $numero = self::numeroWhatsappBolivia($telefono);

        if ($numero === null) {
            return null;
        }

        $url = 'https://wa.me/'.$numero;

        if ($mensaje !== '') {
            $url .= '?text='.rawurlencode($mensaje);
        }

        return $url;
    }

    public static function urlLlamada(?string $telefono): ?string
    {
        $digitos = self::soloDigitos($telefono);

        if (strlen($digitos) < 7) {
            return null;
        }

        if (strlen($digitos) <= 8) {
            return 'tel:+591'.$digitos;
        }

        return 'tel:+'.$digitos;
    }
}
