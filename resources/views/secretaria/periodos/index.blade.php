@extends('layouts.app_parroquia_admin')

@section('title', 'Periodos - Secretaría')
@section('header_title', 'Gestión de Periodos')

@section('content')
    <style>
        .periodos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .periodo-card {
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
            min-height: 170px;
        }

        .periodo-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .periodo-card .card-header-content {
            margin-bottom: 1rem;
            padding-right: 4.5rem;
        }

        .periodo-card .period-title {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .periodo-card .period-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .periodo-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .periodo-card .info-badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .periodo-card .date-start-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .periodo-card .date-end-badge {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .periodo-card .status-active-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .periodo-card .status-inactive-badge {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px solid #94a3b8;
        }

        .periodo-card .card-actions {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
        }

        .periodo-card .btn-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
            font-size: 0.9rem;
        }

        .periodo-card .btn-edit {
            border: 1px solid #3b82f6;
            color: #3b82f6;
        }

        .periodo-card .btn-edit:hover {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .periodo-card .btn-delete {
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .periodo-card .btn-delete:hover {
            background-color: #ef4444;
            color: #ffffff;
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

        .module-filter-bar {
            background: #ffffff;
        }

        .modal-content {
            border-radius: 18px;
        }

        @media (max-width: 768px) {
            .periodos-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
        }
    </style>

    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Periodos</h5>
                <small class="text-muted">Administración de periodos activos e inactivos.</small>
            </div>

            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nuevo periodo
            </button>
        </div>

        <div class="card-body border-bottom module-filter-bar">
            <form method="GET" action="{{ route('secretaria.periodos.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Buscar por fecha o estado..."
                    >
                </div>

                <div class="col-md-2">
                    <select name="per_page" class="form-select">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>

        <div class="periodos-grid">
            @forelse($registros as $registro)
                @php
                    $fechaInicioTexto = $registro->fecha_inicio
                        ? \Carbon\Carbon::parse($registro->fecha_inicio)->format('d/m/Y')
                        : 'N/D';

                    $fechaFinTexto = $registro->fecha_fin
                        ? \Carbon\Carbon::parse($registro->fecha_fin)->format('d/m/Y')
                        : 'N/D';
                @endphp

                <div class="periodo-card">
                    <div class="card-actions">
                        <button
                            type="button"
                            class="btn-circle btn-edit"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditar{{ $registro->id }}"
                            title="Editar"
                        >
                            <i class="bi bi-pencil-fill"></i>
                        </button>

                        <form
                            action="{{ route('secretaria.periodos.destroy', $registro->id) }}"
                            method="POST"
                            class="d-inline js-delete-form"
                            data-message="Esta acción eliminará el periodo seleccionado."
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn-circle btn-delete" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-header-content">
                        <h4 class="period-title">
                            Periodo {{ $fechaInicioTexto }} - {{ $fechaFinTexto }}
                        </h4>

                        <span class="period-subtitle">
                            Periodo de catequesis registrado
                        </span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge date-start-badge">
                            <i class="bi bi-calendar-event"></i>
                            Inicio: {{ $fechaInicioTexto }}
                        </span>

                        <span class="info-badge date-end-badge">
                            <i class="bi bi-calendar-check"></i>
                            Fin: {{ $fechaFinTexto }}
                        </span>

                        @if((int) $registro->estado === 1)
                            <span class="info-badge status-active-badge">
                                <i class="bi bi-check-circle"></i>
                                Activo
                            </span>
                        @else
                            <span class="info-badge status-inactive-badge">
                                <i class="bi bi-x-circle"></i>
                                Inactivo
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    No hay periodos registrados.
                </div>
            @endforelse
        </div>

        <div class="card-footer bg-white">
            {{ $registros->withQueryString()->links() }}
        </div>
    </div>

    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Nuevo periodo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.periodos.store') }}" method="POST">
                    @csrf

                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha de inicio</label>
                            <input
                                type="date"
                                name="fecha_inicio"
                                value="{{ old('fecha_inicio') }}"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha de fin</label>
                            <input
                                type="date"
                                name="fecha_fin"
                                value="{{ old('fecha_fin') }}"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" required>
                                <option value="1" {{ (string) old('estado', '1') === '1' ? 'selected' : '' }}>
                                    Activo
                                </option>
                                <option value="0" {{ (string) old('estado', '1') === '0' ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button class="btn btn-parroquia rounded-pill px-4">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($registros as $registro)
        <div class="modal fade" id="modalEditar{{ $registro->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Editar periodo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.periodos.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha de inicio</label>
                                <input
                                    type="date"
                                    name="fecha_inicio"
                                    value="{{ old('fecha_inicio', $registro->fecha_inicio ? \Carbon\Carbon::parse($registro->fecha_inicio)->format('Y-m-d') : '') }}"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha de fin</label>
                                <input
                                    type="date"
                                    name="fecha_fin"
                                    value="{{ old('fecha_fin', $registro->fecha_fin ? \Carbon\Carbon::parse($registro->fecha_fin)->format('Y-m-d') : '') }}"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select" required>
                                    <option value="1" {{ (string) old('estado', $registro->estado) === '1' ? 'selected' : '' }}>
                                        Activo
                                    </option>
                                    <option value="0" {{ (string) old('estado', $registro->estado) === '0' ? 'selected' : '' }}>
                                        Inactivo
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button class="btn btn-parroquia rounded-pill px-4">
                                Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
