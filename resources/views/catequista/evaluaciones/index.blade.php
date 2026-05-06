@extends('layouts.app_parroquia_catequista')

@section('title', 'Captura de Calificaciones - Catequista')
@section('header_title', 'Captura de Calificaciones')

@section('content')
    @if($asignacion)
        <div class="card card-parroquia module-card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 module-title">Grupo detectado automáticamente</h5>
                <small class="text-muted">
                    Solo se muestran los alumnos del grupo asignado a la catequista.
                </small>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                            <div class="card-body">
                                <span class="cell-subtitle">Comunidad</span>
                                <span class="cell-title">{{ $asignacion->comunidad }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                            <div class="card-body">
                                <span class="cell-subtitle">Grupo</span>
                                <span class="cell-title">{{ $asignacion->grupo }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                            <div class="card-body">
                                <span class="cell-subtitle">Nivel</span>
                                <span class="cell-title">{{ $asignacion->nivel }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                            <div class="card-body">
                                <span class="cell-subtitle">Periodo</span>
                                <span class="cell-title">{{ $asignacion->periodo }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('catequista.evaluaciones.index') }}" class="row g-3">
                    @if($asignaciones->count() > 1)
                        <div class="col-md-5">
                            <label class="form-label">Grupo asignado</label>
                            <select name="asignacion_id" class="form-select" onchange="this.form.submit()">
                                @foreach($asignaciones as $asig)
                                    <option value="{{ $asig->asignacion_id }}" {{ (int) $asignacionId === (int) $asig->asignacion_id ? 'selected' : '' }}>
                                        {{ $asig->texto_asignacion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                    @else
                        <input type="hidden" name="asignacion_id" value="{{ $asignacionId }}">
                        <div class="col-md-9">
                    @endif
                        <label class="form-label">Unidad a evaluar</label>
                        <select name="unidad_id" class="form-select" required>
                            <option value="">Selecciona una unidad</option>
                            @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id }}" {{ (string) $unidadId === (string) $unidad->id ? 'selected' : '' }}>
                                    {{ $unidad->text }}
                                </option>
                            @endforeach
                            <option value="final" {{ $unidadId === 'final' ? 'selected' : '' }}>Resumen Final de Nivel</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-grid align-self-end">
                        <button class="btn btn-parroquia">
                            <i class="bi bi-search me-1"></i>
                            Cargar alumnos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($unidadSeleccionada)
            @if($unidadId === 'final')
                <div class="card card-parroquia module-card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 module-title">
                            {{ $unidadSeleccionada->text }}
                        </h5>
                        <small class="text-muted">
                            Promedios obtenidos en cada unidad y promedio general del nivel. Aprobado desde 6.0.
                        </small>
                    </div>

                    @if($alumnos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="min-width: 260px;">Alumno</th>
                                    @foreach($unidades as $unidad)
                                        <th class="text-center" style="min-width: 120px;">
                                            {{ $unidad->nombre }}
                                        </th>
                                    @endforeach
                                    <th class="text-center" style="min-width: 160px;">Promedio Final</th>
                                    <th class="text-center" style="min-width: 140px;">Dictamen</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($alumnos as $alumno)
                                    <tr>
                                        <td>
                                            <span class="cell-title">{{ $alumno->alumno_nombre }}</span>
                                        </td>
                                        @foreach($unidades as $unidad)
                                            <td class="text-center">
                                                @if($alumno->promedios_unidad[$unidad->id] !== null)
                                                    <span class="fw-bold {{ $alumno->promedios_unidad[$unidad->id] >= 6 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($alumno->promedios_unidad[$unidad->id], 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            @if($alumno->promedio_final !== null)
                                                <span class="soft-badge">
                                                    <i class="bi bi-calculator"></i>
                                                    {{ number_format($alumno->promedio_final, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($alumno->promedio_final === null)
                                                <span class="badge rounded-pill px-3 py-2 bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                                    Pendiente
                                                </span>
                                            @elseif($alumno->promedio_final >= 6)
                                                <span class="badge rounded-pill px-3 py-2 bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    Aprobado
                                                </span>
                                            @else
                                                <span class="badge rounded-pill px-3 py-2 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                    Reprobado
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="card-body">
                            <div class="text-center p-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                <h5 class="fw-bold">No hay alumnos inscritos</h5>
                                <p class="text-muted mb-0">
                                    No se encontraron alumnos para tu grupo asignado.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                @if($rubros->count() === 0)
                    <div class="card border-0 shadow-sm" style="border-radius: 18px;">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-ui-checks-grid fs-1 text-muted d-block mb-3"></i>
                            <h5 class="fw-bold">No hay rubros registrados</h5>
                            <p class="text-muted mb-0">
                                Secretaría debe registrar rubros antes de capturar calificaciones.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="card card-parroquia module-card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>
                                <h5 class="fw-bold mb-0 module-title">
                                    {{ $unidadSeleccionada->text }}
                                </h5>
                                <small class="text-muted">
                                    Ingresa lo <strong>obtenido</strong> y el <strong>total posible</strong> para cada rubro. El sistema calcula automáticamente con regla de 3.
                                </small>
                            </div>

                            <span class="soft-badge">
                                <i class="bi bi-percent"></i>
                                Suma de rubros: {{ number_format($totalRubros, 1) }} / 10.0
                            </span>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger border-0 rounded-0 mb-0">
                                <i class="bi bi-x-circle me-1"></i>
                                <strong>No se pudieron guardar las calificaciones:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($alumnos->count() > 0)
                            <form method="POST"
                                  action="{{ route('catequista.evaluaciones.guardar') }}"
                                  class="js-confirm-save-form"
                                  data-message="Se guardarán las calificaciones capturadas para este grupo.">
                                @csrf

                                <input type="hidden" name="asignacion_id" value="{{ $asignacionId }}">
                                <input type="hidden" name="unidad_id" value="{{ $unidadSeleccionada->id }}">

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                        <tr>
                                            <th style="min-width: 220px;">Alumno</th>

                                            @foreach($rubros as $rubro)
                                                <th class="text-center" style="min-width: 180px;">
                                                    {{ $rubro->nombre }}
                                                    <span class="cell-subtitle">
                                                        Valor máx: {{ number_format($rubro->valor, 1) }}
                                                    </span>
                                                </th>
                                            @endforeach

                                            <th class="text-center" style="min-width: 140px;">Resultado</th>
                                            <th class="text-center" style="min-width: 120px;">Dictamen</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        @foreach($alumnos as $alumno)
                                            <tr class="js-alumno-row"
                                                data-total-rubros="{{ $totalRubros > 0 ? $totalRubros : 10 }}">
                                                <td>
                                                    <span class="cell-title">{{ $alumno->alumno_nombre }}</span>
                                                </td>

                                                @foreach($rubros as $rubro)
                                                    @php
                                                        $dataRubro = $alumno->calificaciones[$rubro->id] ?? null;
                                                        $calificacion = $dataRubro['calificacion'] ?? null;
                                                    @endphp

                                                    <td class="text-center">
                                                        {{-- Hidden input que envía el valor calculado al backend --}}
                                                        <input type="hidden"
                                                            name="calificaciones[{{ $alumno->inscripcion_id }}][{{ $rubro->id }}]"
                                                            class="js-hidden-calificacion"
                                                            value="{{ $calificacion }}"
                                                            data-valor="{{ $rubro->valor }}"
                                                        >

                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <input type="number"
                                                                class="form-control form-control-sm text-center fw-bold js-obtenido"
                                                                style="width: 58px;"
                                                                step="any"
                                                                placeholder="0"
                                                                data-rubro-valor="{{ $rubro->valor }}"
                                                                data-inscripcion="{{ $alumno->inscripcion_id }}"
                                                                data-rubro="{{ $rubro->id }}"
                                                            >
                                                            <span class="text-muted fw-bold">/</span>
                                                            <input type="number"
                                                                class="form-control form-control-sm text-center fw-bold js-total-posible"
                                                                style="width: 58px;"
                                                                step="any"
                                                                placeholder="0"
                                                                data-rubro-valor="{{ $rubro->valor }}"
                                                                data-inscripcion="{{ $alumno->inscripcion_id }}"
                                                                data-rubro="{{ $rubro->id }}"
                                                            >
                                                        </div>
                                                        <span class="cell-subtitle mt-1 d-block">
                                                            = <strong class="js-resultado-rubro text-primary" id="res-{{ $alumno->inscripcion_id }}-{{ $rubro->id }}">
                                                                {{ $calificacion !== null ? number_format($calificacion, 2) : '—' }}
                                                            </strong>
                                                            <span class="text-muted">/ {{ number_format($rubro->valor, 1) }}</span>
                                                        </span>
                                                    </td>
                                                @endforeach

                                                <td class="text-center">
                                                    <span class="soft-badge">
                                                        <i class="bi bi-calculator"></i>
                                                        <span class="js-promedio">
                                                            {{ $alumno->promedio !== null ? number_format($alumno->promedio, 2) : '0.00' }}
                                                        </span>
                                                    </span>
                                                    <span class="cell-subtitle mt-1 d-block">
                                                        Puntos: <span class="js-puntos">{{ number_format($alumno->puntos, 2) }}</span>
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    <span class="badge rounded-pill px-3 py-2 js-estado-row">
                                                        Pendiente
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="card-footer bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Escribe cuántas obtuvo el alumno y de cuántas posibles. Ej: asistió <strong>4</strong> de <strong>5</strong> sesiones → el sistema calcula automáticamente.
                                    </small>

                                    <button class="btn btn-parroquia rounded-pill px-4">
                                        <i class="bi bi-save me-1"></i>
                                        Guardar calificaciones
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="card-body">
                                <div class="text-center p-5">
                                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                    <h5 class="fw-bold">No hay alumnos inscritos</h5>
                                    <p class="text-muted mb-0">
                                        No se encontraron alumnos para tu grupo asignado.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        @else
            <div class="card border-0 shadow-sm" style="border-radius: 18px;">
                <div class="card-body text-center p-5">
                    <i class="bi bi-funnel fs-1 text-primary d-block mb-3"></i>
                    <h5 class="fw-bold module-title">Selecciona una unidad</h5>
                    <p class="text-muted mb-0">
                        El grupo ya fue detectado. Ahora selecciona la unidad que deseas evaluar.
                    </p>
                </div>
            </div>
        @endif
    @else
        <div class="card border-0 shadow-sm" style="border-radius: 18px;">
            <div class="card-body text-center p-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5 class="fw-bold">Sin grupo asignado</h5>
                <p class="text-muted mb-0">
                    Todavía no tienes un grupo asignado por Secretaría.
                </p>
            </div>
        </div>
    @endif
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
