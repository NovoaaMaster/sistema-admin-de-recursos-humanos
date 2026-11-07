@extends('layouts.app')

@section('title', $cliente->nombre)

@php
    $esProspecto = $cliente->origen === 'Prospecto';
    $ref = (string) $cliente->ref;
    $pestanas = array_filter([
        'actividad' => ['Actividad', $actividad->count()],
        'compras' => $esProspecto ? null : ['Compras', $compras->count()],
        'garantias' => $esProspecto ? null : ['Garantías', $garantias->count()],
        'pedidos' => $cliente->origen === 'Web' ? ['Pedidos web', $pedidosWeb->count()] : null,
        'notas' => ['Notas', $notas->count()],
        'tareas' => ['Tareas', $tareas->whereNull('hecha_en')->count()],
        'oportunidades' => ['Oportunidades', $oportunidades->count()],
    ]);
    $pestana = array_key_exists($pestana, $pestanas) ? $pestana : 'actividad';
    $iconos = [
        'compra' => ['fa-bag-shopping', '#6d28d9'],
        'garantia' => ['fa-shield-halved', '#0e7490'],
        'pedido' => ['fa-globe', '#4338ca'],
        'nota' => ['fa-note-sticky', '#92400e'],
        'tarea' => ['fa-list-check', '#166534'],
        'oportunidad' => ['fa-filter-circle-dollar', '#9d174d'],
    ];
@endphp

