@props(['categoria', 'class' => 'h-5 w-5'])

@php
    $datos = \App\Support\CategoriaIconos::datos($categoria?->nombre ?? '');
@endphp

<span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1 {{ $datos['chip'] }}">
    <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
         aria-hidden="true">
        {!! $datos['svg'] !!}
    </svg>
</span>
