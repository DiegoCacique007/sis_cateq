@extends('layouts.app_parroquia_admin')

@section('title', 'Reporte por Comunidad - Secretaría')
@section('header_title', 'Reporte de Alumnos por Comunidad')

@section('content')
    <style>
        .reporte-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .reporte-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 160px;
        }

        .reporte-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .reporte-card .card-header-content {
            margin-bottom: 1rem;
        }

        .reporte-card .student-name {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .reporte-card .student-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .reporte-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .reporte-card .info-badge {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .reporte-card .community-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .reporte-card .sacramento-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
            text-transform: capitalize;
        }

        .reporte-card .nivel-badge {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .reporte-card .empty-badge {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .filter-card {
            background: #ffffff;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: #64748b;
            border: 1px dashed #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
        }

        .empty-state i {
            font-size: 2.4rem;
            color: #94a3b8;
            display: block;
            margin-bottom: 0.75rem;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .filter-actions {
                flex-direction: column;
            }

            .reporte-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
        }
    </style>

    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Reporte por Comunidad</h5>
                <small class="text-muted">Consulta alumnos activos por comunidad, sacramento y nivel.</small>
            </div>

            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                <i class="bi bi-people me-1"></i>
                Alumnos activos
            </span>
        </div>

        <div class="card-body border-bottom filter-card">
            <form method="GET" action="{{ route('secretaria.alumnos_comunidades.index') }}" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Comunidad</label>
                    <select name="comunidad_id" class="form-select">
                        <option value="">Todas las comunidades</option>
                        @foreach($comunidades as $comunidad)
                            <option value="{{ $comunidad->id }}" {{ request('comunidad_id') == $comunidad->id ? 'selected' : '' }}>
                                {{ $comunidad->comunidad }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Sacramento</label>
                    <select name="sacramento" class="form-select">
                        <option value="">Todos los sacramentos</option>
                        <option value="primera_comunion" {{ request('sacramento') == 'primera_comunion' ? 'selected' : '' }}>
                            Primera Comunión
                        </option>
                        <option value="confirmacion" {{ request('sacramento') == 'confirmacion' ? 'selected' : '' }}>
                            Confirmación
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nivel</label>
                    <select name="numero_nivel" class="form-select">
                        <option value="">Todos los niveles</option>
                        @foreach($nivelesDisponibles as $nivel)
                            <option value="{{ $nivel->numero }}" {{ request('numero_nivel') == $nivel->numero ? 'selected' : '' }}>
                                Nivel {{ $nivel->numero }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="filter-actions">
                        <button class="btn btn-outline-primary flex-fill" style="height: 46px;">
                            <i class="bi bi-search me-1"></i>
                            Buscar
                        </button>

                        <a href="{{ route('secretaria.alumnos_comunidades.index') }}"
                           class="btn btn-outline-secondary flex-fill d-flex align-items-center justify-content-center"
                           style="height: 46px;">
                            <i class="bi bi-x-circle me-1"></i>
                            Limpiar
                        </a>
                    </div>
                </div>

            </form>
        </div>

        <div class="reporte-grid">
            @forelse($registros as $alumno)
                @php
                    $inscripcion = $alumno->inscripciones->first();

                    $nivelObj = null;

                    if ($inscripcion && isset($inscripcion->asignaGrupo) && $inscripcion->asignaGrupo) {
                        $nivelObj = $inscripcion->asignaGrupo->nivel ?? null;
                    }

                    if (!$nivelObj && $inscripcion && $inscripcion->grupo && $inscripcion->grupo->asignacionesGrupo) {
                        $asignacion = $inscripcion->grupo->asignacionesGrupo
                            ->where('periodo_id', $inscripcion->periodo_id)
                            ->first();

                        $nivelObj = $asignacion ? $asignacion->nivel : null;
                    }

                    $sacramentoTexto = $nivelObj && $nivelObj->sacramento
                        ? str_replace('_', ' ', $nivelObj->sacramento)
                        : null;
                @endphp

                <div class="reporte-card">
                    <div class="card-header-content">
                        <h4 class="student-name">
                            {{ $alumno->nombre }} {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }}
                        </h4>

                        <span class="student-subtitle">Alumno activo</span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge community-badge">
                            <i class="bi bi-geo-alt"></i>
                            {{ $alumno->comunidad->comunidad ?? 'Sin comunidad' }}
                        </span>

                        @if($sacramentoTexto)
                            <span class="info-badge sacramento-badge">
                                <i class="bi bi-award"></i>
                                {{ $sacramentoTexto }}
                            </span>
                        @else
                            <span class="info-badge empty-badge">
                                <i class="bi bi-award"></i>
                                Sin sacramento
                            </span>
                        @endif

                        @if($nivelObj && $nivelObj->numero)
                            <span class="info-badge nivel-badge">
                                <i class="bi bi-layers"></i>
                                Nivel {{ $nivelObj->numero }}
                            </span>
                        @else
                            <span class="info-badge empty-badge">
                                <i class="bi bi-layers"></i>
                                Sin nivel
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    No se encontraron alumnos con los filtros seleccionados.
                </div>
            @endforelse
        </div>

        @if($registros->hasPages())
            <div class="card-footer bg-white">
                {{ $registros->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
