@extends('layouts.app_parroquia_catequista')

@section('title', 'Mi Grupo | La Asunción de María')
@section('header_title', 'Mi Grupo Asignado')
@section('header_subtitle', 'Consulta tus grupos, alumnos inscritos y documentos de asistencia')

@section('content')
    <style>
        .mg-wrap{--mg-primary:#267fc9;--mg-heading:#214781;--mg-text:#334155;--mg-muted:#64748b;--mg-border:#e2e8f0;font-family:'Inter','Segoe UI','Roboto',sans-serif;color:var(--mg-text)}
        .mg-wrap .mg-heading{color:var(--mg-heading);font-weight:800}
        .mg-wrap .mg-page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
        .mg-wrap .mg-page-head h2{font-size:1.2rem;margin:0 0 4px}
        .mg-wrap .mg-page-head p{font-size:.84rem;color:var(--mg-muted);margin:0}
        .mg-wrap .mg-period-pill{display:inline-flex;align-items:center;gap:7px;background:#eff8ff;border:1px solid #cfe8fa;border-radius:50px;color:#2473b1;padding:8px 13px;font-size:.76rem;font-weight:700;max-width:100%;overflow-wrap:anywhere}
        .mg-wrap .mg-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}
        .mg-wrap .mg-stat{--mg-accent:#3499e7;display:flex;align-items:center;gap:14px;background:#fff;padding:18px;border:1px solid var(--mg-border);border-radius:16px;box-shadow:0 2px 10px rgba(30,58,138,.045);transition:transform .2s,box-shadow .2s;min-height:112px;min-width:0}
        .mg-wrap .mg-stat:hover{transform:translateY(-3px);box-shadow:0 10px 22px rgba(30,58,138,.10)}
        .mg-wrap .mg-stat-icon{flex:0 0 48px;height:48px;width:48px;display:grid;place-items:center;background:var(--mg-accent);color:#fff;border-radius:14px;font-size:1.3rem}
        .mg-wrap .mg-stat-info{min-width:0}
        .mg-wrap .mg-stat-label{display:block;color:#64748b;font-size:.78rem;font-weight:700;margin-bottom:4px}
        .mg-wrap .mg-stat-value{display:block;color:#17243b;font-size:1.1rem;font-weight:800;line-height:1.35;overflow-wrap:anywhere}
        .mg-wrap .mg-stat.mg-blue .mg-stat-icon{background:linear-gradient(135deg,#60b8f3,#2f90dd)}
        .mg-wrap .mg-stat.mg-orange .mg-stat-icon{background:linear-gradient(135deg,#fbbf65,#e99a27)}
        .mg-wrap .mg-stat.mg-green .mg-stat-icon{background:linear-gradient(135deg,#55d5a5,#18a071)}
        .mg-wrap .mg-stat.mg-cyan .mg-stat-icon{background:linear-gradient(135deg,#61cfed,#169cbe)}
        .mg-wrap .mg-panel{background:#fff;border:1px solid var(--mg-border);border-radius:17px;box-shadow:0 2px 10px rgba(30,58,138,.045);overflow:hidden;margin-bottom:19px}
        .mg-wrap .mg-panel-head{padding:18px 21px;border-bottom:1px solid #ecf1f7;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}
        .mg-wrap .mg-panel-title{margin:0 0 4px;color:var(--mg-heading);font-size:1rem;font-weight:800}
        .mg-wrap .mg-panel-subtitle{margin:0;font-size:.78rem;color:var(--mg-muted)}
        .mg-wrap .mg-panel-body{padding:20px 21px}
        .mg-wrap .mg-panel-icon{display:inline-grid;place-items:center;background:#ecf7ff;color:#278ad1;height:31px;width:31px;border-radius:9px;margin-right:7px;vertical-align:middle;font-size:.95rem}
        .mg-wrap .mg-field-label{display:block;font-size:.8rem;font-weight:700;color:#42526b;margin:0 0 7px}
        .mg-wrap .mg-control{min-height:43px;border-radius:10px;border:1px solid #dbe6f2;color:#334155;box-shadow:none}
        .mg-wrap .mg-control:focus{border-color:#63b5ee;box-shadow:0 0 0 3px rgba(73,168,237,.13)}
        .mg-wrap .mg-find-actions{display:flex;gap:8px;align-items:stretch}
        .mg-wrap .mg-clear{border:1px solid #dbe6f2;border-radius:10px;background:#fff;color:#64748b;min-width:43px;display:grid;place-items:center;transition:background .15s,color .15s}
        .mg-wrap .mg-clear:hover{background:#eaf6ff;color:#267fc9}
        .mg-wrap .mg-export{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#fff;color:#ce3c43;border:1px solid #efb3b7;border-radius:10px;font-weight:750;font-size:.78rem;min-height:40px;padding:9px 13px;text-decoration:none;transition:background .2s,box-shadow .2s}
        .mg-wrap .mg-export:hover{background:#fff2f2;color:#b42c34;box-shadow:0 5px 14px rgba(206,60,67,.10)}
        .mg-wrap .mg-count{display:inline-flex;align-items:center;gap:7px;background:#eef7ff;border:1px solid #cee5fb;color:#237cbd;border-radius:30px;padding:7px 12px;font-size:.77rem;font-weight:700;white-space:nowrap}
        .mg-wrap .mg-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .mg-wrap .mg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),1fr));gap:17px}
        .mg-wrap .mg-student{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:19px;min-width:0;min-height:200px;display:flex;flex-direction:column;box-shadow:0 1px 3px rgba(0,0,0,.045);transition:transform .2s,box-shadow .2s}
        .mg-wrap .mg-student:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(30,58,138,.10)}
        .mg-wrap .mg-person{display:flex;align-items:flex-start;gap:12px;margin-bottom:15px;min-width:0}
        .mg-wrap .mg-avatar{flex:0 0 45px;width:45px;height:45px;border-radius:13px;background:#eaf4ff;color:#308dd2;display:grid;place-items:center;font-size:1.25rem}
        .mg-wrap .mg-person-main{min-width:0}
        .mg-wrap .mg-name{margin:1px 0 4px;color:var(--mg-heading);font-size:1rem;line-height:1.35;font-weight:800;overflow-wrap:anywhere}
        .mg-wrap .mg-person-desc{display:block;color:#7a8899;font-size:.76rem}
        .mg-wrap .mg-tags{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:15px}
        .mg-wrap .mg-tag{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:30px;font-size:.72rem;font-weight:700;background:#edf7ff;border:1px solid #cce8fc;color:#267fc9}
        .mg-wrap .mg-tag.green{background:#ecfdf5;color:#11865f;border-color:#b5ebd2}
        .mg-wrap .mg-student-info{border-top:1px solid #eff2f6;padding-top:12px;margin-top:auto;display:grid;gap:8px}
        .mg-wrap .mg-info-line{display:flex;align-items:flex-start;gap:8px;color:#64748b;font-size:.77rem;line-height:1.45;overflow-wrap:anywhere}
        .mg-wrap .mg-info-line i{color:#58a9e6;width:17px;flex:0 0 17px;margin-top:1px}
        .mg-wrap .mg-info-line strong{font-weight:700;color:#334155}
        .mg-wrap .mg-empty{padding:50px 20px;text-align:center;background:#fff;border:1px dashed #d3e1ee;border-radius:14px}
        .mg-wrap .mg-empty-icon{display:grid;place-items:center;margin:0 auto 13px;width:60px;height:60px;background:#eaf6ff;color:#59a7df;border-radius:17px;font-size:1.55rem}
        .mg-wrap .mg-empty h3{font-size:1.01rem;font-weight:800;color:var(--mg-heading);margin:0 0 8px}
        .mg-wrap .mg-empty p{font-size:.82rem;color:#64748b;line-height:1.6;max-width:480px;margin:0 auto}
        .mg-wrap .mg-student[hidden],.mg-wrap .mg-search-empty[hidden]{display:none!important}
        @media(max-width:1199.98px){.mg-wrap .mg-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:575.98px){.mg-wrap .mg-stats{grid-template-columns:1fr;gap:10px}.mg-wrap .mg-stat{padding:13px;min-height:82px}.mg-wrap .mg-panel-head,.mg-wrap .mg-panel-body{padding:15px}.mg-wrap .mg-page-head{align-items:flex-start}.mg-wrap .mg-actions{width:100%}.mg-wrap .mg-export{flex:1}.mg-wrap .mg-grid{gap:12px}}
        @media(prefers-reduced-motion:reduce){.mg-wrap .mg-stat,.mg-wrap .mg-student,.mg-wrap .mg-export{transition:none}.mg-wrap .mg-stat:hover,.mg-wrap .mg-student:hover{transform:none}}
    </style>

    <div class="mg-wrap" id="mgMiGrupo">
        <header class="mg-page-head">
            <div>
                <h2 class="mg-heading">Mi grupo de catequesis</h2>
                <p>Consulta la información académica y los alumnos de tu grupo asignado.</p>
            </div>
            @if($asignacion)
                <span class="mg-period-pill"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $asignacion->periodo }}</span>
            @endif
        </header>

        @if($asignacion)
            <section class="mg-stats" aria-label="Información de la asignación académica">
                <article class="mg-stat mg-blue"><span class="mg-stat-icon"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span><div class="mg-stat-info"><span class="mg-stat-label">Comunidad catequética</span><strong class="mg-stat-value">{{ $asignacion->comunidad }}</strong></div></article>
                <article class="mg-stat mg-orange"><span class="mg-stat-icon"><i class="bi bi-collection-fill" aria-hidden="true"></i></span><div class="mg-stat-info"><span class="mg-stat-label">Grupo</span><strong class="mg-stat-value">{{ $asignacion->grupo }}</strong></div></article>
                <article class="mg-stat mg-green"><span class="mg-stat-icon"><i class="bi bi-layers-fill" aria-hidden="true"></i></span><div class="mg-stat-info"><span class="mg-stat-label">Nivel</span><strong class="mg-stat-value">{{ $asignacion->nivel }}</strong></div></article>
                <article class="mg-stat mg-cyan"><span class="mg-stat-icon"><i class="bi bi-calendar-event-fill" aria-hidden="true"></i></span><div class="mg-stat-info"><span class="mg-stat-label">Periodo</span><strong class="mg-stat-value">{{ $asignacion->periodo }}</strong></div></article>
            </section>
        @endif

        @if($asignaciones->count() > 1 || $asignacion)
            <section class="mg-panel" aria-labelledby="mgFiltersTitle">
                <div class="mg-panel-head">
                    <div>
                        <h3 class="mg-panel-title" id="mgFiltersTitle"><span class="mg-panel-icon"><i class="bi bi-funnel" aria-hidden="true"></i></span>Consulta de grupos y alumnos</h3>
                        <p class="mg-panel-subtitle">Selecciona tu grupo y localiza alumnos por nombre.</p>
                    </div>
                    <span class="mg-count"><i class="bi bi-sliders" aria-hidden="true"></i>Filtros de consulta</span>
                </div>
                <div class="mg-panel-body">
                    <div class="row g-3 align-items-end">
                        @if($asignaciones->count() > 1)
                            <div class="col-12 col-lg-6">
                                <form method="GET" action="{{ route('catequista.mi_grupo') }}" id="mgGroupForm">
                                    <label for="mgGroupSelect" class="mg-field-label">Grupo asignado</label>
                                    <select id="mgGroupSelect" name="asignacion_id" class="form-select mg-control" onchange="this.form.submit()">
                                        <option value="" @selected(!$asignacion)>Selecciona un grupo...</option>
                                        @foreach($asignaciones as $asig)
                                            <option value="{{ $asig->asignacion_id }}" @selected((int) $asignacionId === (int) $asig->asignacion_id)>{{ $asig->texto_asignacion }}</option>
                                        @endforeach
                                    </select>
                                    <noscript><button type="submit" class="btn btn-primary btn-sm mt-2">Ver grupo</button></noscript>
                                </form>
                            </div>
                        @endif
                        @if($asignacion)
                            <div class="col-12 {{ $asignaciones->count() > 1 ? 'col-lg-6' : '' }}">
                                <label for="mgBuscarAlumno" class="mg-field-label">Buscar alumno en este grupo</label>
                                <div class="mg-find-actions">
                                    <input id="mgBuscarAlumno" type="search" class="form-control mg-control" autocomplete="off" placeholder="Escribe el nombre del alumno..." aria-controls="mgStudentGrid">
                                    <button type="button" id="mgLimpiarBusqueda" class="mg-clear" aria-label="Limpiar búsqueda" title="Limpiar búsqueda"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($asignacion)
            <section class="mg-panel" aria-labelledby="mgListTitle">
                <div class="mg-panel-head">
                    <div>
                        <h3 class="mg-panel-title" id="mgListTitle"><span class="mg-panel-icon"><i class="bi bi-people-fill" aria-hidden="true"></i></span>Lista de alumnos y asistencia</h3>
                        <p class="mg-panel-subtitle">Alumnos inscritos en la asignación seleccionada.</p>
                    </div>
                    <div class="mg-actions">
                        <span class="mg-count" aria-live="polite"><i class="bi bi-people" aria-hidden="true"></i><span id="mgVisibleCount">{{ $alumnos->count() }}</span> de {{ $alumnos->count() }} alumnos</span>
                        <a class="mg-export" href="{{ route('catequista.asistencia.pdf', ['asignacion_id' => $asignacion->asignacion_id]) }}"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>Descargar PDF de asistencia</a>
                    </div>
                </div>
                <div class="mg-panel-body">
                    @if($alumnos->isEmpty())
                        <div class="mg-empty"><span class="mg-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></span><h3>No hay alumnos inscritos</h3><p>En este momento no aparecen alumnos vinculados al grupo seleccionado.</p></div>
                    @else
                        <div class="mg-grid" id="mgStudentGrid">
                            @foreach($alumnos as $index => $alumno)
                                <article class="mg-student" data-mg-student data-search="{{ $alumno->alumno }}">
                                    <div class="mg-person">
                                        <span class="mg-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                                        <div class="mg-person-main">
                                            <h4 class="mg-name">{{ $alumno->alumno }}</h4>
                                            <span class="mg-person-desc">Alumno #{{ $index + 1 }} de la lista</span>
                                        </div>
                                    </div>
                                    <div class="mg-tags"><span class="mg-tag"><i class="bi bi-person-check" aria-hidden="true"></i>Alumno inscrito</span><span class="mg-tag green"><i class="bi bi-check-circle" aria-hidden="true"></i>En tu grupo</span></div>
                                    <div class="mg-student-info">
                                        <div class="mg-info-line"><i class="bi bi-collection" aria-hidden="true"></i><span>Grupo: <strong>{{ $asignacion->grupo }}</strong></span></div>
                                        <div class="mg-info-line"><i class="bi bi-layers" aria-hidden="true"></i><span>Nivel: <strong>{{ $asignacion->nivel }}</strong></span></div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="mg-empty mg-search-empty mt-3" id="mgNoSearchResults" hidden><span class="mg-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></span><h3>No se encontraron alumnos</h3><p>Intenta escribir otro nombre o limpia la búsqueda para mostrar toda la lista.</p></div>
                    @endif
                </div>
            </section>
        @else
            <section class="mg-panel" aria-labelledby="mgNoGroupTitle"><div class="mg-panel-body"><div class="mg-empty"><span class="mg-empty-icon"><i class="bi bi-collection" aria-hidden="true"></i></span>
                        @if($asignaciones->count() > 1)
                            <h3 id="mgNoGroupTitle">Selecciona una asignación</h3><p>Tienes varios grupos disponibles. Elige uno en el selector anterior para consultar su información y lista de alumnos.</p>
                        @else
                            <h3 id="mgNoGroupTitle">Sin grupo asignado</h3><p>Todavía no tienes un grupo disponible para este periodo. Si consideras que debería aparecer, comunícate con Secretaría.</p>
                        @endif
                    </div></div></section>
        @endif
    </div>

    <script>
        (() => {
            const root = document.getElementById('mgMiGrupo');
            const input = root?.querySelector('#mgBuscarAlumno');
            const reset = root?.querySelector('#mgLimpiarBusqueda');
            const cards = Array.from(root?.querySelectorAll('[data-mg-student]') ?? []);
            if (!input || !cards.length) return;
            const counter = root.querySelector('#mgVisibleCount');
            const empty = root.querySelector('#mgNoSearchResults');
            const normalize = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es').trim();
            const filter = () => {
                const term = normalize(input.value);
                let visible = 0;
                cards.forEach(card => {
                    const matches = normalize(card.dataset.search).includes(term);
                    card.hidden = !matches;
                    if (matches) visible++;
                });
                if (counter) counter.textContent = String(visible);
                if (empty) empty.hidden = visible !== 0;
            };
            input.addEventListener('input', filter);
            reset?.addEventListener('click', () => { input.value = ''; filter(); input.focus(); });
        })();
    </script>
@endsection
