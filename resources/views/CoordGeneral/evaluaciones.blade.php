@extends('layouts.app_parroquia_coordinador_general')

@section('title', 'Supervisión de Evaluaciones')
@section('header_title', 'Supervisión de Evaluaciones')

@section('content')
    <style>
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .filter-card .card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .filter-card .card-body {
            padding: 1.5rem;
        }

        .summary-card {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            color: #312e81;
        }

        .summary-card .summary-item {
            display: inline-block;
            margin-right: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .summary-card .summary-label {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #4338ca;
            margin-right: 0.35rem;
        }

        .summary-card .summary-val {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
            border: 2px dashed #e2e8f0;
            border-radius: 0.75rem;
            background: #f8fafc;
        }

        .empty-state i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            display: block;
        }

        .evaluaciones-table {
            min-width: 1100px;
        }

        .evaluaciones-table thead th {
            background: #f8fbff;
            color: #1e3a8a;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 800;
            border-bottom: 1px solid rgba(30, 58, 138, .12);
            padding: 1rem;
            white-space: nowrap;
        }

        .evaluaciones-table tbody td {
            vertical-align: middle;
            font-size: .92rem;
            padding: 1rem;
            white-space: nowrap;
        }

        .alumno-name {
            color: #0d6efd;
            font-weight: 800;
        }

        .rubro-score {
            font-weight: 700;
            color: #1f2937;
            text-align: center;
        }

        .rubro-empty {
            color: #94a3b8;
            font-weight: 700;
        }

        .promedio-aprobado {
            color: #198754;
            font-weight: 900;
        }

        .promedio-reprobado {
            color: #dc3545;
            font-weight: 900;
        }

        .promedio-pendiente {
            color: #64748b;
            font-weight: 800;
        }

        .badge-aprobado {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-reprobado {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .badge-pendiente {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .badge-parcial {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fef08a;
        }
    </style>

    @if(isset($error_periodo) && $error_periodo)
        <div class="alert alert-warning border-warning shadow-sm rounded-3">
            <h5 class="fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Atención</h5>
            <p class="mb-0">Debe seleccionar un periodo activo en el menú superior antes de consultar evaluaciones.</p>
        </div>
    @else
        <!-- RESUMEN VISUAL DE FILTROS ACTIVOS -->
        @if($sacramento || $nivelId || $asignacionId || $unidadId)
            <div class="summary-card shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Resumen de Consulta Actual</h6>
                <div>
                    <div class="summary-item">
                        <span class="summary-label">Periodo Activo:</span>
                        <span class="summary-val">{{ $periodoTexto ?? 'No definido' }}</span>
                    </div>
                    
                    @if($sacramento)
                        <div class="summary-item">
                            <span class="summary-label">Sacramento:</span>
                            <span class="summary-val text-capitalize">{{ str_replace('_', ' ', $sacramento) }}</span>
                        </div>
                    @endif

                    @if($nivelId)
                        @php
                            $n = $niveles->firstWhere('id', (int) $nivelId);
                        @endphp
                        <div class="summary-item">
                            <span class="summary-label">Nivel:</span>
                            <span class="summary-val">Nivel {{ $n->numero ?? $n->nivel ?? $nivelId }}</span>
                        </div>
                    @endif

                    @if($asignacionId)
                        @php
                            $a = $asignaciones->firstWhere('id', (int) $asignacionId);
                        @endphp
                        @if($a)
                            <div class="summary-item">
                                <span class="summary-label">Comunidad:</span>
                                <span class="summary-val">{{ $a->comunidad->comunidad ?? 'N/A' }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Grupo:</span>
                                <span class="summary-val">{{ $a->grupo->nombre ?? 'N/A' }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Catequista:</span>
                                <span class="summary-val">{{ $a->catequista->name ?? 'N/A' }}</span>
                            </div>
                        @endif
                    @endif

                    @if($unidadId)
                        @php
                            $u = $unidades->firstWhere('id', (int) $unidadId);
                        @endphp
                        <div class="summary-item">
                            <span class="summary-label">Unidad:</span>
                            <span class="summary-val">Unidad {{ $u->numero ?? '' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="filter-card">
            <div class="card-header">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-funnel me-2"></i>Filtros de Consulta
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('coordinador_general.evaluaciones.index') }}" id="filterForm">
                    <!-- Fila 1: Sacramento | Nivel | Grupo/Asignación -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Sacramento</label>
                            <select name="sacramento" class="form-select" onchange="resetNivel(); document.getElementById('filterForm').submit()">
                                <option value="">Selecciona un sacramento...</option>
                                <option value="primera_comunion" {{ $sacramento == 'primera_comunion' ? 'selected' : '' }}>Primera Comunión</option>
                                <option value="confirmacion" {{ $sacramento == 'confirmacion' ? 'selected' : '' }}>Confirmación</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Nivel</label>
                            <select name="nivel_id" id="nivel_id" class="form-select" onchange="resetAsignacionYUnidad(); document.getElementById('filterForm').submit()" {{ !$sacramento || $niveles->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona un nivel...</option>
                                @foreach($niveles as $nivel)
                                    <option value="{{ $nivel->id }}" {{ $nivelId == $nivel->id ? 'selected' : '' }}>
                                        Nivel {{ $nivel->numero ?? $nivel->nivel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase">Grupo / Asignación</label>
                            <select name="asignacion_id" id="asignacion_id" class="form-select" onchange="resetUnidad(); document.getElementById('filterForm').submit()" {{ !$nivelId || $asignaciones->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona una asignación...</option>
                                @foreach($asignaciones as $asig)
                                    <option value="{{ $asig->id }}" {{ $asignacionId == $asig->id ? 'selected' : '' }}>
                                        {{ $asig->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Fila 2: Unidad | Buscar | Limpiar -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase">Unidad</label>
                            <select name="unidad_id" id="unidad_id" class="form-select" {{ !$nivelId || $unidades->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona una unidad...</option>
                                @foreach($unidades as $unidad)
                                    <option value="{{ $unidad->id }}" {{ $unidadId == $unidad->id ? 'selected' : '' }}>
                                        Unidad {{ $unidad->numero }}: {{ $unidad->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-search me-1"></i> Buscar Evaluaciones
                                </button>
                                <a href="{{ route('coordinador_general.evaluaciones.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($sacramento && $nivelId && $asignacionId && $unidadId)
            <div class="card card-parroquia border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        Resultados de Evaluación
                    </h5>
                </div>
                
                <div class="card-body p-0">
                    @if($alumnos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover evaluaciones-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">No.</th>
                                        <th>Alumno</th>
                                        @foreach($rubros as $rubro)
                                            <th class="text-center">{{ $rubro->nombre }}</th>
                                        @endforeach
                                        <th class="text-center">Promedio</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($alumnos as $index => $inscripcion)
                                        @php
                                            $alumno = $inscripcion->alumno;
                                            $promedio = $promedios[$inscripcion->id] ?? null;
                                            $califs = $calificacionesMap[$inscripcion->id] ?? [];
                                            
                                            // Determinar estado
                                            if (count($califs) === 0) {
                                                $estado = 'Pendiente';
                                                $badgeClass = 'badge-pendiente';
                                                $promedioClass = 'promedio-pendiente';
                                            } elseif (count($califs) < count($rubros)) {
                                                $estado = 'Parcial';
                                                $badgeClass = 'badge-parcial';
                                                $promedioClass = 'promedio-pendiente';
                                            } else {
                                                if ($promedio !== null && $promedio >= 6.0) {
                                                    $estado = 'Aprobado';
                                                    $badgeClass = 'badge-aprobado';
                                                    $promedioClass = 'promedio-aprobado';
                                                } else {
                                                    $estado = 'Reprobado';
                                                    $badgeClass = 'badge-reprobado';
                                                    $promedioClass = 'promedio-reprobado';
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                            <td class="alumno-name">
                                                {{ $alumno->nombre }} {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }}
                                            </td>
                                            
                                            <!-- Calificaciones dinámicas por rubro -->
                                            @foreach($rubros as $rubro)
                                                <td class="text-center">
                                                    @if(isset($califs[$rubro->id]))
                                                        <span class="rubro-score">{{ number_format($califs[$rubro->id], 1) }}</span>
                                                    @else
                                                        <span class="rubro-empty">-</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            <td class="text-center">
                                                @if($promedio !== null)
                                                    <span class="{{ $promedioClass }}">
                                                        {{ number_format($promedio, 1) }}
                                                    </span>
                                                @else
                                                    <span class="promedio-pendiente">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill px-3 py-2 {{ $badgeClass }}">
                                                    {{ $estado }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state border-0 m-4">
                            <i class="bi bi-people"></i>
                            <h5 class="fw-bold text-dark">No se encontraron alumnos o evaluaciones</h5>
                            <p class="mb-0">No hay registros con los filtros seleccionados.</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="empty-state mt-4">
                <i class="bi bi-funnel"></i>
                <h4 class="fw-bold text-dark mb-2">Selecciona los filtros para iniciar</h4>
                <p class="mb-0">Para consultar evaluaciones, selecciona sacramento, nivel, grupo/asignación y unidad.</p>
            </div>
        @endif
    @endif

    <script>
        function resetNivel() {
            let nivel = document.getElementById('nivel_id');
            if(nivel) nivel.value = "";
            resetAsignacionYUnidad();
        }
        function resetAsignacionYUnidad() {
            let asignacion = document.getElementById('asignacion_id');
            let unidad = document.getElementById('unidad_id');
            if(asignacion) asignacion.value = "";
            if(unidad) unidad.value = "";
        }
        function resetUnidad() {
            let unidad = document.getElementById('unidad_id');
            if(unidad) unidad.value = "";
        }
    </script>
@endsection
