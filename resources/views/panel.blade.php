@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mt-4 mb-4">
        <div>
            <h1 class="mb-1">Panel</h1>
            <p class="text-muted mb-0">{{ ucfirst(now()->translatedFormat('l j \d\e F')) }}</p>
        </div>
        <a href="{{ route('embudo.index') }}" class="btn btn-primary"><i class="fa-solid fa-filter-circle-dollar me-1"></i> Abrir el embudo</a>
    </div>

    @if ($errorPclap)
        <div class="alert alert-warning" role="alert"><i class="fa-solid fa-plug-circle-xmark me-2"></i>{{ $errorPclap }}</div>
    @endif

    @php
        $indicadores = [
            ['Clientes', $clientes, 'fa-users', '#ede9fe', '#6d28d9', $prospectos.' '.($prospectos === 1 ? 'prospecto' : 'prospectos').' además', route('clientes.index')],
            ['Nuevos del mes', $nuevosDelMes, 'fa-user-plus', '#dcfce7', '#166534', 'Primera compra este mes', route('clientes.index', ['segmento' => 'Nuevo'])],
            ['Inactivos', $inactivos, 'fa-user-clock', '#fee2e2', '#991b1b', 'Más de 90 días sin comprar', route('clientes.index', ['segmento' => 'Inactivo'])],
            ['Tareas para hoy', $tareasHoy, 'fa-list-check', '#fef3c7', '#92400e', 'Incluye las vencidas', route('tareas.index')],
            ['Valor del embudo', 'S/ '.number_format($valorEmbudo, 2), 'fa-sack-dollar', '#e0e7ff', '#3730a3', 'Oportunidades abiertas', route('embudo.index')],
        ];
    @endphp

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-4">
        @foreach ($indicadores as [$etiqueta, $valor, $icono, $fondo, $color, $detalle, $enlace])
            <div class="col">
                <a href="{{ $enlace }}" class="kpi d-block text-reset text-decoration-none" style="--kpi-fondo: {{ $fondo }}; --kpi-color: {{ $color }}">
                    <span class="kpi-icono"><i class="fa-solid {{ $icono }}"></i></span>
                    <div class="kpi-valor">{{ $valor ?? '—' }}</div>
                    <div class="fw-semibold mt-1">{{ $etiqueta }}</div>
                    <div class="kpi-etiqueta">{{ $detalle }}</div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list-check me-1 text-muted"></i> Tareas para hoy</span>
                    <a href="{{ route('tareas.index') }}" class="small fw-semibold">Ver todas</a>
                </div>
                <div class="card-body">
                    @forelse ($tareas as $tarea)
                        @php $cliente = $tarea->clienteRef() ? $clientesPorRef->get((string) $tarea->clienteRef()) : null; @endphp
                        <div class="d-flex align-items-center gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                            @if ($cliente)
                                <x-avatar :nombre="$cliente->nombre" />
                            @else
                                <span class="avatar" style="background-color: #c4b5fd" aria-hidden="true"><i class="fa-solid fa-thumbtack"></i></span>
                            @endif
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $tarea->titulo }}</div>
                                @if ($cliente)
                                    <a href="{{ route('clientes.show', (string) $cliente->ref) }}" class="small">{{ $cliente->nombre }}</a>
                                @endif
                            </div>
                            @if ($tarea->vence_el->isToday())
                                <span class="badge text-bg-warning">Hoy</span>
                            @else
                                <span class="badge text-bg-danger">Vencida {{ $tarea->vence_el->diffForHumans(today(), ['syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No tienes tareas para hoy. Programa la próxima desde la ficha de un cliente o en <a href="{{ route('tareas.index') }}">Tareas</a>.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-filter-circle-dollar me-1 text-muted"></i> Embudo de ventas</span>
                    <a href="{{ route('embudo.index') }}" class="small fw-semibold">Abrir</a>
                </div>
                <div class="card-body">
                    @foreach ($columnas as $columna)
                        <div class="{{ $loop->last ? '' : 'mb-3' }}">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold">{{ $columna['nombre'] }} <span class="text-muted fw-normal">({{ $columna['cantidad'] }})</span></span>
                                <span class="cifra">S/ {{ number_format($columna['total'], 2) }}</span>
                            </div>
                            <div class="barra-etapa" role="img" aria-label="{{ $columna['porcentaje'] }} % del embudo">
                                <span style="width: {{ $columna['porcentaje'] }}%; --etapa-color: {{ \App\Models\Oportunidad::COLORES[$columna['etapa']] }}"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-1 text-muted"></i> Oportunidades movidas recientemente</div>
                <div class="card-body">
                    @if ($recientes->isEmpty())
                        <p class="text-muted mb-0">Aún no hay oportunidades. Crea la primera desde el <a href="{{ route('embudo.index') }}">embudo</a>.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    @foreach ($recientes as $oportunidad)
                                        <tr>
                                            <td style="width: 3rem"><x-avatar :nombre="$oportunidad->contacto_nombre" tamano="sm" /></td>
                                            <td>
                                                <div class="fw-semibold">{{ $oportunidad->titulo }}</div>
                                                <div class="small text-muted">{{ $oportunidad->contacto_nombre }}</div>
                                            </td>
                                            <td><span class="badge rounded-pill" style="background-color: {{ \App\Models\Oportunidad::COLORES[$oportunidad->etapa] }}">{{ \App\Models\Oportunidad::NOMBRES[$oportunidad->etapa] }}</span></td>
                                            <td class="text-end fw-semibold">S/ {{ number_format($oportunidad->monto, 2) }}</td>
                                            <td class="text-end small text-muted text-nowrap">{{ $oportunidad->updated_at->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
