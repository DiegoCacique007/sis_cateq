@extends('layouts.app_parroquia')

@section('title', 'Registros pendientes')
@section('subtitle', 'Secretaría · Aprobación de accesos')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-1" style="font-family:'Cinzel',serif; color: var(--gold);">Registros pendientes</h1>
    <div class="text-muted">
      Usuarios registrados que aún no tienen acceso.
      <span class="badge badge-gold ms-1">{{ $pendientes->count() }}</span>
    </div>
  </div>

  <div class="d-flex gap-2">
    <a class="btn btn-outline-parroquia btn-sm" href="{{ route('secretaria.dashboard') }}">Volver</a>
  </div>
</div>

@if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card card-parroquia">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="fw-semibold">Lista de solicitudes</div>

    <div class="input-group" style="max-width: 380px;">
      <span class="input-group-text">Buscar</span>
      <input id="searchInput" type="text" class="form-control" placeholder="Nombre o correo...">
    </div>
  </div>

  <div class="card-body">
    @if($pendientes->isEmpty())
      <div class="alert alert-info mb-0">No hay usuarios pendientes por aprobar.</div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="pendientesTable">
          <thead class="table-dark">
            <tr>
              <th style="min-width: 170px;">Nombre</th>
              <th style="min-width: 220px;">Correo</th>
              <th style="width: 150px;">Solicita</th>
              <th style="width: 140px;">Rol actual</th>
              <th style="width: 170px;">Registro</th>
              <th class="text-end" style="width: 360px;">Acciones</th>
            </tr>
          </thead>

          <tbody>
            @foreach($pendientes as $u)
              <tr>
                <td class="fw-semibold">{{ $u->name }}</td>
                <td class="text-muted">{{ $u->email }}</td>

                <td>
                  <span class="badge badge-gold">
                    {{ $u->requested_role ?? 'catequista' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-muted">{{ $u->role }}</span>
                </td>

                <td class="text-muted small">
                  {{ $u->created_at?->format('Y-m-d H:i') }}
                </td>

                <td class="text-end">
                  <div class="d-flex gap-2 justify-content-end flex-wrap">

                    <form method="POST" action="{{ route('secretaria.usuarios.aprobar', $u->id) }}" class="d-flex gap-2">
                      @csrf

                      <select name="role" class="form-select form-select-sm" style="max-width: 200px;" required>
                        <option value="catequista" {{ ($u->requested_role ?? 'catequista')=='catequista' ? 'selected' : '' }}>Catequista</option>
                        <option value="coord_comunidad" {{ ($u->requested_role ?? '')=='coord_comunidad' ? 'selected' : '' }}>Coordinador Comunidad</option>
                        <option value="coord_general" {{ ($u->requested_role ?? '')=='coord_general' ? 'selected' : '' }}>Coordinador General</option>
                        <option value="parroco" {{ ($u->requested_role ?? '')=='parroco' ? 'selected' : '' }}>Parroco</option>
                        <option value="secretaria" {{ ($u->requested_role ?? '')=='secretaria' ? 'selected' : '' }}>Secretaria</option>
                      </select>

                      <button class="btn btn-sm btn-parroquia"
                              onclick="return confirm('¿Aprobar a {{ $u->name }} con el rol seleccionado?');">
                        Aprobar
                      </button>
                    </form>

                    <form method="POST" action="{{ route('secretaria.usuarios.bloquear', $u->id) }}">
                      @csrf
                      <button class="btn btn-outline-danger btn-sm"
                              onclick="return confirm('¿Bloquear a {{ $u->name }}? Esta acción impedirá su acceso.');">
                        Bloquear
                      </button>
                    </form>

                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="text-muted small mt-2">
        Tip: revisa la solicitud antes de aprobar. Si no reconoces el correo/nombre, puedes bloquear la cuenta.
      </div>
    @endif
  </div>
</div>

<script>
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