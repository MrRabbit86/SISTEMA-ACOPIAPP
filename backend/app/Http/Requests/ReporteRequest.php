<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['nullable', 'date', 'after_or_equal:2020-01-01', 'before_or_equal:today'],
            'hasta' => ['nullable', 'date', 'after_or_equal:2020-01-01', 'after_or_equal:desde', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'desde.date' => 'La fecha inicial no tiene un formato válido.',
            'desde.after_or_equal' => 'La fecha inicial no puede ser anterior al 01/01/2020.',
            'desde.before_or_equal' => 'La fecha inicial no puede ser futura.',
            'hasta.date' => 'La fecha final no tiene un formato válido.',
            'hasta.before_or_equal' => 'La fecha final no puede ser futura.',
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial y desde 2020.',
        ];
    }
}
