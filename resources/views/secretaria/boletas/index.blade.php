@extends('layouts.app_parroquia_admin')

@section('title', 'Boletas - Secretaría')
@section('header_title', 'Boletas de Evaluación')
@section('header_subtitle', 'Genera boletas de calificaciones para los alumnos.')

@section('content')
    <div class="card card-parroquia module-card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div>
                <h5 class="fw-bold mb-0 module-title"><i class="bi bi-printer me-2"></i>Generar Boletas</h5>
                <small class="text-muted">Filtra por catequista, nivel y grupo para ver los alumnos y generar sus boletas.</small>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('secretaria.boletas.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Catequista</label>
                    <select name="catequista_id" class="form-select">
                        <option value="">— Todas —</option>
                        @foreach($catequistas as $cat)
                            <option value="{{ $cat->id }}" {{ $filtros['catequista_id'] == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nivel</label>
                    <select name="nivel_id" class="form-select">
                        <option value="">— Todos —</option>
                        @foreach($niveles as $niv)
                            <option value="{{ $niv->id }}" {{ $filtros['nivel_id'] == $niv->id ? 'selected' : '' }}>
                                {{ $niv->nivel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Grupo</label>
                    <select name="grupo_id" class="form-select">
                        <option value="">— Todos —</option>
                        @foreach($gruposDisponibles as $grp)
                            <option value="{{ $grp->id }}" {{ $filtros['grupo_id'] == $grp->id ? 'selected' : '' }}>
                                {{ $grp->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-parroquia">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($filtros['catequista_id'] || $filtros['nivel_id'] || $filtros['grupo_id'])
        <div class="card card-parroquia module-card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 module-title">Alumnos encontrados</h5>
                    <small class="text-muted">{{ $alumnos->count() }} alumno(s) encontrado(s).</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Grupo</th>
                        <th>Nivel</th>
                        <th>Catequista</th>
                        <th class="text-center">Boleta</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($alumnos as $idx => $insc)
                        <tr>
                            <td>
                                <span class="cell-title">
                                    {{ $insc->alumno->apellido_paterno }}
                                    {{ $insc->alumno->apellido_materno }}
                                    {{ $insc->alumno->nombre }}
                                </span>
                                <span class="cell-subtitle">
                                    Inscripción #{{ $insc->id }}
                                </span>
                            </td>
                            <td>
                                <span class="soft-badge">
                                    <i class="bi bi-collection"></i>
                                    {{ $insc->grupo->nombre ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="soft-badge">
                                    <i class="bi bi-layers"></i>
                                    {{ $insc->asignacion->nivel->nivel ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">
                                    <i class="bi bi-person"></i>
                                    {{ $insc->asignacion->catequista->name ?? '—' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('secretaria.boletas.generar', $insc->id) }}?asignacion_id={{ $insc->asignacion->id ?? '' }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Generar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                No se encontraron alumnos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-funnel fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-0">Selecciona al menos un filtro y presiona <strong>Buscar</strong> para ver los alumnos.</p>
        </div>
    @endif
@endsection