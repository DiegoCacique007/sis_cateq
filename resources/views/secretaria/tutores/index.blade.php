@extends('layouts.app_parroquia_admin')

@section('title', 'Tutores - Secretaría')
@section('header_title', 'Gestión de Tutores')

@section('content')
    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Tutores</h5>
                <small class="text-muted">Administración de tutores asociados a alumnos.</small>
            </div>

            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nuevo tutor
            </button>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('secretaria.tutores.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nombre del tutor o alumno...">
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
                    <th>Tutor</th>
                    <th>Alumno asignado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>

                <tbody>
                @forelse($registros as $registro)
                    <tr>
                        <td>
                            <span class="cell-title">
                                {{ $registro->nombre }} {{ $registro->ap }} {{ $registro->am }}
                            </span>
                            <span class="cell-subtitle">Tutor registrado</span>
                        </td>

                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-person-check"></i>
                                {{ $registro->alumno_nombre ?? $registro->alumno->nombre_completo ?? 'Sin alumno' }}
                            </span>
                        </td>

                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary rounded-circle btn-action" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $registro->id }}">
                                <i class="bi bi-pencil-fill"></i>
                            </button>

                            <form action="{{ route('secretaria.tutores.destroy', $registro->id) }}"
                                  method="POST"
                                  class="d-inline js-delete-form"
                                  data-message="Esta acción eliminará el tutor seleccionado.">
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
                        <td colspan="3" class="text-center py-5 text-muted">No hay tutores registrados.</td>
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registrar tutor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.tutores.store') }}" method="POST">
                    @csrf

                    <div class="modal-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nombre(s)</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Apellido paterno</label>
                            <input type="text" name="ap" value="{{ old('ap') }}" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Apellido materno</label>
                            <input type="text" name="am" value="{{ old('am') }}" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Alumno asignado</label>
                            <select name="alumno_id" class="form-select" required>
                                <option value="">Selecciona un alumno</option>
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id }}" {{ old('alumno_id') == $alumno->id ? 'selected' : '' }}>
                                        {{ $alumno->text ?? ($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}
                                    </option>
                                @endforeach
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
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Editar tutor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.tutores.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nombre(s)</label>
                                <input type="text" name="nombre" value="{{ old('nombre', $registro->nombre) }}" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Apellido paterno</label>
                                <input type="text" name="ap" value="{{ old('ap', $registro->ap) }}" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Apellido materno</label>
                                <input type="text" name="am" value="{{ old('am', $registro->am) }}" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Alumno asignado</label>
                                <select name="alumno_id" class="form-select" required>
                                    <option value="">Selecciona un alumno</option>
                                    @foreach($alumnos as $alumno)
                                        <option value="{{ $alumno->id }}" {{ $registro->alumno_id == $alumno->id ? 'selected' : '' }}>
                                            {{ $alumno->text ?? ($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}
                                        </option>
                                    @endforeach
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
