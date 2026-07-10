<?php

// Genera los iconos PWA del Sistema de Reciclaje.
// PHP GD requerido. Salida: public/icons/icon-512.png y icon-192.png.

function rotarPunto(float $cx, float $cy, float $r, float $grados): array
{
    $rad = deg2rad($grados);
    return [$cx + $r * cos($rad), $cy + $r * sin($rad)];
}

function pintarIcono(int $size, string $archivo): void
{
    $img = imagecreatetruecolor($size, $size);
    imageantialias($img, true);

    // Fondo esmeralda #064e3b con la "zona segura" del icono (radios) en esmeralda claro.
    $fondo = imagecolorallocate($img, 6, 78, 59);
    $blanco = imagecolorallocate($img, 255, 255, 255);

    imagefilledrectangle($img, 0, 0, $size, $size, $fondo);

    $cx = $size / 2;
    $cy = $size / 2;
    $r = 0.30 * $size;
    $grosor = max(8, (int) round($size * 0.05));

    // Vértices del triángulo de reciclaje (orden horario en pantalla).
    $angulos = [-90, 150, 30];
    $pts = [];
    foreach ($angulos as $a) {
        $pts[] = rotarPunto($cx, $cy, $r, $a);
    }

    imagesetthickness($img, $grosor);
    for ($i = 0; $i < 3; $i++) {
        $j = ($i + 1) % 3;
        imageline($img, (int) $pts[$i][0], (int) $pts[$i][1], (int) $pts[$j][0], (int) $pts[$j][1], $blanco);
    }

    // Flechas (puntas sólidas) en cada vértice apuntando en sentido horario.
    $largo = 0.14 * $size;
    $ancho = 0.075 * $size;
    for ($i = 0; $i < 3; $i++) {
        $v = $pts[$i];
        $siguiente = $pts[($i + 1) % 3];

        $dx = $siguiente[0] - $v[0];
        $dy = $siguiente[1] - $v[1];
        $mod = sqrt($dx * $dx + $dy * $dy) ?: 1;
        $ux = $dx / $mod;
        $uy = $dy / $mod;
        $px = -$uy;
        $py = $ux;

        $punta = [$v[0] + $ux * $largo * 1.5, $v[1] + $uy * $largo * 1.5];
        $b1 = [$v[0] + $px * $ancho, $v[1] + $py * $ancho];
        $b2 = [$v[0] - $px * $ancho, $v[1] - $py * $ancho];

        imagefilledpolygon($img, [
            (int) $punta[0], (int) $punta[1],
            (int) $b1[0], (int) $b1[1],
            (int) $b2[0], (int) $b2[1],
        ], $blanco);
    }

    imagepng($img, $archivo);
    echo "Generado: $archivo\n";
}

if ($argc < 2) {
    fwrite(STDERR, "Uso: php generar_iconos.php <directorio-salida>\n");
    exit(1);
}

$dir = rtrim($argv[1], '/');
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

pintarIcono(512, $dir.'/icon-512.png');
pintarIcono(192, $dir.'/icon-192.png');