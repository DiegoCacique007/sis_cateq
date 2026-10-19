@extends($layout_role ?? 'layouts.app_parroquia_admin')

@section('title', 'Cambiar contraseña')
@section('header_title', 'Cambiar contraseña')
@section('header_subtitle', 'Actualiza la contraseña con la que inicias sesión')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">
                        Por seguridad, escribe tu contraseña actual. Al guardar, se cerrarán las sesiones abiertas en otros equipos.
                    </p>

                    <form method="POST" action="{{ route('password.update') }}" autocomplete="off">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Contraseña actual</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   autocomplete="current-password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Nueva contraseña</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   autocomplete="new-password" minlength="8" required>
                            <div class="form-text">Al menos 8 caracteres.</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirmar nueva contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" autocomplete="new-password" minlength="8" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-key me-1"></i> Guardar contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
