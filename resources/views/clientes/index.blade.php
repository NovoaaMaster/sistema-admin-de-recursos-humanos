@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mt-4 mb-3">
        <div>
            <h1 class="mb-1">Clientes</h1>
            <p class="text-muted mb-0">Clientes de la tienda y de la web (leídos de PCLAP) y prospectos del CRM.</p>
        </div>
        <a href="{{ route('prospectos.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i> Nuevo prospecto</a>
    </div>

    @if ($errorPclap)
        <div class="alert alert-warning" role="alert"><i class="fa-solid fa-plug-circle-xmark me-2"></i>{{ $errorPclap }}</div>
    @endif

    {{-- Resumen por etiqueta: cada una filtra la lista --}}
    @php $segmentoActual = $filtros['segmento'] ?? null; @endphp
    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Filtrar por etiqueta">
        <a href="{{ route('clientes.index', array_filter(['q' => $filtros['q'] ?? null])) }}"
            class="btn btn-sm {{ $segmentoActual ? 'btn-outline-primary' : 'btn-primary' }}" @if (! $segmentoActual) aria-current="true" @endif>
            Todos <span class="ms-1 opacity-75">{{ array_sum($conteo) }}</span>
        </a>
        @foreach ($conteo as $segmento => $cantidad)
            <a href="{{ route('clientes.index', array_filter(['segmento' => $segmento, 'q' => $filtros['q'] ?? null])) }}"
                class="segmento segmento-{{ \Illuminate\Support\Str::slug($segmento) }} text-decoration-none d-inline-flex align-items-center gap-1 px-3 py-2 {{ $segmentoActual === $segmento ? 'shadow-sm border border-2 border-dark-subtle' : '' }}"
                @if ($segmentoActual === $segmento) aria-current="true" @endif>
                {{ $segmento }} <span class="opacity-75">{{ $cantidad }}</span>
            </a>
        @endforeach
    </nav>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('clientes.index') }}" class="d-flex gap-2" role="search">
                @if ($segmentoActual)
                    <input type="hidden" name="segmento" value="{{ $segmentoActual }}">
                @endif
                <input type="search" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Buscar por nombre o documento" aria-label="Buscar cliente">
                <button type="submit" class="btn btn-sm btn-outline-primary">Buscar</button>
            </form>
        </div>
        <div class="card-body">
            @if ($clientes->isEmpty())
                <p class="text-muted mb-0">
                    No hay clientes que coincidan{{ array_filter($filtros) ? ' con la búsqueda' : '' }}.
                    Registra a quien aún no compra como <a href="{{ route('prospectos.create') }}">nuevo prospecto</a>.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Cliente</th>
                                <th scope="col">Origen</th>
                                <th scope="col">Teléfono</th>
                                <th scope="col" class="text-end">Compras</th>
                                <th scope="col" class="text-end">Total comprado</th>
                                <th scope="col">Última compra</th>
                                <th scope="col">Etiqueta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clientes as $cliente)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :nombre="$cliente->nombre" />
                                            <div>
                                                <a href="{{ route('clientes.show', (string) $cliente->ref) }}" class="fw-semibold text-reset">{{ $cliente->nombre }}</a>
                                                @if ($cliente->esDemo)
                                                    <span class="etiqueta-demo ms-1" title="Dato de demostración">Demo</span>
                                                @endif
                                                @if ($cliente->documento)
                                                    <div class="small text-muted">{{ $cliente->documento }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $cliente->origen }}</td>
                                    <td class="text-nowrap">
                                        @if ($cliente->telefono)
                                            {{ $cliente->telefono }}
                                            @if ($cliente->whatsapp())
                                                <a href="{{ $cliente->whatsapp() }}" target="_blank" rel="noopener" class="ms-1 text-success" title="Escribir por WhatsApp"><i class="fa-brands fa-whatsapp"></i><span class="visually-hidden">Escribir a {{ $cliente->nombre }} por WhatsApp</span></a>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $cliente->compras }}</td>
                                    <td class="text-end text-nowrap">S/ {{ number_format($cliente->totalComprado, 2) }}</td>
                                    <td class="text-nowrap">{{ $cliente->ultimaCompra?->format('d/m/Y') ?? '—' }}</td>
                                    <td><x-segmento :segmento="$cliente->segmento" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $clientes->links() }}
            @endif
        </div>
    </div>
@endsection
