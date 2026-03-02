@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Usuarios pendientes</h1>
</div>

@if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card shadow-sm">
  <div class="card-body">
    @if($pendientes->isEmpty())
      <div class="text-muted">No hay usuarios pendientes.</div>
    @else
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Registro</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pendientes as $u)
              <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td><span class="badge bg-secondary">{{ $u->role }}</span></td>
                <td>{{ $u->created_at?->format('Y-m-d H:i') }}</td>
                <td class="text-end">
                  <form method="POST" action="{{ route('admin.usuarios.aprobar', $u->id) }}">
                    @csrf
                    <button class="btn btn-success btn-sm">Aprobar</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection