@extends('layouts.app_parroquia_admin')

@section('title', 'Evaluaciones por Grupo - Secretaría')
@section('header_title', 'Evaluaciones por Grupo')

@section('content')
    @php
        $periodoSeleccionado = $periodos->firstWhere('id', (int) $periodoId);
        $grupoSeleccionado = $grupos->firstWhere('id', (int) $grupoId);
        $unidadSeleccionada = $unidades->firstWhere('id', (int) $unidadId);
        $rubroSeleccionado = $rubros->firstWhere('id', (int) $rubroId);
    @endphp

    <div class="card card-parroquia module-card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Captura guiada de evaluaciones</h5>
                <small class="text-muted">
                    Selecciona el periodo, grupo, unidad y rubro. Después se mostrarán únicamente los alumnos correspondientes.
                </small>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('secretaria.evaluaciones.index') }}" class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Periodo</label>
                    <select name="periodo_id" class="form-select" required>
                        <option value="">Selecciona un periodo</option>
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}" {{ (string) $periodoId === (string) $periodo->id ? 'selected' : '' }}>
                                {{ $periodo->text }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Grupo</label>
                    <select name="grupo_id" class="form-select" required>
                        <option value="">Selecciona un grupo</option>
                        @foreach($grupos as $grupo)
                            <option value="{{ $grupo->id }}" {{ (string) $grupoId === (string) $grupo->id ? 'selected' : '' }}>
                                {{ $grupo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Unidad</label>
                    <select name="unidad_id" class="form-select" required>
                        <option value="">Selecciona una unidad</option>
                        @foreach($unidades as $unidad)
                            <option value="{{ $unidad->id }}" {{ (string) $unidadId === (string) $unidad->id ? 'selected' : '' }}>
                                {{ $unidad->text }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label class="form-label">Rubro</label>
                    <select name="rubro_id" class="form-select" required>
                        <option value="">Selecciona un rubro</option>
                        @foreach($rubros as $rubro)
                            <option value="{{ $rubro->id }}" {{ (string) $rubroId === (string) $rubro->id ? 'selected' : '' }}>
                                {{ $rubro->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex flex-column flex-md-row justify-content-end gap-2 mt-2">
                    <a href="{{ route('secretaria.evaluaciones.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Limpiar
                    </a>

                    <button class="btn btn-parroquia rounded-pill px-4">
                        <i class="bi bi-search me-1"></i>
                        Buscar alumnos
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($contextoCompleto)
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-body">
                        <span class="cell-subtitle">Periodo</span>
                        <span class="cell-title">{{ $periodoSeleccionado->text ?? 'No seleccionado' }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-body">
                        <span class="cell-subtitle">Grupo</span>
                        <span class="cell-title">{{ $grupoSeleccionado->nombre ?? 'No seleccionado' }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-body">
                        <span class="cell-subtitle">Unidad</span>
                        <span class="cell-title">{{ $unidadSeleccionada->text ?? 'No seleccionada' }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                    <div class="card-body">
                        <span class="cell-subtitle">Rubro</span>
                        <span class="cell-title">{{ $rubroSeleccionado->nombre ?? 'No seleccionado' }}</span>

                        @if($rubroSeleccionado)
                            <span class="cell-subtitle mt-1">Valor: {{ number_format($rubroSeleccionado->valor, 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-parroquia module-card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h5 class="fw-bold mb-0 module-title">Alumnos del grupo</h5>
                    <small class="text-muted">
                        Captura la calificación de cada alumno. Si dejas un campo vacío, quedará pendiente o se eliminará la calificación existente.
                    </small>
                </div>

                <span class="soft-badge">
                <i class="bi bi-people"></i>
                {{ $alumnos->count() }} alumno(s)
            </span>
            </div>

            @if($alumnos->count() > 0)
                <form method="POST"
                      action="{{ route('secretaria.evaluaciones.guardarMasivo') }}"
                      class="js-confirm-save-form"
                      data-message="Se guardarán las calificaciones capturadas para este grupo.">
                    @csrf

                    <input type="hidden" name="periodo_id" value="{{ $periodoId }}">
                    <input type="hidden" name="grupo_id" value="{{ $grupoId }}">
                    <input type="hidden" name="unidad_id" value="{{ $unidadId }}">
                    <input type="hidden" name="rubro_id" value="{{ $rubroId }}">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="width: 70px;">#</th>
                                <th>Alumno</th>
                                <th class="text-center" style="width: 220px;">Calificación</th>
                                <th class="text-center" style="width: 170px;">Estado</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($alumnos as $index => $alumno)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $index + 1 }}</td>

                                    <td>
                                        <span class="cell-title">{{ $alumno->alumno_nombre }}</span>
                                        <span class="cell-subtitle">Alumno del grupo seleccionado</span>
                                    </td>

                                    <td class="text-center">
                                        <input
                                            type="number"
                                            name="calificaciones[{{ $alumno->inscripcion_id }}]"
                                            value="{{ old('calificaciones.' . $alumno->inscripcion_id, $alumno->calificacion) }}"
                                            class="form-control mx-auto text-center fw-bold"
                                            style="max-width: 140px;"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            placeholder="0 - 100"
                                        >
                                    </td>

                                    <td class="text-center">
                                        @if($alumno->evaluacion_id)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                                Registrada
                                            </span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2">
                                                Pendiente
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <small class="text-muted">
                            Revisa las calificaciones antes de guardar. Los valores deben estar entre 0 y 100.
                        </small>

                        <button class="btn btn-parroquia rounded-pill px-4">
                            <i class="bi bi-save me-1"></i>
                            Guardar evaluaciones
                        </button>
                    </div>
                </form>
            @else
                <div class="card-body">
                    <div class="text-center p-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h6 class="fw-bold text-dark">No hay alumnos inscritos</h6>
                        <p class="text-muted mb-0">
                            No se encontraron alumnos para el periodo y grupo seleccionados.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="card border-0 shadow-sm" style="border-radius: 18px;">
            <div class="card-body text-center p-5">
                <i class="bi bi-funnel fs-1 text-primary d-block mb-3"></i>
                <h5 class="fw-bold module-title">Selecciona los filtros para iniciar</h5>
                <p class="text-muted mb-0">
                    Para capturar evaluaciones, primero selecciona periodo, grupo, unidad y rubro.
                </p>
            </div>
        </div>
    @endif
@endsection
