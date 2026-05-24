<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIS_CATEQ - Coordinador General')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --blue-light: #4facfe;
            --blue-main: #2f7fd3;
            --blue-dark: #1e3a8a;
            --blue-deep: #163172;

            --sidebar-gradient: linear-gradient(180deg, #4facfe 0%, #2f7fd3 42%, #1e3a8a 100%);
            --button-gradient: linear-gradient(90deg, #4facfe 0%, #2f7fd3 55%, #1e3a8a 100%);

            --text-main: #2c3e50;
            --muted: rgba(44, 62, 80, 0.65);
            --panel: rgba(255, 255, 255, 0.94);
            --border: rgba(79, 172, 254, 0.22);
            --bg-soft: #f8fbff;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh; margin: 0; font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at 35% 25%, #ffffff 0%, #e0f2fe 55%),
                radial-gradient(circle at center, #f0f8ff 0%, #bae6fd 100%);
            background-attachment: fixed; color: var(--text-main);
        }

        .app-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: 300px; background: var(--sidebar-gradient); padding: 22px 16px;
            position: sticky; top: 0; height: 100vh; overflow-y: auto;
            box-shadow: 10px 0 30px rgba(30,58,138,0.22);
        }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.35); border-radius: 999px; }

        .brand {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 10px 18px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.25); margin-bottom: 16px;
        }
        .logo {
            display: inline-grid; place-items: center; width: 46px; height: 46px;
            border: 1px solid rgba(255,255,255,0.70); border-radius: 50%;
            color: var(--blue-dark); box-shadow: 0 10px 24px rgba(15,23,42,0.18);
            background: #ffffff; flex: 0 0 auto;
        }
        .brand-title {
            margin: 0; font-family: 'Cinzel', serif; color: #ffffff;
            letter-spacing: 1.6px; font-weight: 700; text-transform: uppercase;
            font-size: 1.02rem; line-height: 1.1; text-shadow: 0 1px 3px rgba(15,23,42,0.22);
        }
        .brand-sub { margin: 0; color: rgba(255,255,255,0.82); font-size: .86rem; }

        .nav-parroquia .nav-link, .menu-toggle {
            color: rgba(255,255,255,0.92); border-radius: 14px; padding: 10px 12px;
            margin-bottom: 7px; border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.08); display: flex; align-items: center;
            gap: 10px; transition: all .2s ease; cursor: pointer; text-decoration: none;
            font-weight: 600; width: 100%; text-align: left; backdrop-filter: blur(6px);
        }
        .nav-parroquia .nav-link i, .menu-toggle i { color: rgba(255,255,255,0.95); }

        .nav-parroquia .nav-link:hover, .menu-toggle:hover {
            color: #ffffff; background: rgba(255,255,255,0.20);
            border-color: rgba(255,255,255,0.38); transform: translateX(3px);
            box-shadow: 0 8px 18px rgba(15,23,42,0.14);
        }

        .nav-parroquia .nav-link.active, .menu-toggle.active {
            background: #ffffff; border-color: #ffffff; color: var(--blue-dark);
            font-weight: 800; box-shadow: 0 10px 24px rgba(15,23,42,0.20);
        }
        .nav-parroquia .nav-link.active i, .menu-toggle.active i { color: var(--blue-main) !important; }

        .menu-toggle .chevron { margin-left: auto; transition: transform .2s ease; font-size: .8rem; }
        .menu-toggle[aria-expanded="true"] .chevron { transform: rotate(180deg); }

        .submenu {
            padding: 8px 0 4px 10px; margin-bottom: 10px; margin-left: 14px;
            border-left: 2px solid rgba(255,255,255,0.36);
        }
        .submenu .nav-link {
            padding: 9px 10px; font-size: .92rem; margin-bottom: 5px;
            background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.18);
        }
        .submenu .nav-link:hover { background: rgba(255,255,255,0.24); }
        .submenu .nav-link.active { background: #ffffff; color: var(--blue-dark); border-color: #ffffff; }

        .sidebar form .btn-outline-parroquia {
            color: #ffffff; border-color: rgba(255,255,255,0.65); background: rgba(255,255,255,0.10);
        }
        .sidebar form .btn-outline-parroquia:hover {
            color: var(--blue-dark); background: #ffffff; border-color: #ffffff;
        }

        .main { flex: 1; padding: 22px 22px 28px; max-width: 100%; overflow-x: hidden; }

        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .topbar-title { font-family: 'Cinzel', serif; color: var(--blue-dark); letter-spacing: 1px; font-weight: 700; margin: 0; font-size: 1.5rem; }
        .topbar-sub { color: var(--muted); margin: 4px 0 0; font-size: .92rem; }

        .chip {
            border: 1px solid rgba(79,172,254,0.35); background: #ffffff; color: var(--text-main);
            padding: 8px 12px; border-radius: 999px; font-size: .84rem; font-weight: 600;
            box-shadow: 0 2px 8px rgba(30,58,138,0.04);
        }

        .btn-parroquia {
            background: var(--button-gradient); color: #ffffff; border: 0; border-radius: 10px;
            font-weight: 800; box-shadow: 0 8px 20px rgba(79,172,254,0.25); transition: all .25s ease;
        }
        .btn-parroquia:hover { filter: brightness(1.05); transform: translateY(-1px); color: #ffffff; }

        .btn-outline-parroquia {
            border: 1px solid rgba(79,172,254,0.6); color: var(--blue-dark); border-radius: 10px;
            background: transparent; transition: all .25s ease; font-weight: 600;
        }
        .btn-outline-parroquia:hover { border-color: var(--blue-main); background: var(--blue-main); color: #ffffff; }

        .card-parroquia {
            background: var(--panel); backdrop-filter: blur(12px); border: 1px solid var(--border);
            border-left: 4px solid var(--blue-main); border-radius: 16px;
            box-shadow: 0 15px 35px rgba(30,58,138,0.07); overflow: hidden;
        }
        .module-card { border-radius: 18px; overflow: hidden; }
        .module-title { color: var(--blue-dark, #1e3a8a); }

        .table thead th { background: #f8fbff !important; color: var(--blue-dark, #1e3a8a); font-weight: 700; border-bottom: 1px solid rgba(0,0,0,.06); }
        .table tbody td { vertical-align: middle; }

        .form-control, .form-select { min-height: 46px; border-radius: 12px; border: 1px solid rgba(79,172,254,0.28); }
        .form-control:focus, .form-select:focus { border-color: var(--blue-main, #4facfe); box-shadow: 0 0 0 4px rgba(79,172,254,0.14); }
        .form-label { color: var(--blue-dark, #1e3a8a); font-weight: 700; margin-bottom: 8px; }

        .cell-title { display: block; font-weight: 700; color: var(--blue-dark, #1e3a8a); line-height: 1.2; }
        .cell-subtitle { display: block; font-size: .82rem; color: rgba(44,62,80,.58); margin-top: 2px; }

        .soft-badge {
            display: inline-flex; align-items: center; gap: 5px; padding: .35rem .7rem;
            border-radius: 999px; background: #f8fbff; border: 1px solid rgba(79,172,254,.22);
            color: var(--blue-dark, #1e3a8a); font-size: .8rem; font-weight: 600;
        }

        .small-formal-toast { border-radius: 14px !important; box-shadow: 0 12px 30px rgba(15,23,42,.16) !important; border: 1px solid rgba(30,58,138,.10) !important; }
        .small-formal-confirm { border-radius: 18px !important; box-shadow: 0 18px 46px rgba(15,23,42,.20) !important; }

        @media (max-width: 992px) {
            .sidebar { width: 94px; padding: 18px 10px; }
            .brand-sub, .brand-title, .nav-parroquia .nav-link span, .menu-toggle span, .menu-toggle .chevron { display: none; }
            .submenu { padding-left: 0; margin-left: 0; border-left: 0; }
            .main { padding: 18px 14px; }
            .topbar { align-items: flex-start; flex-direction: column; }
        }
    </style>

    @stack('styles')
</head>

<body>

<div class="app-shell">

    <aside class="sidebar">
        <div class="brand">
            <div class="logo" aria-hidden="true">
                <i class="bi bi-mortarboard fs-5"></i>
            </div>
            <div>
                <p class="brand-title mb-0">Parroquia</p>
                <p class="brand-sub mb-0">@yield('subtitle', 'Coordinador General')</p>
            </div>
        </div>

        <nav class="nav flex-column nav-parroquia">
            @auth
                @if(auth()->user()->role === 'coordinador_general')

                    @php
                        $gestionActiva =
                            request()->routeIs('coordinador_general.comunidades.*') ||
                            request()->routeIs('coordinador_general.alumnos.*') ||
                            request()->routeIs('coordinador_general.tutores.*') ||
                            request()->routeIs('coordinador_general.inscripciones.*') ||
                            request()->routeIs('coordinador_general.grupos.*') ||
                            request()->routeIs('coordinador_general.niveles.*');

                        $seguimientoActivo =
                            request()->routeIs('coordinador_general.evaluaciones.*') ||
                            request()->routeIs('coordinador_general.boletas.*');
                    @endphp

                    <a class="nav-link {{ request()->routeIs('coordinador_general.dashboard') ? 'active' : '' }}"
                       href="{{ route('coordinador_general.dashboard') }}">
                        <i class="bi bi-house"></i><span>Inicio</span>
                    </a>

                    {{-- Submenú Gestión Catequética --}}
                    <button class="menu-toggle {{ $gestionActiva ? 'active' : '' }}"
                            type="button" data-bs-toggle="collapse" data-bs-target="#menuGestion"
                            aria-expanded="{{ $gestionActiva ? 'true' : 'false' }}" aria-controls="menuGestion">
                        <i class="bi bi-mortarboard"></i>
                        <span>Gestión Catequética</span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>

                    <div class="collapse {{ $gestionActiva ? 'show' : '' }}" id="menuGestion">
                        <div class="submenu">
                            <a class="nav-link {{ request()->routeIs('coordinador_general.comunidades.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.comunidades.index') }}">
                                <i class="bi bi-geo-alt"></i><span>Comunidades</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('coordinador_general.alumnos.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.alumnos.index') }}">
                                <i class="bi bi-people"></i><span>Alumnos</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('coordinador_general.tutores.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.tutores.index') }}">
                                <i class="bi bi-person-hearts"></i><span>Tutores</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('coordinador_general.inscripciones.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.inscripciones.index') }}">
                                <i class="bi bi-card-checklist"></i><span>Inscripciones</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('coordinador_general.grupos.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.grupos.index') }}">
                                <i class="bi bi-diagram-3"></i><span>Grupos</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('coordinador_general.niveles.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.niveles.index') }}">
                                <i class="bi bi-layers"></i><span>Niveles</span>
                            </a>
                        </div>
                    </div>

                    {{-- Submenú Seguimiento --}}
                    <button class="menu-toggle {{ $seguimientoActivo ? 'active' : '' }}"
                            type="button" data-bs-toggle="collapse" data-bs-target="#menuSeguimiento"
                            aria-expanded="{{ $seguimientoActivo ? 'true' : 'false' }}" aria-controls="menuSeguimiento">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Seguimiento</span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>

                    <div class="collapse {{ $seguimientoActivo ? 'show' : '' }}" id="menuSeguimiento">
                        <div class="submenu">
                            <a class="nav-link {{ request()->routeIs('coordinador_general.evaluaciones.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.evaluaciones.index') }}">
                                <i class="bi bi-clipboard-check"></i><span>Evaluaciones</span>
                            </a>
                            <a class="nav-link {{ request()->routeIs('coordinador_general.boletas.*') ? 'active' : '' }}"
                               href="{{ route('coordinador_general.boletas.index') }}">
                                <i class="bi bi-printer"></i><span>Boletas</span>
                            </a>
                        </div>
                    </div>

                @else
                    <a class="nav-link" href="{{ url('/dashboard') }}">
                        <i class="bi bi-house"></i><span>Dashboard</span>
                    </a>
                @endif
            @else
                <a class="nav-link" href="{{ route('login') }}">
                    <i class="bi bi-box-arrow-in-right"></i><span>Iniciar sesión</span>
                </a>
            @endauth
        </nav>

        @auth
            <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.25);">
                <form id="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-parroquia btn-sm w-100" type="button" onclick="window.AppAlert.confirmLogout().then(r => { if(r.isConfirmed) document.getElementById('logout-form').submit(); })">
                        <i class="bi bi-box-arrow-right me-1"></i> Salir
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1 class="topbar-title fw-bold">@yield('header_title', 'Dashboard')</h1>
                <p class="topbar-sub">@yield('header_subtitle', '')</p>
            </div>

            @auth
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary chip" data-bs-toggle="modal" data-bs-target="#modalCambiarPeriodo" style="cursor:pointer; border-color: var(--blue-main); color: var(--blue-dark); text-decoration: none;">
                        <i class="bi bi-calendar-event me-1 text-primary"></i>
                        Periodo actual: {{ session('periodo_activo_nombre', 'Seleccionar') }}
                    </button>

                    <span class="chip">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ auth()->user()->name }} ({{ auth()->user()->role }})
                    </span>

                    @yield('header_actions')
                </div>
            @endauth
        </div>

        @yield('content')
    </main>
