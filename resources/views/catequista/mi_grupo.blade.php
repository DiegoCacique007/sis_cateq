@extends('layouts.app_parroquia_catequista')

@section('title', 'Mi Grupo - Catequista')
@section('header_title', 'Mi Grupo Asignado')

@section('content')
    <style>
        .grupo-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
        }

        .info-window-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.15rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            min-height: 125px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .info-window-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .info-window-card .info-icon {
            width: 42px;
            height: 42px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.7rem;
            font-size: 1.2rem;
        }

        .info-window-card .info-label {
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
        }

        .info-window-card .info-value {
            color: #1e3a8a;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
            display: block;
        }

        .theme-blue .info-icon {
            background: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .theme-green .info-icon {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .theme-orange .info-icon {
            background: #fff7ed;
            color: #d97706;
            border: 1px solid #f59e0b;
        }

        .theme-cyan .info-icon {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .alumnos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .alumno-card {
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
            min-height: 145px;
        }

        .alumno-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .alumno-card .card-header-content {
            margin-bottom: 1rem;
        }

        .alumno-card .student-name {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .alumno-card .student-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .alumno-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .alumno-card .info-badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .alumno-card .active-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .alumno-card .student-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .module-filter-bar {
            background: #ffffff;
        }

        .group-select-form {
            width: 100%;
            max-width: 520px;
        }

        .group-select-form .form-select {
            min-width: 260px;
        }

        .actions-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-pdf-asistencia {
            border: 1px solid #dc2626;
            color: #dc2626;
            background: #ffffff;
            border-radius: 9999px;
            padding: 0.45rem 1rem;
            font-size: 0.86rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .btn-pdf-asistencia:hover {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(220, 38, 38, 0.18);
        }

        .count-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
            border-radius: 9999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
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

        .sin-grupo-card {
            border-radius: 18px;
            border: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .sin-grupo-card .empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 9999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .alumnos-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .group-select-form {
                max-width: 100%;
            }

            .group-select-form .form-select {
                min-width: 100%;
            }

            .actions-header {
                align-items: stretch;
                width: 100%;
            }

            .btn-pdf-asistencia,
            .count-badge {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    @if($asignacion)
        <div class="card card-parroquia border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 module-title">Información del grupo</h5>
                    <small class="text-muted">Grupo asignado actualmente al catequista.</small>
                </div>

                @if($asignaciones->count() > 1)
                    <form method="GET" action="{{ route('catequista.mi_grupo') }}" class="group-select-form">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                            <label class="form-label mb-0 text-nowrap fw-bold text-primary">
                                Cambiar grupo:
                            </label>

                            <select name="asignacion_id" class="form-select border-primary" onchange="this.form.submit()">
                                @foreach($asignaciones as $asig)
                                    <option value="{{ $asig->asignacion_id }}" {{ (int) $asignacionId === (int) $asig->asignacion_id ? 'selected' : '' }}>
                                        {{ $asig->texto_asignacion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif
            </div>

            <div class="card-body module-filter-bar">
                <div class="grupo-info-grid">
                    <div class="info-window-card theme-blue">
                        <div>
                            <span class="info-icon">
                                <i class="bi bi-geo-alt"></i>
                            </span>

                            <span class="info-label">Comunidad</span>
                            <span class="info-value">{{ $asignacion->comunidad }}</span>
                        </div>
                    </div>

                    <div class="info-window-card theme-cyan">
                        <div>
                            <span class="info-icon">
                                <i class="bi bi-collection"></i>
                            </span>

                            <span class="info-label">Grupo</span>
                            <span class="info-value">{{ $asignacion->grupo }}</span>
                        </div>
                    </div>

                    <div class="info-window-card theme-green">
                        <div>
                            <span class="info-icon">
                                <i class="bi bi-layers"></i>
                            </span>

                            <span class="info-label">Nivel</span>
                            <span class="info-value">{{ $asignacion->nivel }}</span>
                        </div>
                    </div>

                    <div class="info-window-card theme-orange">
                        <div>
                            <span class="info-icon">
                                <i class="bi bi-calendar-event"></i>
                            </span>

                            <span class="info-label">Periodo</span>
                            <span class="info-value">{{ $asignacion->periodo }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-parroquia border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 module-title">Lista de alumnos y asistencia</h5>
                    <small class="text-muted">Alumnos inscritos en el grupo asignado.</small>
                </div>

                <div class="actions-header">
                    <a href="{{ route('catequista.asistencia.pdf', ['asignacion_id' => $asignacion->asignacion_id]) }}"
                       class="btn-pdf-asistencia">
                        <i class="bi bi-file-earmark-pdf"></i>
                        Descargar PDF de Asistencia
                    </a>

                    <span class="count-badge">
                        <i class="bi bi-people"></i>
                        {{ $alumnos->count() }} alumno(s)
                    </span>
                </div>
            </div>

            <div class="alumnos-grid">
                @forelse($alumnos as $index => $alumno)
                    <div class="alumno-card">
                        <div class="card-header-content">
                            <h4 class="student-name">
                                {{ $alumno->alumno }}
                            </h4>

                            <span class="student-subtitle">
                                Alumno inscrito en tu grupo
                            </span>
                        </div>

                        <div class="card-body-content">
                            <span class="info-badge student-badge">
                                <i class="bi bi-person-check"></i>
                                Alumno
                            </span>

                            <span class="info-badge active-badge">
                                <i class="bi bi-check-circle"></i>
                                Activo
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        No hay alumnos inscritos en este grupo.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <div class="card sin-grupo-card">
            <div class="card-body text-center p-5">
                <div class="empty-icon">
                    <i class="bi bi-inbox"></i>
                </div>

                <h5 class="fw-bold" style="color: #1e3a8a;">
                    Sin grupo asignado
                </h5>

                <p class="text-muted mb-0">
                    Todavía no tienes un grupo asignado por Secretaría.
                </p>
            </div>
        </div>
    @endif
@endsection
