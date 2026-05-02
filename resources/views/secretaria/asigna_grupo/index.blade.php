@extends('layouts.app_parroquia_admin')

@section('title', 'Asignación de Grupos - Secretaría')
@section('header_title', 'Asignación de Grupos')

@section('content')
    @php
        $catequistasLista = $catequistas ?? $users ?? collect();
    @endphp

    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Asignación de grupos</h5>
                <small class="text-muted">Relación entre comunidad, grupo, nivel, periodo y catequista.</small>
            </div>

            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nueva asignación
            </button>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('secretaria.asigna_grupo.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por comunidad, grupo, nivel, periodo o catequista...">
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
                    <th>Comunidad</th>
                    <th>Grupo</th>
                    <th>Nivel</th>
                    <th>Periodo</th>
                    <th>Catequista</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>

                <tbody>
                @forelse($registros as $registro)
                    <tr>
                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-geo-alt"></i>
                                {{ $registro->comunidad_nombre ?? $registro->comunidad->comunidad ?? 'Sin comunidad' }}
                            </span>
                        </td>

                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-collection"></i>
                                {{ $registro->grupo_nombre ?? $registro->grupo->nombre ?? 'Sin grupo' }}
                            </span>
                        </td>

                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-layers"></i>
                                {{ $registro->nivel_nombre ?? $registro->nivel->nivel ?? 'Sin nivel' }}
                            </span>
                        </td>

                        <td>
                            <span class="soft-badge">
                                <i class="bi bi-calendar-range"></i>
                                {{ $registro->periodo_nombre ?? $registro->periodo_texto ?? 'Sin periodo' }}
                            </span>
                        </td>

                        <td>
                            <span class="cell-title">
                                {{ $registro->catequista_nombre ?? $registro->catequista->name ?? 'Sin catequista' }}
                            </span>
                            <span class="cell-subtitle">Catequista responsable</span>
                        </td>

                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary rounded-circle btn-action" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $registro->id }}">
                                <i class="bi bi-pencil-fill"></i>
                            </button>

                            <form action="{{ route('secretaria.asigna_grupo.destroy', $registro->id) }}"
                                  method="POST"
                                  class="d-inline js-delete-form"
                                  data-message="Esta acción eliminará la asignación seleccionada.">
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
                        <td colspan="6" class="text-center py-5 text-muted">No hay asignaciones registradas.</td>
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
                    <h5 class="modal-title fw-bold">Nueva asignación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.asigna_grupo.store') }}" method="POST">
                    @csrf

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
                            <label class="form-label">Periodo</label>
                            <select name="periodo_id" class="form-select" required>
                                <option value="">Selecciona un periodo</option>
                                @foreach($periodos as $periodo)
                                    <option value="{{ $periodo->id }}" {{ old('periodo_id') == $periodo->id ? 'selected' : '' }}>
                                        {{ $periodo->text ?? (($periodo->fecha_inicio ?? '') . ' al ' . ($periodo->fecha_fin ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
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
                        <h5 class="modal-title fw-bold">Editar asignación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.asigna_grupo.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Comunidad</label>
                                <select name="comunidad_id" class="form-select" required>
                                    @foreach($comunidades as $comunidad)
                                        <option value="{{ $comunidad->id }}" {{ $registro->comunidad_id == $comunidad->id ? 'selected' : '' }}>
                                            {{ $comunidad->text ?? $comunidad->comunidad }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Grupo</label>
                                <select name="grupo_id" class="form-select" required>
                                    @foreach($grupos as $grupo)
                                        <option value="{{ $grupo->id }}" {{ $registro->grupo_id == $grupo->id ? 'selected' : '' }}>
                                            {{ $grupo->text ?? $grupo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nivel</label>
                                <select name="nivel_id" class="form-select" required>
                                    @foreach($niveles as $nivel)
                                        <option value="{{ $nivel->id }}" {{ $registro->nivel_id == $nivel->id ? 'selected' : '' }}>
                                            {{ $nivel->text ?? $nivel->nivel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Periodo</label>
                                <select name="periodo_id" class="form-select" required>
                                    @foreach($periodos as $periodo)
                                        <option value="{{ $periodo->id }}" {{ $registro->periodo_id == $periodo->id ? 'selected' : '' }}>
                                            {{ $periodo->text ?? (($periodo->fecha_inicio ?? '') . ' al ' . ($periodo->fecha_fin ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Catequista responsable</label>
                                <select name="catequista_id" class="form-select" required>
                                    @foreach($catequistasLista as $catequista)
                                        <option value="{{ $catequista->id }}" {{ $registro->catequista_id == $catequista->id ? 'selected' : '' }}>
                                            {{ $catequista->text ?? $catequista->name }}
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
