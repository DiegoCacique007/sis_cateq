@extends('layouts.app_parroquia_catequista')

@section('title', 'Dashboard - Catequista')
@section('header_title', 'BIENVENIDA, CATEQUISTA')

@section('content')
    <style>
        .catequista-hero {
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--blue-main, #4facfe) 0%, var(--blue-dark, #1e3a8a) 100%);
            box-shadow: 0 18px 40px rgba(30, 58, 138, 0.18);
        }

        .catequista-card,
        .stat-card {
            border: 0;
            border-radius: 20px;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
        }

        .catequista-card:hover,
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 38px rgba(30, 58, 138, 0.13) !important;
        }

        .catequista-card .icon-box,
        .stat-card .icon-box {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            color: #fff;
        }

        .catequista-card h6,
        .stat-card h6 {
            font-weight: 800;
            margin-bottom: 8px;
        }

        .catequista-card p {
            font-size: .88rem;
            line-height: 1.45;
        }

        .section-label {
            color: var(--blue-dark, #1e3a8a);
            font-weight: 800;
            letter-spacing: .3px;
        }

        .mini-badge {
            font-size: .72rem;
            border-radius: 999px;
            padding: .28rem .65rem;
            background: #f8fbff;
            color: var(--blue-dark, #1e3a8a);
            border: 1px solid rgba(30, 58, 138, .10);
        }

        .quick-link {
            text-decoration: none;
            color: inherit;
        }

        .stat-number {
            font-size: 1.9rem;
            font-weight: 900;
            color: #111827;
            line-height: 1;
        }

        .stat-label {
            font-size: .84rem;
            color: #6b7280;
            font-weight: 600;
        }

        .stat-description {
            font-size: .75rem;
            color: #9ca3af;
            margin-top: 4px;
        }

        .styled-module-card,
        .styled-stat-card,
        .styled-workflow-card {
            border-left: 4px solid transparent;
        }

        .theme-primary {
            border-left-color: #2563eb;
        }

        .theme-primary .icon-box {
            background: linear-gradient(135deg, #60a5fa, #2563eb);
        }

        .theme-primary h6 {
            color: #2563eb;
        }

        .theme-info {
            border-left-color: #0891b2;
        }

        .theme-info .icon-box {
            background: linear-gradient(135deg, #22d3ee, #0891b2);
        }

        .theme-info h6 {
            color: #0891b2;
        }

        .theme-success {
            border-left-color: #16a34a;
        }

        .theme-success .icon-box {
            background: linear-gradient(135deg, #4ade80, #16a34a);
        }

        .theme-success h6 {
            color: #16a34a;
        }

        .theme-warning {
            border-left-color: #d97706;
        }

        .theme-warning .icon-box {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #111827;
        }

        .theme-warning h6 {
            color: #d97706;
        }

        .theme-secondary {
            border-left-color: #64748b;
        }

        .theme-secondary .icon-box {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }

        .theme-secondary h6 {
            color: #64748b;
        }

        .small-formal-toast {
            border-radius: 14px !important;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .16) !important;
            border: 1px solid rgba(30, 58, 138, .10) !important;
        }
    </style>

    @php
        $totalGruposAsignados = $totalGruposAsignados ?? 0;
        $totalAlumnosGrupo = $totalAlumnosGrupo ?? 0;
        $totalEvaluacionesRegistradas = $totalEvaluacionesRegistradas ?? 0;
        $totalNivelesAsignados = $totalNivelesAsignados ?? 0;
    @endphp

    <div class="catequista-hero mb-4">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill mb-3 px-3 py-2">
                        Panel principal
                    </span>

                    <h3 class="fw-bold mb-3 text-white">
                        ¡Bienvenida al módulo de Catequista!
                    </h3>

                    <p class="mb-0 fs-6" style="color: rgba(255, 255, 255, 0.88); max-width: 760px;">
                        Desde este panel podrás consultar tu grupo asignado, visualizar alumnos activos,
                        descargar la plantilla formal de asistencia y registrar calificaciones por unidad y rubro
                        dentro del periodo actual.
                    </p>
                </div>

                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-person-workspace" style="font-size: 7rem; opacity: .22;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h5 class="section-label mb-1">Indicadores de mi grupo</h5>
            <small class="text-muted">Resumen del periodo activo y registros relacionados con tus grupos.</small>
        </div>

        <span class="mini-badge">
            <i class="bi bi-calendar-event me-1"></i>
            Periodo actual: {{ session('periodo_activo_nombre', 'No seleccionado') }}
        </span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card styled-stat-card theme-primary shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-people fs-3"></i>
                    </div>

                    <div class="stat-number">{{ $totalAlumnosGrupo }}</div>
                    <div class="stat-label mt-2">Alumnos activos</div>
                    <div class="stat-description">Alumnos inscritos en tus grupos asignados.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card styled-stat-card theme-warning shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-diagram-3 fs-3"></i>
                    </div>

                    <div class="stat-number">{{ $totalGruposAsignados }}</div>
                    <div class="stat-label mt-2">Grupos asignados</div>
                    <div class="stat-description">Grupos vinculados a tu usuario.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card styled-stat-card theme-success shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-layers fs-3"></i>
                    </div>

                    <div class="stat-number">{{ $totalNivelesAsignados }}</div>
                    <div class="stat-label mt-2">Niveles asignados</div>
                    <div class="stat-description">Niveles relacionados con tus grupos.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card styled-stat-card theme-info shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-clipboard-check fs-3"></i>
                    </div>

                    <div class="stat-number">{{ $totalEvaluacionesRegistradas }}</div>
                    <div class="stat-label mt-2">Evaluaciones</div>
                    <div class="stat-description">Calificaciones registradas en el periodo.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h5 class="section-label mb-1">Módulos de trabajo</h5>
            <small class="text-muted">Selecciona el módulo que deseas gestionar.</small>
        </div>

        <span class="mini-badge">
            <i class="bi bi-person-badge me-1"></i>
            Catequista
        </span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <a href="{{ route('catequista.mi_grupo') }}" class="quick-link">
                <div class="card catequista-card styled-module-card theme-primary shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="bi bi-people fs-3"></i>
                        </div>

                        <h6>Lista de Grupo</h6>

                        <p class="text-muted mb-0">
                            Consulta los alumnos activos asignados a tu grupo, revisa comunidad,
                            nivel, periodo y descarga la plantilla formal de asistencia.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="{{ route('catequista.evaluaciones.index') }}" class="quick-link">
                <div class="card catequista-card styled-module-card theme-warning shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="bi bi-clipboard-check fs-3"></i>
                        </div>

                        <h6>Captura de Calificaciones</h6>

                        <p class="text-muted mb-0">
                            Registra calificaciones por unidad y rubro. El sistema calculará automáticamente
                            los aportes, promedios parciales y resultado general.
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 mt-4">
        <div>
            <h5 class="section-label mb-1">Flujo recomendado de trabajo</h5>
            <small class="text-muted">Sigue estos pasos para llevar un control ordenado de tu grupo.</small>
        </div>

        <span class="mini-badge">
            <i class="bi bi-diagram-3 me-1"></i>
            Proceso sugerido
        </span>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card catequista-card styled-workflow-card theme-primary shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-1-circle fs-3"></i>
                    </div>

                    <h6>Consultar grupo</h6>

                    <p class="text-muted mb-0">
                        Revisa primero los alumnos que se encuentran activos dentro de tu grupo asignado.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card catequista-card styled-workflow-card theme-warning shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-2-circle fs-3"></i>
                    </div>

                    <h6>Descargar asistencia</h6>

                    <p class="text-muted mb-0">
                        Genera la plantilla oficial de asistencia para llevar el control manual de cada sesión.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card catequista-card styled-workflow-card theme-success shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box">
                        <i class="bi bi-3-circle fs-3"></i>
                    </div>

                    <h6>Capturar calificaciones</h6>

                    <p class="text-muted mb-0">
                        Selecciona la unidad correspondiente, registra los rubros y verifica el promedio calculado.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const successMessage = @json(session('success'));
            const statusMessage = @json(session('status'));
            const errorMessage = @json(session('error'));

            if (!window.Swal) {
                return;
            }

            const config = {
                toast: true,
                position: 'top-end',
                timer: 2600,
                timerProgressBar: true,
                showConfirmButton: false,
                width: '320px',
                customClass: { popup: 'small-formal-toast' }
            };

            if (successMessage) {
                Swal.fire({
                    ...config,
                    icon: 'success',
                    title: 'Correcto',
                    text: successMessage
                });
            }

            if (statusMessage) {
                Swal.fire({
                    ...config,
                    icon: 'success',
                    title: 'Correcto',
                    text: statusMessage
                });
            }

            if (errorMessage) {
                Swal.fire({
                    ...config,
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    timer: 3400
                });
            }
        });
    </script>
@endsection
