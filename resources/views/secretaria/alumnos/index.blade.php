@extends('layouts.app_parroquia_admin')

@section('title', 'Alumnos - Secretaría')
@section('header_title', 'Gestión de Alumnos')

@section('content')
    <style>
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
            min-height: 155px;
        }

        .alumno-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.10),
            0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .alumno-card .card-header-content {
            margin-bottom: 1rem;
            padding-right: 4.5rem;
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

        .alumno-card .community-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .alumno-card .date-badge {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .alumno-card .card-actions {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
        }

        .alumno-card .btn-circle {
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

        .alumno-card .btn-edit {
            border: 1px solid #3b82f6;
            color: #3b82f6;
        }

        .alumno-card .btn-edit:hover {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .alumno-card .btn-delete {
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .alumno-card .btn-delete:hover {
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
    </style>

    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Alumnos</h5>
                <small class="text-muted">Administración general de alumnos registrados.</small>
            </div>

            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nuevo alumno
            </button>
        </div>

        <div class="card-body border-bottom module-filter-bar">
            <form method="GET" action="{{ route('secretaria.alumnos.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Buscar por nombre, apellido o comunidad..."
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

        <div class="alumnos-grid">
            @forelse($registros as $registro)
                <div class="alumno-card">
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
                            action="{{ route('secretaria.alumnos.destroy', $registro->id) }}"
                            method="POST"
                            class="d-inline js-delete-form"
                            data-message="Esta acción eliminará el alumno seleccionado."
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn-circle btn-delete" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-header-content">
                        <h4 class="student-name">
                            {{ $registro->nombre }}
                            {{ $registro->apellido_paterno }}
                            {{ $registro->apellido_materno }}
                        </h4>

                        <span class="student-subtitle">Alumno registrado</span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge community-badge">
                            <i class="bi bi-geo-alt"></i>
                            {{ $registro->comunidad_nombre ?? $registro->comunidad->comunidad ?? 'Sin comunidad' }}
                        </span>

                        <span class="info-badge date-badge">
                            <i class="bi bi-calendar-event"></i>
                            {{ $registro->fecha_nacimiento ? \Carbon\Carbon::parse($registro->fecha_nacimiento)->format('d/m/Y') : 'N/D' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-person-x"></i>
                    No hay alumnos registrados.
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
                    <h5 class="modal-title fw-bold">Registrar alumno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.alumnos.store') }}" method="POST">
                    @csrf

                    <div class="modal-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nombre(s)</label>
                            <input
                                type="text"
                                name="nombre"
                                value="{{ old('nombre') }}"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Apellido paterno</label>
                            <input
                                type="text"
                                name="apellido_paterno"
                                value="{{ old('apellido_paterno') }}"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Apellido materno</label>
                            <input
                                type="text"
                                name="apellido_materno"
                                value="{{ old('apellido_materno') }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Comunidad</label>
                            <select name="comunidad_id" class="form-select" required>
                                <option value="">Selecciona una comunidad</option>
                                @foreach($comunidades as $comunidad)
                                    <option value="{{ $comunidad->id }}" {{ old('comunidad_id') == $comunidad->id ? 'selected' : '' }}>
                                        {{ $comunidad->comunidad ?? $comunidad->text }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input
                                type="date"
                                name="fecha_nacimiento"
                                value="{{ old('fecha_nacimiento') }}"
                                class="form-control"
                                min="{{ now()->subYears(15)->format('Y-m-d') }}"
                                max="{{ now()->subYears()->endOfYear()->format('Y-m-d') }}"
                            >
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
                        <h5 class="modal-title fw-bold">Editar alumno</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.alumnos.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nombre(s)</label>
                                <input
                                    type="text"
                                    name="nombre"
                                    value="{{ old('nombre', $registro->nombre) }}"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Apellido paterno</label>
                                <input
                                    type="text"
                                    name="apellido_paterno"
                                    value="{{ old('apellido_paterno', $registro->apellido_paterno) }}"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Apellido materno</label>
                                <input
                                    type="text"
                                    name="apellido_materno"
                                    value="{{ old('apellido_materno', $registro->apellido_materno) }}"
                                    class="form-control"
                                >
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Comunidad</label>
                                <select name="comunidad_id" class="form-select" required>
                                    <option value="">Selecciona una comunidad</option>
                                    @foreach($comunidades as $comunidad)
                                        <option value="{{ $comunidad->id }}" {{ old('comunidad_id', $registro->comunidad_id) == $comunidad->id ? 'selected' : '' }}>
                                            {{ $comunidad->comunidad ?? $comunidad->text }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Fecha de nacimiento</label>
                                <input
                                    type="date"
                                    name="fecha_nacimiento"
                                    value="{{ old('fecha_nacimiento', $registro->fecha_nacimiento ? \Carbon\Carbon::parse($registro->fecha_nacimiento)->format('Y-m-d') : '') }}"
                                    class="form-control"
                                    min="{{ now()->subYears(15)->format('Y-m-d') }}"
                                    max="{{ now()->subYears()->endOfYear()->format('Y-m-d') }}"
                                >
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
