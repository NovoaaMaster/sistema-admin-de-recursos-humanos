<nav class="sb-topnav navbar navbar-expand navbar-light barra-crm">
    <a class="navbar-brand ps-3" href="{{ route('panel') }}"><x-logo /> CRM</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button" aria-label="Mostrar u ocultar el menú">
        <i class="fas fa-bars"></i>
    </button>

    <form class="d-none d-md-flex ms-auto me-3 buscador-global" method="GET" action="{{ route('clientes.index') }}" role="search">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
            <input type="search" name="q" value="{{ request()->routeIs('clientes.index') ? request('q') : '' }}" class="form-control border-start-0" placeholder="Buscar cliente por nombre o documento" aria-label="Buscar cliente">
        </div>
    </form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="menuUsuario" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <x-avatar :nombre="auth()->user()->name" tamano="sm" /><span class="visually-hidden">Menú de usuario</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuUsuario">
                <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">Cerrar sesión</button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>
