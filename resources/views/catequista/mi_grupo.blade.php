@extends('layouts.app_parroquia_admin')

@section('title', 'Mi Grupo - Catequista')
@section('header_title', 'Mi Grupo Asignado')

@section('content')
    @if($asignacion)
        <div class="card card-parroquia border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 module-title">Información del grupo</h5>
                <small class="text-muted">Grupo asignado actualmente al catequista.</small>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <span class="cell-subtitle">Comunidad</span>
                        <span class="cell-title">{{ $asignacion->comunidad }}</span>
                    </div>

                    <div class="col-md-3">
                        <span class="cell-subtitle">Grupo</span>
                        <span class="cell-title">{{ $asignacion->grupo }}</span>
                    </div>

                    <div class="col-md-3">
                        <span class="cell-subtitle">Nivel</span>
                        <span class="cell-title">{{ $asignacion->nivel }}</span>
                    </div>

                    <div class="col-md-3">
                        <span class="cell-subtitle">Periodo</span>
                        <span class="cell-title">{{ $asignacion->periodo }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-parroquia border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 module-title">Lista de alumnos y Asistencia</h5>
                    <small class="text-muted">Alumnos inscritos en el grupo asignado.</small>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('catequista.mi_grupo.exportar_asistencia') }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="bi bi-file-earmark-excel me-1"></i> Descargar Excel de Asistencia
                    </a>
                    <span class="soft-badge">
                        <i class="bi bi-people"></i>
                        {{ $alumnos->count() }} alumno(s)
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Estado</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($alumnos as $index => $alumno)
                        <tr>
                            <td>
                                <span class="cell-title">{{ $alumno->alumno }}</span>
                                <span class="cell-subtitle">Alumno inscrito en tu grupo</span>
                            </td>

                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                    Activo
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted">
                                No hay alumnos inscritos en este grupo.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm" style="border-radius: 18px;">
            <div class="card-body text-center p-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5 class="fw-bold">Sin grupo asignado</h5>
                <p class="text-muted mb-0">
                    Todavía no tienes un grupo asignado por Secretaría.
                </p>
            </div>
        </div>
    @endif
@endsection
