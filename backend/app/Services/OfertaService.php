<?php

namespace App\Services;

use App\Enums\EstadoOferta;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\Ciudadano;
use App\Models\Oferta;
use App\Models\User;
use App\Notifications\NotificacionSistema;
use App\Support\Result;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OfertaService
{
    public const HORAS_RESERVA = 2;

    public const MOTIVOS_LIBERACION = [
        'desacuerdo_precio' => 'No hubo acuerdo en el precio',
        'ciudadano_ausente' => 'Ciudadano ausente en el punto',
        'material_no_apto' => 'Material no apto o ya no disponible',
    ];

    public function publicar(Ciudadano $ciudadano, array $datos, ?UploadedFile $foto): Result
    {
        $resultado = DB::transaction(function () use ($ciudadano, $datos, $foto): Result {
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
                'codigo_confirmacion' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            ]);

            return Result::ok($oferta);
        });

        if ($resultado->bien()) {
            $this->notificarEmpresasNuevaOferta($resultado->valor());
        }

        return $resultado;
    }

    public function reservar(User $empresa, Oferta $oferta): Result
    {
        Oferta::liberarReservasExpiradas();

        $resultado = DB::transaction(function () use ($empresa, $oferta): Result {
            $oferta = Oferta::query()->lockForUpdate()->find($oferta->id);

            if (! $oferta || $oferta->estado !== EstadoOferta::PENDIENTE) {
                return Result::fail(
                    'La oferta ya no está disponible para reservar recojo.',
                    'oferta_no_disponible',
                );
            }

            $oferta->update([
                'estado' => EstadoOferta::EN_PROCESO,
                'empresa_reservada_id' => $empresa->id,
                'reservada_hasta' => now()->addHours(self::HORAS_RESERVA),
                'llegada_en' => null,
                'motivo_cierre' => null,
            ]);

            return Result::ok($oferta->fresh());
        });

        if ($resultado->bien()) {
            $ofertaActualizada = $resultado->valor()->loadMissing(['categoria', 'ciudadano.usuario']);
            $dueno = $ofertaActualizada->ciudadano?->usuario;
            if ($dueno) {
                $material = $ofertaActualizada->categoria?->nombre ?? 'material';
                $tel = $empresa->telefono ? " · Tel: {$empresa->telefono}" : '';
                $dueno->notify(new NotificacionSistema(
                    titulo: 'Empresa en camino',
                    mensaje: "{$empresa->name} reservó el recojo de tu oferta de {$material}{$tel}.",
                    url: route('mis-ofertas'),
                    tipo: 'recojo_reservado',
                ));
            }
        }

        return $resultado;
    }

    public function marcarLlegada(User $empresa, Oferta $oferta): Result
    {
        Oferta::liberarReservasExpiradas();

        $resultado = DB::transaction(function () use ($empresa, $oferta): Result {
            $oferta = Oferta::query()->lockForUpdate()->find($oferta->id);

            if (! $oferta || $oferta->estado !== EstadoOferta::EN_PROCESO) {
                return Result::fail('La oferta no está en proceso de recojo.', 'estado_invalido');
            }

            if ($oferta->empresa_reservada_id !== $empresa->id) {
                return Result::fail('Solo la empresa que reservó el recojo puede notificar su llegada.', 'sin_permiso');
            }

            $oferta->update([
                'llegada_en' => now(),
            ]);

            return Result::ok($oferta->fresh());
        });

        if ($resultado->bien()) {
            $ofertaActualizada = $resultado->valor()->loadMissing(['categoria', 'ciudadano.usuario']);
            $dueno = $ofertaActualizada->ciudadano?->usuario;
            if ($dueno) {
                $material = $ofertaActualizada->categoria?->nombre ?? 'material';
                $pin = $ofertaActualizada->codigo_confirmacion
                    ? " Ten a mano tu código de entrega ({$ofertaActualizada->codigo_confirmacion})."
                    : '';
                $dueno->notify(new NotificacionSistema(
                    titulo: 'La empresa llegó al punto de recojo',
                    mensaje: "{$empresa->name} ya se encuentra en el punto para recoger {$material}.{$pin}",
                    url: route('mis-ofertas'),
                    tipo: 'empresa_en_punto',
                ));
            }
        }

        return $resultado;
    }

    public function liberar(User $empresa, Oferta $oferta, string $motivo, ?string $nota = null): Result
    {
        $resultado = DB::transaction(function () use ($empresa, $oferta, $motivo, $nota): Result {
            $oferta = Oferta::query()->lockForUpdate()->find($oferta->id);

            if (! $oferta || $oferta->estado !== EstadoOferta::EN_PROCESO) {
                return Result::fail('La oferta no se encuentra en proceso de recojo.', 'estado_invalido');
            }

            if ($oferta->empresa_reservada_id !== $empresa->id) {
                return Result::fail('Solo la empresa que reservó el recojo puede liberarlo.', 'sin_permiso');
            }

            $etiqueta = self::MOTIVOS_LIBERACION[$motivo] ?? $motivo;
            $detalle = $nota ? "{$etiqueta}: {$nota}" : $etiqueta;
            $nuevoEstado = $motivo === 'material_no_apto'
                ? EstadoOferta::CANCELADA
                : EstadoOferta::PENDIENTE;

            $oferta->update([
                'estado' => $nuevoEstado,
                'empresa_reservada_id' => null,
                'reservada_hasta' => null,
                'llegada_en' => null,
                'motivo_cierre' => mb_substr($detalle, 0, 255),
            ]);

            return Result::ok($oferta->fresh());
        });

        if ($resultado->bien()) {
            $ofertaActualizada = $resultado->valor()->loadMissing(['categoria', 'ciudadano.usuario']);
            $dueno = $ofertaActualizada->ciudadano?->usuario;
            if ($dueno) {
                $material = $ofertaActualizada->categoria?->nombre ?? 'material';
                $cancelada = $ofertaActualizada->estado === EstadoOferta::CANCELADA;
                $dueno->notify(new NotificacionSistema(
                    titulo: $cancelada ? 'Oferta cerrada en recojo' : 'Recojo liberado',
                    mensaje: $cancelada
                        ? "{$empresa->name} cerró la oferta de {$material} ({$ofertaActualizada->motivo_cierre})."
                        : "{$empresa->name} liberó el recojo de {$material} ({$ofertaActualizada->motivo_cierre}). Tu oferta vuelve a estar disponible.",
                    url: route('mis-ofertas'),
                    tipo: 'recojo_liberado',
                ));
            }
        }

        return $resultado;
    }

    public function cancelar(Ciudadano $ciudadano, Oferta $oferta): Result
    {
        return DB::transaction(function () use ($ciudadano, $oferta): Result {
            // Bloquea la fila para evitar carreras entre cancelación del ciudadano y compra de la empresa.
            $oferta = Oferta::query()->lockForUpdate()->find($oferta->id);

            if (! $oferta || $oferta->ciudadano_id !== $ciudadano->id) {
                return Result::fail('No tiene permisos para cancelar esta oferta.', 'sin_permiso');
            }

            if ($oferta->estado === EstadoOferta::COMPLETADA || $oferta->estado === EstadoOferta::CANCELADA) {
                return Result::fail('La oferta no puede cancelarse en su estado actual.', 'estado_invalido');
            }

            $oferta->update([
                'estado' => EstadoOferta::CANCELADA,
                'empresa_reservada_id' => null,
                'reservada_hasta' => null,
                'llegada_en' => null,
            ]);

            return Result::ok($oferta);
        });
    }

    private function notificarEmpresasNuevaOferta(Oferta $oferta): void
    {
        $empresas = User::query()
            ->where('rol', Rol::EMPRESA->value)
            ->where('estado', EstadoUsuario::APROBADO->value)
            ->get();

        if ($empresas->isEmpty()) {
            return;
        }

        $oferta->loadMissing(['categoria', 'ciudadano.usuario']);
        $material = $oferta->categoria?->nombre ?? 'Material reciclable';
        $zona = $oferta->ciudadano?->zona ?? 'Zona Sur';
        $modalidad = $oferta->modalidadLabel() ?? 'Entrega';

        Notification::send($empresas, new NotificacionSistema(
            titulo: "Nueva oferta de {$material}",
            mensaje: "{$oferta->cantidad_estimada_kg} kg en {$zona} · {$modalidad}.",
            url: route('ofertas.show', $oferta),
            tipo: 'nueva_oferta',
        ));
    }
}
