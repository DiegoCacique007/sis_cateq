@extends('layouts.app_parroquia_admin')

@section('title', 'Gestión de usuarios | Sis_Cateq')
@section('subtitle', 'Gestión de accesos')
@section('header_title', 'Gestión de usuarios')
@section('header_subtitle', 'Cuentas, solicitudes y permisos en un solo lugar.')

@section('content')
    @php
        $roleLabel = fn ($value) => \App\Enums\UserRole::tryFrom((string) $value)?->label() ?? 'Sin rol asignado';
        $statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobada', 'bloqueado' => 'Bloqueada'];
        $statusClasses = ['pendiente' => 'gu-pending', 'aprobado' => 'gu-approved', 'bloqueado' => 'gu-blocked'];
        $sections = [
            'todos' => ['Todos los usuarios', 'bi-people-fill', 'gu-blue'],
            'pendientes' => ['Pendientes', 'bi-clock-history', 'gu-orange'],
            'activos' => ['Activos', 'bi-person-check-fill', 'gu-green'],
            'inactivos' => ['Inactivos', 'bi-person-x-fill', 'gu-red']
        ];
    @endphp

    <style>
        .gu-wrap{font-family:'Inter','Segoe UI','Roboto',sans-serif;color:#334155}
        .gu-heading{color:#1e3a8a;font-weight:800}
        .gu-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1.15rem}
        .gu-stat{display:flex;align-items:center;gap:1rem;padding:1.05rem 1.15rem;border-radius:16px;background:#fff;border:1px solid #e2e8f0;box-shadow:0 2px 10px #1e3a8a0a;text-decoration:none;transition:transform .2s,box-shadow .2s,border-color .2s}
        .gu-stat:hover{transform:translateY(-3px);box-shadow:0 10px 22px #1e3a8a16}
        .gu-stat[aria-current="page"]{border-color:#60a5fa;background:#f4f9ff}
        .gu-stat-icon{flex:0 0 47px;width:47px;height:47px;border-radius:14px;display:grid;place-items:center;color:#fff;font-size:1.25rem}
        .gu-stat-info{min-width:0}.gu-stat-title{color:#64748b;font-size:.82rem;display:block;font-weight:600}
        .gu-stat-value{color:#0f172a;font-size:1.65rem;line-height:1.2;font-weight:800;display:block}
        .gu-blue .gu-stat-icon{background:linear-gradient(135deg,#60a5fa,#2563eb)}
        .gu-orange .gu-stat-icon{background:linear-gradient(135deg,#fbbf24,#f59e0b)}
        .gu-green .gu-stat-icon{background:linear-gradient(135deg,#34d399,#059669)}
        .gu-red .gu-stat-icon{background:linear-gradient(135deg,#fb7185,#e11d48)}
        .gu-panel{background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 2px 10px #1e3a8a0a;overflow:hidden;margin-bottom:1.3rem}
        .gu-panel-head{padding:1.1rem 1.35rem;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
        .gu-panel-body{padding:1.2rem 1.35rem}
        .gu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,315px),1fr));gap:1.25rem}
        .gu-user-card{background:#fff;border:1px solid #e2e8f0;border-radius:13px;padding:1.25rem;position:relative;display:flex;flex-direction:column;min-height:235px;box-shadow:0 1px 3px #0000000d;transition:transform .2s,box-shadow .2s}
        .gu-user-card:hover{transform:translateY(-3px);box-shadow:0 10px 20px #1e3a8a16}
        .gu-person{display:flex;align-items:flex-start;gap:.85rem;padding-right:2.45rem;margin-bottom:1rem;min-width:0}
        .gu-avatar{flex:0 0 43px;height:43px;width:43px;display:grid;place-items:center;border-radius:13px;background:#eef2ff;color:#2563eb;font-size:1.25rem}
        .gu-person-main{min-width:0}.gu-name{font-size:1.03rem;font-weight:750;line-height:1.35;color:#1e3a8a;overflow-wrap:anywhere;margin:0 0 .25rem}
        .gu-mail{font-size:.8rem;color:#64748b;overflow-wrap:anywhere;display:block}
        .gu-tags{display:flex;gap:.45rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem}
        .gu-pill{display:inline-flex;align-items:center;gap:.35rem;border-radius:30px;padding:.3rem .65rem;font-size:.73rem;font-weight:650;border:1px solid #bfdbfe;color:#1d4ed8;background:#eff6ff}
        .gu-pending{background:#fffbeb;border-color:#fcd34d;color:#92400e}
        .gu-approved{background:#ecfdf5;border-color:#86efac;color:#047857}
        .gu-blocked{background:#fff1f2;border-color:#fda4af;color:#be123c}
        .gu-info{display:grid;gap:.55rem;font-size:.8rem;margin-top:auto}
        .gu-info-line{display:flex;align-items:flex-start;gap:.55rem;min-width:0;color:#64748b;overflow-wrap:anywhere}
        .gu-info-line i{width:17px;color:#60a5fa;flex:0 0 17px}
        .gu-info-line strong{color:#334155;font-weight:600}
        .gu-action-details{margin-top:1rem;border-top:1px solid #eef2f7;padding-top:.7rem}
        .gu-action-details>summary{position:absolute;top:1.22rem;right:1.2rem;width:34px;height:34px;border:1px solid #3b82f6;border-radius:50%;display:grid;place-items:center;color:#2563eb;cursor:pointer;list-style:none;transition:background .2s,color .2s}
        .gu-action-details>summary::-webkit-details-marker{display:none}
        .gu-action-details>summary:hover,.gu-action-details[open]>summary{color:#fff;background:#2563eb}
        .gu-action-details>summary:focus-visible{outline:3px solid #93c5fd;outline-offset:3px}
        .gu-action-content{padding-top:.45rem;display:grid;gap:.85rem}
        .gu-action-content form{min-width:0}
        .gu-action-content label{color:#475569;font-size:.78rem;font-weight:600}
        .gu-action-content .form-select{min-width:0}
        .gu-action-content .btn{border-radius:9px}
        .gu-action-divider{border-top:1px dashed #e2e8f0;padding-top:.85rem}
        .gu-action-inline{display:flex;gap:.45rem;align-items:center;flex-wrap:wrap}
        .gu-action-inline>.form-select{flex:1 1 155px;width:auto}
        .gu-action-inline>.btn{flex:0 0 auto}
        .gu-empty{text-align:center;padding:3.5rem 1rem;border:1px dashed #cbd5e1;border-radius:14px;color:#64748b;background:#fff}
        .gu-empty i{display:block;font-size:2.4rem;margin-bottom:.7rem;color:#94a3b8}
        .gu-note{font-size:.78rem;color:#64748b;line-height:1.5}
        .gu-pagination{margin-top:1.4rem}
        @media(max-width:1199.98px){.gu-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:575.98px){.gu-stats{gap:.6rem}.gu-stat{padding:.75rem;gap:.55rem}.gu-stat-icon{height:38px;width:38px;flex-basis:38px;font-size:1rem}.gu-stat-title{font-size:.72rem}.gu-stat-value{font-size:1.3rem}.gu-panel-head,.gu-panel-body{padding:1rem}.gu-grid{gap:1rem}}
    </style>

    <div class="gu-wrap">

        <nav class="gu-stats" aria-label="Secciones de gestión de usuarios">
            @foreach($sections as $key => [$label, $icon, $color])
                <a href="{{ route('secretaria.usuarios.index', ['seccion' => $key]) }}" class="gu-stat {{ $color }}" @if($seccion === $key) aria-current="page" @endif>
                    <span class="gu-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <span class="gu-stat-info"><span class="gu-stat-title">{{ $label }}</span><strong class="gu-stat-value">{{ number_format($resumen[$key] ?? 0) }}</strong></span>
                </a>
            @endforeach
        </nav>

        @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="gu-panel">
            <div class="gu-panel-head">
                <div>
                    <h6 class="gu-heading mb-1"><i class="bi bi-funnel me-2"></i>Filtros de búsqueda</h6>
                    <small class="text-muted">Encuentra usuarios por nombre, correo, rol o estado.</small>
                </div>
                <span class="badge bg-light text-primary border rounded-pill px-3 py-2">{{ $sections[$seccion][0] ?? 'Usuarios' }}</span>
            </div>
            <div class="gu-panel-body">
                <form method="GET" action="{{ route('secretaria.usuarios.index') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="seccion" value="{{ $seccion }}">
                    <div class="col-12 col-lg-4">
                        <label for="buscar" class="form-label small fw-semibold mb-1">Buscar usuario</label>
                        <input id="buscar" name="buscar" type="search" maxlength="100" class="form-control" value="{{ $buscar }}" placeholder="Nombre o correo electrónico...">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="estado" class="form-label small fw-semibold mb-1">Estado</label>
                        <select id="estado" name="estado" class="form-select" @disabled($seccion !== 'todos')>
                            <option value="">Todos</option>
                            @foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($estado === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="rol" class="form-label small fw-semibold mb-1">Rol</label>
                        <select id="rol" name="rol" class="form-select">
                            <option value="">Todos</option>
                            @foreach(\App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected($rol === $role->value)>{{ $role->label() }}</option>@endforeach
                            <option value="usuario" @selected($rol === 'usuario')>Sin rol asignado</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="per_page" class="form-label small fw-semibold mb-1">Por página</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-1"></i>Buscar</button>
                        <a class="btn btn-outline-secondary" href="{{ route('secretaria.usuarios.index', ['seccion' => $seccion]) }}" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="gu-panel">
            <div class="gu-panel-head">
                <div>
                    <h6 class="gu-heading mb-1"><i class="bi bi-people me-2"></i>{{ $sections[$seccion][0] ?? 'Usuarios' }}</h6>
                    <small class="text-muted">Selecciona <i class="bi bi-pencil-square"></i> en una tarjeta para administrar la cuenta.</small>
                </div>
                <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">{{ $usuarios->total() }} resultado(s)</span>
            </div>
            <div class="gu-panel-body">
                @if($usuarios->isEmpty())
                    <div class="gu-empty"><i class="bi bi-person-x"></i><h6 class="fw-semibold">No se encontraron usuarios</h6><span>Prueba con otros filtros o selecciona otra sección.</span></div>
                @else
                    <div class="gu-grid">
                        @foreach($usuarios as $u)
                            @php
                                $isSelf = $u->is(auth()->user());
                                $currentRole = $roleLabel($u->role);
                                $isCatequista = $u->role === \App\Enums\UserRole::Catequista->value;
                                $isCoordinator = $u->role === \App\Enums\UserRole::CoordinadorComunidades->value;
                                $groups = (int) $u->grupos_periodo_actual;
                                $blockText = "{$u->name} no podrá entrar al sistema. Sus datos se conservarán.";
                                if ($isCatequista && $groups > 0) {
                                    $blockText .= ' Este catequista tiene '.\App\Services\Accounts\AccountManager::groupsPhrase($groups).' en el periodo actual. Sus asignaciones y evaluaciones se conservarán, pero no podrá entrar al sistema. Para que otro catequista atienda esos grupos, reasígnalos en Asignación de grupos.';
                                }
                                if ($isCoordinator && $u->comunidad) $blockText .= " Coordina la comunidad {$u->comunidad->comunidad}. La comunidad se conservará en su cuenta.";
                                $reactivateText = $u->operationalRole() ? "{$u->name} podrá volver a entrar con el rol {$currentRole}." : 'La solicitud volverá a la sección Pendientes de Gestión de usuarios.';
                            @endphp
                            <article class="gu-user-card">
                                <div class="gu-person">
                                    <span class="gu-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                                    <div class="gu-person-main">
                                        <h6 class="gu-name">{{ $u->name }} @if($isSelf)<span class="badge bg-secondary fw-normal">Tu cuenta</span>@endif</h6>
                                        <span class="gu-mail">{{ $u->email }}</span>
                                    </div>
                                </div>
                                <div class="gu-tags">
                                    <span class="gu-pill"><i class="bi bi-person-badge"></i>{{ $currentRole }}</span>
                                    <span class="gu-pill {{ $statusClasses[$u->status] ?? '' }}"><i class="bi {{ $u->status === 'aprobado' ? 'bi-check-circle' : ($u->status === 'pendiente' ? 'bi-clock' : 'bi-slash-circle') }}"></i>{{ $statusLabels[$u->status] ?? $u->status }}</span>
                                </div>
                                <div class="gu-info">
                                    @if($u->status === 'pendiente')
                                        <div class="gu-info-line"><i class="bi bi-person-plus"></i><span>Solicita: <strong>{{ $roleLabel($u->requested_role ?? 'catequista') }}</strong></span></div>
                                        <div class="gu-info-line"><i class="bi bi-calendar-plus"></i><span>Registro: {{ $u->created_at?->format('d/m/Y') ?? '—' }}</span></div>
                                    @endif
                                    @if($isCatequista && $groups > 0)
                                        <div class="gu-info-line"><i class="bi bi-collection"></i><span>{{ \App\Services\Accounts\AccountManager::groupsPhrase($groups) }} en el periodo actual</span></div>
                                    @endif
                                    @if($isCoordinator)
                                        <div class="gu-info-line"><i class="bi bi-geo-alt"></i><span>Comunidad: <strong>{{ $u->comunidad?->comunidad ?? 'Sin comunidad' }}</strong></span></div>
                                    @endif
                                    <div class="gu-info-line"><i class="bi bi-calendar-check"></i><span>Aprobación: {{ $u->approved_at ? \Illuminate\Support\Carbon::parse($u->approved_at)->format('d/m/Y') : '—' }}</span></div>
                                    @if($u->status === 'aprobado' && !$u->operationalRole())
                                        <div class="gu-info-line text-danger"><i class="bi bi-exclamation-triangle text-danger"></i><span>Sin acceso: rol no válido</span></div>
                                    @endif
                                </div>

                                <details class="gu-action-details">
                                    <summary aria-label="Administrar cuenta de {{ $u->name }}" title="Administrar cuenta"><i class="bi bi-pencil-fill" aria-hidden="true"></i></summary>
                                    <div class="gu-action-content">
                                        <div class="small fw-bold text-primary">Administrar cuenta</div>
                                        @if($u->status === 'pendiente')
                                            <form method="POST" action="{{ route('secretaria.usuarios.aprobar', $u->id) }}" class="js-confirm-form" data-confirm-title="¿Aprobar la solicitud?" data-confirm-text="¿Aprobar a {{ $u->name }} con el rol solicitado?" data-confirm-button="Aprobar">
                                                @csrf
                                                <input type="hidden" name="role" value="{{ $u->requested_role ?? 'catequista' }}">
                                                @if(($u->requested_role ?? '') === 'coordinador_comunidades')
                                                    <label class="form-label mb-1" for="gu-approve-community-{{ $u->id }}">Comunidad que coordinará</label>
                                                    <select id="gu-approve-community-{{ $u->id }}" name="comunidad_id" class="form-select form-select-sm mb-2" required>
                                                        <option value="">Selecciona una comunidad</option>
                                                        @foreach($comunidades as $comunidad)<option value="{{ $comunidad->id }}">{{ $comunidad->comunidad }}</option>@endforeach
                                                    </select>
                                                @endif
                                                <button type="submit" class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg me-1"></i>Aprobar solicitud</button>
                                            </form>
                                        @endif
                                        @if(!$isSelf && $u->status !== 'bloqueado')
                                            <form method="POST" action="{{ route('secretaria.usuarios.bloquear', $u->id) }}" class="js-confirm-form" data-confirm-title="¿Bloquear la cuenta?" data-confirm-text="{{ $blockText }}" data-confirm-button="Bloquear" data-confirm-danger="1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-slash-circle me-1"></i>{{ $u->status === 'pendiente' ? 'Rechazar solicitud' : 'Bloquear cuenta' }}</button>
                                            </form>
                                        @endif
                                        @if($u->status === 'bloqueado')
                                            <form method="POST" action="{{ route('secretaria.usuarios.reactivar', $u->id) }}" class="js-confirm-form" data-confirm-title="¿Reactivar la cuenta?" data-confirm-text="{{ $reactivateText }}" data-confirm-button="Reactivar">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reactivar cuenta</button>
                                            </form>
                                        @endif
                                        @if($isCoordinator)
                                            <form method="POST" action="{{ route('secretaria.usuarios.comunidad', $u->id) }}" class="js-confirm-form gu-action-divider" data-confirm-title="¿Cambiar la comunidad?" data-confirm-text="{{ $u->name }} coordinará la comunidad __COMUNIDAD__." data-confirm-button="Guardar">
                                                @csrf
                                                <label class="form-label mb-1" for="gu-community-{{ $u->id }}">Comunidad asignada</label>
                                                <div class="gu-action-inline">
                                                    <select id="gu-community-{{ $u->id }}" name="comunidad_id" class="form-select form-select-sm" required data-placeholder="__COMUNIDAD__">
                                                        <option value="">Comunidad…</option>
                                                        @foreach($comunidades as $comunidad)<option value="{{ $comunidad->id }}" @selected((int) $u->comunidad_id === (int) $comunidad->id)>{{ $comunidad->comunidad }}</option>@endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
                                                </div>
                                            </form>
                                        @endif
                                        @if(!$isSelf && $u->status === 'aprobado')
                                            <form method="POST" action="{{ route('secretaria.usuarios.rol', $u->id) }}" class="js-confirm-form gu-action-divider" data-confirm-title="¿Cambiar el rol?" data-confirm-text="{{ $u->name }} pasará de {{ $currentRole }} a __ROL__. Sus permisos cambian de inmediato." data-confirm-text-secretaria="{{ $u->name }} pasará de {{ $currentRole }} a Secretaría y tendrá control total del sistema. Sus permisos cambian de inmediato." data-confirm-button="Cambiar rol">
                                                @csrf
                                                <label class="form-label mb-1" for="gu-role-{{ $u->id }}">Cambiar rol</label>
                                                <div class="gu-action-inline">
                                                    <select id="gu-role-{{ $u->id }}" name="role" class="form-select form-select-sm js-role-select" data-placeholder="__ROL__">
                                                        @foreach(\App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected($u->role === $role->value)>{{ $role->label() }}</option>@endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cambiar</button>
                                                </div>
                                                <div class="js-role-comunidad d-none mt-2">
                                                    <label for="gu-role-community-{{ $u->id }}" class="form-label mb-1">Comunidad que coordinará</label>
                                                    <select id="gu-role-community-{{ $u->id }}" name="comunidad_id" class="form-select form-select-sm">
                                                        <option value="">Selecciona una comunidad</option>
                                                        @foreach($comunidades as $comunidad)<option value="{{ $comunidad->id }}">{{ $comunidad->comunidad }}</option>@endforeach
                                                    </select>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="gu-pagination">{{ $usuarios->withQueryString()->links() }}</div>
        <p class="gu-note mt-3"><i class="bi bi-info-circle me-1"></i>Activos: cuentas aprobadas con rol operativo. Inactivos: cuentas bloqueadas. Rechazar una solicitud la bloquea sin borrar su información. Los indicadores superiores muestran los totales generales antes de aplicar filtros.</p>
    </div>

    <script>
        document.querySelectorAll('.gu-wrap .js-role-select').forEach(select => {
            const wrapper = select.form.querySelector('.js-role-comunidad');
            const comunidad = wrapper.querySelector('select');
            const sync = () => {
                const needed = select.value === 'coordinador_comunidades';
                wrapper.classList.toggle('d-none', !needed);
                comunidad.required = needed;
                comunidad.disabled = !needed;
            };
            select.addEventListener('change', sync);
            sync();
        });
        document.querySelectorAll('.gu-wrap .js-confirm-form').forEach(form => {
            form.addEventListener('submit', async event => {
                event.preventDefault();
                const role = form.querySelector('select[name="role"]');
                let message = role?.value === 'secretaria' && form.dataset.confirmTextSecretaria ? form.dataset.confirmTextSecretaria : form.dataset.confirmText;
                form.querySelectorAll('select[data-placeholder]').forEach(select => {
                    const selected = select.options[select.selectedIndex];
                    message = message.replace(select.dataset.placeholder, selected?.text ?? '');
                });
                const confirmed = window.AppAlert?.confirmAction
                    ? (await window.AppAlert.confirmAction({title:form.dataset.confirmTitle,text:message,confirmText:form.dataset.confirmButton,danger:form.dataset.confirmDanger === '1'})).isConfirmed
                    : window.confirm(form.dataset.confirmTitle + '\n' + message);
                if(confirmed) form.submit();
            });
        });
    </script>
@endsection
