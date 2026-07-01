<?php

namespace App\Support;

final class Geolocalizacion
{
    public const LAT_MIN = -17.50000000;

    public const LAT_MAX = -17.20000000;

    public const LNG_MIN = -66.30000000;

    public const LNG_MAX = -66.00000000;

    public const RADIO_MAX_KM = 50;

    public static function dentroLimites(float|int $latitud, float|int $longitud): bool
    {
        return $latitud >= self::LAT_MIN && $latitud <= self::LAT_MAX
            && $longitud >= self::LNG_MIN && $longitud <= self::LNG_MAX;
    }

    public static function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radTierra = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}