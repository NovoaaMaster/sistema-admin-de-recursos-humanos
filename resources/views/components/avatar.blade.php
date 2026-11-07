@props(['nombre', 'tamano' => null])

{{-- Decorativo: el nombre siempre aparece al lado, por eso aria-hidden. --}}
<span {{ $attributes->class(['avatar', 'avatar-'.$tamano => $tamano]) }} style="background-color: {{ \App\Crm\Avatar::color($nombre) }}" aria-hidden="true">{{ \App\Crm\Avatar::iniciales($nombre) }}</span>
