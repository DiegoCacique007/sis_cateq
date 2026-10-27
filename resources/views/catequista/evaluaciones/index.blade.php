@extends('layouts.app_parroquia_catequista')

@section('title', 'Captura de Calificaciones - Catequista')
@section('header_title', 'Captura de Calificaciones')
@section('header_subtitle', 'Consulta de grupos, captura por rubros y resumen académico del nivel')

@section('content')
    <style>
        #ecEvaluaciones{--ec-blue:#3499e7;--ec-blue-dark:#267fc9;--ec-navy:#1e3a8a;--ec-border:#e2e8f0;--ec-muted:#64748b;color:#334155;font-family:'Inter','Segoe UI','Roboto',sans-serif}
        #ecEvaluaciones *{box-sizing:border-box}
        #ecEvaluaciones .ec-intro{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
        #ecEvaluaciones .ec-title{font-size:1.25rem;color:var(--ec-navy);font-weight:800;letter-spacing:-.025em;margin:0 0 4px}
        #ecEvaluaciones .ec-description{font-size:.85rem;color:var(--ec-muted);margin:0}
        #ecEvaluaciones .ec-period{display:inline-flex;gap:7px;align-items:center;padding:8px 13px;background:#eaf6ff;border:1px solid #cfe8fc;border-radius:30px;color:#24679c;font-size:.77rem;font-weight:700}
        #ecEvaluaciones .ec-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:20px}
        #ecEvaluaciones .ec-stat{min-width:0;display:flex;align-items:center;gap:13px;padding:18px 16px;background:#fff;border:1px solid var(--ec-border);border-radius:16px;box-shadow:0 3px 13px rgba(30,58,138,.045);transition:transform .2s,box-shadow .2s}
        #ecEvaluaciones .ec-stat:hover{transform:translateY(-3px);box-shadow:0 10px 22px rgba(30,58,138,.105)}
        #ecEvaluaciones .ec-stat-icon{width:50px;height:50px;flex:0 0 50px;border-radius:14px;color:#fff;display:grid;place-items:center;font-size:1.3rem}
        #ecEvaluaciones .ec-stat-info{min-width:0}
        #ecEvaluaciones .ec-stat-label{display:block;font-size:.76rem;font-weight:700;color:var(--ec-muted);margin-bottom:4px}
        #ecEvaluaciones .ec-stat-value{display:block;color:#173660;font-size:.97rem;font-weight:800;line-height:1.32;overflow-wrap:anywhere}
        #ecEvaluaciones .ec-blue .ec-stat-icon{background:linear-gradient(135deg,#67c0fa,#298de0)}
        #ecEvaluaciones .ec-amber .ec-stat-icon{background:linear-gradient(135deg,#f8ca69,#e59b27)}
        #ecEvaluaciones .ec-green .ec-stat-icon{background:linear-gradient(135deg,#54d9ad,#139b76)}
        #ecEvaluaciones .ec-cyan .ec-stat-icon{background:linear-gradient(135deg,#68d8ed,#0ca1bd)}
        #ecEvaluaciones .ec-panel{background:#fff;border:1px solid var(--ec-border);border-radius:18px;box-shadow:0 3px 14px rgba(30,58,138,.055);overflow:hidden;margin-bottom:20px}
        #ecEvaluaciones .ec-panel-head{padding:19px 23px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-bottom:1px solid #edf2f7}
        #ecEvaluaciones .ec-panel-name{display:flex;align-items:center;gap:11px;min-width:0}
        #ecEvaluaciones .ec-panel-icon{width:41px;height:41px;border-radius:12px;display:grid;place-items:center;flex:none;background:#e9f5ff;color:var(--ec-blue-dark);font-size:1.08rem}
        #ecEvaluaciones .ec-panel-title{font-size:.99rem;font-weight:800;color:var(--ec-navy);margin:0 0 3px}
        #ecEvaluaciones .ec-panel-subtitle{font-size:.78rem;color:var(--ec-muted);margin:0;line-height:1.5}
        #ecEvaluaciones .ec-panel-body{padding:21px 23px}
        #ecEvaluaciones .ec-tag{display:inline-flex;align-items:center;gap:7px;font-weight:700;font-size:.77rem;color:#297ac0;background:#edf7ff;border:1px solid #d4eafa;border-radius:30px;padding:7px 11px;white-space:nowrap}
        #ecEvaluaciones .ec-form-label{display:block;color:#475569;font-size:.8rem;font-weight:700;margin-bottom:8px}
        #ecEvaluaciones .ec-control{min-height:43px;border-color:#d9e4ef;border-radius:10px;font-size:.88rem;color:#334155}
        #ecEvaluaciones .ec-control:focus{border-color:#49a8ed;box-shadow:0 0 0 3px rgba(73,168,237,.12)}
        #ecEvaluaciones .ec-primary{min-height:43px;display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(125deg,#49a8ed,#267fc9);border:0;border-radius:10px;color:#fff;font-size:.84rem;font-weight:750;padding:10px 17px;box-shadow:0 6px 14px rgba(38,127,201,.14)}
        #ecEvaluaciones .ec-primary:hover{background:linear-gradient(125deg,#3d9fdf,#1e71b8);color:#fff}
        #ecEvaluaciones .ec-primary:focus-visible{outline:3px solid #9ad5ff;outline-offset:2px}
        #ecEvaluaciones .ec-table-wrap{overflow-x:auto}
        #ecEvaluaciones .ec-table{width:100%;margin:0;font-size:.82rem;vertical-align:middle}
        #ecEvaluaciones .ec-table thead th{background:#eef7ff;color:#265784;font-size:.76rem;font-weight:800;letter-spacing:.012em;border-bottom:1px solid #dcecf9;padding:15px 14px;vertical-align:middle;white-space:normal}
        #ecEvaluaciones .ec-table tbody td{padding:14px;border-bottom:1px solid #eef2f7;color:#475569}
        #ecEvaluaciones .ec-table tbody tr:last-child td{border-bottom:0}
        #ecEvaluaciones .ec-table tbody tr:hover td{background:#f9fcff}
        #ecEvaluaciones .ec-person{display:flex;align-items:center;gap:10px;min-width:220px}
        #ecEvaluaciones .ec-avatar{width:36px;height:36px;flex:none;border-radius:11px;background:#e8f3ff;color:#308bd4;display:grid;place-items:center;font-size:1rem}
        #ecEvaluaciones .ec-person-name{font-weight:750;color:#1f427c;line-height:1.35;overflow-wrap:anywhere}
        #ecEvaluaciones .ec-person-sub{display:block;font-size:.69rem;color:#94a3b8;margin-top:2px}
        #ecEvaluaciones .ec-table-note{display:block;font-size:.7rem;font-weight:500;color:#8190a3;margin-top:3px}
        #ecEvaluaciones .ec-score{font-size:.88rem;font-weight:800;font-variant-numeric:tabular-nums;color:#235b9b}
        #ecEvaluaciones .ec-score-badge{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;background:#e9f5ff;color:#236db0;border:1px solid #cee7fb;border-radius:9px;font-weight:800;padding:7px 11px;font-variant-numeric:tabular-nums}
        #ecEvaluaciones .ec-muted{color:#94a3b8}
        #ecEvaluaciones .ec-status{display:inline-flex;align-items:center;justify-content:center;border-radius:30px;padding:6px 10px;font-size:.72rem;font-weight:750;white-space:nowrap}
        #ecEvaluaciones .ec-status-pending{background:#fff8e8;color:#ad7514;border:1px solid #f7e3ad}
        #ecEvaluaciones .ec-status-ok{background:#e9f9f0;color:#18784e;border:1px solid #bcebd2}
        #ecEvaluaciones .ec-status-bad{background:#fff1f2;color:#b93749;border:1px solid #f8d1d7}
        #ecEvaluaciones .ec-input-pair{display:flex;justify-content:center;align-items:center;gap:6px;white-space:nowrap}
        #ecEvaluaciones .ec-grade-input{width:67px;min-height:37px;padding:6px 5px;border-radius:9px;border:1px solid #d7e4f1;background:#fff;color:#334155;text-align:center;font-weight:750;font-size:.83rem}
        #ecEvaluaciones .ec-grade-input:focus{outline:none;border-color:#3499e7;box-shadow:0 0 0 3px rgba(52,153,231,.12)}
        #ecEvaluaciones .ec-rubro-result{display:block;text-align:center;margin-top:6px;color:#64748b;font-size:.69rem}
        #ecEvaluaciones .ec-rubro-result strong{color:#237ec4}
        #ecEvaluaciones .ec-save-bar{padding:16px 23px;border-top:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;background:#fbfdff}
        #ecEvaluaciones .ec-save-note{font-size:.77rem;line-height:1.6;color:#64748b;max-width:720px;margin:0}
        #ecEvaluaciones .ec-empty{text-align:center;padding:48px 24px;background:#fff}
        #ecEvaluaciones .ec-empty-icon{width:70px;height:70px;margin:0 auto 17px;border-radius:20px;background:#eef7ff;color:#3499e7;display:grid;place-items:center;font-size:2rem}
        #ecEvaluaciones .ec-empty h3{color:#1e3a8a;font-size:1.06rem;font-weight:800;margin:0 0 8px}
        #ecEvaluaciones .ec-empty p{font-size:.82rem;color:#64748b;max-width:510px;margin:0 auto;line-height:1.65}
        #ecEvaluaciones .ec-validation{padding:15px 23px;margin:0;border:0;border-bottom:1px solid #fecaca;border-radius:0;font-size:.82rem}
        #ecEvaluaciones .ec-validation ul{margin-bottom:0}
        @media(max-width:1200px){#ecEvaluaciones .ec-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:650px){#ecEvaluaciones .ec-stats{gap:10px}#ecEvaluaciones .ec-stat{padding:12px 10px;gap:9px}#ecEvaluaciones .ec-stat-icon{width:40px;height:40px;flex-basis:40px;border-radius:11px;font-size:1rem}#ecEvaluaciones .ec-stat-value{font-size:.85rem}#ecEvaluaciones .ec-stat-label{font-size:.7rem}#ecEvaluaciones .ec-panel-head,#ecEvaluaciones .ec-panel-body,#ecEvaluaciones .ec-save-bar{padding:16px}#ecEvaluaciones .ec-table thead th,#ecEvaluaciones .ec-table tbody td{padding:12px}#ecEvaluaciones .ec-primary{width:100%}}
        @media(prefers-reduced-motion:reduce){#ecEvaluaciones .ec-stat{transition:none}#ecEvaluaciones .ec-stat:hover{transform:none}}
    </style>

    <div id="ecEvaluaciones">
        <header class="ec-intro">
            <div><h2 class="ec-title">Captura de calificaciones</h2><p class="ec-description">Administra las evaluaciones de tus alumnos por unidad y consulta su resumen final.</p></div>
            @if($asignacion)<span class="ec-period"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $asignacion->periodo }}</span>@endif
        </header>

        @if($asignacion)
            <section class="ec-stats" aria-label="Información del grupo asignado">
                <article class="ec-stat ec-blue"><span class="ec-stat-icon"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span><div class="ec-stat-info"><span class="ec-stat-label">Comunidad catequética</span><strong class="ec-stat-value">{{ $asignacion->comunidad }}</strong></div></article>
                <article class="ec-stat ec-amber"><span class="ec-stat-icon"><i class="bi bi-collection-fill" aria-hidden="true"></i></span><div class="ec-stat-info"><span class="ec-stat-label">Grupo</span><strong class="ec-stat-value">{{ $asignacion->grupo }}</strong></div></article>
                <article class="ec-stat ec-green"><span class="ec-stat-icon"><i class="bi bi-layers-fill" aria-hidden="true"></i></span><div class="ec-stat-info"><span class="ec-stat-label">Nivel</span><strong class="ec-stat-value">{{ $asignacion->nivel }}</strong></div></article>
                <article class="ec-stat ec-cyan"><span class="ec-stat-icon"><i class="bi bi-calendar-event-fill" aria-hidden="true"></i></span><div class="ec-stat-info"><span class="ec-stat-label">Periodo</span><strong class="ec-stat-value">{{ $asignacion->periodo }}</strong></div></article>
            </section>

            <section class="ec-panel" aria-labelledby="ecFiltrosTitulo">
                <div class="ec-panel-head"><div class="ec-panel-name"><span class="ec-panel-icon"><i class="bi bi-funnel-fill" aria-hidden="true"></i></span><div><h3 id="ecFiltrosTitulo" class="ec-panel-title">Filtros de evaluación</h3><p class="ec-panel-subtitle">Selecciona el grupo y la unidad que deseas consultar o evaluar.</p></div></div><span class="ec-tag"><i class="bi bi-sliders" aria-hidden="true"></i>Consulta académica</span></div>
                <div class="ec-panel-body">
                    <form method="GET" action="{{ route('catequista.evaluaciones.index') }}" class="row g-3 align-items-end">
                        @if($asignaciones->count() > 1)
                            <div class="col-12 col-lg-5"><label for="ecAsignacion" class="ec-form-label">Grupo asignado</label><select id="ecAsignacion" name="asignacion_id" class="form-select ec-control" onchange="this.form.submit()">@foreach($asignaciones as $asig)<option value="{{ $asig->asignacion_id }}" @selected((int) $asignacionId === (int) $asig->asignacion_id)>{{ $asig->texto_asignacion }}</option>@endforeach</select></div>
                        @else
                            <input type="hidden" name="asignacion_id" value="{{ $asignacionId }}">
                        @endif
                        <div class="col-12 {{ $asignaciones->count() > 1 ? 'col-lg-4' : 'col-lg-9' }}">
                            <label for="ecUnidad" class="ec-form-label">Unidad a evaluar</label>
                            <select id="ecUnidad" name="unidad_id" class="form-select ec-control" required>
                                <option value="">Selecciona una unidad</option>
                                @foreach($unidades as $unidad)<option value="{{ $unidad->id }}" @selected((string) $unidadId === (string) $unidad->id)>{{ $unidad->text }}</option>@endforeach
                                <option value="final" @selected($unidadId === 'final')>Resumen Final de Nivel</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-3 d-grid"><button type="submit" class="btn ec-primary"><i class="bi bi-search" aria-hidden="true"></i>Cargar alumnos</button></div>
                    </form>
                </div>
            </section>

            @if($unidadSeleccionada)
                @if($unidadId === 'final')
                    <section class="ec-panel" aria-labelledby="ecResumenTitulo">
                        <div class="ec-panel-head"><div class="ec-panel-name"><span class="ec-panel-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span><div><h3 class="ec-panel-title" id="ecResumenTitulo">{{ $unidadSeleccionada->text }}</h3><p class="ec-panel-subtitle">Promedios por unidad, promedio general y dictamen. Aprobado desde 6.0.</p></div></div><span class="ec-tag"><i class="bi bi-people" aria-hidden="true"></i>{{ $alumnos->count() }} alumnos</span></div>
                        @if($alumnos->count() > 0)
                            <div class="ec-table-wrap"><table class="table table-hover align-middle ec-table">
                                    <thead><tr><th style="min-width:240px">Alumno</th>@foreach($unidades as $unidad)<th class="text-center" style="min-width:115px">{{ $unidad->nombre }}</th>@endforeach<th class="text-center" style="min-width:150px">Promedio final</th><th class="text-center" style="min-width:125px">Dictamen</th></tr></thead>
                                    <tbody>
                                    @foreach($alumnos as $alumno)
                                        <tr><td><div class="ec-person"><span class="ec-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span><div><span class="ec-person-name">{{ $alumno->alumno_nombre }}</span><span class="ec-person-sub">Alumno del grupo</span></div></div></td>
                                            @foreach($unidades as $unidad)
                                                <td class="text-center">
                                                    @if(($alumno->promedios_unidad[$unidad->id] ?? null) !== null)
                                                        <span class="fw-bold {{ $alumno->promedios_unidad[$unidad->id] >= 6 ? 'text-success' : 'text-danger' }}">{{ number_format($alumno->promedios_unidad[$unidad->id], 2) }}</span>
                                                    @else<span class="ec-muted">—</span>@endif
                                                </td>
                                            @endforeach
                                            <td class="text-center">@if($alumno->promedio_final !== null)<span class="ec-score-badge"><i class="bi bi-calculator" aria-hidden="true"></i>{{ number_format($alumno->promedio_final, 2) }}</span>@else<span class="ec-muted">N/A</span>@endif</td>
                                            <td class="text-center">@if($alumno->promedio_final === null)<span class="ec-status ec-status-pending">Pendiente</span>@elseif($alumno->promedio_final >= 6)<span class="ec-status ec-status-ok">Aprobado</span>@else<span class="ec-status ec-status-bad">Reprobado</span>@endif</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table></div>
                        @else
                            <div class="ec-empty"><span class="ec-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></span><h3>No hay alumnos inscritos</h3><p>No se encontraron alumnos para tu grupo asignado.</p></div>
                        @endif
                    </section>
                @else
                    @if($rubros->count() === 0)
                        <section class="ec-panel"><div class="ec-empty"><span class="ec-empty-icon"><i class="bi bi-ui-checks-grid" aria-hidden="true"></i></span><h3>No hay rubros registrados</h3><p>Secretaría debe registrar rubros antes de capturar calificaciones.</p></div></section>
                    @else
                        <section class="ec-panel" aria-labelledby="ecCapturaTitulo">
                            <div class="ec-panel-head"><div class="ec-panel-name"><span class="ec-panel-icon"><i class="bi bi-clipboard2-check-fill" aria-hidden="true"></i></span><div><h3 class="ec-panel-title" id="ecCapturaTitulo">{{ $unidadSeleccionada->text }}</h3><p class="ec-panel-subtitle">Ingresa puntos obtenidos y total posible por rubro. El sistema calcula cada resultado con regla de tres.</p></div></div><span class="ec-tag"><i class="bi bi-percent" aria-hidden="true"></i>Rubros: {{ number_format($totalRubros, 1) }} / 10.0</span></div>
                            @if($errors->any())
                                <div class="alert alert-danger ec-validation" role="alert"><strong><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>No se pudieron guardar las calificaciones:</strong><ul class="mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            @if($alumnos->count() > 0)
                                <form method="POST" action="{{ route('catequista.evaluaciones.guardar') }}" class="js-confirm-save-form" data-message="Se guardarán las calificaciones capturadas para este grupo.">
                                    @csrf
                                    <input type="hidden" name="asignacion_id" value="{{ $asignacionId }}">
                                    <input type="hidden" name="unidad_id" value="{{ $unidadSeleccionada->id }}">
                                    <div class="ec-table-wrap"><table class="table table-hover align-middle ec-table">
                                            <thead><tr><th style="min-width:235px">Alumno</th>@foreach($rubros as $rubro)<th class="text-center" style="min-width:185px">{{ $rubro->nombre }}<span class="ec-table-note">Valor máx.: {{ number_format($rubro->valor, 1) }}</span></th>@endforeach<th class="text-center" style="min-width:145px">Resultado</th><th class="text-center" style="min-width:125px">Dictamen</th></tr></thead>
                                            <tbody>
                                            @foreach($alumnos as $alumno)
                                                <tr class="js-alumno-row" data-total-rubros="{{ $totalRubros > 0 ? $totalRubros : 10 }}">
                                                    <td><div class="ec-person"><span class="ec-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span><div><span class="ec-person-name">{{ $alumno->alumno_nombre }}</span><span class="ec-person-sub">Captura de evaluación</span></div></div></td>
                                                    @foreach($rubros as $rubro)
                                                        @php $dataRubro = $alumno->calificaciones[$rubro->id] ?? null; $calificacion = $dataRubro['calificacion'] ?? null; @endphp
                                                        <td class="text-center">
                                                            <input type="hidden" name="calificaciones[{{ $alumno->inscripcion_id }}][{{ $rubro->id }}]" class="js-hidden-calificacion" value="{{ $calificacion }}" data-valor="{{ $rubro->valor }}">
                                                            <div class="ec-input-pair"><input type="number" class="ec-grade-input js-obtenido" step="any" placeholder="0" aria-label="Obtenido en {{ $rubro->nombre }} para {{ $alumno->alumno_nombre }}" data-rubro-valor="{{ $rubro->valor }}" data-inscripcion="{{ $alumno->inscripcion_id }}" data-rubro="{{ $rubro->id }}"><span class="text-muted fw-bold">/</span><input type="number" class="ec-grade-input js-total-posible" step="any" placeholder="0" aria-label="Total posible de {{ $rubro->nombre }} para {{ $alumno->alumno_nombre }}" data-rubro-valor="{{ $rubro->valor }}" data-inscripcion="{{ $alumno->inscripcion_id }}" data-rubro="{{ $rubro->id }}"></div>
                                                            <span class="ec-rubro-result">= <strong class="js-resultado-rubro" id="res-{{ $alumno->inscripcion_id }}-{{ $rubro->id }}">{{ $calificacion !== null ? number_format($calificacion, 2) : '—' }}</strong> / {{ number_format($rubro->valor, 1) }}</span>
                                                        </td>
                                                    @endforeach
                                                    <td class="text-center"><span class="ec-score-badge"><i class="bi bi-calculator" aria-hidden="true"></i><span class="js-promedio">{{ $alumno->promedio !== null ? number_format($alumno->promedio, 2) : '0.00' }}</span></span><span class="ec-table-note mt-1">Puntos: <span class="js-puntos">{{ number_format($alumno->puntos, 2) }}</span></span></td>
                                                    <td class="text-center"><span class="badge rounded-pill px-3 py-2 js-estado-row">Pendiente</span></td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table></div>
                                    <div class="ec-save-bar"><p class="ec-save-note"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Indica cuántos puntos obtuvo cada alumno y de cuántos posibles. Por ejemplo, 4 de 5 asistencias; el cálculo se realizará automáticamente.</p><button type="submit" class="btn ec-primary"><i class="bi bi-floppy2" aria-hidden="true"></i>Guardar calificaciones</button></div>
                                </form>
                            @else
                                <div class="ec-empty"><span class="ec-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></span><h3>No hay alumnos inscritos</h3><p>No se encontraron alumnos para tu grupo asignado.</p></div>
                            @endif
                        </section>
                    @endif
                @endif
            @else
                <section class="ec-panel"><div class="ec-empty"><span class="ec-empty-icon"><i class="bi bi-clipboard2-data" aria-hidden="true"></i></span><h3>Selecciona una unidad</h3><p>El grupo está identificado. Elige una unidad en los filtros para consultar o registrar evaluaciones.</p></div></section>
            @endif
        @else
            <section class="ec-panel"><div class="ec-empty"><span class="ec-empty-icon"><i class="bi bi-collection" aria-hidden="true"></i></span><h3>Sin grupo asignado</h3><p>Todavía no tienes un grupo disponible para este periodo. Consulta con Secretaría si esperabas tener una asignación.</p></div></section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        function actualizarEstado(row, capturados, total, promedio) {
            const estado = row.querySelector('.js-estado-row');
            if (!estado) return;

            estado.className = 'badge rounded-pill px-3 py-2 js-estado-row';

            if (capturados === 0) {
                estado.classList.add('bg-warning', 'bg-opacity-10', 'text-warning', 'border', 'border-warning', 'border-opacity-25');
                estado.textContent = 'Pendiente';
            } else if (capturados < total) {
                estado.classList.add('bg-info', 'bg-opacity-10', 'text-info', 'border', 'border-info', 'border-opacity-25');
                estado.textContent = 'Parcial';
            } else if (promedio < 6) {
                estado.classList.add('bg-danger', 'bg-opacity-10', 'text-danger', 'border', 'border-danger', 'border-opacity-25');
                estado.textContent = 'Reprobado';
            } else {
                estado.classList.add('bg-success', 'bg-opacity-10', 'text-success', 'border', 'border-success', 'border-opacity-25');
                estado.textContent = 'Aprobado';
            }
        }

        /**
         * Calcula el valor de un rubro usando regla de 3:
         * (obtenido / total) * valorMaxRubro
         */
        function calcularRubro(obtenidoInput, totalInput) {
            const valorRubro = parseFloat(obtenidoInput.dataset.rubroValor || '0');
            const inscripcionId = obtenidoInput.dataset.inscripcion;
            const rubroId = obtenidoInput.dataset.rubro;

            const obtenido = parseFloat(obtenidoInput.value);
            const total = parseFloat(totalInput.value);

            const hiddenInput = obtenidoInput.closest('td').querySelector('.js-hidden-calificacion');
            const resultadoSpan = document.getElementById('res-' + inscripcionId + '-' + rubroId);

            if (!isNaN(obtenido) && !isNaN(total) && total > 0) {
                // Regla de 3: (obtenido / total) * valorMaxRubro
                let resultado = (obtenido / total) * valorRubro;
                resultado = Math.round(resultado * 100) / 100;

                if (hiddenInput) hiddenInput.value = resultado;
                if (resultadoSpan) resultadoSpan.textContent = resultado.toFixed(2);
            } else if (obtenidoInput.value === '' && totalInput.value === '') {
                if (hiddenInput) hiddenInput.value = '';
                if (resultadoSpan) resultadoSpan.textContent = '—';
            } else {
                if (hiddenInput) hiddenInput.value = '';
                if (resultadoSpan) resultadoSpan.textContent = '—';
            }
        }

        function recalcularFila(row) {
            const totalRubros = parseFloat(row.dataset.totalRubros || '10');
            const hiddenInputs = row.querySelectorAll('.js-hidden-calificacion');

            let sumaAportes = 0;
            let capturados = 0;

            hiddenInputs.forEach(function (hidden) {
                const val = parseFloat(hidden.value);
                if (!isNaN(val) && hidden.value !== '') {
                    sumaAportes += val;
                    capturados++;
                }
            });

            const promedio = totalRubros > 0 ? (sumaAportes / totalRubros) * 10 : 0;

            const promedioTarget = row.querySelector('.js-promedio');
            const puntosTarget = row.querySelector('.js-puntos');

            if (promedioTarget) promedioTarget.textContent = promedio.toFixed(2);
            if (puntosTarget) puntosTarget.textContent = sumaAportes.toFixed(2);

            actualizarEstado(row, capturados, hiddenInputs.length, promedio);
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Recalcular filas al cargar (para datos existentes)
            document.querySelectorAll('.js-alumno-row').forEach(function (row) {
                recalcularFila(row);
            });

            // Evento en inputs de "obtenido" y "total"
            document.querySelectorAll('.js-obtenido, .js-total-posible').forEach(function (input) {
                input.addEventListener('input', function () {
                    const td = input.closest('td');
                    const obtenidoInput = td.querySelector('.js-obtenido');
                    const totalInput = td.querySelector('.js-total-posible');

                    calcularRubro(obtenidoInput, totalInput);

                    const row = input.closest('.js-alumno-row');
                    if (row) recalcularFila(row);
                });
            });
        });
    </script>
@endpush
