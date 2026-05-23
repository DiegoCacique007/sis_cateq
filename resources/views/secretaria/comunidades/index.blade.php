@extends('layouts.app_parroquia_admin')

@section('title', 'Comunidades - Secretaría')
@section('header_title', 'Gestión de Comunidades')

@section('content')
    <style>
        .comunidades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .comunidad-card {
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

        .comunidad-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .comunidad-card .card-header-content {
            margin-bottom: 1rem;
            padding-right: 4.5rem;
        }

        .comunidad-card .community-name {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .comunidad-card .community-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .comunidad-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .comunidad-card .info-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .comunidad-card .card-actions {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
        }

        .comunidad-card .btn-circle {
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

        .comunidad-card .btn-edit {
            border: 1px solid #3b82f6;
            color: #3b82f6;
        }

        .comunidad-card .btn-edit:hover {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .comunidad-card .btn-delete {
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .comunidad-card .btn-delete:hover {
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
                <h5 class="fw-bold mb-0 module-title">Comunidades</h5>
                <small class="text-muted">Administración de comunidades registradas.</small>
            </div>

            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nueva comunidad
            </button>
        </div>

        <div class="card-body border-bottom module-filter-bar">
            <form method="GET" action="{{ route('secretaria.comunidades.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Buscar comunidad..."
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

        <div class="comunidades-grid">
            @forelse($registros as $registro)
                <div class="comunidad-card">
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
                            action="{{ route('secretaria.comunidades.destroy', $registro->id) }}"
                            method="POST"
                            class="d-inline js-delete-form"
                            data-message="Esta acción eliminará la comunidad seleccionada."
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn-circle btn-delete" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </div>

                    <div class="card-header-content">
                        <h4 class="community-name">
                            {{ $registro->comunidad }}
                        </h4>

                        <span class="community-subtitle">Comunidad registrada</span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge">
                            <i class="bi bi-geo-alt"></i>
                            Comunidad
                        </span>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-geo-alt"></i>
                    No hay comunidades registradas.
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
                    <h5 class="modal-title fw-bold">Nueva comunidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.comunidades.store') }}" method="POST">
                    @csrf

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Comunidad</label>
                            <input
                                type="text"
                                id="crearComunidad"
                                name="comunidad"
                                value="{{ old('comunidad') }}"
                                class="form-control"
                                placeholder="Ej. San Gabriel Ixtla"
                                required
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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Editar comunidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.comunidades.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Comunidad</label>
                                <input
                                    type="text"
                                    id="edit{{ $registro->id }}Comunidad"
                                    name="comunidad"
                                    value="{{ old('comunidad', $registro->comunidad) }}"
                                    class="form-control"
                                    required
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
