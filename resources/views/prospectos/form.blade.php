@extends('layouts.app')

@php $editando = $prospecto->exists; @endphp

@section('title', $editando ? 'Editar prospecto' : 'Nuevo prospecto')

@section('content')
    <div class="mt-4"></div>
    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('clientes.index')" content="Clientes" />
        <x-breadcrumb.item :active="true" :content="$editando ? 'Editar prospecto' : 'Nuevo prospecto'" />
    </x-breadcrumb.template>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <h1 class="h3 mb-1">{{ $editando ? 'Editar prospecto' : 'Nuevo prospecto' }}</h1>
            <p class="text-muted">Alguien que aún no compra. Cuando compre, regístralo como cliente en PCLAP.</p>

            <form method="POST" action="{{ $editando ? route('prospectos.update', $prospecto) : route('prospectos.store') }}" class="card">
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif
                <div class="card-body row g-3">
                    @foreach ([['nombre', 'Nombre', 'text', true], ['empresa', 'Empresa', 'text', false], ['documento', 'DNI o RUC', 'text', false], ['telefono', 'Teléfono', 'tel', false], ['email', 'Correo electrónico', 'email', false]] as [$campo, $etiqueta, $tipo, $requerido])
                        <div class="{{ $campo === 'nombre' || $campo === 'empresa' ? 'col-12' : 'col-md-6' }}">
                            <label for="{{ $campo }}" class="form-label">{{ $etiqueta }}@if ($requerido) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
                            <input id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" value="{{ old($campo, $prospecto->$campo) }}" class="form-control @error($campo) is-invalid @enderror" @if ($requerido) required @endif>
                            @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label for="origen" class="form-label">Cómo llegó</label>
                        <select id="origen" name="origen" class="form-select @error('origen') is-invalid @enderror">
                            @foreach (\App\Models\Prospecto::ORIGENES as $origen)
                                <option value="{{ $origen }}" @selected(old('origen', $prospecto->origen) === $origen)>{{ $origen }}</option>
                            @endforeach
                        </select>
                        @error('origen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ $editando ? route('clientes.show', (string) $prospecto->ref()) : route('clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar cambios' : 'Crear prospecto' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
