@extends('layouts.app_parroquia_admin')

@section('title', 'Asignación de Grupos - Secretaría')
@section('header_title', 'Asignación de Grupos')

@section('content')
    @php
        $catequistasLista = $catequistas ?? $users ?? collect();
    @endphp

    <style>
        .asignaciones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .asignacion-card {
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
            min-height: 175px;
        }

        .asignacion-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .asignacion-card .card-header-content {
            margin-bottom: 1rem;
            padding-right: 4.5rem;
        }

        .asignacion-card .main-title {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .asignacion-card .main-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .asignacion-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .asignacion-card .info-badge {
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

        .asignacion-card .community-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .asignacion-card .group-badge {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .asignacion-card .level-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .asignacion-card .catequista-badge {
            background-color: #fff7ed;
            color: #d97706;
            border: 1px solid #f59e0b;
        }

        .asignacion-card .card-actions {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
        }

        .asignacion-card .btn-circle {
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

        .asignacion-card .btn-edit {
            border: 1px solid #3b82f6;
            color: #3b82f6;
        }

        .asignacion-card .btn-edit:hover {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .asignacion-card .btn-delete {
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .asignacion-card .btn-delete:hover {
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
            .asignaciones-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
        }
    </style>

    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Asignación de grupos</h5>
                <small class="text-muted">Relación entre comunidad, grupo, nivel y catequista.</small>
            </div>

            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nueva asignación
            </button>
        </div>

        <div class="card-body border-bottom module-filter-bar">
            <form method="GET" action="{{ route('secretaria.asigna_grupo.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Buscar por comunidad, grupo, nivel o catequista..."
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

        <div class="asignaciones-grid">
            @forelse($registros as $registro)
                <div class="asignacion-card">
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
                            action="{{ route('secretaria.asigna_grupo.destroy', $registro->id) }}"
                            method="POST"
                            class="d-inline js-delete-form"
                            data-message="Esta acción eliminará la asignación seleccionada."
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn-circle btn-delete" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-header-content">
                        <h4 class="main-title">
                            {{ $registro->catequista_nombre ?? $registro->catequista->name ?? 'Sin catequista' }}
                        </h4>

                        <span class="main-subtitle">Catequista responsable del grupo</span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge community-badge">
                            <i class="bi bi-geo-alt"></i>
                            {{ $registro->comunidad_nombre ?? $registro->comunidad->comunidad ?? 'Sin comunidad' }}
                        </span>

                        <span class="info-badge group-badge">
                            <i class="bi bi-collection"></i>
                            {{ $registro->grupo_nombre ?? $registro->grupo->nombre ?? 'Sin grupo' }}
                        </span>

                        <span class="info-badge level-badge">
                            <i class="bi bi-layers"></i>
                            {{ $registro->nivel_nombre ?? $registro->nivel->nivel ?? 'Sin nivel' }}
                        </span>

                        <span class="info-badge catequista-badge">
                            <i class="bi bi-person-badge"></i>
                            Catequista
                        </span>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-diagram-3"></i>
                    No hay asignaciones registradas.
                </div>
            @endforelse
        </div>

        <div class="card-footer bg-white">
            {{ $registros->withQueryString()->links() }}
        </div>
    </div>

    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Nueva asignación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.asigna_grupo.store') }}" method="POST">
                    @csrf

                    <input type="hidden" name="periodo_id" value="{{ session('periodo_activo_id') }}">

                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Comunidad</label>
                            <select name="comunidad_id" class="form-select" required>
                                <option value="">Selecciona una comunidad</option>
                                @foreach($comunidades as $comunidad)
                                    <option value="{{ $comunidad->id }}" {{ old('comunidad_id') == $comunidad->id ? 'selected' : '' }}>
                                        {{ $comunidad->text ?? $comunidad->comunidad }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Grupo</label>
                            <select name="grupo_id" class="form-select" required>
                                <option value="">Selecciona un grupo</option>
                                @foreach($grupos as $grupo)
                                    <option value="{{ $grupo->id }}" {{ old('grupo_id') == $grupo->id ? 'selected' : '' }}>
                                        {{ $grupo->text ?? $grupo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nivel</label>
                            <select name="nivel_id" class="form-select" required>
                                <option value="">Selecciona un nivel</option>
                                @foreach($niveles as $nivel)
                                    <option value="{{ $nivel->id }}" {{ old('nivel_id') == $nivel->id ? 'selected' : '' }}>
                                        {{ $nivel->text ?? $nivel->nivel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Catequista responsable</label>
                            <select name="catequista_id" class="form-select" required>
                                <option value="">Selecciona un catequista</option>
                                @foreach($catequistasLista as $catequista)
                                    <option value="{{ $catequista->id }}" {{ old('catequista_id') == $catequista->id ? 'selected' : '' }}>
                                        {{ $catequista->text ?? $catequista->name }}
                                    </option>
                                @endforeach
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
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Editar asignación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.asigna_grupo.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="periodo_id" value="{{ session('periodo_activo_id') ?? $registro->periodo_id }}">

                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Comunidad</label>
                                <select name="comunidad_id" class="form-select" required>
                                    @foreach($comunidades as $comunidad)
                                        <option value="{{ $comunidad->id }}" {{ old('comunidad_id', $registro->comunidad_id) == $comunidad->id ? 'selected' : '' }}>
                                            {{ $comunidad->text ?? $comunidad->comunidad }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Grupo</label>
                                <select name="grupo_id" class="form-select" required>
                                    @foreach($grupos as $grupo)
                                        <option value="{{ $grupo->id }}" {{ old('grupo_id', $registro->grupo_id) == $grupo->id ? 'selected' : '' }}>
                                            {{ $grupo->text ?? $grupo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nivel</label>
                                <select name="nivel_id" class="form-select" required>
                                    @foreach($niveles as $nivel)
                                        <option value="{{ $nivel->id }}" {{ old('nivel_id', $registro->nivel_id) == $nivel->id ? 'selected' : '' }}>
                                            {{ $nivel->text ?? $nivel->nivel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Catequista responsable</label>
                                <select name="catequista_id" class="form-select" required>
                                    @foreach($catequistasLista as $catequista)
                                        <option value="{{ $catequista->id }}" {{ old('catequista_id', $registro->catequista_id) == $catequista->id ? 'selected' : '' }}>
                                            {{ $catequista->text ?? $catequista->name }}
                                        </option>
                                    @endforeach
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
