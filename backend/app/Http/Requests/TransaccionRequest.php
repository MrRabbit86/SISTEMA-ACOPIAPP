<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransaccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'oferta_id' => ['required', 'integer', 'exists:ofertas,id'],
            'peso_real_kg' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'precio_acordado_kg' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'oferta_id.exists' => 'La oferta seleccionada no existe.',
            'peso_real_kg.gt' => 'El peso real debe ser mayor a cero.',
            'precio_acordado_kg.gt' => 'El precio acordado debe ser mayor a cero.',
        ];
    }
}