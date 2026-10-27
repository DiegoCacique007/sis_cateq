@extends('layouts.app_parroquia_catequista')

@section('title', 'Panel de Catequista | La Asunción de María')
@section('header_title', 'Panel de Catequista')
@section('header_subtitle', 'Seguimiento de grupos, alumnos y evaluaciones del periodo activo')

@section('content')
    @php
        $totalGruposAsignados = max(0, (int) ($totalGruposAsignados ?? 0));
        $totalAlumnosGrupo = max(0, (int) ($totalAlumnosGrupo ?? 0));
        $totalEvaluacionesRegistradas = max(0, (int) ($totalEvaluacionesRegistradas ?? 0));
        $totalNivelesAsignados = max(0, (int) ($totalNivelesAsignados ?? 0));
        $periodoActual = session('periodo_activo_nombre', 'No seleccionado');
        $nombreCatequista = auth()->user()?->name ?: 'Catequista';
        $indicadores = [
            ['titulo' => 'Alumnos activos', 'valor' => $totalAlumnosGrupo, 'detalle' => 'Alumnos inscritos en tus grupos.', 'icono' => 'bi-people-fill', 'tono' => 'azul'],
            ['titulo' => 'Grupos asignados', 'valor' => $totalGruposAsignados, 'detalle' => 'Grupos vinculados a tu cuenta.', 'icono' => 'bi-diagram-3-fill', 'tono' => 'ambar'],
            ['titulo' => 'Niveles asignados', 'valor' => $totalNivelesAsignados, 'detalle' => 'Niveles de tus grupos actuales.', 'icono' => 'bi-layers-fill', 'tono' => 'verde'],
            ['titulo' => 'Evaluaciones', 'valor' => $totalEvaluacionesRegistradas, 'detalle' => 'Registros de evaluación del periodo.', 'icono' => 'bi-clipboard2-check-fill', 'tono' => 'cian'],
        ];
        $maxAcademico = max(1, $totalAlumnosGrupo, $totalEvaluacionesRegistradas);
        $alturaAlumnos = $totalAlumnosGrupo ? max(12, (int) round(78 * $totalAlumnosGrupo / $maxAcademico)) : 0;
        $alturaEvaluaciones = $totalEvaluacionesRegistradas ? max(12, (int) round(78 * $totalEvaluacionesRegistradas / $maxAcademico)) : 0;
        $maxEstructura = max(1, $totalGruposAsignados, $totalNivelesAsignados);
        $anchoGrupos = $totalGruposAsignados ? round(100 * $totalGruposAsignados / $maxEstructura) : 0;
        $anchoNiveles = $totalNivelesAsignados ? round(100 * $totalNivelesAsignados / $maxEstructura) : 0;
    @endphp

    <style>
        #catequista-dashboard { --cp-navy:#1167a8; --cp-blue:#3499e7; --cp-text:#334155; --cp-muted:#64748b; --cp-border:#d9eaf8; color:var(--cp-text); }
        #catequista-dashboard .cp-hero { position:relative; isolation:isolate; overflow:hidden; border-radius:22px; background:linear-gradient(120deg,#49a8ed 0%,#3698dc 55%,#267fc9 100%); color:#fff; box-shadow:0 14px 34px rgba(38,127,201,.18); }
        #catequista-dashboard .cp-hero:after { content:""; position:absolute; right:-85px; top:-135px; width:390px; height:390px; border:55px solid rgba(255,255,255,.11); border-radius:50%; pointer-events:none; z-index:-1; }
        #catequista-dashboard .cp-hero-inner { padding:28px 30px; }
        #catequista-dashboard .cp-eyebrow { font-size:.68rem; font-weight:750; letter-spacing:.09em; text-transform:uppercase; }
        #catequista-dashboard .cp-hero h2 { font-size:clamp(1.4rem,2.3vw,2rem); font-weight:800; letter-spacing:-.035em; margin:.6rem 0; }
        #catequista-dashboard .cp-hero p { color:#f1f9ff; font-size:.9rem; line-height:1.65; max-width:620px; }
        #catequista-dashboard .cp-hero .cp-periodo { display:inline-flex; align-items:center; gap:7px; padding:7px 12px; font-size:.77rem; color:#fff; border:1px solid rgba(255,255,255,.25); background:rgba(255,255,255,.11); border-radius:50px; }
        #catequista-dashboard .cp-hero-visual { width:165px; height:165px; border:1px solid rgba(255,255,255,.28); background:rgba(255,255,255,.1); backdrop-filter:blur(5px); display:grid; place-items:center; border-radius:32px; transform:rotate(-7deg); margin:auto; }
        #catequista-dashboard .cp-hero-visual i { font-size:5rem; transform:rotate(7deg); opacity:.85; }
        #catequista-dashboard .cp-btn-light { background:#fff; color:#1167a8; border:1px solid #fff; font-weight:700; font-size:.81rem; border-radius:10px; padding:10px 15px; text-decoration:none; }
        #catequista-dashboard .cp-btn-light:hover { background:#eff8ff; color:#1167a8; }
        #catequista-dashboard .cp-btn-ghost { background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.36); color:#fff; font-weight:650; font-size:.81rem; border-radius:10px; padding:10px 15px; }
        #catequista-dashboard .cp-btn-ghost:hover { background:rgba(255,255,255,.22); color:#fff; }
        #catequista-dashboard .cp-section-title { font-weight:800; font-size:1.05rem; letter-spacing:-.018em; margin:0; color:var(--cp-navy); }
        #catequista-dashboard .cp-section-subtitle { color:var(--cp-muted); font-size:.79rem; margin:3px 0 0; }
        #catequista-dashboard .cp-panel, #catequista-dashboard .cp-stat, #catequista-dashboard .cp-module { border:1px solid var(--cp-border); background:#fff; border-radius:17px; box-shadow:0 5px 20px rgba(38,127,201,.06); }
        #catequista-dashboard .cp-panel { height:100%; overflow:hidden; }
        #catequista-dashboard .cp-stat { position:relative; min-height:153px; height:100%; padding:19px 20px; border-bottom:3px solid var(--cp-accent); transition:transform .2s,box-shadow .2s; }
        #catequista-dashboard .cp-stat:hover, #catequista-dashboard .cp-module:hover { transform:translateY(-3px); box-shadow:0 12px 27px rgba(38,127,201,.12); }
        #catequista-dashboard .cp-stat-header { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
        #catequista-dashboard .cp-stat-icon { display:grid; place-items:center; border-radius:12px; width:43px; height:43px; flex:none; color:var(--cp-accent); background:var(--cp-soft); font-size:1.12rem; }
        #catequista-dashboard .cp-stat-value { font-size:1.85rem; font-weight:850; line-height:1.2; font-variant-numeric:tabular-nums; letter-spacing:-.04em; }
        #catequista-dashboard .cp-stat-title { color:#344156; font-size:.83rem; font-weight:750; margin:8px 0 2px; }
        #catequista-dashboard .cp-stat-desc { color:var(--cp-muted); font-size:.72rem; margin:0; }
        #catequista-dashboard .azul { --cp-accent:#3499e7; --cp-soft:#e5f4ff; }
        #catequista-dashboard .ambar { --cp-accent:#c47b16; --cp-soft:#fff4dc; }
        #catequista-dashboard .verde { --cp-accent:#16875b; --cp-soft:#e6f8ef; }
        #catequista-dashboard .cian { --cp-accent:#0891b2; --cp-soft:#e2f7fb; }
        #catequista-dashboard .cp-panel-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:21px 22px 10px; }
        #catequista-dashboard .cp-panel-heading h3 { color:var(--cp-navy); font-weight:800; font-size:.94rem; margin:0 0 5px; }
        #catequista-dashboard .cp-panel-heading p { color:var(--cp-muted); font-size:.73rem; margin:0; }
        #catequista-dashboard .cp-icon-btn { display:grid; place-items:center; height:32px; width:32px; border:1px solid #e3eaf4; border-radius:9px; color:#52657f; background:#fff; }
        #catequista-dashboard .cp-icon-btn:hover { background:#e5f4ff; color:var(--cp-blue); }
        #catequista-dashboard .cp-vchart { padding:14px 28px 9px; }
        #catequista-dashboard .cp-vchart-area { height:198px; display:flex; justify-content:space-around; align-items:flex-end; gap:32px; padding:10px 18px 0; border-bottom:1px solid #d5e9f8; background:repeating-linear-gradient(to top, #e4f0f9 0, #e4f0f9 1px, transparent 1px, transparent 46px); }
        #catequista-dashboard .cp-vchart-col { flex:1; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:6px; }
        #catequista-dashboard .cp-vchart-num { color:#334155; font-size:.81rem; font-weight:800; font-variant-numeric:tabular-nums; }
        #catequista-dashboard .cp-vchart-bar { width:clamp(42px,46%,104px); border-radius:9px 9px 0 0; min-height:0; transition:height .6s ease; }
        #catequista-dashboard .cp-vchart-labels { display:grid; grid-template-columns:1fr 1fr; gap:20px; text-align:center; color:#53657a; font-size:.79rem; font-weight:700; padding:12px 0 4px; }
        #catequista-dashboard .cp-hchart { padding:35px 25px 23px; display:flex; flex-direction:column; gap:33px; }
        #catequista-dashboard .cp-hchart-header { display:flex; justify-content:space-between; gap:10px; color:#43536a; font-size:.8rem; font-weight:700; margin-bottom:10px; }
        #catequista-dashboard .cp-track { height:16px; border-radius:8px; overflow:hidden; background:#eef7fe; }
        #catequista-dashboard .cp-fill { height:100%; border-radius:8px; transition:width .6s ease; }
        #catequista-dashboard .cp-chart-foot { display:flex; align-items:flex-start; gap:7px; border-top:1px solid #eff2f7; margin-top:4px; padding:12px 20px; font-size:.7rem; color:#78869a; }
        #catequista-dashboard .cp-module { height:100%; overflow:hidden; transition:transform .2s,box-shadow .2s; }
        #catequista-dashboard .cp-module-inner { display:flex; gap:16px; align-items:flex-start; padding:22px; }
        #catequista-dashboard .cp-module-icon { display:grid; place-items:center; width:51px; height:51px; flex:none; background:var(--cp-soft); color:var(--cp-accent); border-radius:13px; font-size:1.45rem; }
        #catequista-dashboard .cp-module h3 { font-size:.98rem; color:var(--cp-navy); font-weight:800; margin:1px 0 6px; }
        #catequista-dashboard .cp-module p { font-size:.79rem; color:var(--cp-muted); line-height:1.55; margin:0 0 14px; }
        #catequista-dashboard .cp-module-link { font-size:.79rem; font-weight:750; color:var(--cp-accent); text-decoration:none; }
        #catequista-dashboard .cp-module-link:hover { text-decoration:underline; }
        #catequista-dashboard .cp-step { display:flex; align-items:flex-start; gap:12px; padding:13px 0; }
        #catequista-dashboard .cp-step + .cp-step { border-top:1px solid #eff2f7; }
        #catequista-dashboard .cp-step-num { display:grid; place-items:center; width:33px; height:33px; border-radius:10px; background:#e5f4ff; color:#1167a8; font-size:.8rem; font-weight:800; flex:none; }
        #catequista-dashboard .cp-step strong { display:block; color:#334155; font-size:.82rem; margin-bottom:3px; }
        #catequista-dashboard .cp-step p { color:var(--cp-muted); font-size:.75rem; line-height:1.5; margin:0; }
        #catequista-dashboard .cp-notice { border:1px solid #d2eafa; background:#f0f9ff; border-radius:13px; padding:15px 16px; color:#335078; font-size:.79rem; line-height:1.5; }
        #catequista-dashboard .cp-small-btn { padding:7px 12px; border-radius:9px; font-size:.76rem; font-weight:700; }

        #catequista-dashboard .btn-outline-primary { border-color:#3499e7; color:#237fbe; background:#fff; }
        #catequista-dashboard .btn-outline-primary:hover, #catequista-dashboard .btn-outline-primary:focus-visible { border-color:#267fc9; background:#3499e7; color:#fff; }
        #catequista-dashboard .btn-primary { border-color:#3499e7; background:#3499e7; color:#fff; }
        #catequista-dashboard .btn-primary:hover { border-color:#267fc9; background:#267fc9; }
        #catequista-dashboard .text-primary { color:#237fbe!important; }
        #catequista-dashboard .badge.bg-primary { background-color:rgba(52,153,231,var(--bs-bg-opacity,1))!important; }
        #catequista-dashboard .modal-content { border:0; border-radius:18px; overflow:hidden; }
        #catequista-dashboard .modal-header { border-bottom:1px solid #edf1f7; }
        #catequista-dashboard .modal-footer { border-top:1px solid #edf1f7; }
        #catequista-dashboard .cp-detail { padding:13px 15px; background:#f5fbff; border:1px solid #dfedf9; border-radius:12px; }
        #catequista-dashboard .cp-detail span { font-size:.75rem; color:#64748b; }
        #catequista-dashboard .cp-detail strong { display:block; font-size:1.45rem; color:var(--cp-navy); }
        @media(max-width:575.98px) { #catequista-dashboard .cp-hero-inner { padding:22px 20px; } #catequista-dashboard .cp-vchart { padding-left:13px; padding-right:13px; } #catequista-dashboard .cp-panel-heading { padding:19px 16px 8px; } #catequista-dashboard .cp-hchart { padding:25px 17px; } #catequista-dashboard .cp-module-inner { padding:17px; } }
        @media(prefers-reduced-motion:reduce) { #catequista-dashboard .cp-stat,#catequista-dashboard .cp-module,#catequista-dashboard .cp-vchart-bar,#catequista-dashboard .cp-fill { transition:none; } }
    </style>

    <div id="catequista-dashboard">
        <section class="cp-hero mb-4" aria-label="Bienvenida al panel de catequista">
            <div class="cp-hero-inner">
                <div class="row align-items-center g-3">
                    <div class="col-lg-9">
                        <span class="cp-eyebrow"><i class="bi bi-stars me-2" aria-hidden="true"></i>Panel de seguimiento catequético</span>
                        <h2>Bienvenido(a), {{ $nombreCatequista }}</h2>
                        <p class="mb-3">Consulta los grupos y alumnos que tienes asignados, organiza tu trabajo y registra las evaluaciones correspondientes al periodo activo.</p>
                        <span class="cp-periodo mb-3"><i class="bi bi-calendar3" aria-hidden="true"></i> Periodo: {{ $periodoActual }}</span>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <a href="{{ route('catequista.mi_grupo') }}" class="btn cp-btn-light"><i class="bi bi-people me-2" aria-hidden="true"></i>Ver mis grupos</a>
                            <a href="{{ route('catequista.evaluaciones.index') }}" class="btn cp-btn-ghost"><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Capturar calificaciones</a>
                            <button type="button" class="btn cp-btn-ghost" data-bs-toggle="modal" data-bs-target="#cpModalGuia"><i class="bi bi-question-circle me-2" aria-hidden="true"></i>Ayuda rápida</button>
                        </div>
                    </div>
                    <div class="col-lg-3 d-none d-lg-block text-center" aria-hidden="true"><div class="cp-hero-visual"><i class="bi bi-person-workspace"></i></div></div>
                </div>
            </div>
        </section>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div><h3 class="cp-section-title">Resumen del periodo</h3><p class="cp-section-subtitle">Información correspondiente a tus asignaciones actuales.</p></div>
            <button type="button" class="btn btn-outline-primary cp-small-btn" data-bs-toggle="modal" data-bs-target="#cpModalIndicadores"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Ver detalles</button>
        </div>

        <div class="row g-3 mb-4">
            @foreach($indicadores as $indicador)
                <div class="col-sm-6 col-xl-3">
                    <article class="cp-stat {{ $indicador['tono'] }}">
                        <div class="cp-stat-header"><div class="cp-stat-value">{{ number_format($indicador['valor']) }}</div><span class="cp-stat-icon"><i class="bi {{ $indicador['icono'] }}" aria-hidden="true"></i></span></div>
                        <div class="cp-stat-title">{{ $indicador['titulo'] }}</div><p class="cp-stat-desc">{{ $indicador['detalle'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div><h3 class="cp-section-title">Estadísticas de mis grupos</h3><p class="cp-section-subtitle">Gráficas elaboradas con los totales existentes, sin estimaciones.</p></div>
            <span class="badge rounded-pill text-primary bg-primary bg-opacity-10 px-3 py-2"><i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>Periodo activo</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <section class="cp-panel" aria-labelledby="cpGraficaAcademica">
                    <div class="cp-panel-heading">
                        <div><h3 id="cpGraficaAcademica">Registros académicos</h3><p>Conteo de alumnos activos y evaluaciones registradas.</p></div>
                        <button type="button" class="cp-icon-btn" data-bs-toggle="modal" data-bs-target="#cpModalGraficas" aria-label="Información sobre las gráficas" title="Acerca de esta gráfica"><i class="bi bi-info-lg" aria-hidden="true"></i></button>
                    </div>
                    <div class="cp-vchart" role="img" aria-label="Alumnos activos: {{ $totalAlumnosGrupo }}. Evaluaciones registradas: {{ $totalEvaluacionesRegistradas }}.">
                        <div class="cp-vchart-area">
                            <div class="cp-vchart-col"><span class="cp-vchart-num">{{ number_format($totalAlumnosGrupo) }}</span><div class="cp-vchart-bar" style="height:{{ $alturaAlumnos }}%;background:linear-gradient(180deg,#7dccf8,#3499e7)"></div></div>
                            <div class="cp-vchart-col"><span class="cp-vchart-num">{{ number_format($totalEvaluacionesRegistradas) }}</span><div class="cp-vchart-bar" style="height:{{ $alturaEvaluaciones }}%;background:linear-gradient(180deg,#67d9e9,#0891b2)"></div></div>
                        </div>
                        <div class="cp-vchart-labels"><span><i class="bi bi-circle-fill me-1" style="color:#3499e7;font-size:.55rem" aria-hidden="true"></i>Alumnos activos</span><span><i class="bi bi-circle-fill me-1" style="color:#0891b2;font-size:.55rem" aria-hidden="true"></i>Evaluaciones</span></div>
                    </div>
                    <div class="cp-chart-foot"><i class="bi bi-info-circle" aria-hidden="true"></i>Son cantidades de registros diferentes; esta gráfica no representa porcentajes de avance ni rendimiento.</div>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="cp-panel" aria-labelledby="cpGraficaEstructura">
                    <div class="cp-panel-heading"><div><h3 id="cpGraficaEstructura">Estructura asignada</h3><p>Grupos y niveles asociados a tu cuenta.</p></div><i class="bi bi-diagram-3 text-secondary" aria-hidden="true"></i></div>
                    <div class="cp-hchart" role="img" aria-label="Grupos asignados: {{ $totalGruposAsignados }}. Niveles asignados: {{ $totalNivelesAsignados }}.">
                        <div><div class="cp-hchart-header"><span><i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>Grupos asignados</span><span>{{ number_format($totalGruposAsignados) }}</span></div><div class="cp-track"><div class="cp-fill" style="width:{{ $anchoGrupos }}%;background:linear-gradient(90deg,#f5bd59,#d99021)"></div></div></div>
                        <div><div class="cp-hchart-header"><span><i class="bi bi-layers me-2" aria-hidden="true"></i>Niveles asignados</span><span>{{ number_format($totalNivelesAsignados) }}</span></div><div class="cp-track"><div class="cp-fill" style="width:{{ $anchoNiveles }}%;background:linear-gradient(90deg,#57d7ac,#16875b)"></div></div></div>
                    </div>
                    <div class="cp-chart-foot"><i class="bi bi-info-circle" aria-hidden="true"></i>Las barras comparan cantidades de cada tipo; no representan cobertura o cumplimiento.</div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div><h3 class="cp-section-title">Mis herramientas de trabajo</h3><p class="cp-section-subtitle">Accede directamente a tus actividades catequéticas.</p></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><article class="cp-module azul"><div class="cp-module-inner"><div class="cp-module-icon"><i class="bi bi-people-fill" aria-hidden="true"></i></div><div><h3>Lista de grupo</h3><p>Consulta tus alumnos, el grupo, nivel y comunidad. Desde este módulo podrás obtener la plantilla de asistencia disponible.</p><a href="{{ route('catequista.mi_grupo') }}" class="cp-module-link">Ir a mis grupos <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div></div></article></div>
            <div class="col-md-6"><article class="cp-module ambar"><div class="cp-module-inner"><div class="cp-module-icon"><i class="bi bi-clipboard2-check-fill" aria-hidden="true"></i></div><div><h3>Captura de calificaciones</h3><p>Registra y consulta evaluaciones por unidad y rubro utilizando las funciones actuales del sistema.</p><a href="{{ route('catequista.evaluaciones.index') }}" class="cp-module-link">Ir a evaluaciones <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div></div></article></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <section class="cp-panel p-3 p-md-4" aria-labelledby="cpFlujoTitle">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2"><h3 id="cpFlujoTitle" class="cp-section-title">Guía de trabajo</h3><button type="button" class="btn btn-outline-primary cp-small-btn" data-bs-toggle="modal" data-bs-target="#cpModalGuia">Ver guía completa</button></div>
                    <div class="cp-step"><div class="cp-step-num">01</div><div><strong>Consultar las asignaciones</strong><p>Comprueba qué grupos y niveles corresponden a tu cuenta.</p></div></div>
                    <div class="cp-step"><div class="cp-step-num">02</div><div><strong>Revisar alumnos y asistencia</strong><p>Consulta la lista y descarga la plantilla de asistencia si la necesitas.</p></div></div>
                    <div class="cp-step"><div class="cp-step-num">03</div><div><strong>Registrar evaluaciones</strong><p>Captura las calificaciones por unidad y rubro según corresponda.</p></div></div>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="cp-panel p-3 p-md-4" aria-labelledby="cpEstadoTitle">
                    <h3 id="cpEstadoTitle" class="cp-section-title mb-3">Orientación del periodo</h3>
                    @if($totalGruposAsignados === 0)
                        <div class="cp-notice"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><strong>Sin grupos asignados.</strong><p class="mb-0 mt-2">Todavía no aparecen grupos vinculados a tu cuenta en el periodo seleccionado. Consulta con Secretaría si esperabas tener asignaciones.</p></div>
                    @elseif($totalAlumnosGrupo === 0)
                        <div class="cp-notice"><i class="bi bi-info-circle me-2" aria-hidden="true"></i><strong>No aparecen alumnos activos.</strong><p class="mb-0 mt-2">Ya tienes grupos asignados, pero el indicador de alumnos está en cero. Revisa tu lista de grupo.</p></div>
                    @else
                        <div class="cp-notice"><i class="bi bi-check-circle me-2" aria-hidden="true"></i><strong>Asignaciones disponibles.</strong><p class="mb-0 mt-2">Tienes {{ number_format($totalGruposAsignados) }} {{ $totalGruposAsignados === 1 ? 'grupo asignado' : 'grupos asignados' }} y {{ number_format($totalAlumnosGrupo) }} {{ $totalAlumnosGrupo === 1 ? 'alumno activo' : 'alumnos activos' }}. Consulta sus registros para continuar.</p></div>
                    @endif
                    <a href="{{ route('catequista.mi_grupo') }}" class="btn btn-outline-primary cp-small-btn mt-3"><i class="bi bi-people me-1" aria-hidden="true"></i>Consultar mis grupos</a>
                </section>
            </div>
        </div>

        <div class="modal fade" id="cpModalIndicadores" tabindex="-1" aria-labelledby="cpModalIndicadoresTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content shadow-lg"><div class="modal-header px-4 py-3"><h2 class="modal-title h6 fw-bold text-primary" id="cpModalIndicadoresTitle"><i class="bi bi-clipboard-data me-2" aria-hidden="true"></i>Detalle de mis indicadores</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body p-4"><p class="small text-muted mb-3">Totales recibidos para el periodo <strong>{{ $periodoActual }}</strong>.</p><div class="row g-3">@foreach($indicadores as $indicador)<div class="col-sm-6"><div class="cp-detail"><span><i class="bi {{ $indicador['icono'] }} me-1" aria-hidden="true"></i>{{ $indicador['titulo'] }}</span><strong>{{ number_format($indicador['valor']) }}</strong><span>{{ $indicador['detalle'] }}</span></div></div>@endforeach</div><p class="small text-muted mt-3 mb-0">Estos valores provienen del controlador del dashboard. No incluyen desgloses históricos ni tasas de asistencia.</p></div><div class="modal-footer px-4"><button type="button" class="btn btn-outline-secondary cp-small-btn" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>

        <div class="modal fade" id="cpModalGuia" tabindex="-1" aria-labelledby="cpModalGuiaTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content shadow-lg"><div class="modal-header px-4 py-3"><h2 class="modal-title h6 fw-bold text-primary" id="cpModalGuiaTitle"><i class="bi bi-journal-check me-2" aria-hidden="true"></i>Guía rápida del catequista</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body px-4 py-3"><div class="cp-step"><div class="cp-step-num">1</div><div><strong>Consulta tus grupos</strong><p>Comprueba la lista de alumnos, su nivel y la información del periodo activo.</p></div></div><div class="cp-step"><div class="cp-step-num">2</div><div><strong>Revisa la asistencia</strong><p>Accede a la plantilla disponible desde el módulo de grupos.</p></div></div><div class="cp-step"><div class="cp-step-num">3</div><div><strong>Captura calificaciones</strong><p>Registra los resultados de evaluación por unidad y rubro.</p></div></div></div><div class="modal-footer px-4"><button type="button" class="btn btn-outline-secondary cp-small-btn" data-bs-dismiss="modal">Entendido</button><a href="{{ route('catequista.mi_grupo') }}" class="btn btn-primary cp-small-btn">Ir a mis grupos</a></div></div></div></div>

        <div class="modal fade" id="cpModalGraficas" tabindex="-1" aria-labelledby="cpModalGraficasTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content shadow-lg"><div class="modal-header px-4 py-3"><h2 class="modal-title h6 fw-bold text-primary" id="cpModalGraficasTitle"><i class="bi bi-bar-chart-line me-2" aria-hidden="true"></i>Cómo interpretar las gráficas</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body px-4 py-3 small text-secondary"><p>La primera gráfica muestra por separado el número de alumnos activos y el número de registros de evaluación.</p><p>La segunda compara las cantidades de grupos y niveles asignados.</p><p class="mb-0">Cada barra representa un conteo independiente. No deben interpretarse como porcentajes, niveles de rendimiento, calificaciones ni evolución a lo largo del tiempo.</p></div><div class="modal-footer px-4"><button type="button" class="btn btn-outline-secondary cp-small-btn" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
    </div>
@endsection
