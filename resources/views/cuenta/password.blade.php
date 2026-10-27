@extends($layout_role ?? 'layouts.app_parroquia_admin')

@section('title', 'Mi perfil')
@section('header_title', 'Mi perfil')
@section('header_subtitle', 'Tu información personal y la seguridad de tu cuenta')

@section('content')
    @php
        $usuario = auth()->user();
        $rol = $usuario->operationalRole();
        $esCoordinador = $rol === \App\Enums\UserRole::CoordinadorComunidades;
        $estado = (string) ($usuario->status ?? '');
        $estados = [
            'aprobado' => ['Aprobada', 'success', 'bi-check-circle-fill'],
            'pendiente' => ['Pendiente', 'warning', 'bi-clock-fill'],
            'bloqueado' => ['Bloqueada', 'danger', 'bi-slash-circle-fill'],
        ];
        [$estadoTexto, $estadoColor, $estadoIcono] = $estados[$estado] ?? ['No disponible', 'secondary', 'bi-info-circle-fill'];
        $inicial = mb_strtoupper(mb_substr(trim((string) $usuario->name), 0, 1)) ?: 'U';
        $erroresClave = $errors->getBag('updatePassword');
        $errorActual = $errors->first('current_password') ?: $erroresClave->first('current_password');
        $errorNueva = $errors->first('password') ?: $erroresClave->first('password');
        $errorConfirmacion = $errors->first('password_confirmation') ?: $erroresClave->first('password_confirmation');
    @endphp

    <style>
        .perfil-page{--perfil-azul:#1167a8;--perfil-acento:#3499e7;--perfil-borde:#d9eaf8;--perfil-texto:#334155;--perfil-suave:#f0f8ff;color:var(--perfil-texto)}
        .perfil-page .perfil-hero{position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(120deg,#49a8ed,#267fc9);padding:1.75rem 2rem;color:#fff;box-shadow:0 12px 28px rgba(38,127,201,.18)}
        .perfil-page .perfil-hero::after{content:"";position:absolute;width:210px;height:210px;right:-70px;top:-115px;border-radius:50%;border:40px solid rgba(255,255,255,.11);pointer-events:none}
        .perfil-page .perfil-hero-eyebrow{display:inline-flex;align-items:center;gap:.45rem;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.16);padding:.4rem .8rem;border-radius:100px;font-size:.77rem;font-weight:600;letter-spacing:.02em}
        .perfil-page .perfil-avatar{width:74px;height:74px;border-radius:20px;background:rgba(255,255,255,.95);color:var(--perfil-azul);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;flex-shrink:0;box-shadow:0 7px 18px rgba(14,82,134,.18)}
        .perfil-page .perfil-panel{height:100%;border:1px solid var(--perfil-borde);border-radius:18px;background:#fff;box-shadow:0 5px 20px rgba(38,127,201,.065);overflow:hidden}
        .perfil-page .perfil-panel-head{display:flex;align-items:center;gap:.85rem;padding:1.4rem 1.5rem;border-bottom:1px solid #e7f2fc}
        .perfil-page .perfil-icon{width:45px;height:45px;border-radius:13px;background:#e5f4ff;color:var(--perfil-acento);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
        .perfil-page .perfil-heading{font-size:1.07rem;font-weight:800;color:var(--perfil-azul);margin:0}
        .perfil-page .perfil-panel-body{padding:1.5rem}
        .perfil-page .perfil-info{display:flex;align-items:flex-start;gap:.85rem;padding:.95rem 0;border-bottom:1px solid #edf5fc}
        .perfil-page .perfil-info:first-child{padding-top:0}
        .perfil-page .perfil-info:last-child{border:0;padding-bottom:0}
        .perfil-page .perfil-info-icon{width:38px;height:38px;border-radius:11px;background:var(--perfil-suave);color:#4295cd;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .perfil-page .perfil-info-label{display:block;color:#64748b;font-size:.78rem;font-weight:600;margin-bottom:.22rem}
        .perfil-page .perfil-info-value{display:block;color:#0f172a;font-weight:650;overflow-wrap:anywhere}
        .perfil-page .perfil-note{background:#f0f8ff;border:1px solid #d6eafb;border-radius:12px;padding:.85rem 1rem;color:#52677b;font-size:.81rem;line-height:1.5}
        .perfil-page .perfil-form .form-label{font-size:.86rem;font-weight:700;color:#334155;margin-bottom:.5rem}
        .perfil-page .perfil-form .form-control{border:1px solid #cddfed;border-radius:11px;min-height:46px;padding:.65rem .9rem;font-size:.93rem}
        .perfil-page .perfil-form .form-control:focus{border-color:var(--perfil-acento);box-shadow:0 0 0 .2rem rgba(52,153,231,.14)}
        .perfil-page .perfil-form .input-group>.form-control{border-top-right-radius:0;border-bottom-right-radius:0}
        .perfil-page .perfil-form .input-group>.btn{border:1px solid #cddfed;border-left:0;border-radius:0 11px 11px 0;background:#f2f9ff;color:#47758f;padding:0 .85rem}
        .perfil-page .perfil-form .input-group>.btn:hover{background:#e5f4ff;color:var(--perfil-azul)}
        .perfil-page .perfil-form .input-group:focus-within>.btn{border-color:var(--perfil-acento)}
        .perfil-page .perfil-save{border:0;border-radius:999px;background:linear-gradient(135deg,#49a8ed,#237bc7);color:#fff;font-weight:700;padding:.78rem 1.25rem;box-shadow:0 6px 14px rgba(38,127,201,.2);transition:transform .2s,box-shadow .2s}
        .perfil-page .perfil-save:hover{color:#fff;transform:translateY(-2px);box-shadow:0 9px 20px rgba(38,127,201,.28)}
        .perfil-page .perfil-state{font-size:.77rem;font-weight:700;padding:.4rem .75rem;border-radius:50rem}
        .perfil-page .bi-key.text-primary{color:var(--perfil-acento)!important}
        @media(max-width:575.98px){.perfil-page .perfil-hero{padding:1.35rem;border-radius:16px}.perfil-page .perfil-avatar{width:59px;height:59px;border-radius:16px;font-size:1.6rem}.perfil-page .perfil-panel-head,.perfil-page .perfil-panel-body{padding:1.1rem}.perfil-page .perfil-save{width:100%}}
        @media(prefers-reduced-motion:reduce){.perfil-page .perfil-save{transition:none}.perfil-page .perfil-save:hover{transform:none}}
    </style>

    <div class="perfil-page">
        <div class="perfil-hero mb-4">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 position-relative" style="z-index:1">
                <div class="perfil-avatar" aria-hidden="true">{{ $inicial }}</div>
                <div class="flex-grow-1">
                    <span class="perfil-hero-eyebrow mb-2"><i class="bi bi-shield-check" aria-hidden="true"></i> La Asunción de María</span>
                    <h1 class="h4 fw-bold text-white mb-1">Mi perfil</h1>
                    <p class="mb-0 small" style="color:rgba(255,255,255,.85)">Consulta tus datos personales y protege el acceso a tu cuenta.</p>
                </div>
                <span class="badge rounded-pill border border-white border-opacity-25 bg-white bg-opacity-25 text-white px-3 py-2 align-self-start align-self-sm-center">
                <i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ $rol?->label() ?? 'Sin rol asignado' }}
            </span>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session('status') === 'password-updated' ? 'Tu contraseña se actualizó correctamente.' : session('status') }}</span>
            </div>
        @endif

        <div class="row g-4 align-items-stretch">
            <div class="col-12 col-lg-6">
                <section class="perfil-panel" aria-labelledby="personal-info-title">
                    <div class="perfil-panel-head">
                        <span class="perfil-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                        <div>
                            <h2 id="personal-info-title" class="perfil-heading">Información personal</h2>
                            <p class="small text-muted mb-0 mt-1">Datos registrados en tu cuenta</p>
                        </div>
                    </div>
                    <div class="perfil-panel-body">
                        <dl class="mb-4">
                            <div class="perfil-info">
                                <span class="perfil-info-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
                                <div class="flex-grow-1"><dt class="perfil-info-label">Nombre completo</dt><dd class="perfil-info-value mb-0">{{ $usuario->name }}</dd></div>
                            </div>
                            <div class="perfil-info">
                                <span class="perfil-info-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                <div class="flex-grow-1"><dt class="perfil-info-label">Correo electrónico</dt><dd class="perfil-info-value mb-0">{{ $usuario->email }}</dd></div>
                            </div>
                            <div class="perfil-info">
                                <span class="perfil-info-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                                <div class="flex-grow-1"><dt class="perfil-info-label">Rol asignado</dt><dd class="perfil-info-value mb-0">{{ $rol?->label() ?? 'Sin rol asignado' }}</dd></div>
                            </div>
                            @if($esCoordinador)
                                <div class="perfil-info">
                                    <span class="perfil-info-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                                    <div class="flex-grow-1"><dt class="perfil-info-label">Comunidad asignada</dt><dd class="perfil-info-value mb-0">{{ $usuario->comunidad?->comunidad ?? 'Sin comunidad asignada' }}</dd></div>
                                </div>
                            @endif
                            <div class="perfil-info">
                                <span class="perfil-info-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
                                <div class="flex-grow-1">
                                    <dt class="perfil-info-label">Estado de la cuenta</dt>
                                    <dd class="mb-0"><span class="badge bg-{{ $estadoColor }} perfil-state {{ $estadoColor === 'warning' ? 'text-dark' : '' }}"><i class="bi {{ $estadoIcono }} me-1" aria-hidden="true"></i>{{ $estadoTexto }}</span></dd>
                                </div>
                            </div>
                        </dl>
                        <div class="perfil-note d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
                            <span>La información personal es de consulta. Para solicitar una corrección, comunícate con Secretaría.</span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-6">
                <section class="perfil-panel" aria-labelledby="seguridad-title">
                    <div class="perfil-panel-head">
                        <span class="perfil-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                        <div>
                            <h2 id="seguridad-title" class="perfil-heading">Seguridad de la cuenta</h2>
                            <p class="small text-muted mb-0 mt-1">Actualización de credenciales</p>
                        </div>
                    </div>
                    <div class="perfil-panel-body">
                        <div class="d-flex align-items-start gap-2 mb-4">
                            <i class="bi bi-key text-primary fs-5" aria-hidden="true"></i>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Cambiar contraseña</h3>
                                <p class="text-muted small mb-0">Ingresa tu contraseña actual y elige una nueva para proteger tu cuenta.</p>
                            </div>
                        </div>
                        <form class="perfil-form" method="POST" action="{{ route('password.update') }}" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Contraseña actual</label>
                                <div class="input-group">
                                    <input type="password" id="current_password" name="current_password" class="form-control {{ $errorActual ? 'is-invalid' : '' }}" autocomplete="current-password" required>
                                    <button type="button" class="btn" data-perfil-password="current_password" aria-label="Mostrar contraseña actual" aria-pressed="false" title="Mostrar contraseña"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                </div>
                                @if($errorActual)<div class="text-danger small mt-1" role="alert">{{ $errorActual }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control {{ $errorNueva ? 'is-invalid' : '' }}" autocomplete="new-password" minlength="8" required>
                                    <button type="button" class="btn" data-perfil-password="password" aria-label="Mostrar nueva contraseña" aria-pressed="false" title="Mostrar contraseña"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                </div>
                                <div class="form-text">Utiliza al menos 8 caracteres y evita reutilizar contraseñas anteriores.</div>
                                @if($errorNueva)<div class="text-danger small mt-1" role="alert">{{ $errorNueva }}</div>@endif
                            </div>
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirmar nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control {{ $errorConfirmacion ? 'is-invalid' : '' }}" autocomplete="new-password" minlength="8" required>
                                    <button type="button" class="btn" data-perfil-password="password_confirmation" aria-label="Mostrar confirmación de contraseña" aria-pressed="false" title="Mostrar contraseña"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                </div>
                                @if($errorConfirmacion)<div class="text-danger small mt-1" role="alert">{{ $errorConfirmacion }}</div>@endif
                            </div>
                            <button type="submit" class="btn perfil-save w-100"><i class="bi bi-check2-circle me-2" aria-hidden="true"></i>Actualizar contraseña</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-perfil-password]').forEach(button => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.perfilPassword);
                if (!input) return;
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(visible));
                button.setAttribute('aria-label', `${visible ? 'Ocultar' : 'Mostrar'} ${input.id === 'current_password' ? 'contraseña actual' : input.id === 'password' ? 'nueva contraseña' : 'confirmación de contraseña'}`);
                button.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
                button.querySelector('i').className = `bi bi-eye${visible ? '-slash' : ''}`;
            });
        });
    </script>
@endsection
