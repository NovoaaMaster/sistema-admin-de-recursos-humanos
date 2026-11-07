<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion" aria-label="Menú principal">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <x-nav.heading>Inicio</x-nav.heading>

                <x-nav.nav-link content='Panel'
                    icon='fa-solid fa-chart-pie'
                    :href="route('panel')"
                    :active="request()->routeIs('panel')" />

                <x-nav.heading>Relación con clientes</x-nav.heading>

                <x-nav.nav-link content='Clientes'
                    icon='fa-solid fa-address-book'
                    :href="route('clientes.index')"
                    :active="request()->routeIs('clientes.*', 'prospectos.*')" />

                <x-nav.nav-link content='Tareas'
                    icon='fa-solid fa-list-check'
                    :href="route('tareas.index')"
                    :active="request()->routeIs('tareas.*')">
                    @if ($tareasVencidas > 0)
                        <span class="contador-menu" title="Tareas vencidas" data-contador-vencidas>{{ $tareasVencidas }}</span><span class="visually-hidden"> tareas vencidas</span>
                    @endif
                </x-nav.nav-link>

                <x-nav.nav-link content='Embudo'
                    icon='fa-solid fa-filter-circle-dollar'
                    :href="route('embudo.index')"
                    :active="request()->routeIs('embudo.*')" />

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small opacity-75">Sesión iniciada como</div>
            {{ auth()->user()->name }}
        </div>
    </nav>
</div>
