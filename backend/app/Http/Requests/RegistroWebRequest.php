<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistroWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['ciudadano', 'empresa'])],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ci' => ['nullable', 'required_if:tipo,ciudadano', 'string', 'max:15', 'unique:ciudadanos,ci'],
            'zona' => ['nullable', 'required_if:tipo,ciudadano', 'string', 'max:150'],
            'nit' => ['nullable', 'required_if:tipo,empresa', 'string', 'max:20', 'unique:empresas_acopiadoras,nit'],
            'razon_social' => ['nullable', 'required_if:tipo,empresa', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo de cuenta debe ser ciudadano o empresa.',
            'ci.required_if' => 'El carnet de identidad es obligatorio para ciudadanos.',
            'zona.required_if' => 'La zona es obligatoria para ciudadanos.',
            'nit.required_if' => 'El NIT es obligatorio para empresas.',
            'razon_social.required_if' => 'La razón social es obligatoria para empresas.',
        ];
    }
}