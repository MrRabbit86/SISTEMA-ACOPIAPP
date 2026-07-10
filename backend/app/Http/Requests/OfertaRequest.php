<?php

namespace App\Http\Requests;

use App\Enums\ModalidadEntrega;
use App\Support\Geolocalizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('modalidad_entrega')) {
            $this->merge([
                'modalidad_entrega' => ModalidadEntrega::ENTREGA_PUNTO_VERDE->value,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias_material,id'],
            'cantidad_estimada_kg' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'latitud' => ['required', 'numeric', 'between:'.Geolocalizacion::LAT_MIN.','.Geolocalizacion::LAT_MAX],
            'longitud' => ['required', 'numeric', 'between:'.Geolocalizacion::LNG_MIN.','.Geolocalizacion::LNG_MAX],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'modalidad_entrega' => ['nullable', Rule::in(array_column(ModalidadEntrega::cases(), 'value'))],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'cantidad_estimada_kg.gt' => 'La cantidad estimada debe ser mayor a cero.',
            'latitud.between' => 'Las coordenadas están fuera de la Zona Sur de Cochabamba.',
            'longitud.between' => 'Las coordenadas están fuera de la Zona Sur de Cochabamba.',
            'modalidad_entrega.in' => 'La modalidad de entrega no es válida.',
            'foto.image' => 'La foto debe ser una imagen.',
            'foto.max' => 'La foto no puede superar los 2 MB.',
        ];
    }
}