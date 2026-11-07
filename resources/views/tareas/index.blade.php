@extends('layouts.app')

@section('title', 'Tareas')

@php
    $secciones = [
        'vencidas' => ['Vencidas', 'fa-triangle-exclamation', '#991b1b', 'Nada vencido. Buen trabajo.'],
        'hoy' => ['Para hoy', 'fa-sun', '#92400e', 'No hay tareas para hoy.'],
        'proximas' => ['Próximas', 'fa-calendar-days', '#6d28d9', 'No hay tareas programadas.'],
    ];
@endphp

@section('content')
    <div class="mt-4 mb-3">
        <h1 class="mb-1">Tareas</h1>
        <p class="text-muted mb-0">Seguimiento pendiente con tus clientes. Marca cada una como hecha cuando la termines.</p>
    </div>

    <form method="POST" action="{{ route('tareas.store') }}" class="card mb-4">
        @csrf
        <div class="card-body row g-2 align-items-end">
            <div class="col-lg-5">
                <label for="titulo" class="form-label">Nueva tarea</label>
                <input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo') }}" maxlength="150" placeholder="Llamar para confirmar la cotización" required>
                @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="vence_el" class="form-label">Vence el</label>
                <input id="vence_el" name="vence_el" type="date" class="form-control @error('vence_el') is-invalid @enderror" value="{{ old('vence_el', today()->toDateString()) }}" required>
                @error('vence_el')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6 col-lg-3">
                <label for="cliente_ref" class="form-label">Cliente</label>
                <select id="cliente_ref" name="cliente_ref" class="form-select @error('cliente_ref') is-invalid @enderror">
                    <option value="">Sin cliente</option>
                    @foreach ($opcionesClientes as $ref => $nombre)
                        <option value="{{ $ref }}" @selected(old('cliente_ref') === $ref)>{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('cliente_ref')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-1"></i> Programar</button>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @foreach ($secciones as $clave => [$titulo, $icono, $color, $vacio])
            <div class="col-lg-4">
                <div class="card h-100" data-grupo="{{ $clave }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid {{ $icono }} me-2" style="color: {{ $color }}"></i>{{ $titulo }}</span>
                        <span class="badge rounded-pill text-bg-light border" data-grupo-cantidad>{{ $grupos[$clave]->count() }}</span>
                    </div>
                    <div class="card-body">
                        @foreach ($grupos[$clave] as $tarea)
                            @php $cliente = $tarea->clienteRef() ? $clientesPorRef->get((string) $tarea->clienteRef()) : null; @endphp
                            <div class="d-flex gap-2 py-2 border-bottom" data-tarea>
                                <button type="button" class="btn btn-sm btn-outline-success align-self-start" data-hecha="{{ route('tareas.hecha', $tarea) }}" title="Marcar como hecha">
                                    <i class="fa-solid fa-check"></i><span class="visually-hidden">Marcar "{{ $tarea->titulo }}" como hecha</span>
                                </button>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">{{ $tarea->titulo }}</div>
                                    <div class="small text-muted">{{ $clave === 'vencidas' ? 'Venció el' : 'Vence el' }} {{ $tarea->vence_el->format('d/m/Y') }}</div>
                                    @if ($cliente)
                                        <a href="{{ route('clientes.show', (string) $cliente->ref) }}" class="d-inline-flex align-items-center gap-1 small mt-1">
                                            <x-avatar :nombre="$cliente->nombre" tamano="sm" /> {{ $cliente->nombre }}
                                        </a>
                                    @endif
                                    @if ($tarea->es_demo)
                                        <span class="etiqueta-demo">Demo</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <p class="text-muted mb-0 {{ $grupos[$clave]->isEmpty() ? '' : 'd-none' }}" data-grupo-vacio>{{ $vacio }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('js')
    <script>
        $(function () {
            const token = $('meta[name="csrf-token"]').attr('content');

            $(document).on('click', '[data-hecha]', function () {
                const $boton = $(this).prop('disabled', true);
                const $tarea = $boton.closest('[data-tarea]');
                const $grupo = $boton.closest('[data-grupo]');

                $.ajax({ url: $boton.data('hecha'), method: 'PATCH', dataType: 'json', headers: { 'X-CSRF-TOKEN': token } })
                    .done(function (respuesta) {
                        $tarea.fadeOut(window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 200, function () {
                            $tarea.remove();
                            const restantes = $grupo.find('[data-tarea]').length;
                            $grupo.find('[data-grupo-cantidad]').text(restantes);
                            $grupo.find('[data-grupo-vacio]').toggleClass('d-none', restantes > 0);
                        });

                        const $contador = $('[data-contador-vencidas]');
                        if (respuesta.vencidas > 0) {
                            $contador.text(respuesta.vencidas);
                        } else {
                            $contador.next('.visually-hidden').remove();
                            $contador.remove();
                        }

                        Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 })
                            .fire({ icon: 'success', titleText: 'Tarea hecha' });
                    })
                    .fail(function () {
                        $boton.prop('disabled', false);
                        Swal.fire({ icon: 'error', titleText: 'No se pudo marcar la tarea', text: 'Vuelve a intentarlo.' });
                    });
            });
        });
    </script>
@endpush
