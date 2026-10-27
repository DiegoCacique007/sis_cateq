@auth
    <style>
        .topbar > .d-flex { margin-left:auto; max-width:100%; justify-content:flex-end; }
        .account-menu { max-width:100%; margin-left:auto; }
        .account-menu .account-toggle { display:flex; align-items:center; gap:.6rem; max-width:100%; border:1px solid #dce6f3; border-radius:14px; background:#fff; color:#1e3a8a; padding:.45rem .75rem; }
        .account-menu .account-toggle:hover { background:#eef5ff; }
        .account-menu .account-identity { min-width:0; text-align:left; }
        .account-menu .account-name { display:block; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; }
        .account-menu .account-role { display:block; font-size:.75rem; white-space:normal; }
        .account-menu .dropdown-menu { border:1px solid #e3eaf4; border-radius:14px; padding:.4rem; min-width:190px; }
        .account-menu .dropdown-item { border-radius:8px; padding:.65rem; }
        @media (max-width:575.98px) { .account-menu .account-name { max-width:140px; } }
    </style>
    <div class="dropdown account-menu">
        <button class="account-toggle dropdown-toggle" type="button" id="accountMenuToggle"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de usuario">
            <i class="bi bi-person-circle fs-4" aria-hidden="true"></i>
            <span class="account-identity">
                <span class="account-name">{{ auth()->user()->name }}</span>
                <span class="account-role">{{ auth()->user()->operationalRole()?->label() }}</span>
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="accountMenuToggle">
            <li><a class="dropdown-item" href="{{ route('cuenta.password.edit') }}">
                <i class="bi bi-person me-2" aria-hidden="true"></i>Mi perfil
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}" id="account-logout-form">
                    @csrf
                    <button class="dropdown-item" type="submit">
                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Cerrar sesión
                    </button>
                </form>
            </li>
        </ul>
    </div>
    <script>
        document.getElementById('account-logout-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const result = await window.AppAlert.confirmLogout();
            if (result.isConfirmed) this.submit();
        });
    </script>
@endauth
