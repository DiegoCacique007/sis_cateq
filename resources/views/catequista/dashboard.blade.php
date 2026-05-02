@extends('layouts.app_parroquia_admin')

@section('title', 'Dashboard - Catequista')
@section('header_title', 'BIENVENIDO, CATEQUISTA')
@section('header_subtitle', 'Panel principal para consultar grupo y capturar calificaciones.')

@section('content')
    <div class="card card-parroquia border-0 shadow-sm mb-4" style="border-radius: 22px; overflow: hidden;">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 mb-3">
                    Panel del catequista
                </span>

                    <h3 class="fw-bold mb-3" style="color: var(--blue-dark, #1e3a8a);">
                        Bienvenido al módulo de Catequista
                    </h3>

                    <p class="text-muted mb-0" style="max-width: 760px;">
                        Desde este panel podrás consultar tu lista de grupo, revisar los alumnos asignados
                        y capturar calificaciones por unidad y rubro. El sistema calculará automáticamente
                        los aportes y el promedio de cada alumno.
                    </p>
                </div>

                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-person-workspace" style="font-size: 6rem; color: var(--blue-main, #4facfe); opacity: .28;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <a href="{{ route('catequista.mi_grupo') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 18px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary"
                                 style="width: 58px; height: 58px; border-radius: 16px;">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>

                            <div>
                                <h5 class="fw-bold mb-2" style="color: var(--blue-dark, #1e3a8a);">
                                    Lista de Grupo
                                </h5>

                                <p class="text-muted mb-3">
                                    Consulta de forma ordenada los alumnos que tienes asignados por grupo,
                                    comunidad, nivel y periodo.
                                </p>

                                <span class="soft-badge">
                                <i class="bi bi-arrow-right-circle"></i>
                                Ver mi grupo
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="{{ route('catequista.evaluaciones.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 18px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success"
                                 style="width: 58px; height: 58px; border-radius: 16px;">
                                <i class="bi bi-clipboard-check-fill fs-3"></i>
                            </div>

                            <div>
                                <h5 class="fw-bold mb-2" style="color: var(--blue-dark, #1e3a8a);">
                                    Captura de Calificaciones
                                </h5>

                                <p class="text-muted mb-3">
                                    Registra calificaciones por rubro y unidad. El sistema realiza la conversión
                                    de valores y calcula el promedio automáticamente.
                                </p>

                                <span class="soft-badge">
                                <i class="bi bi-calculator"></i>
                                Capturar calificaciones
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 18px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3" style="color: var(--blue-dark, #1e3a8a);">
                Flujo recomendado de trabajo
            </h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 h-100" style="border-radius: 16px; background: #f8fbff; border: 1px solid rgba(79, 172, 254, .20);">
                    <span class="soft-badge mb-2">
                        <i class="bi bi-1-circle"></i>
                        Paso 1
                    </span>

                        <span class="cell-title">Consultar grupo</span>
                        <span class="cell-subtitle">
                        Revisa primero qué alumnos están inscritos en tu grupo asignado.
                    </span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 h-100" style="border-radius: 16px; background: #f8fbff; border: 1px solid rgba(79, 172, 254, .20);">
                    <span class="soft-badge mb-2">
                        <i class="bi bi-2-circle"></i>
                        Paso 2
                    </span>

                        <span class="cell-title">Seleccionar unidad</span>
                        <span class="cell-subtitle">
                        Elige la unidad correspondiente para capturar los rubros de evaluación.
                    </span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 h-100" style="border-radius: 16px; background: #f8fbff; border: 1px solid rgba(79, 172, 254, .20);">
                    <span class="soft-badge mb-2">
                        <i class="bi bi-3-circle"></i>
                        Paso 3
                    </span>

                        <span class="cell-title">Guardar calificaciones</span>
                        <span class="cell-subtitle">
                        Captura las calificaciones y verifica el promedio calculado por el sistema.
                    </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
