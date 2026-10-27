@extends('layouts.app_parroquia_admin')

@section('title', 'Dashboard - Secretaría')
@section('header_title', 'Panel de Secretaría')
@section('header_subtitle', 'Gestión y seguimiento de la catequesis')

@section('content')
    @php
        $totalAlumnos = max(0, (int) ($totalAlumnos ?? 0));
        $totalInscripcionesActivas = max(0, (int) ($totalInscripcionesActivas ?? 0));
        $totalGruposAsignados = max(0, (int) ($totalGruposAsignados ?? 0));
        $totalCatequistas = max(0, (int) ($totalCatequistas ?? 0));
        $usuariosPendientes = max(0, (int) ($usuariosPendientes ?? 0));
        $periodoActivo = session('periodo_activo_nombre') ?: 'No seleccionado';
        $nombreUsuario = auth()->user()->name ?? 'Secretaría';
        $primerNombre = explode(' ', trim($nombreUsuario))[0] ?: 'Secretaría';
        $estructura = [
            ['nombre' => 'Grupos asignados', 'valor' => $totalGruposAsignados, 'icono' => 'bi-diagram-3', 'color' => 'blue'],
            ['nombre' => 'Catequistas aprobados', 'valor' => $totalCatequistas, 'icono' => 'bi-person-badge', 'color' => 'teal'],
        ];
        $maxEstructura = max(1, $totalGruposAsignados, $totalCatequistas);
        $comunidadesGrafica = collect($alumnosPorComunidad ?? [])->map(fn ($fila) => [
            'nombre' => (string) data_get($fila, 'comunidad', data_get($fila, 'nombre', 'Comunidad')),
            'total' => max(0, (int) data_get($fila, 'total', 0)),
        ])->filter(fn ($fila) => $fila['total'] > 0)->take(6)->values();
        $maxComunidad = max(1, (int) $comunidadesGrafica->max('total'));
        $modulos = [
            ['nombre' => 'Alumnos', 'detalle' => 'Expedientes y seguimiento', 'icono' => 'bi-people', 'color' => 'blue', 'ruta' => route('secretaria.alumnos.index')],
            ['nombre' => 'Tutores', 'detalle' => 'Responsables de alumnos', 'icono' => 'bi-person-hearts', 'color' => 'purple', 'ruta' => route('secretaria.tutores.index')],
            ['nombre' => 'Inscripciones', 'detalle' => 'Registro del periodo', 'icono' => 'bi-person-check', 'color' => 'teal', 'ruta' => route('secretaria.inscripciones.index')],
            ['nombre' => 'Asignación de grupos', 'detalle' => 'Organización catequética', 'icono' => 'bi-diagram-3', 'color' => 'amber', 'ruta' => route('secretaria.asigna_grupo.index')],
            ['nombre' => 'Boletas', 'detalle' => 'Resultados de evaluación', 'icono' => 'bi-file-earmark-text', 'color' => 'rose', 'ruta' => route('secretaria.boletas.index')],
            ['nombre' => 'Gestión de usuarios', 'detalle' => 'Cuentas, roles y accesos', 'icono' => 'bi-person-gear', 'color' => 'blue', 'ruta' => route('secretaria.usuarios.index', ['seccion' => 'todos'])],
        ];
        $catalogos = [
            ['nombre' => 'Comunidades', 'icono' => 'bi-geo-alt', 'ruta' => route('secretaria.comunidades.index')],
            ['nombre' => 'Niveles', 'icono' => 'bi-layers', 'ruta' => route('secretaria.niveles.index')],
            ['nombre' => 'Grupos', 'icono' => 'bi-collection', 'ruta' => route('secretaria.grupos.index')],
            ['nombre' => 'Periodos', 'icono' => 'bi-calendar-range', 'ruta' => route('secretaria.periodos.index')],
            ['nombre' => 'Unidades', 'icono' => 'bi-book', 'ruta' => route('secretaria.unidades.index')],
            ['nombre' => 'Rubros', 'icono' => 'bi-ui-checks-grid', 'ruta' => route('secretaria.rubros.index')],
        ];
    @endphp

    <style>
        #secretaria-dashboard{--sd-blue:#2563eb;--sd-navy:#1e3a8a;--sd-teal:#0891b2;--sd-text:#26334c;--sd-muted:#718096;--sd-border:#e6ebf3;color:var(--sd-text)}
        #secretaria-dashboard .sd-hero{background:linear-gradient(115deg,#4BA7F9 0%,#378DE0 55%,#2E7BCC 100%);border-radius:20px;padding:25px 28px;color:#fff;box-shadow:0 14px 28px rgba(30,58,138,.13);position:relative;overflow:hidden}
        #secretaria-dashboard .sd-hero:after{content:"";position:absolute;right:-48px;bottom:-120px;width:240px;height:240px;border-radius:50%;border:46px solid rgba(255,255,255,.05);pointer-events:none}
        #secretaria-dashboard .sd-eyebrow{text-transform:uppercase;font-size:.68rem;letter-spacing:1.4px;font-weight:800;opacity:.8}
        #secretaria-dashboard .sd-hero h2{font-size:1.4rem;font-weight:800;margin:7px 0}
        #secretaria-dashboard .sd-hero p{font-size:.84rem;color:#dbeafe;max-width:690px;margin:0}
        #secretaria-dashboard .sd-period{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:10px;color:#fff;font-size:.76rem;font-weight:600;max-width:100%}
        #secretaria-dashboard .sd-help{color:#fff;background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.22);border-radius:10px;padding:8px 12px;font-size:.78rem;font-weight:600}
        #secretaria-dashboard .sd-help:hover{background:rgba(255,255,255,.2);color:#fff}
        #secretaria-dashboard .sd-kpi{display:flex;align-items:center;gap:15px;height:100%;background:#fff;border:1px solid var(--sd-border);border-radius:17px;padding:21px;box-shadow:0 5px 20px rgba(25,55,95,.045);position:relative;overflow:hidden}
        #secretaria-dashboard .sd-kpi:before{content:"";width:4px;background:var(--sd-accent);position:absolute;inset:0 auto 0 0}
        #secretaria-dashboard .sd-kpi-icon{width:52px;height:52px;flex:0 0 52px;display:grid;place-items:center;border-radius:15px;background:var(--sd-soft);color:var(--sd-accent);font-size:1.35rem}
        #secretaria-dashboard .sd-kpi-name{font-size:.78rem;color:var(--sd-muted);font-weight:600}
        #secretaria-dashboard .sd-kpi-value{font-size:1.86rem;font-weight:850;line-height:1.15;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
        #secretaria-dashboard .sd-kpi-desc{font-size:.7rem;color:var(--sd-muted);margin-top:4px}
        #secretaria-dashboard .sd-blue{--sd-accent:#2563eb;--sd-soft:#eaf2ff}
        #secretaria-dashboard .sd-teal{--sd-accent:#0891b2;--sd-soft:#e6f9fc}
        #secretaria-dashboard .sd-amber{--sd-accent:#d97706;--sd-soft:#fff4df}
        #secretaria-dashboard .sd-purple{--sd-accent:#7c3aed;--sd-soft:#f1eafe}
        #secretaria-dashboard .sd-rose{--sd-accent:#e11d48;--sd-soft:#fff0f4}
        #secretaria-dashboard .sd-panel{background:#fff;border:1px solid var(--sd-border);border-radius:17px;padding:23px;height:100%;box-shadow:0 5px 20px rgba(25,55,95,.04)}
        #secretaria-dashboard .sd-panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:18px}
        #secretaria-dashboard .sd-panel-head h3{font-size:.96rem;font-weight:800;margin:0 0 5px}
        #secretaria-dashboard .sd-muted{color:var(--sd-muted);font-size:.77rem;margin:0}
        #secretaria-dashboard .sd-section-title{font-size:.95rem;color:var(--sd-navy);font-weight:800;margin:0}
        #secretaria-dashboard .sd-section-note{font-size:.76rem;color:var(--sd-muted);margin:4px 0 0}
        #secretaria-dashboard .sd-outline-btn{border:1px solid var(--sd-border);background:#f9fbff;color:#3d5d89;border-radius:9px;padding:7px 10px;font-size:.73rem;text-decoration:none;white-space:nowrap}
        #secretaria-dashboard .sd-outline-btn:hover{background:#eef5ff;color:#1d4ed8}
        #secretaria-dashboard .sd-chart-item{margin:23px 0}
        #secretaria-dashboard .sd-chart-info{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:9px}
        #secretaria-dashboard .sd-chart-label{display:flex;align-items:center;gap:8px;font-size:.79rem;font-weight:700}
        #secretaria-dashboard .sd-chart-label i{color:var(--sd-accent)}
        #secretaria-dashboard .sd-chart-count{font-weight:800;font-size:.85rem;font-variant-numeric:tabular-nums}
        #secretaria-dashboard .sd-track{height:12px;overflow:hidden;border-radius:99px;background:#edf2f8}
        #secretaria-dashboard .sd-bar{height:100%;background:var(--sd-accent);border-radius:99px;min-width:0;transition:width .5s}
        #secretaria-dashboard .sd-chart-foot{border-top:1px solid var(--sd-border);padding-top:13px;margin-top:17px;display:flex;gap:8px;align-items:flex-start;font-size:.72rem;color:var(--sd-muted)}
        #secretaria-dashboard .sd-tasks{display:grid;gap:10px}
        #secretaria-dashboard .sd-task{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--sd-border);border-radius:12px;background:#fafcff;text-decoration:none;color:var(--sd-text);transition:.18s}
        #secretaria-dashboard .sd-task:hover{background:#eef5ff;border-color:#cbdcf5;transform:translateY(-2px);color:var(--sd-text)}
        #secretaria-dashboard .sd-task-icon{width:39px;height:39px;display:grid;place-items:center;flex-shrink:0;border-radius:11px;background:var(--sd-soft);color:var(--sd-accent);font-size:1.05rem}
        #secretaria-dashboard .sd-task strong{display:block;font-size:.8rem}
        #secretaria-dashboard .sd-task small{display:block;color:var(--sd-muted);font-size:.7rem;margin-top:2px}
        #secretaria-dashboard .sd-task-arrow{margin-left:auto;font-size:.76rem;color:#9aabc0}
        #secretaria-dashboard .sd-module-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}
        #secretaria-dashboard .sd-module{display:flex;align-items:center;gap:12px;border:1px solid var(--sd-border);border-radius:12px;padding:13px;text-decoration:none;color:var(--sd-text);transition:.18s;min-width:0}
        #secretaria-dashboard .sd-module:hover,#secretaria-dashboard .sd-catalog:hover{background:#f7faff;border-color:#c4d9f4;transform:translateY(-2px);color:var(--sd-text)}
        #secretaria-dashboard .sd-module-icon{display:grid;place-items:center;width:40px;height:40px;flex:0 0 40px;border-radius:11px;background:var(--sd-soft);color:var(--sd-accent);font-size:1.09rem}
        #secretaria-dashboard .sd-module strong{display:block;font-size:.78rem;line-height:1.3}
        #secretaria-dashboard .sd-module small{display:block;color:var(--sd-muted);font-size:.68rem;margin-top:2px}
        #secretaria-dashboard .sd-module-arrow{margin-left:auto;font-size:.7rem;color:#9aabc0}
        #secretaria-dashboard .sd-catalog-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        #secretaria-dashboard .sd-catalog{display:flex;align-items:center;gap:9px;padding:12px;border-radius:11px;border:1px solid var(--sd-border);font-size:.79rem;font-weight:650;text-decoration:none;color:var(--sd-text);transition:.18s}
        #secretaria-dashboard .sd-catalog i{font-size:1.05rem;color:#377bce}
        #secretaria-dashboard .sd-chart-community{margin:13px 0}
        #secretaria-dashboard .sd-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 25px 70px rgba(15,23,42,.19)}
        #secretaria-dashboard .sd-modal .modal-header{border-bottom:1px solid var(--sd-border);padding:19px 22px}
        #secretaria-dashboard .sd-modal .modal-title{font-weight:800;font-size:1rem;color:var(--sd-navy)}
        #secretaria-dashboard .sd-modal .modal-body{font-size:.86rem;color:#485872;padding:21px 22px}
        #secretaria-dashboard .sd-modal .modal-footer{border-top:1px solid var(--sd-border);padding:13px 22px}
        #secretaria-dashboard .sd-note-list{padding-left:20px;margin:0}
        #secretaria-dashboard .sd-note-list li{margin-bottom:10px}
        @media(max-width:767.98px){#secretaria-dashboard .sd-hero{padding:20px}#secretaria-dashboard .sd-panel{padding:18px}#secretaria-dashboard .sd-module-grid{grid-template-columns:1fr}#secretaria-dashboard .sd-hero h2{font-size:1.2rem}}
        @media(max-width:380px){#secretaria-dashboard .sd-catalog-grid{grid-template-columns:1fr}}
        @media(prefers-reduced-motion:reduce){#secretaria-dashboard .sd-task,#secretaria-dashboard .sd-module,#secretaria-dashboard .sd-catalog,#secretaria-dashboard .sd-bar{transition:none}}
    </style>

    <div id="secretaria-dashboard">
        <section class="sd-hero mb-4" aria-label="Bienvenida al panel">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 position-relative" style="z-index:1">
                <div>
                    <h2>¡Qué gusto verte, {{ $primerNombre }}!</h2>
                    <p>Administra la información catequética, revisa las solicitudes de acceso y accede a tus herramientas de trabajo.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="sd-period"><i class="bi bi-calendar-event"></i> Periodo: {{ $periodoActivo }}</span>
                    <button type="button" class="sd-help" data-bs-toggle="modal" data-bs-target="#sdAyuda"><i class="bi bi-question-circle me-1"></i> Ayuda</button>
                </div>
            </div>
        </section>

        <section aria-label="Indicadores principales" class="row g-3 mb-4">
            <div class="col-md-4">
                <article class="sd-kpi sd-blue">
                    <span class="sd-kpi-icon"><i class="bi bi-people-fill"></i></span>
                    <div><div class="sd-kpi-name">Alumnos registrados</div><div class="sd-kpi-value">{{ number_format($totalAlumnos) }}</div><div class="sd-kpi-desc">Registro general del sistema</div></div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="sd-kpi sd-teal">
                    <span class="sd-kpi-icon"><i class="bi bi-person-check-fill"></i></span>
                    <div><div class="sd-kpi-name">Inscripciones activas</div><div class="sd-kpi-value">{{ number_format($totalInscripcionesActivas) }}</div><div class="sd-kpi-desc">Correspondientes al periodo actual</div></div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="sd-kpi sd-amber">
                    <span class="sd-kpi-icon"><i class="bi bi-person-exclamation"></i></span>
                    <div>
                        <div class="sd-kpi-name">Solicitudes por revisar</div>
                        <div class="sd-kpi-value">{{ number_format($usuariosPendientes) }}</div>
                        <a href="{{ route('secretaria.usuarios.index', ['seccion' => 'pendientes']) }}" class="text-decoration-none fw-semibold" style="color:#a65c00;font-size:.72rem">Ir a solicitudes <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <section class="sd-panel" aria-labelledby="sdGraficaTitulo">
                    <div class="sd-panel-head">
                        <div><h3 id="sdGraficaTitulo">Estructura catequética</h3><p class="sd-muted">Grupos del periodo y catequistas con acceso aprobado.</p></div>
                        <button class="sd-outline-btn" type="button" data-bs-toggle="modal" data-bs-target="#sdSobreIndicadores"><i class="bi bi-info-circle me-1"></i> Interpretar</button>
                    </div>
                    <div role="img" aria-label="{{ $totalGruposAsignados }} grupos asignados y {{ $totalCatequistas }} catequistas aprobados">
                        @foreach($estructura as $dato)
                            <div class="sd-chart-item sd-{{ $dato['color'] }}">
                                <div class="sd-chart-info"><div class="sd-chart-label"><i class="bi {{ $dato['icono'] }}"></i>{{ $dato['nombre'] }}</div><span class="sd-chart-count">{{ number_format($dato['valor']) }}</span></div>
                                <div class="sd-track"><div class="sd-bar" style="width:{{ round($dato['valor'] * 100 / $maxEstructura, 1) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="sd-chart-foot"><i class="bi bi-info-circle"></i><span>Comparación de cantidades distintas; no representa asignaciones individuales ni evolución histórica.</span></div>
                </section>
            </div>
            <div class="col-xl-5">
                <section class="sd-panel" aria-labelledby="sdComplementoTitulo">
                    @if($comunidadesGrafica->isNotEmpty())
                        <div class="sd-panel-head"><div><h3 id="sdComplementoTitulo">Alumnos por comunidad</h3><p class="sd-muted">Desglose disponible de registros por comunidad.</p></div><span class="sd-outline-btn">Top 6</span></div>
                        @foreach($comunidadesGrafica as $fila)
                            <div class="sd-chart-community sd-purple">
                                <div class="sd-chart-info"><span class="sd-chart-label text-truncate" title="{{ $fila['nombre'] }}">{{ $fila['nombre'] }}</span><strong class="sd-chart-count">{{ number_format($fila['total']) }}</strong></div>
                                <div class="sd-track"><div class="sd-bar" style="width:{{ round($fila['total'] * 100 / $maxComunidad, 1) }}%"></div></div>
                            </div>
                        @endforeach
                        <div class="sd-chart-foot"><i class="bi bi-info-circle"></i><span>Este desglose utiliza datos proporcionados por el controlador.</span></div>
                    @else
                        <div class="sd-panel-head"><div><h3 id="sdComplementoTitulo">Organización del trabajo</h3><p class="sd-muted">Acciones útiles para mantener los registros al día.</p></div><span class="sd-outline-btn"><i class="bi bi-list-check"></i></span></div>
                        <div class="sd-tasks">
                            <div class="sd-task sd-teal"><span class="sd-task-icon"><i class="bi bi-1-circle"></i></span><span><strong>Organiza los registros</strong><small>Confirma que los alumnos pertenezcan al periodo correspondiente.</small></span></div>
                            <div class="sd-task sd-blue"><span class="sd-task-icon"><i class="bi bi-2-circle"></i></span><span><strong>Verifica la distribución</strong><small>Revisa las asignaciones antes de elaborar evaluaciones.</small></span></div>
                            <div class="sd-task sd-purple"><span class="sd-task-icon"><i class="bi bi-3-circle"></i></span><span><strong>Da seguimiento</strong><small>Mantén actualizada la información académica del alumnado.</small></span></div>
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-end gap-2 mb-3">
            <div><h3 class="sd-section-title">Herramientas administrativas</h3><p class="sd-section-note">Accesos principales para la operación diaria.</p></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <section class="sd-panel" aria-labelledby="sdModulosTitulo">
                    <div class="sd-panel-head"><div><h3 id="sdModulosTitulo">Módulos de gestión</h3><p class="sd-muted">Trabaja con los registros catequéticos.</p></div></div>
                    <div class="sd-module-grid">
                        @foreach($modulos as $modulo)
                            <a href="{{ $modulo['ruta'] }}" class="sd-module sd-{{ $modulo['color'] }}">
                                <span class="sd-module-icon"><i class="bi {{ $modulo['icono'] }}"></i></span>
                                <span class="min-w-0"><strong>{{ $modulo['nombre'] }}</strong><small>{{ $modulo['detalle'] }}</small></span>
                                <i class="bi bi-chevron-right sd-module-arrow"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
            <div class="col-xl-5">
                <section class="sd-panel" aria-labelledby="sdCatalogosTitulo">
                    <div class="sd-panel-head"><div><h3 id="sdCatalogosTitulo">Catálogos</h3><p class="sd-muted">Configuración de la estructura académica.</p></div></div>
                    <div class="sd-catalog-grid">
                        @foreach($catalogos as $catalogo)
                            <a href="{{ $catalogo['ruta'] }}" class="sd-catalog"><i class="bi {{ $catalogo['icono'] }}"></i><span>{{ $catalogo['nombre'] }}</span></a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        <div class="modal fade sd-modal" id="sdAyuda" tabindex="-1" aria-labelledby="sdAyudaTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md"><div class="modal-content">
                    <div class="modal-header"><h3 class="modal-title" id="sdAyudaTitulo"><i class="bi bi-question-circle me-2"></i>Guía de Secretaría</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                    <div class="modal-body"><ul class="sd-note-list"><li>Utiliza <strong>Alumnos y Tutores</strong> para consultar la información personal de los inscritos y sus responsables.</li><li>Registra las inscripciones en el periodo correspondiente antes de revisar las asignaciones de grupos.</li><li>Revisa las solicitudes de acceso antes de autorizar usuarios nuevos.</li><li>Los catálogos definen comunidades, niveles, grupos, periodos y criterios académicos.</li></ul></div>
                    <div class="modal-footer"><button type="button" class="btn btn-primary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Entendido</button></div>
                </div></div>
        </div>
        <div class="modal fade sd-modal" id="sdSobreIndicadores" tabindex="-1" aria-labelledby="sdIndicadoresTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md"><div class="modal-content">
                    <div class="modal-header"><h3 class="modal-title" id="sdIndicadoresTitulo"><i class="bi bi-bar-chart me-2"></i>Acerca de los indicadores</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                    <div class="modal-body"><ul class="sd-note-list"><li><strong>Alumnos registrados:</strong> total general de alumnos que informa el controlador.</li><li><strong>Inscripciones activas:</strong> registros activos correspondientes al periodo seleccionado.</li><li><strong>Grupos asignados:</strong> asignaciones vigentes contabilizadas por el sistema.</li><li><strong>Catequistas aprobados:</strong> cuentas autorizadas, no necesariamente vinculadas a todos los grupos de la gráfica.</li><li><strong>Solicitudes:</strong> cuentas que aún necesitan una decisión de acceso.</li></ul><p class="mb-0 small text-muted">No calculamos porcentajes de cobertura ni tendencias temporales porque estos datos no representan la misma población ni incluyen un historial comparable.</p></div>
                    <div class="modal-footer"><button type="button" class="btn btn-primary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button></div>
                </div></div>
        </div>
    </div>
@endsection
