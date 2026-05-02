@extends('layouts.app_parroquia_admin')

@section('title', 'Dashboard - Secretaría')
@section('header_title', 'BIENVENIDA, SECRETARÍA')

@section('content')
    <style>
        .secretaria-hero {
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--blue-main, #4facfe) 0%, var(--blue-dark, #1e3a8a) 100%);
            box-shadow: 0 18px 40px rgba(30, 58, 138, 0.18);
        }

        .secretaria-card {
            border: 0;
            border-radius: 20px;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
        }

        .secretaria-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 38px rgba(30, 58, 138, 0.13) !important;
        }

        .secretaria-card .icon-box {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .secretaria-card h6 {
            color: var(--blue-dark, #1e3a8a);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .secretaria-card p {
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

        .small-formal-toast {
            border-radius: 14px !important;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .16) !important;
            border: 1px solid rgba(30, 58, 138, .10) !important;
        }
    </style>

    <div class="secretaria-hero mb-4">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill mb-3 px-3 py-2">
                    Panel principal
                </span>

                    <h3 class="fw-bold mb-3 text-white">
                        ¡Bienvenida al módulo de Secretaría!
                    </h3>

                    <p class="mb-0 fs-6" style="color: rgba(255, 255, 255, 0.88); max-width: 760px;">
                        Desde este panel se accede a la administración general del sistema de catequesis:
                        alumnos, tutores, inscripciones, grupos, niveles, periodos, unidades, rubros,
                        evaluaciones y aprobación de usuarios.
                    </p>
                </div>

                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-shield-lock" style="font-size: 7rem; opacity: .22;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h5 class="section-label mb-1">Accesos administrativos</h5>
            <small class="text-muted">Selecciona el módulo que deseas gestionar.</small>
        </div>

        <span class="mini-badge">
        <i class="bi bi-person-badge me-1"></i>
        Secretaría / Administrador general
    </span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('secretaria.alumnos.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people fs-3"></i>
                        </div>
                        <h6>Alumnos</h6>
                        <p class="text-muted mb-0">Registra, consulta, edita y da de baja alumnos del sistema.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('secretaria.tutores.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-info bg-opacity-10 text-info">
                            <i class="bi bi-person-hearts fs-3"></i>
                        </div>
                        <h6>Tutores</h6>
                        <p class="text-muted mb-0">Administra tutores y su relación con los alumnos registrados.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('secretaria.inscripciones.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-card-checklist fs-3"></i>
                        </div>
                        <h6>Inscripciones</h6>
                        <p class="text-muted mb-0">Inscribe alumnos en grupos y periodos de catequesis.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('secretaria.asigna_grupo.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-diagram-3 fs-3"></i>
                        </div>
                        <h6>Asignación de grupos</h6>
                        <p class="text-muted mb-0">Relaciona comunidad, grupo, nivel, periodo y catequista.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('secretaria.usuarios.pendientes') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-person-lines-fill fs-3"></i>
                        </div>
                        <h6>Usuarios pendientes</h6>
                        <p class="text-muted mb-0">Aprueba o bloquea solicitudes de acceso al sistema.</p>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 mt-4">
        <div>
            <h5 class="section-label mb-1">Catálogos del sistema</h5>
            <small class="text-muted">Administra los datos base utilizados por los módulos principales.</small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('secretaria.comunidades.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-geo-alt fs-3"></i>
                        </div>
                        <h6>Comunidades</h6>
                        <p class="text-muted mb-0">Gestiona las comunidades registradas.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="{{ route('secretaria.niveles.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-layers fs-3"></i>
                        </div>
                        <h6>Niveles</h6>
                        <p class="text-muted mb-0">Administra niveles de catequesis.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="{{ route('secretaria.grupos.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-info bg-opacity-10 text-info">
                            <i class="bi bi-collection fs-3"></i>
                        </div>
                        <h6>Grupos</h6>
                        <p class="text-muted mb-0">Gestiona los grupos disponibles.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="{{ route('secretaria.periodos.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-calendar-range fs-3"></i>
                        </div>
                        <h6>Periodos</h6>
                        <p class="text-muted mb-0">Administra ciclos y periodos activos.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="{{ route('secretaria.unidades.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-book fs-3"></i>
                        </div>
                        <h6>Unidades</h6>
                        <p class="text-muted mb-0">Configura unidades por nivel.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="{{ route('secretaria.rubros.index') }}" class="quick-link">
                <div class="card secretaria-card shadow-sm p-2">
                    <div class="card-body">
                        <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-ui-checks-grid fs-3"></i>
                        </div>
                        <h6>Rubros</h6>
                        <p class="text-muted mb-0">Gestiona criterios de evaluación.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const successMessage = @json(session('success'));
            const statusMessage = @json(session('status'));
            const errorMessage = @json(session('error'));

            if (!window.Swal) {
                return;
            }

            const config = {
                toast: true,
                position: 'top-end',
                timer: 2600,
                timerProgressBar: true,
                showConfirmButton: false,
                width: '320px',
                customClass: { popup: 'small-formal-toast' }
            };

            if (successMessage) {
                Swal.fire({
                    ...config,
                    icon: 'success',
                    title: 'Correcto',
                    text: successMessage
                });
            }

            if (statusMessage) {
                Swal.fire({
                    ...config,
                    icon: 'success',
                    title: 'Correcto',
                    text: statusMessage
                });
            }

            if (errorMessage) {
                Swal.fire({
                    ...config,
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    timer: 3400
                });
            }
        });
    </script>
@endsection
