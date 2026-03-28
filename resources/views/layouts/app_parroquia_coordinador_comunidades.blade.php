<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIS_CATEQ - Comunidades')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root{
            --blue-main:#4facfe;
            --blue-light:#8fd3f4;
            --blue-dark:#1e3a8a;
            --text-main:#2c3e50;
            --muted:rgba(44, 62, 80, 0.65);
            --panel:rgba(255, 255, 255, 0.9);
            --border:rgba(79, 172, 254, 0.22);
            --sidebar:rgba(255, 255, 255, 0.96);
            --bg-soft:#f8fbff;
        }

        *{ box-sizing:border-box; }

        body{
            min-height:100vh;
            margin:0;
            font-family:'Inter', sans-serif;
            background:
                radial-gradient(circle at 35% 25%, #ffffff 0%, #e0f2fe 55%),
                radial-gradient(circle at center, #f0f8ff 0%, #bae6fd 100%);
            background-attachment:fixed;
            color:var(--text-main);
        }

        .app-shell{ display:flex; min-height:100vh; }

        .sidebar{
            width:290px;
            background:var(--sidebar);
            border-right:1px solid var(--border);
            border-left:4px solid var(--blue-main);
            padding:22px 16px;
            backdrop-filter:blur(10px);
        }

        .brand{
            display:flex; align-items:center; gap:12px;
            padding:10px 10px 18px 10px;
            border-bottom:1px solid rgba(30, 58, 138, 0.08);
            margin-bottom:14px;
        }

        .logo{
            display:inline-grid; place-items:center;
            width:46px; height:46px; border:1px solid rgba(79, 172, 254, 0.45);
            border-radius:50%; color:var(--blue-main);
            box-shadow:0 0 22px rgba(79, 172, 254, 0.15);
            background:#ffffff; flex:0 0 auto;
        }

        .brand-title{
            margin:0; font-family:'Cinzel', serif; color:var(--blue-dark);
            letter-spacing:1.6px; font-weight:700; text-transform:uppercase;
            font-size:1.02rem; line-height:1.1;
        }

        .brand-sub{ margin:0; color:var(--muted); font-size:.86rem; }

        .menu-label{
            color:var(--blue-dark); font-size:.74rem; font-weight:800;
            letter-spacing:1px; padding:10px 10px 4px; margin-top:10px;
            text-transform:uppercase;
        }

        .nav-parroquia .nav-link{
            color:var(--text-main); border-radius:12px; padding:10px 12px;
            margin-bottom:6px; border:1px solid transparent; background:transparent;
            display:flex; align-items:center; gap:10px; transition:all .2s ease;
            cursor:pointer; text-decoration:none; font-weight:500;
        }

        .nav-parroquia .nav-link:hover{
            color:var(--blue-dark); background:rgba(79, 172, 254, 0.1); border-color:var(--border);
        }

        .nav-parroquia .nav-link.active,
        .nav-parroquia .nav-link.active-menu{
            background:rgba(79, 172, 254, 0.15); border-color:rgba(79, 172, 254, 0.35);
            color:var(--blue-dark); font-weight:700;
        }

        .main{ flex:1; padding:22px 22px 28px; max-width:100%; overflow-x:hidden; }

        .topbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
        .topbar-title{ font-family:'Cinzel', serif; color:var(--blue-dark); letter-spacing:1px; font-weight:700; margin:0; font-size:1.5rem; }
        .topbar-sub{ color:var(--muted); margin:4px 0 0; font-size:.92rem; }

        .chip{
            border:1px solid rgba(79, 172, 254, 0.35); background:#ffffff; color:var(--text-main);
            padding:8px 12px; border-radius:999px; font-size:.84rem; font-weight:600;
            box-shadow:0 2px 8px rgba(30, 58, 138, 0.04);
        }

        .btn-parroquia{
            background:linear-gradient(180deg, var(--blue-light) 0%, var(--blue-main) 100%);
            color:#ffffff; border:0; border-radius:10px; font-weight:800;
            box-shadow:0 8px 20px rgba(79, 172, 254, 0.25); transition:all .25s ease;
        }
        .btn-parroquia:hover{ filter:brightness(1.05); transform:translateY(-1px); color:#ffffff; }

        .btn-outline-parroquia{
            border:1px solid rgba(79, 172, 254, 0.6); color:var(--blue-dark); border-radius:10px;
            background:transparent; transition:all .25s ease; font-weight:600;
        }
        .btn-outline-parroquia:hover{ border-color:var(--blue-main); background:var(--blue-main); color:#ffffff; }

        @media (max-width: 992px){
            .sidebar{ width:92px; padding:18px 10px; }
            .brand-sub, .brand-title, .nav-parroquia .nav-link span, .menu-label{ display:none; }
            .main{ padding:18px 14px; margin-left: 92px !important; }
        }
    </style>
</head>
<body>

<div class="app-shell">

    <aside class="sidebar d-flex flex-column h-100 position-fixed" style="z-index: 1000;">
        <div class="brand">
            <div class="logo" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div>
                <p class="brand-title mb-0">Parroquia</p>
                <p class="brand-sub mb-0">@yield('subtitle', 'Panel de Coordinación')</p>
            </div>
        </div>

        <nav class="nav flex-column nav-parroquia flex-grow-1" id="menu-tablas">

            @if(auth()->check() && auth()->user()->role === 'coord_comunidad')

                <a class="nav-link menu-item active" href="#" onclick="switchSection('')">
                    <i class="bi bi-house me-2"></i> <span>Inicio</span>
                </a>

                <div class="menu-label mt-3">Gestión de Zonas</div>

                <a class="nav-link menu-item" href="#comunidades" onclick="switchSection('comunidades')">
                    <i class="bi bi-geo-alt me-2"></i> <span>Comunidades</span>
                </a>

            @else
                <a class="nav-link" href="{{ url('/dashboard') }}">
                    <i class="bi bi-house me-2"></i> <span>Dashboard</span>
                </a>
            @endif

        </nav>

        <div class="mt-auto pt-3 pb-2" style="border-top:1px solid rgba(30, 58, 138, 0.08);">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-parroquia btn-sm w-100" type="submit">Salir</button>
            </form>
        </div>
    </aside>

    <main class="main" style="margin-left: 290px; min-height: 100vh;">
        <div class="topbar">
            <div>
                <h1 class="topbar-title">@yield('header_title', 'Dashboard')</h1>
                <p class="topbar-sub">@yield('header_subtitle', '')</p>
            </div>

            @auth
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="chip"><i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                    @yield('header_actions')
                </div>
            @endauth
        </div>

        @if(session('status'))
            <div class="alert alert-success py-2"><i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const menuItems = document.querySelectorAll('.menu-item');

        // Función para cambiar la clase activa cuando le das click al menú lateral
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                // Quitarle el azul a todos
                menuItems.forEach(link => link.classList.remove('active'));
                // Ponerle el azul solo al que diste click
                this.classList.add('active');
            });
        });

        // Este pedacito sirve por si recargas la página estando en #comunidades
        let hash = window.location.hash;
        if (hash) {
            let activeElement = document.querySelector(`.menu-item[href="${hash}"]`);
            if(activeElement) {
                menuItems.forEach(link => link.classList.remove('active'));
                activeElement.classList.add('active');
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
