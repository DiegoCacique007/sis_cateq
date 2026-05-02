@extends('layouts.app_parroquia_admin')

@section('title', 'Periodos - Secretaría')
@section('header_title', 'Gestión de Periodos')

@section('content')
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

        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('secretaria.periodos.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por fecha o estado...">
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

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Fecha de inicio</th>
                    <th>Fecha de fin</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>

                <tbody>
                @forelse($registros as $registro)
                    <tr>
                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-calendar-event"></i>
                                {{ $registro->fecha_inicio }}
                            </span>
                        </td>

                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-calendar-check"></i>
                                {{ $registro->fecha_fin }}
                            </span>
                        </td>

                        <td>
                            @if($registro->estado == 1)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                    Activo
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-2">
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary rounded-circle btn-action" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $registro->id }}">
                                <i class="bi bi-pencil-fill"></i>
                            </button>

                            <form action="{{ route('secretaria.periodos.destroy', $registro->id) }}"
                                  method="POST"
                                  class="d-inline js-delete-form"
                                  data-message="Esta acción eliminará el periodo seleccionado.">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger rounded-circle btn-action">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No hay periodos registrados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
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
                            <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha de fin</label>
                            <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" required>
                                <option value="1" {{ old('estado', 1) == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('estado') == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-parroquia rounded-pill px-4">Guardar</button>
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
                                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $registro->fecha_inicio) }}" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha de fin</label>
                                <input type="date" name="fecha_fin" value="{{ old('fecha_fin', $registro->fecha_fin) }}" class="form-control" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select" required>
                                    <option value="1" {{ $registro->estado == 1 ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ $registro->estado == 0 ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-parroquia rounded-pill px-4">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
