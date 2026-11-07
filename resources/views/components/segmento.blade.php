@props(['segmento'])

@php
    $ayuda = [
        'Nuevo' => 'Su primera compra fue hace menos de 30 días',
        'Frecuente' => '3 compras o más',
        'Inactivo' => 'Más de 90 días sin comprar',
        'Ocasional' => 'Compra de vez en cuando',
        'Sin compras' => 'Registrado en PCLAP, aún sin compras pagadas',
        'Prospecto' => 'Todavía no es cliente',
    ];
@endphp

<span {{ $attributes->class(['segmento', 'segmento-'.\Illuminate\Support\Str::slug($segmento)]) }} title="{{ $ayuda[$segmento] ?? '' }}">{{ $segmento }}</span>
