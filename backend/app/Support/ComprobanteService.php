<?php

namespace App\Support;

use App\Models\Comprobante;
use App\Models\Transaccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as Dompdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComprobanteService
{
    public function generar(Transaccion $transaccion): Comprobante
    {
        return DB::transaction(function () use ($transaccion): Comprobante {
            $comprobante = $transaccion->comprobante;

            if ($comprobante === null) {
                $comprobante = Comprobante::create([
                    'transaccion_id' => $transaccion->id,
                    'numero_comprobante' => $this->siguienteNumero(),
                    'fecha_emision' => now(),
                ]);
            }

            $numero = $comprobante->numero_comprobante;
            $ruta = 'comprobantes/'.$numero.'.pdf';

            Storage::disk('public')->put($ruta, $this->render($transaccion, $comprobante)->output());

            $comprobante->update(['pdf_url' => '/storage/'.$ruta]);

            return $comprobante->fresh();
        });
    }

    public function render(Transaccion $transaccion, Comprobante $comprobante): Dompdf
    {
        return Pdf::loadView('pdf.comprobante', [
            'transaccion' => $transaccion->load([
                'oferta.categoria', 'oferta.ciudadano.usuario', 'empresa.empresa',
            ]),
            'comprobante' => $comprobante,
        ])->setPaper('a4');
    }

    public function siguienteNumero(): string
    {
        $anio = now()->year;

        $ultimo = Comprobante::query()
            ->whereYear('fecha_emision', $anio)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('numero_comprobante');

        $secuencia = $ultimo !== null
            ? (int) Str::afterLast($ultimo, '-') + 1
            : 1;

        return 'CMP-'.$anio.'-'.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }
}