@extends($layout_role ?? 'layouts.app_parroquia_admin')

@php
    $route_name = ($route_prefix ?? 'secretaria.') . 'boletas.index';
    $route_generar = ($route_prefix ?? 'secretaria.') . 'boletas.generar';
@endphp

@section('title', 'Boletas - Secretaría')
@section('header_title', 'Boletas de Evaluación')
@section('header_subtitle', 'Genera boletas de calificaciones para los alumnos.')

@section('content')
    <style>
        .boletas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .boleta-card {
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
            min-height: 210px;
        }

        .boleta-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 10px 15px -3px rgba(30, 58, 138, 0.10),
                0 4px 6px -2px rgba(30, 58, 138, 0.05);
        }

        .boleta-card .card-header-content {
            margin-bottom: 1rem;
        }

        .boleta-card .student-name {
            color: #1e3a8a;
            font-weight: 700;
            font-size: 1.08rem;
            margin: 0 0 0.25rem 0;
            line-height: 1.25;
        }

        .boleta-card .student-subtitle {
            color: #64748b;
            font-weight: 400;
            font-size: 0.875rem;
            display: block;
        }

        .boleta-card .card-body-content {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
            margin-bottom: 1rem;
        }

        .boleta-card .info-badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .boleta-card .group-badge {
            background-color: #eef2ff;
            color: #2563eb;
            border: 1px solid #3b82f6;
        }

        .boleta-card .level-badge {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .boleta-card .catequista-badge {
            background-color: #fff7ed;
            color: #d97706;
            border: 1px solid #f59e0b;
        }

        .boleta-card .folio-badge {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .boleta-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: auto;
        }

        .btn-generate-pdf {
            border: 1px solid #3b82f6;
            color: #3b82f6;
            background: #ffffff;
            border-radius: 9999px;
            padding: 0.45rem 1rem;
            font-size: 0.86rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .btn-generate-pdf:hover {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.22);
        }

        .filter-card {
            background: #ffffff;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            border: 1px dashed #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            margin: 1.5rem;
        }

        .empty-state i {
            font-size: 2.4rem;
            color: #94a3b8;
            display: block;
            margin-bottom: 0.75rem;
        }

        .empty-state.full-grid {
            grid-column: 1 / -1;
            margin: 0;
        }

        .modal-content {
            border-radius: 18px;
        }

        @media (max-width: 768px) {
            .boletas-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .filter-actions {
                flex-direction: column;
            }
        }
    </style>

    @php
        $catequistaFiltro = $filtros['catequista_id'] ?? null;
        $nivelFiltro = $filtros['nivel_id'] ?? null;
        $grupoFiltro = $filtros['grupo_id'] ?? null;

        $hayFiltros = $catequistaFiltro || $nivelFiltro || $grupoFiltro;
    @endphp

    <div class="card card-parroquia module-card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div>
                <h5 class="fw-bold mb-0 module-title">
                    <i class="bi bi-printer me-2"></i>
                    Generar Boletas
                </h5>
                <small class="text-muted">
                    Filtra por catequista, nivel y grupo para ver los alumnos y generar sus boletas.
                </small>
            </div>
        </div>

        <div class="card-body filter-card">
            <form method="GET" action="{{ route($route_name) }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Catequista</label>
                    <select name="catequista_id" class="form-select">
                        <option value="">— Todas —</option>
                        @foreach($catequistas as $cat)
                            <option value="{{ $cat->id }}" {{ $catequistaFiltro == $cat->id ? 'selected' : '' }}>
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
                            <option value="{{ $niv->id }}" {{ $nivelFiltro == $niv->id ? 'selected' : '' }}>
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
                            <option value="{{ $grp->id }}" {{ $grupoFiltro == $grp->id ? 'selected' : '' }}>
                                {{ $grp->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="filter-actions">
                        <button class="btn btn-parroquia flex-fill" style="height: 46px;">
                            <i class="bi bi-search me-1"></i>
                            Buscar
                        </button>

                        <a href="{{ route($route_name) }}"
                           class="btn btn-outline-secondary flex-fill d-flex align-items-center justify-content-center"
                           style="height: 46px;">
                            <i class="bi bi-x-circle me-1"></i>
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($hayFiltros)
        <div class="card card-parroquia module-card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 module-title">Alumnos encontrados</h5>
                    <small class="text-muted">
                        {{ $alumnos->count() }} alumno(s) encontrado(s).
                    </small>
                </div>

                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                    <i class="bi bi-file-earmark-pdf me-1"></i>
                    Boletas disponibles
                </span>
            </div>

            <div class="boletas-grid">
                @forelse($alumnos as $idx => $insc)
                    <div class="boleta-card">
                        <div class="card-header-content">
                            <h4 class="student-name">
                                {{ $insc->alumno->apellido_paterno }}
                                {{ $insc->alumno->apellido_materno }}
                                {{ $insc->alumno->nombre }}
                            </h4>

                            <span class="student-subtitle">
                                Alumno inscrito para generación de boleta
                            </span>
                        </div>

                        <div class="card-body-content">
                            <span class="info-badge folio-badge">
                                <i class="bi bi-hash"></i>
                                Inscripción {{ $insc->id }}
                            </span>

                            <span class="info-badge group-badge">
                                <i class="bi bi-collection"></i>
                                {{ $insc->grupo->nombre ?? 'Sin grupo' }}
                            </span>

                            <span class="info-badge level-badge">
                                <i class="bi bi-layers"></i>
                                {{ $insc->asignacion->nivel->nivel ?? 'Sin nivel' }}
                            </span>

                            <span class="info-badge catequista-badge">
                                <i class="bi bi-person"></i>
                                {{ $insc->asignacion->catequista->name ?? 'Sin catequista' }}
                            </span>
                        </div>

                        <div class="boleta-actions">
                            <a href="{{ route($route_generar, $insc->id) }}?asignacion_id={{ $insc->asignacion->id ?? '' }}"
                               target="_blank"
                               class="btn-generate-pdf">
                                <i class="bi bi-file-earmark-pdf"></i>
                                Generar boleta
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state full-grid">
                        <i class="bi bi-inbox"></i>
                        No se encontraron alumnos con los filtros seleccionados.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <div class="empty-state">
            <i class="bi bi-funnel"></i>
            <p class="mb-0">
                Selecciona al menos un filtro y presiona <strong>Buscar</strong> para ver los alumnos.
            </p>
        </div>
    @endif
@endsection
