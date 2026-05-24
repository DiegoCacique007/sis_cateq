@extends('layouts.app_parroquia_parroco')

@section('title', 'Dashboard - Párroco')
@section('header_title', 'BIENVENIDO, PÁRROCO')

@section('content')
    <style>
        .role-hero {
            border-radius: 22px; overflow: hidden;
            background: linear-gradient(135deg, var(--blue-main, #4facfe) 0%, var(--blue-dark, #1e3a8a) 100%);
            box-shadow: 0 18px 40px rgba(30, 58, 138, 0.18);
        }
        .stat-card {
            border: 0; border-radius: 20px; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            background: #fff; overflow: hidden; height: 100%; border-left: 4px solid transparent;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 18px 38px rgba(30,58,138,0.13) !important; }
        .stat-card .icon-box {
            width: 58px; height: 58px; border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 14px; color: #fff;
        }
        .stat-number { font-size: 1.9rem; font-weight: 900; color: #111827; line-height: 1; }
        .stat-label { font-size: .84rem; color: #6b7280; font-weight: 600; }
        .stat-description { font-size: .75rem; color: #9ca3af; margin-top: 4px; }
        .section-label { color: var(--blue-dark, #1e3a8a); font-weight: 800; letter-spacing: .3px; }
        .mini-badge {
            font-size: .72rem; border-radius: 999px; padding: .28rem .65rem;
            background: #f8fbff; color: var(--blue-dark, #1e3a8a); border: 1px solid rgba(30,58,138,.10);
        }
        .quick-link { text-decoration: none; color: inherit; }
        .quick-card {
            border: 0; border-radius: 20px; transition: transform .18s ease, box-shadow .18s ease;
            background: #fff; overflow: hidden; height: 100%; border-left: 4px solid transparent;
        }
        .quick-card:hover { transform: translateY(-5px); box-shadow: 0 18px 38px rgba(30,58,138,0.13) !important; }
        .quick-card .icon-box { width: 58px; height: 58px; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; color: #fff; }
        .quick-card h6 { font-weight: 800; margin-bottom: 8px; }
        .quick-card p { font-size: .88rem; line-height: 1.45; }

        .theme-primary { border-left-color: #2563eb; }
        .theme-primary .icon-box { background: linear-gradient(135deg, #60a5fa, #2563eb); }
        .theme-primary h6 { color: #2563eb; }
        .theme-info { border-left-color: #0891b2; }
        .theme-info .icon-box { background: linear-gradient(135deg, #22d3ee, #0891b2); }
        .theme-info h6 { color: #0891b2; }
        .theme-success { border-left-color: #16a34a; }
        .theme-success .icon-box { background: linear-gradient(135deg, #4ade80, #16a34a); }
        .theme-success h6 { color: #16a34a; }
        .theme-warning { border-left-color: #d97706; }
        .theme-warning .icon-box { background: linear-gradient(135deg, #fbbf24, #d97706); color: #111827; }
        .theme-warning h6 { color: #d97706; }
        .theme-secondary { border-left-color: #64748b; }
        .theme-secondary .icon-box { background: linear-gradient(135deg, #94a3b8, #64748b); }
        .theme-secondary h6 { color: #64748b; }
        .theme-purple { border-left-color: #7c3aed; }
        .theme-purple .icon-box { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .theme-purple h6 { color: #7c3aed; }
    </style>

    @php
        $totalAlumnos = $totalAlumnos ?? 0;
        $totalComunidades = $totalComunidades ?? 0;
        $totalCatequistas = $totalCatequistas ?? 0;
        $totalGrupos = $totalGrupos ?? 0;
        $totalEvaluaciones = $totalEvaluaciones ?? 0;
        $totalInscripciones = $totalInscripciones ?? 0;
    @endphp

    <div class="role-hero mb-4">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill mb-3 px-3 py-2">
                        Panel de supervisión
                    </span>
                    <h3 class="fw-bold mb-3 text-white">¡Bienvenido al Panel del Párroco!</h3>
                    <p class="mb-0 fs-6" style="color: rgba(255,255,255,0.88); max-width: 760px;">
                        Desde aquí puede supervisar de manera general el estado de la catequesis parroquial:
                        comunidades, alumnos, catequistas, grupos, evaluaciones y boletas.
                    </p>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-shield-check" style="font-size: 7rem; opacity: .22;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h5 class="section-label mb-1">Indicadores generales</h5>
            <small class="text-muted">Resumen del periodo activo y registros principales.</small>
        </div>
        <span class="mini-badge">
            <i class="bi bi-calendar-event me-1"></i>
            Periodo actual: {{ session('periodo_activo_nombre', 'No seleccionado') }}
        </span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card theme-primary shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box"><i class="bi bi-people fs-3"></i></div>
                    <div class="stat-number">{{ $totalAlumnos }}</div>
                    <div class="stat-label mt-2">Alumnos activos</div>
                    <div class="stat-description">Total de alumnos activos registrados.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card theme-info shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box"><i class="bi bi-geo-alt fs-3"></i></div>
                    <div class="stat-number">{{ $totalComunidades }}</div>
                    <div class="stat-label mt-2">Comunidades</div>
                    <div class="stat-description">Comunidades registradas en el sistema.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card theme-secondary shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box"><i class="bi bi-person-workspace fs-3"></i></div>
                    <div class="stat-number">{{ $totalCatequistas }}</div>
                    <div class="stat-label mt-2">Catequistas aprobados</div>
                    <div class="stat-description">Catequistas con acceso activo al sistema.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card theme-warning shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box"><i class="bi bi-diagram-3 fs-3"></i></div>
                    <div class="stat-number">{{ $totalGrupos }}</div>
                    <div class="stat-label mt-2">Grupos asignados</div>
                    <div class="stat-description">Asignaciones creadas para el periodo.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card theme-success shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box"><i class="bi bi-person-check fs-3"></i></div>
                    <div class="stat-number">{{ $totalInscripciones }}</div>
                    <div class="stat-label mt-2">Inscripciones activas</div>
                    <div class="stat-description">Alumnos inscritos en el periodo actual.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card stat-card theme-purple shadow-sm p-2">
                <div class="card-body">
                    <div class="icon-box"><i class="bi bi-clipboard-check fs-3"></i></div>
                    <div class="stat-number">{{ $totalEvaluaciones }}</div>
                    <div class="stat-label mt-2">Evaluaciones registradas</div>
                    <div class="stat-description">Total de evaluaciones capturadas.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 mt-4">
        <div>
            <h5 class="section-label mb-1">Accesos rápidos</h5>
            <small class="text-muted">Consulta la información de cada módulo.</small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('parroco.comunidades.index') }}" class="quick-link">
                <div class="card quick-card theme-primary shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box"><i class="bi bi-geo-alt fs-3"></i></div>
                        <h6>Comunidades</h6>
                        <p class="text-muted mb-0">Consultar comunidades registradas.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('parroco.alumnos.index') }}" class="quick-link">
                <div class="card quick-card theme-info shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box"><i class="bi bi-people fs-3"></i></div>
                        <h6>Alumnos</h6>
                        <p class="text-muted mb-0">Ver lista de alumnos activos.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('parroco.grupos.index') }}" class="quick-link">
                <div class="card quick-card theme-warning shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box"><i class="bi bi-diagram-3 fs-3"></i></div>
                        <h6>Grupos</h6>
                        <p class="text-muted mb-0">Consultar grupos asignados.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('parroco.evaluaciones.index') }}" class="quick-link">
                <div class="card quick-card theme-purple shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box"><i class="bi bi-clipboard-check fs-3"></i></div>
                        <h6>Evaluaciones</h6>
                        <p class="text-muted mb-0">Supervisar evaluaciones capturadas.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
