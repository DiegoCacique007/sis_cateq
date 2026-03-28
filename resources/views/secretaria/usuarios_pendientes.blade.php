@extends('layouts.app_parroquia_admin')

@section('title', 'Registros Pendientes | Sis_Cateq')
@section('subtitle', 'Aprobación de accesos')
@section('header_title', 'Registros Pendientes')
@section('header_subtitle', 'Usuarios registrados que aún no tienen acceso.')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
    <span class="badge bg-primary rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.9rem;">
      <i class="bi bi-person-lines-fill me-1"></i> {{ $pendientes->count() }} Solicitudes Pendientes
    </span>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-parroquia btn-sm px-4 rounded-pill fw-bold" href="{{ route('secretaria.dashboard') }}">
                <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success fw-bold shadow-sm rounded-3 py-3">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
        </div>
    @endif

    <div class="card card-parroquia border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold" style="color: var(--blue-dark, #1e3a8a);">Lista de solicitudes</h5>

            <div class="input-group input-group-sm shadow-sm" style="max-width: 350px;">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control border-start-0 ps-0" placeholder="Nombre o correo...">
            </div>
        </div>

        <div class="card-body p-0">
            @if($pendientes->isEmpty())
                <div class="alert alert-info m-4 border-0 bg-info bg-opacity-10 text-primary d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    No hay usuarios pendientes por aprobar en este momento.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="pendientesTable">
                        <thead class="bg-light text-secondary" style="border-bottom: 2px solid rgba(79, 172, 254, 0.2);">
                        <tr>
                            <th class="ps-4" style="min-width: 170px;">Nombre</th>
                            <th style="min-width: 220px;">Correo</th>
                            <th style="width: 150px;">Solicita</th>
                            <th style="width: 140px;">Rol actual</th>
                            <th style="width: 170px;">Registro</th>
                            <th class="text-end pe-4" style="width: 360px;">Acciones</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($pendientes as $u)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $u->name }}</td>
                                <td class="text-muted">{{ $u->email }}</td>

                                <td>
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">
                    {{ strtoupper($u->requested_role ?? 'catequista') }}
                  </span>
                                </td>

                                <td>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1">
                    {{ strtoupper($u->role) }}
                  </span>
                                </td>

                                <td class="text-muted small">
                                    {{ $u->created_at?->format('Y-m-d H:i') }}
                                </td>

                                <td class="text-end pe-4">
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">

                                        <form method="POST" action="{{ route('secretaria.usuarios.aprobar', $u->id) }}" class="d-flex gap-2">
                                            @csrf

                                            <select name="role" class="form-select form-select-sm border-success text-success fw-semibold" style="max-width: 190px;" required>
                                                <option value="catequista" {{ ($u->requested_role ?? 'catequista')=='catequista' ? 'selected' : '' }}>Catequista</option>
                                                <option value="coord_comunidad" {{ ($u->requested_role ?? '')=='coord_comunidad' ? 'selected' : '' }}>Coord. Comunidad</option>
                                                <option value="coord_general" {{ ($u->requested_role ?? '')=='coord_general' ? 'selected' : '' }}>Coord. General</option>
                                                <option value="parroco" {{ ($u->requested_role ?? '')=='parroco' ? 'selected' : '' }}>Párroco</option>
                                                <option value="secretaria" {{ ($u->requested_role ?? '')=='secretaria' ? 'selected' : '' }}>Secretaria</option>
                                            </select>

                                            <button class="btn btn-sm btn-success shadow-sm fw-bold px-3"
                                                    onclick="return confirm('¿Aprobar a {{ $u->name }} con el rol seleccionado?');">
                                                <i class="bi bi-check-lg me-1"></i> Aprobar
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('secretaria.usuarios.bloquear', $u->id) }}">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm fw-bold px-3"
                                                    onclick="return confirm('¿Bloquear a {{ $u->name }}? Esta acción impedirá su acceso.');">
                                                <i class="bi bi-slash-circle me-1"></i> Bloquear
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white border-0 p-3">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-lightbulb text-warning me-1"></i> <strong>Tip:</strong> revisa la solicitud antes de aprobar. Si no reconoces el correo/nombre, puedes bloquear la cuenta.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Tu script original de búsqueda, sin modificaciones, funcionando perfecto.
        const input = document.getElementById('searchInput');
        const table = document.getElementById('pendientesTable');

        if (input && table) {
            input.addEventListener('keyup', function () {
                const q = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(r => {
                    const text = r.innerText.toLowerCase();
                    r.style.display = text.includes(q) ? '' : 'none';
                });
            });
        }
    </script>
@endsection
