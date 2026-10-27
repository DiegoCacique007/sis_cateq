@extends($layout_role ?? 'layouts.app_parroquia_admin')

@php
    $route_name = ($route_prefix ?? 'secretaria.') . 'boletas.index';
    $route_generar = ($route_prefix ?? 'secretaria.') . 'boletas.generar';
    $filtros = $filtros ?? [];
    $catequistaFiltro = $filtros['catequista_id'] ?? null;
    $nivelFiltro = $filtros['nivel_id'] ?? null;
    $grupoFiltro = $filtros['grupo_id'] ?? null;
    $hayFiltros = filled($catequistaFiltro) || filled($nivelFiltro) || filled($grupoFiltro);
    $totalEncontrados = $hayFiltros ? (method_exists($alumnos, 'total') ? $alumnos->total() : $alumnos->count()) : 0;
@endphp

@section('title', 'Boletas de evaluación')
@section('header_title', 'Boletas de Evaluación')
@section('header_subtitle', 'Consulta de alumnos y emisión de documentos académicos')

@section('content')
    <style>
        #boletas-evaluacion { --b-azul:#1e3a8a; --b-acento:#3b82f6; --b-borde:#e4eaf3; --b-texto:#243247; --b-suave:#64748b; color:var(--b-texto); }
        #boletas-evaluacion .be-intro { border-radius:17px; background:linear-gradient(115deg,#4BA7F9 0%,#378DE0 55%,#2E7BCC 100%); padding:23px 27px; color:#fff; box-shadow:0 12px 28px #1e3a8a20; }
        #boletas-evaluacion .be-eyebrow { font-size:.7rem; letter-spacing:.75px; text-transform:uppercase; font-weight:700; color:#bfdbfe; }
        #boletas-evaluacion .be-intro h2 { color:#fff; font-size:1.3rem; font-weight:800; margin:6px 0; }
        #boletas-evaluacion .be-intro p { color:#e2eefd; font-size:.85rem; max-width:650px; margin:0; line-height:1.6; }
        #boletas-evaluacion .be-periodo { display:inline-flex; gap:7px; align-items:center; max-width:100%; border:1px solid #ffffff47; background:#ffffff1a; border-radius:999px; padding:8px 12px; font-size:.78rem; font-weight:700; overflow-wrap:anywhere; }
        #boletas-evaluacion .be-help { border:1px solid #ffffff80; color:#fff; background:#ffffff15; font-size:.78rem; border-radius:999px; padding:8px 13px; }
        #boletas-evaluacion .be-help:hover { background:#ffffff30; color:#fff; }
        #boletas-evaluacion .be-panel { background:#fff; border:1px solid var(--b-borde); border-radius:17px; box-shadow:0 5px 22px #1e3a8a09; overflow:hidden; }
        #boletas-evaluacion .be-panel-head { padding:19px 22px 15px; border-bottom:1px solid #edf1f7; display:flex; justify-content:space-between; align-items:center; gap:12px; }
        #boletas-evaluacion .be-panel-head h3 { font-size:.98rem; font-weight:800; color:var(--b-azul); margin:0 0 3px; }
        #boletas-evaluacion .be-panel-head p { color:var(--b-suave); font-size:.79rem; margin:0; }
        #boletas-evaluacion .be-panel-body { padding:20px 22px; }
        #boletas-evaluacion .form-label { color:#334155; font-weight:700; font-size:.78rem; margin-bottom:7px; }
        #boletas-evaluacion .form-select { font-size:.84rem; border:1px solid #dce5f0; border-radius:10px; min-height:43px; }
        #boletas-evaluacion .form-select:focus { box-shadow:0 0 0 .2rem #3b82f61c; border-color:#93c5fd; }
        #boletas-evaluacion .be-btn { min-height:42px; border-radius:10px; font-size:.82rem; font-weight:700; padding:9px 14px; }
        #boletas-evaluacion .be-btn-primary { background:#2563eb; border:1px solid #2563eb; color:#fff; }
        #boletas-evaluacion .be-btn-primary:hover { background:#1d4ed8; color:#fff; }
        #boletas-evaluacion .be-count { display:inline-flex; gap:8px; align-items:center; background:#eff6ff; color:#1d4ed8; font-size:.78rem; border-radius:999px; padding:7px 12px; font-weight:750; }
        #boletas-evaluacion .be-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; padding:16px; }
        #boletas-evaluacion .be-card { position:relative; display:flex; flex-direction:column; min-width:0; min-height:172px; padding:16px; background:#fff; border:1px solid #dde6f2; border-radius:15px; box-shadow:0 1px 3px #0000000d; transition:transform .2s,box-shadow .2s,border-color .2s; }
        #boletas-evaluacion .be-card:hover { transform:translateY(-3px); box-shadow:0 10px 20px #1e3a8a16; border-color:#c9dbf2; }
        #boletas-evaluacion .be-person { display:flex; align-items:flex-start; gap:10px; padding-right:32px; margin-bottom:12px; min-width:0; }
        #boletas-evaluacion .be-avatar { flex:0 0 38px; width:38px; height:38px; display:grid; place-items:center; border-radius:11px; background:#eef5ff; color:#378DE0; font-size:1.05rem; }
        #boletas-evaluacion .be-person-main { min-width:0; }
        #boletas-evaluacion .be-student { color:#1e3a8a; font-size:.97rem; font-weight:800; line-height:1.3; overflow-wrap:anywhere; margin:0 0 2px; }
        #boletas-evaluacion .be-subtitle { color:#64748b; font-size:.74rem; }
        #boletas-evaluacion .be-tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
        #boletas-evaluacion .be-tag { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; border-radius:30px; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; font-size:.71rem; font-weight:700; max-width:100%; overflow-wrap:anywhere; }
        #boletas-evaluacion .be-tag-level { background:#eafaf1; border-color:#86efac; color:#047857; }
        #boletas-evaluacion .be-info { display:grid; gap:7px; font-size:.76rem; margin-top:auto; }
        #boletas-evaluacion .be-info-line { display:flex; align-items:flex-start; gap:7px; color:#64748b; min-width:0; overflow-wrap:anywhere; }
        #boletas-evaluacion .be-info-line > i { flex:0 0 17px; width:17px; color:#4b99e7; }
        #boletas-evaluacion .be-info-line strong { color:#334155; font-weight:650; }
        #boletas-evaluacion .be-actions { margin-top:auto; }
        #boletas-evaluacion .be-actions[open] { border-top:1px solid #eaf0f7; margin-top:12px; padding-top:10px; }
        #boletas-evaluacion .be-actions > summary { position:absolute; top:15px; right:15px; display:grid; place-items:center; width:32px; height:32px; border:1px solid #3b82f6; border-radius:50%; color:#2563eb; background:#fff; cursor:pointer; list-style:none; font-size:.88rem; transition:background .2s,color .2s; }
        #boletas-evaluacion .be-actions > summary::-webkit-details-marker { display:none; }
        #boletas-evaluacion .be-actions > summary:hover, #boletas-evaluacion .be-actions[open] > summary { background:#378DE0; color:#fff; }
        #boletas-evaluacion .be-actions > summary:focus-visible { outline:3px solid #93c5fd; outline-offset:3px; }
        #boletas-evaluacion .be-actions-content { display:grid; gap:9px; padding-top:6px; }
        #boletas-evaluacion .be-action-title { font-size:.8rem; font-weight:750; color:#1e3a8a; }
        #boletas-evaluacion .be-action-description { font-size:.74rem; color:#64748b; line-height:1.45; }
        #boletas-evaluacion .be-download { display:inline-flex; align-items:center; justify-content:center; gap:8px; width:100%; text-decoration:none; border:1px solid #bfdbfe; border-radius:9px; padding:10px 13px; background:#eff6ff; color:#1d4ed8; font-size:.81rem; font-weight:750; transition:background .2s,border-color .2s; }
        #boletas-evaluacion .be-download:hover { background:#dbeafe; border-color:#93c5fd; color:#1e40af; }
        #boletas-evaluacion .be-empty { text-align:center; padding:44px 18px; color:#64748b; }
        #boletas-evaluacion .be-empty-icon { width:64px; height:64px; display:grid; place-items:center; background:#eff6ff; color:#3b82f6; border-radius:19px; margin:0 auto 15px; font-size:1.75rem; }
        #boletas-evaluacion .be-empty h3 { font-size:.99rem; color:#334155; font-weight:800; margin-bottom:7px; }
        #boletas-evaluacion .be-empty p { margin:0 auto; max-width:425px; font-size:.81rem; line-height:1.6; }
        #boletas-evaluacion .be-caption { color:#8290a4; font-size:.75rem; }
        #beAyudaModal .modal-content { border:0; border-radius:17px; overflow:hidden; }
        #beAyudaModal .modal-header { background:#1e3a8a; color:#fff; padding:17px 21px; }
        #beAyudaModal .modal-title { font-size:1rem; font-weight:800; }
        #beAyudaModal .be-step { display:flex; align-items:flex-start; gap:12px; margin-bottom:18px; }
        #beAyudaModal .be-step-num { width:28px; height:28px; flex:0 0 28px; background:#eff6ff; color:#1d4ed8; border-radius:9px; display:grid; place-items:center; font-weight:800; font-size:.76rem; }
        #beAyudaModal .be-step strong { font-size:.84rem; color:#1e3a8a; }
        #beAyudaModal .be-step p { font-size:.79rem; color:#64748b; margin:2px 0 0; }
        @media (max-width:1199.98px) { #boletas-evaluacion .be-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:767.98px) { #boletas-evaluacion .be-grid { grid-template-columns:1fr; } }
        @media (max-width:767.98px) { #boletas-evaluacion .be-intro { padding:19px; } #boletas-evaluacion .be-panel-head { padding:16px; } #boletas-evaluacion .be-panel-body { padding:17px; } #boletas-evaluacion .be-grid { padding:15px; gap:12px; } }
        @media (prefers-reduced-motion:reduce) { #boletas-evaluacion .be-card { transition:none; } #boletas-evaluacion .be-card:hover { transform:none; } }
    </style>

    <div id="boletas-evaluacion">
        <section class="be-intro mb-4" aria-label="Consulta de boletas">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <span class="be-eyebrow"><i class="bi bi-mortarboard me-1"></i> La Asunción de María · Secretaría</span>
                    <h2>Consulta y emisión de boletas</h2>
                    <p>Localiza las inscripciones por catequista, nivel o grupo y genera el documento de evaluación de cada alumno.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="be-periodo"><i class="bi bi-calendar-event"></i> {{ session('periodo_activo_nombre', 'Periodo no seleccionado') }}</span>
                    <button type="button" class="be-help" data-bs-toggle="modal" data-bs-target="#beAyudaModal"><i class="bi bi-question-circle me-1"></i> Cómo generar</button>
                </div>
            </div>
        </section>

        <section class="be-panel mb-4" aria-labelledby="be-filtros-titulo">
            <div class="be-panel-head">
                <div>
                    <h3 id="be-filtros-titulo"><i class="bi bi-funnel me-2"></i>Filtros de búsqueda</h3>
                    <p>Selecciona uno o varios criterios para encontrar las boletas.</p>
                </div>
                <span class="d-none d-sm-inline be-caption"><i class="bi bi-sliders2 me-1"></i> Búsqueda personalizada</span>
            </div>
            <div class="be-panel-body">
                <form method="GET" action="{{ route($route_name) }}" class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="be-catequista" class="form-label">Catequista</label>
                        <select id="be-catequista" name="catequista_id" class="form-select">
                            <option value="">Todos los catequistas</option>
                            @foreach($catequistas as $cat)
                                <option value="{{ $cat->id }}" @selected((string)$catequistaFiltro === (string)$cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="be-nivel" class="form-label">Nivel</label>
                        <select id="be-nivel" name="nivel_id" class="form-select">
                            <option value="">Todos los niveles</option>
                            @foreach($niveles as $niv)
                                <option value="{{ $niv->id }}" @selected((string)$nivelFiltro === (string)$niv->id)>{{ $niv->nivel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="be-grupo" class="form-label">Grupo</label>
                        <select id="be-grupo" name="grupo_id" class="form-select">
                            <option value="">Todos los grupos</option>
                            @foreach($gruposDisponibles as $grp)
                                <option value="{{ $grp->id }}" @selected((string)$grupoFiltro === (string)$grp->id)>{{ $grp->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 d-flex gap-2">
                        <button type="submit" class="btn be-btn be-btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Buscar</button>
                        <a href="{{ route($route_name) }}" class="btn btn-outline-secondary be-btn d-inline-flex align-items-center justify-content-center" title="Restablecer filtros" aria-label="Restablecer filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </section>

        @if($hayFiltros)
            <section class="be-panel mb-4" aria-labelledby="be-resultados-titulo">
                <div class="be-panel-head">
                    <div>
                        <h3 id="be-resultados-titulo"><i class="bi bi-file-earmark-text me-2"></i>Alumnos encontrados</h3>
                        <p>Tarjetas compactas: consulta los datos y utiliza el ícono PDF para generar la boleta.</p>
                    </div>
                    <span class="be-count"><i class="bi bi-people"></i> {{ number_format($totalEncontrados) }} {{ $totalEncontrados === 1 ? 'resultado' : 'resultados' }}</span>
                </div>
                @if($alumnos->isEmpty())
                    <div class="be-empty">
                        <div class="be-empty-icon"><i class="bi bi-search"></i></div>
                        <h3>No se encontraron alumnos</h3>
                        <p>Prueba con otro catequista, nivel o grupo. Solo se muestran inscripciones con asignación válida en el mismo grupo, periodo y comunidad del alumno. Si falta un alumno, solicita a Secretaría revisar su inscripción y asignación.</p>
                        <a href="{{ route($route_name) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 mt-3">Limpiar filtros</a>
                    </div>
                @else
                    <div class="be-grid">
                        @foreach($alumnos as $insc)
                            @php
                                $nombreAlumno = trim(implode(' ', array_filter([
                                    $insc->alumno?->apellido_paterno,
                                    $insc->alumno?->apellido_materno,
                                    $insc->alumno?->nombre,
                                ]))) ?: 'Nombre no disponible';
                            @endphp
                            <article class="be-card">
                                <div class="be-person">
                                    <span class="be-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                                    <div class="be-person-main">
                                        <h4 class="be-student">{{ $nombreAlumno }}</h4>
                                        <span class="be-subtitle">Alumno inscrito</span>
                                    </div>
                                </div>
                                <div class="be-tags">
                                    <span class="be-tag"><i class="bi bi-collection" aria-hidden="true"></i>{{ $insc->grupo?->nombre ?? 'Sin grupo' }}</span>
                                    <span class="be-tag be-tag-level"><i class="bi bi-layers" aria-hidden="true"></i>{{ $insc->asignacion?->nivel?->nivel ?? 'Seleccionar nivel' }}</span>
                                </div>
                                <div class="be-info">
                                    <div class="be-info-line"><i class="bi bi-person-workspace" aria-hidden="true"></i><span>Catequista: <strong>{{ $insc->asignacion?->catequista?->name ?? 'Varias asignaciones disponibles' }}</strong></span></div>
                                </div>
                                <details class="be-actions">
                                    <summary title="Generar boleta" aria-label="Opciones para generar boleta de {{ $nombreAlumno }}"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></summary>
                                    <div class="be-actions-content">
                                        <span class="be-action-title"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Generar boleta</span>
                                        <span class="be-action-description">Abre el documento de evaluación de este alumno en una nueva pestaña.</span>
                                        @if($insc->opcionesBoleta->count() > 1)
                                            <form method="GET" action="{{ route($route_generar, $insc->id) }}" target="_blank" rel="noopener noreferrer">
                                                <label class="small" for="boleta-asignacion-{{ $insc->id }}">Nivel y catequista</label>
                                                <select id="boleta-asignacion-{{ $insc->id }}" name="asignacion_id" class="form-select form-select-sm mb-2" required>
                                                    <option value="">Selecciona una asignación</option>
                                                    @foreach($insc->opcionesBoleta as $option)
                                                        <option value="{{ $option->id }}">{{ $option->nivel->nivel }} · {{ $option->catequista->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="be-download">Abrir boleta</button>
                                            </form>
                                        @else
                                        <a class="be-download" href="{{ route($route_generar, ['inscripcion' => $insc->id, 'asignacion_id' => $insc->asignacion->id]) }}" target="_blank" rel="noopener noreferrer" aria-label="Generar boleta PDF de {{ $nombreAlumno }}">
                                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Generar PDF <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                        </a>
                                        @endif
                                    </div>
                                </details>
                            </article>
                        @endforeach
                    </div>
                    @if(method_exists($alumnos, 'links'))
                        <div class="px-3 pb-3">{{ $alumnos->withQueryString()->links() }}</div>
                    @endif
                @endif
            </section>
        @else
            <div class="be-panel mb-4">
                <div class="be-empty">
                    <div class="be-empty-icon"><i class="bi bi-funnel"></i></div>
                    <h3>Prepara tu consulta</h3>
                    <p>Elige al menos un catequista, nivel o grupo y presiona <strong>Buscar</strong> para mostrar las inscripciones disponibles.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="modal fade" id="beAyudaModal" tabindex="-1" aria-labelledby="beAyudaModalTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h2 class="modal-title" id="beAyudaModalTitulo"><i class="bi bi-info-circle me-2"></i>Cómo generar una boleta</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="be-step"><span class="be-step-num">1</span><div><strong>Filtra los registros</strong><p>Selecciona un catequista, nivel o grupo.</p></div></div>
                    <div class="be-step"><span class="be-step-num">2</span><div><strong>Encuentra al alumno</strong><p>Revisa su nombre, grupo, nivel y catequista; después abre el ícono PDF.</p></div></div>
                    <div class="be-step mb-0"><span class="be-step-num">3</span><div><strong>Genera el PDF</strong><p>Presiona «Generar PDF» para abrir la boleta en una nueva pestaña.</p></div></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4"><button type="button" class="btn btn-primary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Entendido</button></div>
            </div>
        </div>
    </div>
@endsection
