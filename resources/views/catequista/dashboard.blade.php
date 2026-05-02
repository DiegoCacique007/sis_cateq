@extends('layouts.app_parroquia_catequista')

@section('title', 'Dashboard - Catequista')
@section('header_title', 'BIENVENIDO, CATEQUISTA')

@section('content')
    <style>
        .catequista-hero {
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--blue-main, #4facfe) 0%, var(--blue-dark, #1e3a8a) 100%);
            box-shadow: 0 18px 40px rgba(30, 58, 138, 0.18);
        }

        .catequista-card {
            border: 0;
            border-radius: 20px;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
        }

        .catequista-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 38px rgba(30, 58, 138, 0.13) !important;
        }

        .catequista-card .icon-box {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .catequista-card h6 {
            color: var(--blue-dark, #1e3a8a);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .catequista-card p {
            font-size: .88rem;
            line-height: 1.45;
        }

        .section-label {
            color: var(--blue-dark, #1e3a8a);
            font-weight: 800;
            letter-spacing: .3px;
        }

        .mini-badge {
            font-size: .72rem;
            border-radius: 999px;
            padding: .28rem .65rem;
            background: #f8fbff;
            color: var(--blue-dark, #1e3a8a);
            border: 1px solid rgba(30, 58, 138, .10);
        }

        .quick-link {
            text-decoration: none;
            color: inherit;
        }
    </style>

    <div class="catequista-hero mb-4">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill mb-3 px-3 py-2">
                        Panel principal
                    </span>

                    <h3 class="fw-bold mb-3 text-white">
                        ¡Bienvenida al módulo de Catequista!
                    </h3>

                    <p class="mb-0 fs-6" style="color: rgba(255, 255, 255, 0.88); max-width: 760px;">
                        Desde este panel podrás consultar tu lista de grupo, revisar los alumnos asignados
                        y capturar calificaciones por unidad y rubro. El sistema calculará automáticamente
                        los aportes y el promedio de cada alumno.
                    </p>
                </div>

                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-person-workspace" style="font-size: 7rem; opacity: .22;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h5 class="section-label mb-1">Tus módulos</h5>
            <small class="text-muted">Selecciona el módulo que deseas gestionar.</small>
        </div>

        <span class="mini-badge">
            <i class="bi bi-person-badge me-1"></i>
            Catequista
        </span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <a href="{{ route('catequista.mi_grupo') }}" class="quick-link">
                <div class="card catequista-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people fs-3"></i>
                        </div>
                        <h6>Lista de Grupo</h6>
                        <p class="text-muted mb-0">Consulta de forma ordenada los alumnos que tienes asignados, descargar la lista de asistencia y revisar tu comunidad, nivel y periodo.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="{{ route('catequista.evaluaciones.index') }}" class="quick-link">
                <div class="card catequista-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-clipboard-check fs-3"></i>
                        </div>
                        <h6>Captura de Calificaciones</h6>
                        <p class="text-muted mb-0">Registra calificaciones por rubro y unidad. El sistema realiza la conversión de valores y calcula el promedio general automáticamente.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 mt-4">
        <div>
            <h5 class="section-label mb-1">Flujo recomendado de trabajo</h5>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card catequista-card shadow-sm p-2 border border-primary border-opacity-10">
                <div class="card-body">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mb-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-1-circle fs-4"></i>
                    </div>
                    <h6>Consultar grupo</h6>
                    <p class="text-muted mb-0">Revisa primero qué alumnos están inscritos en tus grupos asignados.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card catequista-card shadow-sm p-2 border border-info border-opacity-10">
                <div class="card-body">
                    <div class="icon-box bg-info bg-opacity-10 text-info mb-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-2-circle fs-4"></i>
                    </div>
                    <h6>Seleccionar unidad</h6>
                    <p class="text-muted mb-0">Elige la unidad correspondiente para capturar los rubros de evaluación.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card catequista-card shadow-sm p-2 border border-success border-opacity-10">
                <div class="card-body">
                    <div class="icon-box bg-success bg-opacity-10 text-success mb-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-3-circle fs-4"></i>
                    </div>
                    <h6>Guardar calificaciones</h6>
                    <p class="text-muted mb-0">Captura las calificaciones y verifica el promedio calculado por el sistema.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