</div>

@auth
    <div class="modal fade" id="modalCambiarPeriodo" tabindex="-1" aria-labelledby="modalCambiarPeriodoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('periodo-activo.cambiar') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCambiarPeriodoLabel">Selecciona Periodo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @if(isset($periodos_globales) && $periodos_globales->count() > 0)
                            <div class="list-group">
                                @foreach($periodos_globales as $pg)
                                    <label class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ session('periodo_activo_id') == $pg->id ? 'active bg-primary bg-opacity-10 text-primary border-primary border-opacity-50' : '' }}" style="cursor: pointer;">
                                        <input class="form-check-input flex-shrink-0" type="radio" name="periodo_id" value="{{ $pg->id }}" {{ session('periodo_activo_id') == $pg->id ? 'checked' : '' }}>
                                        <span class="{{ session('periodo_activo_id') == $pg->id ? 'fw-bold' : '' }}">{{ $pg->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">No hay periodos registrados.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        @if(isset($periodos_globales) && $periodos_globales->count() > 0)
                            <button type="submit" class="btn btn-primary">Cambiar</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    window.AppAlert = window.AppAlert || {
        success(message = 'Operación realizada correctamente.') {
            if (window.Swal) { return Swal.fire({ position: 'center', icon: 'success', title: 'Correcto', text: message, timer: 2600, timerProgressBar: true, showConfirmButton: false, width: '360px', customClass: { popup: 'small-formal-confirm' } }); }
            alert(message); return Promise.resolve();
        },
        error(message = 'Ocurrió un error inesperado.', title = 'Error') {
            if (window.Swal) { return Swal.fire({ position: 'center', icon: 'error', title: title, text: message, timer: 3400, timerProgressBar: true, showConfirmButton: false, width: '360px', customClass: { popup: 'small-formal-confirm' } }); }
            alert(title + ': ' + message); return Promise.resolve();
        },
        validation(errors) {
            let html = '<div style="text-align:left;font-size:12.5px;line-height:1.35;">';
            Object.values(errors).forEach(function(items) { items.forEach(function(e) { html += '<div style="margin-bottom:4px;">• ' + e + '</div>'; }); });
            html += '</div>';
            if (window.Swal) { return Swal.fire({ position: 'center', icon: 'warning', title: 'Validación requerida', html: html, timer: 4300, timerProgressBar: true, showConfirmButton: false, width: '360px', customClass: { popup: 'small-formal-confirm' } }); }
            alert('Verifica la información ingresada.'); return Promise.resolve();
        },
        confirmLogout() {
            if (window.Swal) {
                return Swal.fire({
                    icon: 'warning',
                    title: '¿Cerrar sesión?',
                    text: '¿Está seguro que desea salir del sistema?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, salir',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    width: '360px',
                    customClass: { popup: 'small-formal-confirm' }
                });
            }
            return Promise.resolve({ isConfirmed: confirm('¿Está seguro que desea salir del sistema?') });
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = @json(session('success'));
        const statusMessage = @json(session('status'));
        const errorMessage = @json(session('error'));
        const validationErrors = @json($errors->messages());
        if (successMessage) window.AppAlert.success(successMessage);
        if (statusMessage) window.AppAlert.success(statusMessage);
        if (errorMessage) window.AppAlert.error(errorMessage);
        if (validationErrors && Object.keys(validationErrors).length > 0) window.AppAlert.validation(validationErrors);
    });
</script>

@stack('scripts')

</body>
</html>
