<?php

namespace App\Http\Requests;

use App\Models\Oferta;
use App\Support\NormalizadorDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TransaccionRequest extends FormRequest
{
    public const MONTO_MAXIMO = 99999999.99;

    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && $user->esEmpresa() && $user->estaAprobado());
    }

    protected function prepareForValidation(): void
    {
        $normalizados = NormalizadorDecimal::normalizar($this->all(), [
            'peso_real_kg',
            'precio_acordado_kg',
        ]);

        if ($normalizados !== []) {
            $this->merge($normalizados);
        }
    }

    public function rules(): array
    {
        $reglas = [
            'peso_real_kg' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999'],
            'precio_acordado_kg' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999'],
            'codigo_confirmacion' => ['nullable', 'digits:4'],
            'sin_codigo_presencial' => ['nullable', 'boolean'],
        ];

        if (! $this->route('oferta')) {
            $reglas['oferta_id'] = ['required', 'integer', 'exists:ofertas,id'];
        }

        return $reglas;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $oferta = $this->route('oferta');
                if (! $oferta instanceof Oferta) {
                    $oferta = Oferta::query()->with('categoria')->find($this->input('oferta_id'));
                }

                if (! $oferta) {
                    return;
                }

                if (
                    $this->filled('codigo_confirmacion')
                    && $oferta->codigo_confirmacion !== null
                    && (string) $this->input('codigo_confirmacion') !== (string) $oferta->codigo_confirmacion
                ) {
                    $validator->errors()->add(
                        'codigo_confirmacion',
                        'El código de confirmación del ciudadano no coincide.',
                    );
                    return;
                }

                $precio = $this->filled('precio_acordado_kg')
                    ? round((float) $this->input('precio_acordado_kg'), 2)
                    : round($oferta->obtenerPrecioReferencia(), 2);

                $monto = round((float) $this->input('peso_real_kg') * $precio, 2);

                if ($monto > self::MONTO_MAXIMO) {
                    $validator->errors()->add(
                        'peso_real_kg',
                        'El monto total calculado supera el máximo permitido (Bs 99,999,999.99).',
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'oferta_id.exists' => 'La oferta seleccionada no existe.',
            'peso_real_kg.gt' => 'El peso real debe ser mayor a cero.',
            'peso_real_kg.decimal' => 'El peso real admite como máximo 2 decimales.',
            'precio_acordado_kg.gt' => 'El precio acordado debe ser mayor a cero.',
            'precio_acordado_kg.decimal' => 'El precio acordado admite como máximo 2 decimales.',
        ];
    }
}