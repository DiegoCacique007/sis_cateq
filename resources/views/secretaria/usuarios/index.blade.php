@extends('layouts.app_parroquia_admin')

@section('title', 'Cuentas de usuario | Sis_Cateq')
@section('subtitle', 'Gestión de accesos')
@section('header_title', 'Cuentas de usuario')
@section('header_subtitle', 'Todas las cuentas: bloquear, reactivar, cambiar rol o comunidad.')

@section('content')
    @php
        $roleLabel = fn ($value) => \App\Enums\UserRole::tryFrom((string) $value)?->label() ?? 'Sin rol asignado';
        $statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobada', 'bloqueado' => 'Bloqueada'];
        $statusClasses = ['pendiente' => 'bg-warning text-dark', 'aprobado' => 'bg-success', 'bloqueado' => 'bg-danger'];
    @endphp

    <div class="card card-parroquia border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('secretaria.usuarios.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="buscar" class="form-label small fw-semibold mb-1">Buscar</label>
                    <input id="buscar" type="text" name="buscar" value="{{ $buscar }}" maxlength="100"
                           class="form-control" placeholder="Nombre o correo...">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="estado" class="form-label small fw-semibold mb-1">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($estado === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="rol" class="form-label small fw-semibold mb-1">Rol</label>
                    <select id="rol" name="rol" class="form-select">
                        <option value="">Todos</option>
                        @foreach(\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected($rol === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                        <option value="usuario" @selected($rol === 'usuario')>Sin rol asignado</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="per_page" class="form-label small fw-semibold mb-1">Por página</label>
                    <select id="per_page" name="per_page" class="form-select">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                    <a href="{{ route('secretaria.usuarios.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-parroquia border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-body p-0">
            @if($usuarios->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-info-circle me-1"></i>
                    No hay cuentas con esos criterios.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Comunidad</th>
                                <th>Aprobación</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usuarios as $u)
                                @php
                                    $isSelf = $u->is(auth()->user());
                                    $currentRole = $roleLabel($u->role);
                                    $isCatequista = $u->role === \App\Enums\UserRole::Catequista->value;
                                    $isCoordinator = $u->role === \App\Enums\UserRole::CoordinadorComunidades->value;
                                    $groups = (int) $u->grupos_periodo_actual;

                                    $blockText = "{$u->name} no podrá entrar al sistema. Sus datos se conservarán.";
                                    if ($isCatequista && $groups > 0) {
                                        $blockText .= ' Este catequista tiene '.\App\Services\Accounts\AccountManager::groupsPhrase($groups)
                                            .' en el periodo actual. Sus asignaciones y evaluaciones se conservarán, pero no podrá entrar al sistema.'
                                            .' Para que otro catequista atienda esos grupos, reasígnalos en Asignación de grupos.';
                                    }
                                    if ($isCoordinator && $u->comunidad) {
                                        $blockText .= " Coordina la comunidad {$u->comunidad->comunidad}. La comunidad se conservará en su cuenta.";
                                    }
                                    $reactivateText = $u->operationalRole()
                                        ? "{$u->name} podrá volver a entrar con el rol {$currentRole}."
                                        : 'La solicitud volverá a Usuarios pendientes.';
                                @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $u->name }}
                                        @if($isSelf)
                                            <span class="badge bg-secondary ms-1">Tu cuenta</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $u->email }}</td>
                                    <td class="small">
                                        {{ $currentRole }}
                                        @if($isCatequista && $groups > 0)
                                            <div class="text-muted">{{ \App\Services\Accounts\AccountManager::groupsPhrase($groups) }} en el periodo actual</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusClasses[$u->status] ?? 'bg-secondary' }}">
                                            {{ $statusLabels[$u->status] ?? $u->status }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        @if($isCoordinator)
                                            {{ $u->comunidad?->comunidad ?? 'Sin comunidad' }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        {{ $u->approved_at ? \Illuminate\Support\Carbon::parse($u->approved_at)->format('Y-m-d') : '—' }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-end gap-2">
                                            {{-- Los nombres van en data-* y se muestran con text: nunca dentro de JavaScript. --}}
                                            @if(! $isSelf && $u->status !== 'bloqueado')
                                                <form method="POST" action="{{ route('secretaria.usuarios.bloquear', $u->id) }}"
                                                      class="js-confirm-form"
                                                      data-confirm-title="¿Bloquear la cuenta?"
                                                      data-confirm-text="{{ $blockText }}"
                                                      data-confirm-button="Bloquear"
                                                      data-confirm-danger="1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-slash-circle me-1"></i> Bloquear
                                                    </button>
                                                </form>
                                            @endif

                                            @if($u->status === 'bloqueado')
                                                <form method="POST" action="{{ route('secretaria.usuarios.reactivar', $u->id) }}"
                                                      class="js-confirm-form"
                                                      data-confirm-title="¿Reactivar la cuenta?"
                                                      data-confirm-text="{{ $reactivateText }}"
                                                      data-confirm-button="Reactivar">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar
                                                    </button>
                                                </form>
                                            @endif

                                            @if($isCoordinator)
                                                <form method="POST" action="{{ route('secretaria.usuarios.comunidad', $u->id) }}"
                                                      class="js-confirm-form d-flex gap-1"
                                                      data-confirm-title="¿Cambiar la comunidad?"
                                                      data-confirm-text="{{ $u->name }} coordinará la comunidad __COMUNIDAD__."
                                                      data-confirm-button="Guardar">
                                                    @csrf
                                                    <select name="comunidad_id" class="form-select form-select-sm" required
                                                            data-placeholder="__COMUNIDAD__" aria-label="Comunidad que coordina">
                                                        <option value="">Comunidad…</option>
                                                        @foreach($comunidades as $comunidad)
                                                            <option value="{{ $comunidad->id }}" @selected((int) $u->comunidad_id === (int) $comunidad->id)>{{ $comunidad->comunidad }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
                                                </form>
                                            @endif

                                            @if(! $isSelf && $u->status === 'aprobado')
                                                <form method="POST" action="{{ route('secretaria.usuarios.rol', $u->id) }}"
                                                      class="js-confirm-form d-flex flex-wrap justify-content-end gap-1"
                                                      data-confirm-title="¿Cambiar el rol?"
                                                      data-confirm-text="{{ $u->name }} pasará de {{ $currentRole }} a __ROL__. Sus permisos cambian de inmediato."
                                                      data-confirm-text-secretaria="{{ $u->name }} pasará de {{ $currentRole }} a Secretaría y tendrá control total del sistema. Sus permisos cambian de inmediato."
                                                      data-confirm-button="Cambiar rol">
                                                    @csrf
                                                    <select name="role" class="form-select form-select-sm js-role-select"
                                                            data-placeholder="__ROL__" aria-label="Nuevo rol">
                                                        @foreach(\App\Enums\UserRole::cases() as $role)
                                                            <option value="{{ $role->value }}" @selected($u->role === $role->value)>{{ $role->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="js-role-comunidad d-none">
                                                        <select name="comunidad_id" class="form-select form-select-sm" aria-label="Comunidad que coordinará">
                                                            <option value="">Comunidad…</option>
                                                            @foreach($comunidades as $comunidad)
                                                                <option value="{{ $comunidad->id }}">{{ $comunidad->comunidad }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cambiar rol</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-center">
        {{ $usuarios->links() }}
    </div>

    <script>
        // El selector de comunidad del cambio de rol solo se envía al elegir Coordinador de Comunidades.
        document.querySelectorAll('.js-role-select').forEach(function (select) {
            const wrapper = select.form.querySelector('.js-role-comunidad');
            const comunidad = wrapper.querySelector('select');
            const sync = function () {
                const needed = select.value === 'coordinador_comunidades';
                wrapper.classList.toggle('d-none', !needed);
                comunidad.required = needed;
                comunidad.disabled = !needed;
            };

            select.addEventListener('change', sync);
            sync();
        });

        // Confirmación con los textos de data-*, mostrados como texto (no HTML).
        document.querySelectorAll('.js-confirm-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const role = form.querySelector('select[name="role"]');
                let text = role && role.value === 'secretaria' && form.dataset.confirmTextSecretaria
                    ? form.dataset.confirmTextSecretaria
                    : form.dataset.confirmText;
                form.querySelectorAll('select[data-placeholder]').forEach(function (select) {
                    const option = select.options[select.selectedIndex];
                    text = text.replace(select.dataset.placeholder, option ? option.text : '');
                });

                const result = await window.AppAlert.confirmAction({
                    title: form.dataset.confirmTitle,
                    text: text,
                    confirmText: form.dataset.confirmButton,
                    danger: form.dataset.confirmDanger === '1',
                });

                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endsection