@section('content')
    <div class="mt-4"></div>
    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('clientes.index')" content="Clientes" />
        <x-breadcrumb.item :active="true" :content="$cliente->nombre" />
    </x-breadcrumb.template>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <x-avatar :nombre="$cliente->nombre" tamano="lg" />
            <div class="flex-grow-1">
                <h1 class="h3 mb-1">{{ $cliente->nombre }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                    <x-segmento :segmento="$cliente->segmento" />
                    <span><i class="fa-solid fa-store me-1"></i>{{ $esProspecto ? 'Prospecto del CRM' : 'Cliente '.mb_strtolower($cliente->origen).' en PCLAP' }}</span>
                    @if ($cliente->documento)<span><i class="fa-regular fa-id-card me-1"></i>{{ $cliente->documento }}</span>@endif
                    @if ($cliente->telefono)<span><i class="fa-solid fa-phone me-1"></i>{{ $cliente->telefono }}</span>@endif
                    @if ($cliente->email)<span><i class="fa-regular fa-envelope me-1"></i>{{ $cliente->email }}</span>@endif
                    @if ($cliente->esDemo)<span class="etiqueta-demo">Demo</span>@endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($cliente->whatsapp())
                    <a href="{{ $cliente->whatsapp('Hola '.$cliente->nombre.', te escribimos de PCLAP SUPPORT.') }}" target="_blank" rel="noopener" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i> WhatsApp</a>
                @endif
                @if ($esProspecto)
                    <a href="{{ route('prospectos.edit', $cliente->ref->id) }}" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i> Editar</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-lg-4 g-3 mb-3">
        @php
            $resumen = $esProspecto
                ? [
                    ['Oportunidades abiertas', $oportunidades->whereIn('etapa', \App\Models\Oportunidad::ABIERTAS)->count()],
                    ['En el embudo', 'S/ '.number_format($oportunidades->whereIn('etapa', \App\Models\Oportunidad::ABIERTAS)->sum('monto'), 2)],
                    ['Tareas pendientes', $tareas->whereNull('hecha_en')->count()],
                    ['Notas', $notas->count()],
                ]
                : [
                    ['Total comprado', 'S/ '.number_format($cliente->totalComprado, 2)],
                    ['Compras', $cliente->compras],
                    ['Ticket promedio', 'S/ '.number_format($ticketPromedio, 2)],
                    ['Última compra', $cliente->ultimaCompra?->format('d/m/Y') ?? '—'],
                ];
        @endphp
        @foreach ($resumen as [$etiqueta, $valor])
            <div class="col">
                <div class="kpi">
                    <div class="kpi-etiqueta">{{ $etiqueta }}</div>
                    <div class="kpi-valor mt-1" style="font-size: 1.4rem">{{ $valor }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header pb-0 border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                @foreach ($pestanas as $clave => [$titulo, $cantidad])
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $pestana === $clave ? 'active' : '' }}" id="pestana-{{ $clave }}" data-bs-toggle="tab" data-bs-target="#panel-{{ $clave }}" type="button" role="tab" aria-controls="panel-{{ $clave }}" aria-selected="{{ $pestana === $clave ? 'true' : 'false' }}">
                            {{ $titulo }} <span class="badge rounded-pill text-bg-light border ms-1">{{ $cantidad }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body tab-content">

            {{-- Actividad: línea de tiempo con PCLAP y el CRM juntos --}}
            <div class="tab-pane fade {{ $pestana === 'actividad' ? 'show active' : '' }}" id="panel-actividad" role="tabpanel" aria-labelledby="pestana-actividad" tabindex="0">
                @forelse ($actividad as $evento)
                    @php [$icono, $color] = $iconos[$evento['tipo']]; @endphp
                    <div class="linea-tiempo-item">
                        <span class="linea-tiempo-icono" style="--evento-color: {{ $color }}"><i class="fa-solid {{ $icono }}"></i></span>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <span class="fw-semibold">{{ $evento['titulo'] }}</span>
                                <time class="small text-muted" datetime="{{ $evento['fecha']->toIso8601String() }}">{{ $evento['fecha']->format('d/m/Y H:i') }}</time>
                            </div>
                            @if ($evento['detalle'])
                                <div class="text-muted small text-break">{{ $evento['detalle'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin actividad todavía. Agrega una nota o programa una tarea para empezar el seguimiento.</p>
                @endforelse
            </div>

            @unless ($esProspecto)
                <div class="tab-pane fade {{ $pestana === 'compras' ? 'show active' : '' }}" id="panel-compras" role="tabpanel" aria-labelledby="pestana-compras" tabindex="0">
                    @if ($compras->isEmpty())
                        <p class="text-muted mb-0">Este cliente aún no tiene compras pagadas en PCLAP.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr><th scope="col">Fecha</th><th scope="col">Venta</th><th scope="col">Productos</th><th scope="col" class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($compras as $venta)
                                        @php $series = $venta->series->groupBy('producto_id'); @endphp
                                        <tr>
                                            <td class="text-nowrap">{{ $venta->fecha_hora->format('d/m/Y') }}</td>
                                            <td class="text-nowrap">{{ $venta->numero_comprobante }}</td>
                                            <td>
                                                @foreach ($venta->productos as $producto)
                                                    <div>
                                                        {{ $producto->pivot->cantidad }} × {{ $producto->nombre }}
                                                        @if ($series->has($producto->id))
                                                            <div class="small text-muted">S/N: {{ $series[$producto->id]->pluck('numero_serie')->implode(', ') }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="text-end text-nowrap fw-semibold">S/ {{ number_format($venta->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade {{ $pestana === 'garantias' ? 'show active' : '' }}" id="panel-garantias" role="tabpanel" aria-labelledby="pestana-garantias" tabindex="0">
                    @if ($garantias->isEmpty())
                        <p class="text-muted mb-0">Sin garantías registradas.</p>
                    @else
                        <table class="table align-middle mb-0">
                            <thead><tr><th scope="col">Producto</th><th scope="col">Serie</th><th scope="col">Estado</th><th scope="col">Fecha</th></tr></thead>
                            <tbody>
                                @foreach ($garantias as $garantia)
                                    <tr>
                                        <td>{{ $garantia->producto?->nombre ?? '—' }}</td>
                                        <td>{{ $garantia->numero_serie }}</td>
                                        <td><span class="badge text-bg-info">{{ ucfirst((string) $garantia->estado) }}</span></td>
                                        <td>{{ $garantia->created_at?->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endunless

            @if ($cliente->origen === 'Web')
                <div class="tab-pane fade {{ $pestana === 'pedidos' ? 'show active' : '' }}" id="panel-pedidos" role="tabpanel" aria-labelledby="pestana-pedidos" tabindex="0">
                    @if ($pedidosWeb->isEmpty())
                        <p class="text-muted mb-0">Sin pedidos en la tienda web.</p>
                    @else
                        <table class="table align-middle mb-0">
                            <thead><tr><th scope="col">Código</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col" class="text-end">Total</th></tr></thead>
                            <tbody>
                                @foreach ($pedidosWeb as $pedido)
                                    <tr>
                                        <td class="fw-semibold">{{ $pedido->codigo }}</td>
                                        <td>{{ $pedido->created_at?->format('d/m/Y') }}</td>
                                        <td>{{ ucfirst((string) $pedido->estado) }}</td>
                                        <td class="text-end">S/ {{ number_format($pedido->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif

            <div class="tab-pane fade {{ $pestana === 'notas' ? 'show active' : '' }}" id="panel-notas" role="tabpanel" aria-labelledby="pestana-notas" tabindex="0">
                <form method="POST" action="{{ route('notas.store', $ref) }}" class="mb-4">
                    @csrf
                    <label for="texto" class="form-label">Nueva nota</label>
                    <textarea id="texto" name="texto" rows="3" maxlength="2000" class="form-control @error('texto') is-invalid @enderror" placeholder="Qué conversaron, qué necesita, cuándo volver a llamar…">{{ old('texto') }}</textarea>
                    @error('texto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Guardar nota</button>
                </form>
                @forelse ($notas as $nota)
                    <div class="border-start border-3 ps-3 mb-3" style="border-color: #c4b5fd !important">
                        <div class="small text-muted">{{ $nota->created_at->format('d/m/Y H:i') }}{{ $nota->user ? ' · '.$nota->user->name : '' }}</div>
                        <div class="text-break">{{ $nota->texto }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin notas todavía.</p>
                @endforelse
            </div>

            <div class="tab-pane fade {{ $pestana === 'tareas' ? 'show active' : '' }}" id="panel-tareas" role="tabpanel" aria-labelledby="pestana-tareas" tabindex="0">
                <form method="POST" action="{{ route('tareas.store') }}" class="row g-2 align-items-end mb-4">
                    @csrf
                    <input type="hidden" name="cliente_ref" value="{{ $ref }}">
                    <div class="col-md-7">
                        <label for="titulo" class="form-label">Nueva tarea</label>
                        <input id="titulo" name="titulo" class="form-control" maxlength="150" placeholder="Llamar para confirmar la cotización" required>
                    </div>
                    <div class="col-md-3">
                        <label for="vence_el" class="form-label">Vence el</label>
                        <input id="vence_el" name="vence_el" type="date" class="form-control" value="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Programar</button>
                    </div>
                </form>
                @forelse ($tareas as $tarea)
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                        <i class="fa-{{ $tarea->hecha_en ? 'solid fa-circle-check text-success' : 'regular fa-circle text-muted' }}"></i>
                        <span class="flex-grow-1 {{ $tarea->hecha_en ? 'text-decoration-line-through text-muted' : '' }}">{{ $tarea->titulo }}</span>
                        <span class="small text-muted">{{ $tarea->hecha_en ? 'Hecha el '.$tarea->hecha_en->format('d/m/Y') : 'Vence el '.$tarea->vence_el->format('d/m/Y') }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin tareas para este cliente.</p>
                @endforelse
            </div>

            <div class="tab-pane fade {{ $pestana === 'oportunidades' ? 'show active' : '' }}" id="panel-oportunidades" role="tabpanel" aria-labelledby="pestana-oportunidades" tabindex="0">
                @forelse ($oportunidades as $oportunidad)
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                        <span class="badge rounded-pill" style="background-color: {{ \App\Models\Oportunidad::COLORES[$oportunidad->etapa] }}">{{ \App\Models\Oportunidad::NOMBRES[$oportunidad->etapa] }}</span>
                        <span class="flex-grow-1">{{ $oportunidad->titulo }}</span>
                        <span class="fw-semibold">S/ {{ number_format($oportunidad->monto, 2) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin oportunidades. Crea una desde el <a href="{{ route('embudo.index') }}">embudo</a>.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
