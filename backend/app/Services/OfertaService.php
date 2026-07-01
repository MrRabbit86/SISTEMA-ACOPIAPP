<?php

namespace App\Services;

use App\Enums\EstadoOferta;
use App\Models\Ciudadano;
use App\Models\Oferta;
use App\Support\Result;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class OfertaService
{
    public function publicar(Ciudadano $ciudadano, array $datos, ?UploadedFile $foto): Result
    {
        return DB::transaction(function () use ($ciudadano, $datos, $foto): Result {
            // Bloquea las ofertas activas del ciudadano para serializar
            // dobles envíos y evitar publicaciones duplicadas en 24 h.
            Oferta::query()
                ->where('ciudadano_id', $ciudadano->id)
                ->whereIn('estado', [EstadoOferta::PENDIENTE->value, EstadoOferta::EN_PROCESO->value])
                ->lockForUpdate()
                ->get();

            if (Oferta::existeDuplicada($ciudadano->id, $datos['categoria_id'])) {
                return Result::fail(
                    'Ya tiene una oferta publicada de este material hace menos de 24 horas.',
                    'oferta_duplicada',
                );
            }

            $oferta = Oferta::create([
                'ciudadano_id' => $ciudadano->id,
                'categoria_id' => $datos['categoria_id'],
                'cantidad_estimada_kg' => $datos['cantidad_estimada_kg'],
                'descripcion' => $datos['descripcion'] ?? null,
                'modalidad_entrega' => $datos['modalidad_entrega'] ?? null,
                'foto_url' => Oferta::guardarFoto($foto),
                'latitud' => $datos['latitud'],
                'longitud' => $datos['longitud'],
                'estado' => EstadoOferta::PENDIENTE,
            ]);

            return Result::ok($oferta);
        });
    }

    public function cancelar(Ciudadano $ciudadano, Oferta $oferta): Result
    {
        if ($oferta->ciudadano_id !== $ciudadano->id) {
            return Result::fail('No tiene permisos para cancelar esta oferta.', 'sin_permiso');
        }

        if ($oferta->estado === EstadoOferta::COMPLETADA || $oferta->estado === EstadoOferta::CANCELADA) {
            return Result::fail('La oferta no puede cancelarse en su estado actual.', 'estado_invalido');
        }

        $oferta->update(['estado' => EstadoOferta::CANCELADA]);

        return Result::ok($oferta);
    }
}