@props(['estado', 'label' => null])

@php
    $colores = [
        'pendiente' => 'bg-teal-100 text-teal-800',
        'en_proceso' => 'bg-blue-100 text-blue-800',
        'completada' => 'bg-emerald-100 text-emerald-800',
        'cancelada' => 'bg-red-100 text-red-800',
        'aprobado' => 'bg-emerald-100 text-emerald-800',
        'rechazado' => 'bg-red-100 text-red-800',
    ];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colores[$estado] ?? 'bg-gray-100 text-gray-800' }}">
    {{ $label ?? $estado }}
</span>