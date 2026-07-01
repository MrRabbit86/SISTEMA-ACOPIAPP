<?php

namespace App\Support;

class CategoriaIconos
{
    /**
     * Mapa de categoría (nombre en minúsculas) => [svg, clases Tailwind del chip].
     * Categorías sin mapeo caen al ícono genérico de reciclaje.
     */
    private const MAPA = [
        'pet' => [
            'etiquetas' => ['plástico pet', 'plastico pet', 'pet'],
            'svg' => '<path d="M8 3h8l2 4-2 14H8L6 7l2-4Z"/><path d="M6 7h12"/><path d="M10 21V11a2 2 0 0 1 4 0v10"/>',
            'chip' => 'bg-sky-100 text-sky-800 ring-sky-200',
        ],
        'cartón' => [
            'etiquetas' => ['cartón', 'carton'],
            'svg' => '<path d="M3 8.5 12 3l9 5.5v7L12 21l-9-5.5v-7Z"/><path d="M3 8.5 12 14l9-5.5"/><path d="M12 14v7"/>',
            'chip' => 'bg-amber-100 text-amber-800 ring-amber-200',
        ],
        'vidrio' => [
            'etiquetas' => ['vidrio'],
            'svg' => '<path d="M8 3h8l-1 6c-.5 3 .5 4.5 1.5 6A4.5 4.5 0 0 1 13 21h-2a4.5 4.5 0 0 1-3.5-6c1-1.5 2-3 1.5-6L8 3Z"/><path d="M9.2 13h5.6"/>',
            'chip' => 'bg-teal-100 text-teal-800 ring-teal-200',
        ],
        'papel' => [
            'etiquetas' => ['papel'],
            'svg' => '<path d="M6 2h9l5 5v15H6V2Z"/><path d="M15 2v5h5"/><path d="M9 12h6M9 16h6M9 8h3"/>',
            'chip' => 'bg-sky-50 text-slate-700 ring-slate-200',
        ],
        'latas de aluminio' => [
            'etiquetas' => ['latas de aluminio', 'aluminio', 'latas'],
            'svg' => '<path d="M7 4h10v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4Z"/><ellipse cx="12" cy="4" rx="5" ry="1.6"/><path d="M7 9h10"/><path d="M17 6.5l3-1M7 6.5 4 5.5"/>',
            'chip' => 'bg-zinc-100 text-zinc-700 ring-zinc-200',
        ],
        'metales' => [
            'etiquetas' => ['metales', 'metal'],
            'svg' => '<path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z"/><circle cx="12" cy="12" r="3"/><path d="m4 7 8 4 8-4M12 11v10"/>',
            'chip' => 'bg-slate-200 text-slate-800 ring-slate-300',
        ],
    ];

    public static function clave(string $nombre): string
    {
        $normalizado = mb_strtolower(trim($nombre));

        foreach (self::MAPA as $clave => $def) {
            if (in_array($normalizado, $def['etiquetas'], true)) {
                return $clave;
            }
        }

        return '';
    }

    /** @return array{svg: string, chip: string} */
    public static function datos(string $nombre): array
    {
        $clave = self::clave($nombre);

        if ($clave === '') {
            return [
                'svg' => '<path d="M7 19 4.6 14.9a2 2 0 0 1 .1-2.1L9 6h9l-3 5"/><path d="M5 15h6l-2-2m2 2-2 2"/><path d="M14.5 19.5 17 15l1.5 2.5"/><path d="M20 12.5 17.6 17"/>',
                'chip' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            ];
        }

        return [
            'svg' => self::MAPA[$clave]['svg'],
            'chip' => self::MAPA[$clave]['chip'],
        ];
    }
}
