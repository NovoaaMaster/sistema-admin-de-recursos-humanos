@extends('layouts.app')

@section('title', 'Embudo')

@section('content')
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mt-4 mb-3">
        <div>
            <h1 class="mb-1">Embudo de ventas</h1>
            <p class="text-muted mb-0">Arrastra las tarjetas entre columnas. Valor abierto: <strong class="cifra text-body" data-valor-abierto>S/ {{ number_format($valorAbierto, 2) }}</strong></p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOportunidad">
            <i class="fa-solid fa-plus me-1"></i> Nueva oportunidad
        </button>
    </div>

    @if ($errorPclap)
        <div class="alert alert-warning" role="alert"><i class="fa-solid fa-plug-circle-xmark me-2"></i>{{ $errorPclap }}</div>
    @endif

    <div class="tablero" data-url-base="{{ url('/oportunidades') }}">
        @foreach ($columnas as $columna)
            <section class="columna" style="--etapa-color: {{ \App\Models\Oportunidad::COLORES[$columna['etapa']] }}" aria-labelledby="columna-{{ $columna['etapa'] }}">
                <header>
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="columna-titulo" id="columna-{{ $columna['etapa'] }}"><span class="columna-punto" aria-hidden="true"></span>{{ $columna['nombre'] }}</h2>
                        <span class="columna-cantidad" data-cantidad title="Oportunidades en esta etapa">{{ $columna['cantidad'] }}</span>
                    </div>
                    <div class="columna-total cifra" data-total>S/ {{ number_format($columna['total'], 2) }}</div>
                    <div class="barra-etapa" aria-hidden="true"><span data-barra style="width: {{ $columna['porcentaje'] }}%"></span></div>
                    <div class="small text-muted mt-1"><span data-porcentaje>{{ $columna['porcentaje'] }}</span> % del embudo</div>
                </header>

                <div class="columna-cuerpo" data-etapa="{{ $columna['etapa'] }}" data-vacio="Arrastra una tarjeta aquí">@foreach ($columna['tarjetas'] as $oportunidad)
                    @php $dias = (int) floor($oportunidad->etapa_desde->diffInDays(now())); @endphp
                    <article class="tarjeta-op" data-id="{{ $oportunidad->id }}" data-etapa="{{ $oportunidad->etapa }}" tabindex="0" aria-label="{{ $oportunidad->titulo }}, S/ {{ number_format($oportunidad->monto, 2) }}">
                        {{-- Contacto arriba y título debajo a todo el ancho: en columnas angostas las palabras largas no se cortan --}}
                        <div class="d-flex gap-2 align-items-center">
                            <x-avatar :nombre="$oportunidad->contacto_nombre" tamano="sm" />
                            <span class="small text-muted text-truncate flex-grow-1 min-w-0">{{ $oportunidad->contacto_nombre }}</span>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-link text-muted p-0 px-1" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mover {{ $oportunidad->titulo }} a otra etapa">
                                    <i class="fa-solid fa-ellipsis"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h3 class="dropdown-header">Mover a…</h3></li>
                                    @foreach (\App\Models\Oportunidad::NOMBRES as $etapa => $nombre)
                                        <li><button type="button" class="dropdown-item" data-mover-a="{{ $etapa }}">{{ $nombre }}</button></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="tarjeta-titulo mt-2">{{ $oportunidad->titulo }}</div>
                        <div class="tarjeta-pie mt-2">
                            <span class="tarjeta-monto cifra">S/ {{ number_format($oportunidad->monto, 2) }}</span>
                            <span class="small text-muted" title="Tiempo en esta etapa"><i class="fa-regular fa-clock me-1"></i><span data-dias>{{ $dias === 0 ? 'Hoy' : $dias.' '.($dias === 1 ? 'día' : 'días') }}</span></span>
                        </div>
                        @if ($oportunidad->pclap_proforma_id || $oportunidad->es_demo || $oportunidad->motivo_perdida)
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @if ($oportunidad->pclap_proforma_id)
                                    <span class="badge rounded-pill text-bg-light border" title="Importada de una cotización de PCLAP">Cotización PCLAP</span>
                                @endif
                                @if ($oportunidad->es_demo)
                                    <span class="etiqueta-demo">Demo</span>
                                @endif
                                @if ($oportunidad->motivo_perdida)
                                    <span class="small text-muted w-100">Motivo: {{ $oportunidad->motivo_perdida }}</span>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach</div>
            </section>
        @endforeach
    </div>

    {{-- Nueva oportunidad: entra en la columna "Nuevo" --}}
    <div class="modal fade" id="modalOportunidad" tabindex="-1" aria-labelledby="modalOportunidadTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('oportunidades.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalOportunidadTitulo">Nueva oportunidad</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label for="titulo" class="form-label">Qué quiere comprar</label>
                        <input id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo') }}" maxlength="150" placeholder="10 laptops para la oficina" required>
                        @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label for="monto" class="form-label">Monto estimado (S/)</label>
                        <input id="monto" name="monto" type="number" min="0" step="0.01" class="form-control @error('monto') is-invalid @enderror" value="{{ old('monto') }}" required>
                        @error('monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label for="cliente_ref" class="form-label">Cliente</label>
                        <select id="cliente_ref" name="cliente_ref" class="form-select @error('cliente_ref') is-invalid @enderror">
                            <option value="">Sin cliente registrado</option>
                            @foreach ($opcionesClientes as $ref => $nombre)
                                <option value="{{ $ref }}" @selected(old('cliente_ref') === $ref)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('cliente_ref')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label for="contacto_nombre" class="form-label">Contacto</label>
                        <input id="contacto_nombre" name="contacto_nombre" class="form-control @error('contacto_nombre') is-invalid @enderror" value="{{ old('contacto_nombre') }}" maxlength="150" placeholder="Si eliges cliente, se usa su nombre">
                        @error('contacto_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label for="contacto_telefono" class="form-label">Teléfono</label>
                        <input id="contacto_telefono" name="contacto_telefono" type="tel" class="form-control" value="{{ old('contacto_telefono') }}" maxlength="20">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear oportunidad</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" integrity="sha384-BSxuMLxX+FCbTdYec3TbXlnMGEEM2QXTFdtDaveen71o+jswm2J36+xFqp8k4VHM" crossorigin="anonymous"></script>
    <script>
        $(function () {
            @if ($errors->any())
                new bootstrap.Modal('#modalOportunidad').show();
            @endif

            const urlBase = document.querySelector('.tablero').dataset.urlBase;
            const token = $('meta[name="csrf-token"]').attr('content');
            const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const soles = (monto) => 'S/ ' + Number(monto).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function actualizarCabeceras(columnas, valorAbierto) {
                Object.entries(columnas).forEach(function ([etapa, columna]) {
                    const $columna = $('.columna-cuerpo[data-etapa="' + etapa + '"]').closest('.columna');
                    $columna.find('[data-cantidad]').text(columna.cantidad);
                    $columna.find('[data-total]').text(soles(columna.total));
                    $columna.find('[data-barra]').css('width', columna.porcentaje + '%');
                    $columna.find('[data-porcentaje]').text(columna.porcentaje);
                });
                $('[data-valor-abierto]').text(soles(valorAbierto));
            }

            async function pedirMotivo() {
                const respuesta = await Swal.fire({
                    titleText: '¿Por qué se perdió?',
                    input: 'text',
                    inputPlaceholder: 'Eligió otra tienda, precio, ya no lo necesita…',
                    showCancelButton: true,
                    confirmButtonText: 'Marcar como perdida',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#6d28d9',
                    inputValidator: (valor) => (!valor || !valor.trim()) && 'Escribe el motivo.',
                });
                return respuesta.isConfirmed ? respuesta.value.trim() : null;
            }

            // Guarda la nueva posición; si algo falla, la tarjeta vuelve a su lugar.
            async function mover(tarjeta, etapa, posicion, revertir) {
                let motivo = null;
                if (etapa === 'perdido' && tarjeta.dataset.etapa !== 'perdido') {
                    motivo = await pedirMotivo();
                    if (motivo === null) {
                        revertir();
                        return;
                    }
                }

                $.ajax({
                    url: urlBase + '/' + tarjeta.dataset.id + '/mover',
                    method: 'PATCH',
                    data: { etapa: etapa, posicion: posicion, motivo: motivo },
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': token },
                })
                    .done(function (respuesta) {
                        if (tarjeta.dataset.etapa !== respuesta.etapa) {
                            tarjeta.querySelector('[data-dias]').textContent = 'Hoy';
                        }
                        tarjeta.dataset.etapa = respuesta.etapa;
                        actualizarCabeceras(respuesta.columnas, respuesta.valor_abierto);
                    })
                    .fail(function (xhr) {
                        revertir();
                        Swal.fire({ icon: 'error', titleText: 'No se pudo mover la tarjeta', text: xhr.responseJSON?.message || 'Vuelve a intentarlo.' });
                    });
            }

            document.querySelectorAll('.columna-cuerpo').forEach(function (lista) {
                new Sortable(lista, {
                    group: 'embudo',
                    animation: reducido ? 0 : 180,
                    forceFallback: true,
                    fallbackClass: 'tarjeta-arrastrada',
                    ghostClass: 'tarjeta-fantasma',
                    filter: '.dropdown',
                    preventOnFilter: false,
                    onEnd: function (evento) {
                        if (evento.from === evento.to && evento.oldIndex === evento.newIndex) {
                            return;
                        }
                        // Referencia para devolver la tarjeta a su lugar original si el servidor la rechaza.
                        const mismaLista = evento.from === evento.to;
                        const referencia = evento.from.children[mismaLista && evento.oldIndex > evento.newIndex ? evento.oldIndex + 1 : evento.oldIndex] || null;
                        const revertir = () => evento.from.insertBefore(evento.item, referencia);
                        mover(evento.item, evento.to.dataset.etapa, evento.newIndex, revertir);
                    },
                });
            });

            // Alternativa al arrastre (teclado): menú "Mover a…" de cada tarjeta.
            $(document).on('click', '[data-mover-a]', function () {
                const tarjeta = this.closest('.tarjeta-op');
                const destino = document.querySelector('.columna-cuerpo[data-etapa="' + this.dataset.moverA + '"]');
                const origen = tarjeta.parentElement;
                if (origen === destino) {
                    return;
                }
                const siguiente = tarjeta.nextElementSibling;
                destino.appendChild(tarjeta);
                tarjeta.focus();
                mover(tarjeta, this.dataset.moverA, destino.children.length - 1, () => origen.insertBefore(tarjeta, siguiente));
            });
        });
    </script>
@endpush
