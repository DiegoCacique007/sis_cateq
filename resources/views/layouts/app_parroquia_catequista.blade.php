<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIS_CATEQ - Catequista')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    @vite(['resources/js/app.js'])

    <style>
        :root {
            --blue-main: #4facfe;
            --blue-light: #8fd3f4;
            --blue-dark: #1e3a8a;
            --text-main: #2c3e50;
            --muted: rgba(44, 62, 80, 0.65);
            --panel: rgba(255, 255, 255, 0.92);
            --border: rgba(79, 172, 254, 0.22);
            --sidebar: rgba(255, 255, 255, 0.97);
            --bg-soft: #f8fbff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at 35% 25%, #ffffff 0%, #e0f2fe 55%),
                radial-gradient(circle at center, #f0f8ff 0%, #bae6fd 100%);
            background-attachment: fixed;
            color: var(--text-main);
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 300px;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            border-left: 4px solid var(--blue-main);
            padding: 22px 16px;
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 10px 18px 10px;
            border-bottom: 1px solid rgba(30, 58, 138, 0.08);
            margin-bottom: 14px;
        }

        .logo {
            display: inline-grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border: 1px solid rgba(79, 172, 254, 0.45);
            border-radius: 50%;
            color: var(--blue-main);
            box-shadow: 0 0 22px rgba(79, 172, 254, 0.15);
            background: #ffffff;
            flex: 0 0 auto;
        }

        .brand-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            color: var(--blue-dark);
            letter-spacing: 1.6px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 1.02rem;
            line-height: 1.1;
        }

        .brand-sub {
            margin: 0;
            color: var(--muted);
            font-size: .86rem;
        }

        .nav-parroquia .nav-link,
        .menu-toggle {
            color: var(--text-main);
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 6px;
            border: 1px solid transparent;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all .2s ease;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            width: 100%;
            text-align: left;
        }

        .nav-parroquia .nav-link:hover,
        .menu-toggle:hover {
            color: var(--blue-dark);
            background: rgba(79, 172, 254, 0.1);
            border-color: var(--border);
        }

        .nav-parroquia .nav-link.active,
        .menu-toggle.active {
            background: rgba(79, 172, 254, 0.15);
            border-color: rgba(79, 172, 254, 0.35);
            color: var(--blue-dark);
            font-weight: 700;
        }

        .menu-toggle .chevron {
            margin-left: auto;
            transition: transform .2s ease;
            font-size: .8rem;
        }

        .menu-toggle[aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        .submenu {
            padding-left: 10px;
            margin-bottom: 8px;
            border-left: 2px solid rgba(79, 172, 254, 0.22);
            margin-left: 14px;
        }

        .submenu .nav-link {
            padding: 9px 10px;
            font-size: .92rem;
            margin-bottom: 4px;
        }

        .nav-parroquia .nav-link.disabled-link {
            opacity: .65;
            cursor: not-allowed;
        }

        .main {
            flex: 1;
            padding: 22px 22px 28px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .topbar-title {
            font-family: 'Cinzel', serif;
            color: var(--blue-dark);
            letter-spacing: 1px;
            font-weight: 700;
            margin: 0;
            font-size: 1.5rem;
        }

        .topbar-sub {
            color: var(--muted);
            margin: 4px 0 0;
            font-size: .92rem;
        }

        .chip {
            border: 1px solid rgba(79, 172, 254, 0.35);
            background: #ffffff;
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 999px;
            font-size: .84rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(30, 58, 138, 0.04);
        }

        .btn-parroquia {
            background: linear-gradient(180deg, var(--blue-light) 0%, var(--blue-main) 100%);
            color: #ffffff;
            border: 0;
            border-radius: 10px;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.25);
            transition: all .25s ease;
        }

        .btn-parroquia:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .btn-outline-parroquia {
            border: 1px solid rgba(79, 172, 254, 0.6);
            color: var(--blue-dark);
            border-radius: 10px;
            background: transparent;
            transition: all .25s ease;
            font-weight: 600;
        }

        .btn-outline-parroquia:hover {
            border-color: var(--blue-main);
            background: var(--blue-main);
            color: #ffffff;
        }

        .card-parroquia {
            background: var(--panel);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-left: 4px solid var(--blue-main);
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(30, 58, 138, 0.07);
            overflow: hidden;
        }

        .module-card {
            border-radius: 18px;
            overflow: hidden;
        }

        .module-title {
            color: var(--blue-dark, #1e3a8a);
        }

        .table thead th {
            background: #f8fbff !important;
            color: var(--blue-dark, #1e3a8a);
            font-weight: 700;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .table tbody td {
            vertical-align: middle;
        }

        .form-control,
        .form-select {
            min-height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(79, 172, 254, 0.28);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--blue-main, #4facfe);
            box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.14);
        }

        .form-label {
            color: var(--blue-dark, #1e3a8a);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .cell-title {
            display: block;
            font-weight: 700;
            color: var(--blue-dark, #1e3a8a);
            line-height: 1.2;
        }

        .cell-subtitle {
            display: block;
            font-size: .82rem;
            color: rgba(44, 62, 80, .58);
            margin-top: 2px;
        }

        .soft-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: .35rem .7rem;
            border-radius: 999px;
            background: #f8fbff;
            border: 1px solid rgba(79, 172, 254, .22);
            color: var(--blue-dark, #1e3a8a);
            font-size: .8rem;
            font-weight: 600;
        }

        .empty-text {
            color: #94a3b8;
            font-size: .84rem;
            font-style: italic;
        }

        .small-formal-toast {
            border-radius: 14px !important;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .16) !important;
            border: 1px solid rgba(30, 58, 138, .10) !important;
        }

        .small-formal-confirm {
            border-radius: 18px !important;
            box-shadow: 0 18px 46px rgba(15, 23, 42, .20) !important;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 94px;
                padding: 18px 10px;
            }

            .brand-sub,
            .brand-title,
            .nav-parroquia .nav-link span,
            .menu-toggle span,
            .menu-toggle .chevron {
                display: none;
            }

            .submenu {
                padding-left: 0;
                margin-left: 0;
                border-left: 0;
            }

            .main {
                padding: 18px 14px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

<div class="app-shell">

    <aside class="sidebar">
        <div class="brand">
            <div class="logo" aria-hidden="true">
                <i class="bi bi-book-half fs-5"></i>
            </div>

            <div>
                <p class="brand-title mb-0">Parroquia</p>
                <p class="brand-sub mb-0">@yield('subtitle', 'Panel de Catequista')</p>
            </div>
        </div>

        <nav class="nav flex-column nav-parroquia">

            @auth
                @if(auth()->user()->role === 'catequista')

                    @php
                        $menuClaseActivo = request()->routeIs('catequista.mi_grupo');
                        $menuEvaluacionesActivo = request()->routeIs('catequista.evaluaciones.*');
                    @endphp

                    <a class="nav-link {{ request()->routeIs('catequista.dashboard') ? 'active' : '' }}"
                       href="{{ route('catequista.dashboard') }}">
                        <i class="bi bi-house"></i>
                        <span>Inicio</span>
                    </a>

                    <button class="menu-toggle {{ $menuClaseActivo ? 'active' : '' }}"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#menuMiClase"
                            aria-expanded="{{ $menuClaseActivo ? 'true' : 'false' }}"
                            aria-controls="menuMiClase">
                        <i class="bi bi-person-workspace"></i>
                        <span>Mi Clase</span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>

                    <div class="collapse {{ $menuClaseActivo ? 'show' : '' }}" id="menuMiClase">
                        <div class="submenu">
                            <a class="nav-link {{ request()->routeIs('catequista.mi_grupo') ? 'active' : '' }}"
                               href="{{ route('catequista.mi_grupo') }}">
                                <i class="bi bi-people text-primary"></i>
                                <span>Lista de Grupo</span>
                            </a>
                        </div>
                    </div>

                    <button class="menu-toggle {{ $menuEvaluacionesActivo ? 'active' : '' }}"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#menuEvaluaciones"
                            aria-expanded="{{ $menuEvaluacionesActivo ? 'true' : 'false' }}"
                            aria-controls="menuEvaluaciones">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Evaluaciones</span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>

                    <div class="collapse {{ $menuEvaluacionesActivo ? 'show' : '' }}" id="menuEvaluaciones">
                        <div class="submenu">
                            <a class="nav-link {{ request()->routeIs('catequista.evaluaciones.*') ? 'active' : '' }}"
                               href="{{ route('catequista.evaluaciones.index') }}">
                                <i class="bi bi-calculator text-success"></i>
                                <span>Captura de Calificaciones</span>
                            </a>
                        </div>
                    </div>

                @else
                    <a class="nav-link" href="{{ url('/dashboard') }}">
                        <i class="bi bi-house"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
            @else
                <a class="nav-link" href="{{ route('login') }}">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Iniciar sesión</span>
                </a>
            @endauth

        </nav>

        @auth
            <div class="mt-3 pt-3" style="border-top:1px solid rgba(30, 58, 138, 0.08);">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button class="btn btn-outline-parroquia btn-sm w-100" type="submit">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Salir
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1 class="topbar-title fw-bold">
                    @yield('header_title', 'Dashboard')
                </h1>
                <p class="topbar-sub">@yield('header_subtitle', '')</p>
            </div>

            @auth
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if(in_array(auth()->user()->role, ['secretaria', 'catequista']))
                        <button type="button" class="btn btn-sm btn-outline-primary chip" data-bs-toggle="modal" data-bs-target="#modalCambiarPeriodo" style="cursor:pointer; border-color: var(--blue-main); color: var(--blue-dark); text-decoration: none;">
                            <i class="bi bi-calendar-event me-1 text-primary"></i>
                            Periodo actual: {{ session('periodo_activo_nombre', 'Seleccionar') }}
                        </button>
                    @endif

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
    @if(in_array(auth()->user()->role, ['secretaria', 'catequista']))
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
                                            <span class="{{ session('periodo_activo_id') == $pg->id ? 'fw-bold' : '' }}">
                                                {{ $pg->nombre }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">
                                    No hay periodos registrados. Registra un periodo antes de continuar.
                                </div>
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
    @endif
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    window.AppAlert = window.AppAlert || {
        success(message = 'Operación realizada correctamente.') {
            if (window.Swal) {
                return Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Correcto',
                    text: message,
                    timer: 2600,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    width: '320px',
                    customClass: {
                        popup: 'small-formal-toast'
                    }
                });
            }

            alert(message);
            return Promise.resolve();
        },

        error(message = 'Ocurrió un error inesperado.', title = 'Error') {
            if (window.Swal) {
                return Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: title,
                    text: message,
                    timer: 3400,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    width: '330px',
                    customClass: {
                        popup: 'small-formal-toast'
                    }
                });
            }

            alert(title + ': ' + message);
            return Promise.resolve();
        },

        info(message = 'Información del sistema.', title = 'Información') {
            if (window.Swal) {
                return Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: title,
                    text: message,
                    timer: 3200,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    width: '330px',
                    customClass: {
                        popup: 'small-formal-toast'
                    }
                });
            }

            alert(message);
            return Promise.resolve();
        },

        validation(errors) {
            let html = '<div style="text-align:left;font-size:12.5px;line-height:1.35;">';

            Object.values(errors).forEach(function (errorArray) {
                errorArray.forEach(function (error) {
                    html += '<div style="margin-bottom:4px;">• ' + error + '</div>';
                });
            });

            html += '</div>';

            if (window.Swal) {
                return Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Validación requerida',
                    html: html,
                    timer: 4300,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    width: '360px',
                    customClass: {
                        popup: 'small-formal-toast'
                    }
                });
            }

            alert('Verifica la información ingresada.');
            return Promise.resolve();
        },

        confirmDelete(message = 'Esta acción eliminará el registro seleccionado.') {
            if (window.Swal) {
                return Swal.fire({
                    icon: 'warning',
                    title: 'Confirmar eliminación',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    width: '360px',
                    customClass: {
                        popup: 'small-formal-confirm'
                    }
                });
            }

            return Promise.resolve({
                isConfirmed: confirm(message)
            });
        },

        confirmSave(message = 'Se guardará la información capturada.') {
            if (window.Swal) {
                return Swal.fire({
                    icon: 'question',
                    title: 'Confirmar guardado',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    width: '360px',
                    customClass: {
                        popup: 'small-formal-confirm'
                    }
                });
            }

            return Promise.resolve({
                isConfirmed: confirm(message)
            });
        }
    };

    function mostrarModuloEnDesarrollo(mensaje) {
        if (window.AppAlert && typeof window.AppAlert.info === 'function') {
            window.AppAlert.info(mensaje, 'Módulo en desarrollo');
            return;
        }

        alert(mensaje);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const statusMessage = @json(session('status'));
        const successMessage = @json(session('success'));
        const errorMessage = @json(session('error'));
        const validationErrors = @json($errors->messages());

        if (statusMessage) {
            window.AppAlert.success(statusMessage);
        }

        if (successMessage) {
            window.AppAlert.success(successMessage);
        }

        if (errorMessage) {
            window.AppAlert.error(errorMessage);
        }

        if (validationErrors && Object.keys(validationErrors).length > 0) {
            window.AppAlert.validation(validationErrors);
        }

        document.querySelectorAll('.js-delete-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const message = form.dataset.message || 'Esta acción eliminará el registro seleccionado.';
                const result = await window.AppAlert.confirmDelete(message);

                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        document.querySelectorAll('.js-confirm-save-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const message = form.dataset.message || 'Se guardará la información capturada.';
                const result = await window.AppAlert.confirmSave(message);

                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

@stack('scripts')

</body>
</html>
