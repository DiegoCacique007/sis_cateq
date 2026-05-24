@extends($layout_role ?? 'layouts.app_parroquia_admin')

@section('title', 'Inscripciones - Secretaría')
@section('header_title', 'Gestión de Inscripciones')

@section('content')
    <style>
        .inscripciones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .inscripcion-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
        }

        .inscripcion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.1), 0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .inscripcion-card .card-header-content {
            margin-bottom: 1rem;
            padding-right: 4.5rem;
        }

        .inscripcion-card .student-name {
            color: #1e3a8a;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }

        .inscripcion-card .student-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .inscripcion-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .inscripcion-card .info-badge {
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

        .inscripcion-card .status-badge-alta {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .inscripcion-card .status-badge-baja {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }

        .inscripcion-card .info-badge svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .inscripcion-card .card-actions {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
        }

        .inscripcion-card .btn-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
        }

        .inscripcion-card .btn-circle svg {
            width: 18px;
            height: 18px;
        }

        .inscripcion-card .btn-edit {
            border: 1px solid #3b82f6;
            color: #3b82f6;
        }

        .inscripcion-card .btn-edit:hover {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .inscripcion-card .btn-delete {
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .inscripcion-card .btn-delete:hover {
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
        }
    </style>

    <div class="card card-parroquia module-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">Inscripciones</h5>
                <small class="text-muted">Administración de alumnos inscritos en grupos y periodos.</small>
            </div>

            @if(auth()->check() && auth()->user()->role === 'secretaria')
            <button class="btn btn-parroquia rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="bi bi-plus-lg me-1"></i> Nueva inscripción
            </button>
            @endif
        </div>

        <div class="card-body border-bottom">
            <form method="GET" action="{{ route(($route_prefix ?? 'secretaria.') . 'inscripciones.index') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por alumno, grupo o periodo...">
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

        <div class="inscripciones-grid">
            @forelse($registros as $registro)
                <div class="inscripcion-card">
                    @if(auth()->check() && auth()->user()->role === 'secretaria')
                    <div class="card-actions">
                        <button type="button" class="btn-circle btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $registro->id }}" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                            </svg>
                        </button>

                        <form action="{{ route('secretaria.inscripciones.destroy', $registro->id) }}"
                              method="POST"
                              class="d-inline js-delete-form"
                              data-message="Esta acción eliminará la inscripción seleccionada.">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn-circle btn-delete" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zm3 0a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zm3 0a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5z"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endif

                    <div class="card-header-content">
                        <h4 class="student-name">
                            {{ $registro->alumno_nombre ?? $registro->alumno->nombre_completo ?? 'Alumno no disponible' }}
                        </h4>
                        <span class="student-subtitle">Alumno inscrito en el periodo actual</span>
                    </div>

                    <div class="card-body-content">
                        <span class="info-badge">
                            <i class="bi bi-calendar-event"></i>
                            {{ $registro->fecha_nacimiento ? \Carbon\Carbon::parse($registro->fecha_nacimiento)->format('d/m/Y') : 'N/D' }}
                        </span>

                        <span class="info-badge">
                            <i class="bi bi-collection"></i>
                            {{ $registro->grupo_nombre ?? $registro->grupo->nombre ?? 'Sin grupo' }}
                        </span>

                        @if((int) $registro->estado === 1)
                            <span class="info-badge status-badge-alta">
                                <i class="bi bi-check-circle"></i>
                                Alta
                            </span>
                        @else
                            <span class="info-badge status-badge-baja">
                                <i class="bi bi-x-circle"></i>
                                Baja
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    No hay inscripciones registradas.
                </div>
            @endforelse
        </div>

        <div class="card-footer bg-white">
            {{ $registros->withQueryString()->links() }}
        </div>
    </div>

    @if(auth()->check() && auth()->user()->role === 'secretaria')
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registrar inscripción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('secretaria.inscripciones.store') }}" method="POST">
                    @csrf

                    <div class="modal-body row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Alumno</label>
                            <select name="alumno_id" class="form-select" required>
                                <option value="">Selecciona un alumno</option>
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id }}" {{ old('alumno_id') == $alumno->id ? 'selected' : '' }}>
                                        {{ $alumno->text ?? ($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}
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
                        <h5 class="modal-title fw-bold">Editar inscripción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('secretaria.inscripciones.update', $registro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Alumno</label>
                                <select name="alumno_id" class="form-select" required>
                                    <option value="">Selecciona un alumno</option>
                                    @foreach($alumnos as $alumno)
                                        <option value="{{ $alumno->id }}" {{ old('alumno_id', $registro->alumno_id) == $alumno->id ? 'selected' : '' }}>
                                            {{ $alumno->text ?? ($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Grupo</label>
                                <select name="grupo_id" class="form-select" required>
                                    <option value="">Selecciona un grupo</option>
                                    @foreach($grupos as $grupo)
                                        <option value="{{ $grupo->id }}" {{ old('grupo_id', $registro->grupo_id) == $grupo->id ? 'selected' : '' }}>
                                            {{ $grupo->text ?? $grupo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Estado de inscripción</label>
                                <select name="estado" class="form-select" required>
                                    <option value="1" {{ old('estado', $registro->estado) == 1 ? 'selected' : '' }}>Alta</option>
                                    <option value="0" {{ old('estado', $registro->estado) == 0 ? 'selected' : '' }}>Baja</option>
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
    @endif
@endsection
