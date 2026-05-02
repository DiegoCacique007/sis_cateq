@extends('layouts.app_parroquia_admin')

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
                    <div class="col-md-9">
                        <label class="form-label">Unidad a evaluar</label>
                        <select name="unidad_id" class="form-select" required>
                            <option value="">Selecciona una unidad</option>
                            @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id }}" {{ (string) $unidadId === (string) $unidad->id ? 'selected' : '' }}>
                                    {{ $unidad->text }}
                                </option>
                            @endforeach
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
                                Captura calificaciones de 0 a 10. El sistema convierte cada rubro según su valor y calcula el resultado final.
                            </small>
                        </div>

                        <span class="soft-badge">
                            <i class="bi bi-percent"></i>
                            Suma de rubros: {{ number_format($totalRubros, 1) }} / 10.0
                        </span>
                    </div>

                    @if($totalRubros != 10.0)
                        <div class="alert alert-warning border-0 rounded-0 mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Los rubros actualmente suman <strong>{{ number_format($totalRubros, 1) }}</strong>.
                            El sistema normalizará el resultado para expresarlo en escala de 0 a 10.
                        </div>
                    @endif

                    @if($alumnos->count() > 0)
                        <form method="POST"
                              action="{{ route('catequista.evaluaciones.guardar') }}"
                              class="js-confirm-save-form"
                              data-message="Se guardarán las calificaciones capturadas para este grupo.">
                            @csrf

                            <input type="hidden" name="unidad_id" value="{{ $unidadSeleccionada->id }}">

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th style="min-width: 260px;">Alumno</th>

                                        @foreach($rubros as $rubro)
                                            <th class="text-center" style="min-width: 155px;">
                                                {{ $rubro->nombre }}
                                                <span class="cell-subtitle">
                                                        Valor: {{ number_format($rubro->valor, 1) }}
                                                    </span>
                                            </th>
                                        @endforeach

                                        <th class="text-center" style="min-width: 160px;">Resultado</th>
                                        <th class="text-center" style="min-width: 140px;">Dictamen</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($alumnos as $alumno)
                                        <tr class="js-alumno-row"
                                            data-total-rubros="{{ $totalRubros > 0 ? $totalRubros : 10 }}">
                                            <td>
                                                <span class="cell-title">{{ $alumno->alumno_nombre }}</span>
                                                <span class="cell-subtitle">Alumno de tu grupo asignado</span>
                                            </td>

                                            @foreach($rubros as $rubro)
                                                @php
                                                    $dataRubro = $alumno->calificaciones[$rubro->id] ?? null;
                                                    $calificacion = $dataRubro['calificacion'] ?? null;
                                                    $aporte = $dataRubro['aporte'] ?? null;
                                                @endphp

                                                <td class="text-center">
                                                    <input
                                                        type="number"
                                                        name="calificaciones[{{ $alumno->inscripcion_id }}][{{ $rubro->id }}]"
                                                        value="{{ old('calificaciones.' . $alumno->inscripcion_id . '.' . $rubro->id, $calificacion) }}"
                                                        class="form-control text-center fw-bold mx-auto js-calificacion-input"
                                                        style="max-width: 105px;"
                                                        min="0"
                                                        max="{{ $rubro->valor }}"
                                                        step="0.1"
                                                        placeholder="0-{{ number_format($rubro->valor, 1) }}"
                                                        data-valor="{{ $rubro->valor }}"
                                                        data-aporte-target="aporte-{{ $alumno->inscripcion_id }}-{{ $rubro->id }}"
                                                    >

                                                    <span class="cell-subtitle mt-1">
                                                            Aporte:
                                                            <strong id="aporte-{{ $alumno->inscripcion_id }}-{{ $rubro->id }}">
                                                                {{ $aporte !== null ? number_format($aporte, 2) : '0.00' }}
                                                            </strong>
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

                                                <span class="cell-subtitle mt-1">
                                                        Puntos:
                                                        <span class="js-puntos">
                                                            {{ number_format($alumno->puntos, 2) }}
                                                        </span>
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
                                    Cada rubro se captura sobre su valor máximo. El resultado final se obtiene sumando los puntos capturados. Aprobado desde 6.0.
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
                return;
            }

            if (capturados < total) {
                estado.classList.add('bg-info', 'bg-opacity-10', 'text-info', 'border', 'border-info', 'border-opacity-25');
                estado.textContent = 'Parcial';
                return;
            }

            if (promedio < 6) {
                estado.classList.add('bg-danger', 'bg-opacity-10', 'text-danger', 'border', 'border-danger', 'border-opacity-25');
                estado.textContent = 'Reprobado';
                return;
            }

            estado.classList.add('bg-success', 'bg-opacity-10', 'text-success', 'border', 'border-success', 'border-opacity-25');
            estado.textContent = 'Aprobado';
        }

        function recalcularFila(row) {
            const totalRubros = parseFloat(row.dataset.totalRubros || '10');
            const inputs = row.querySelectorAll('.js-calificacion-input');

            let sumaAportes = 0;
            let capturados = 0;

            inputs.forEach(function (input) {
                const valorRubro = parseFloat(input.dataset.valor || '0');
                const calificacion = parseFloat(input.value);
                const aporteTarget = document.getElementById(input.dataset.aporteTarget);

                let aporte = 0;

                if (!Number.isNaN(calificacion)) {
                    const calificacionLimitada = Math.max(0, Math.min(valorRubro, calificacion));
                    aporte = calificacionLimitada;
                    sumaAportes += aporte;
                    capturados++;
                }

                if (aporteTarget) {
                    aporteTarget.textContent = aporte.toFixed(2);
                }
            });

            const promedio = totalRubros > 0 ? (sumaAportes / totalRubros) * 10 : 0;

            const promedioTarget = row.querySelector('.js-promedio');
            const puntosTarget = row.querySelector('.js-puntos');

            if (promedioTarget) {
                promedioTarget.textContent = promedio.toFixed(2);
            }

            if (puntosTarget) {
                puntosTarget.textContent = sumaAportes.toFixed(2);
            }

            actualizarEstado(row, capturados, inputs.length, promedio);
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-alumno-row').forEach(function (row) {
                recalcularFila(row);
            });

            document.querySelectorAll('.js-calificacion-input').forEach(function (input) {
                input.addEventListener('input', function () {
                    const row = input.closest('.js-alumno-row');

                    if (row) {
                        recalcularFila(row);
                    }
                });
            });
        });
    </script>
@endpush
