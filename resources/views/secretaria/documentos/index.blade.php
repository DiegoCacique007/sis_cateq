@extends('layouts.app_parroquia_admin')

@section('title', 'Emisión de Documentos')
@section('header_title', 'MÓDULO DE DOCUMENTOS')

@section('content')
    <style>
        .doc-hero {
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
            box-shadow: 0 18px 40px rgba(180, 83, 9, 0.18);
        }
        .doc-card {
            border: 0;
            border-radius: 20px;
            transition: transform .18s ease, box-shadow .18s ease;
            background: #fff;
            overflow: hidden;
            height: 100%;
        }
        .doc-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(180, 83, 9, 0.12) !important;
        }
        .doc-card .icon-box {
            width: 58px; height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }
        .doc-card h6 { color: #b45309; font-weight: 800; margin-bottom: 8px; }
        .doc-card p { font-size: .88rem; line-height: 1.45; }
        .section-label-doc { color: #b45309; font-weight: 800; letter-spacing: .3px; }
    </style>

    <div class="doc-hero mb-4">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill mb-3 px-3 py-2">
                        <i class="bi bi-shield-lock me-1"></i> Solo Administrador
                    </span>
                    <h3 class="fw-bold mb-3 text-white">Emisión de Documentos</h3>
                    <p class="mb-0 fs-6" style="color: rgba(255,255,255,0.88); max-width: 760px;">
                        Genera boletas generales, certificados de Primera Comunión y certificados de Confirmación.
                        El sistema validará automáticamente que los datos de sacramentos previos estén completos antes de permitir la generación.
                    </p>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <i class="bi bi-printer" style="font-size: 7rem; opacity: .22;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ BOLETAS GENERALES ═══ --}}
    <div class="card card-parroquia shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="mb-0 fw-bold" style="color: var(--blue-dark);">
                <i class="bi bi-file-earmark-text me-2"></i>Boletas Generales
            </h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-3">Genera boletas de calificaciones. Selecciona un grupo para impresión masiva, u opcionalmente un alumno para impresión individual.</p>

            <form method="POST" action="{{ route('secretaria.documentos.boletas') }}" target="_blank">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Grupo <span class="text-danger">*</span></label>
                        <select name="grupo_id" class="form-select" required id="boletaGrupoId">
                            <option value="">Selecciona un grupo</option>
                            @foreach($grupos as $grupo)
                                <option value="{{ $grupo->id }}">{{ $grupo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Alumno <small class="text-muted">(opcional — individual)</small></label>
                        <select name="alumno_id" class="form-select" id="boletaAlumnoId">
                            <option value="">Todos los alumnos del grupo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-parroquia w-100">
                            <i class="bi bi-printer me-1"></i> Generar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ CERTIFICADO DE PRIMERA COMUNIÓN ═══ --}}
    <div class="card card-parroquia shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="mb-0 fw-bold" style="color: var(--blue-dark);">
                <i class="bi bi-patch-check me-2"></i>Certificado de Primera Comunión
            </h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-2">Genera un certificado de Primera Comunión para un alumno específico.</p>
            <div class="alert alert-warning border-0 rounded-4 py-2 px-3 mb-3" style="font-size: .88rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Requisito:</strong> El alumno debe tener completos los <strong>Datos de Bautizo</strong> (lugar, fecha, libro y acta).
            </div>

            <form method="POST" action="{{ route('secretaria.documentos.certificado.primera_comunion') }}" target="_blank">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Alumno <span class="text-danger">*</span></label>
                        <select name="alumno_id" class="form-select" required id="certPCAlumnoId">
                            <option value="">Selecciona un alumno</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-parroquia w-100">
                            <i class="bi bi-patch-check me-1"></i> Generar certificado
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ CERTIFICADO DE CONFIRMACIÓN ═══ --}}
    <div class="card card-parroquia shadow-sm mb-4">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="mb-0 fw-bold" style="color: var(--blue-dark);">
                <i class="bi bi-award me-2"></i>Certificado de Confirmación
            </h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-2">Genera un certificado de Confirmación para un alumno específico.</p>
            <div class="alert alert-danger border-0 rounded-4 py-2 px-3 mb-3" style="font-size: .88rem;">
                <i class="bi bi-exclamation-octagon me-1"></i>
                <strong>Requisitos:</strong> El alumno debe tener completos los <strong>Datos de Bautizo</strong> Y los <strong>Datos de Primera Comunión</strong> (lugar, fecha, libro y acta de cada uno).
            </div>

            <form method="POST" action="{{ route('secretaria.documentos.certificado.confirmacion') }}" target="_blank">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Alumno <span class="text-danger">*</span></label>
                        <select name="alumno_id" class="form-select" required id="certConfAlumnoId">
                            <option value="">Selecciona un alumno</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-parroquia w-100">
                            <i class="bi bi-award me-1"></i> Generar certificado
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buscarUrl = @json(route('secretaria.documentos.buscar_alumnos'));

    // ── Carga de alumnos para boletas al seleccionar grupo ──
    const boletaGrupo = document.getElementById('boletaGrupoId');
    const boletaAlumno = document.getElementById('boletaAlumnoId');

    if (boletaGrupo && boletaAlumno) {
        boletaGrupo.addEventListener('change', function () {
            boletaAlumno.innerHTML = '<option value="">Cargando...</option>';

            const grupoId = this.value;
            if (!grupoId) {
                boletaAlumno.innerHTML = '<option value="">Todos los alumnos del grupo</option>';
                return;
            }

            fetch(buscarUrl + '?grupo_id=' + grupoId)
                .then(r => r.json())
                .then(data => {
                    let html = '<option value="">Todos los alumnos del grupo</option>';
                    data.forEach(a => {
                        html += `<option value="${a.id}">${a.nombre_completo}</option>`;
                    });
                    boletaAlumno.innerHTML = html;
                })
                .catch(() => {
                    boletaAlumno.innerHTML = '<option value="">Error al cargar alumnos</option>';
                });
        });
    }

    // ── Carga de todos los alumnos activos para certificados ──
    function cargarAlumnosCertificados() {
        fetch(buscarUrl)
            .then(r => r.json())
            .then(data => {
                let html = '<option value="">Selecciona un alumno</option>';
                data.forEach(a => {
                    html += `<option value="${a.id}">${a.nombre_completo}</option>`;
                });

                const selectPC = document.getElementById('certPCAlumnoId');
                const selectConf = document.getElementById('certConfAlumnoId');

                if (selectPC) selectPC.innerHTML = html;
                if (selectConf) selectConf.innerHTML = html;
            })
            .catch(() => {
                console.error('Error al cargar alumnos para certificados.');
            });
    }

    cargarAlumnosCertificados();
});
</script>
@endpush
