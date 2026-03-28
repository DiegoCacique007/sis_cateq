<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIS_CATEQ - Catequista')</title>

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

        .card-parroquia{
            background:var(--panel); backdrop-filter:blur(12px); border:1px solid var(--border);
            border-left:4px solid var(--blue-main); border-radius:16px;
            box-shadow:0 15px 35px rgba(30, 58, 138, 0.07); overflow:hidden;
        }

        .alert-success{
            background:rgba(79, 172, 254, 0.1); border:1px solid rgba(79, 172, 254, 0.25);
            color:var(--blue-dark); border-radius:10px;
        }

        @media (max-width: 992px){
            .sidebar{ width:92px; padding:18px 10px; }
            .brand-sub, .brand-title, .nav-parroquia .nav-link span, .menu-label{ display:none; }
            .main{ padding:18px 14px; }
        }
    </style>
</head>
<body>

<div class="app-shell">

    <aside class="sidebar">
        <div class="brand">
            <div class="logo" aria-hidden="true">
                <i class="bi bi-book-half fs-5"></i>
            </div>
            <div>
                <p class="brand-title mb-0">Parroquia</p>
                <p class="brand-sub mb-0">@yield('subtitle', 'Panel de Catequista')</p>
            </div>
        </div>

        <nav class="nav flex-column nav-parroquia" id="menu-tablas">

            @if(auth()->check() && auth()->user()->role === 'catequista')

                <a class="nav-link {{ request()->routeIs('catequista.dashboard') && !request()->hash ? 'active' : '' }}" href="{{ route('catequista.dashboard') }}" onclick="if(typeof switchSection==='function') switchSection('', this)">
                    <i class="bi bi-house me-2"></i> <span>Inicio</span>
                </a>

                <div class="menu-label mt-3">Mi Clase</div>

                <a class="nav-link" href="{{ route('catequista.dashboard') }}#mi_grupo" onclick="if(typeof switchSection==='function') switchSection('mi_grupo', this)">
                    <i class="bi bi-people me-2 text-primary"></i> <span>Lista de Grupo</span>
                </a>
                <a class="nav-link" href="{{ route('catequista.dashboard') }}#evaluaciones" onclick="if(typeof switchSection==='function') switchSection('evaluaciones', this)">
                    <i class="bi bi-clipboard-check me-2 text-success"></i> <span>Evaluaciones</span>
                </a>

                <a class="nav-link text-muted" href="#" onclick="alert('Módulo de Asistencias en desarrollo. Disponible próximamente.'); return false;" style="cursor: not-allowed;">
                    <i class="bi bi-calendar2-check me-2"></i> <span>Asistencias</span> <i class="bi bi-lock-fill ms-auto small"></i>
                </a>

                <a class="nav-link text-muted" href="#" onclick="alert('Módulo de Calificaciones en desarrollo. Disponible próximamente.'); return false;">
                    <i class="bi bi-journal-text me-2"></i> <span>Calificaciones</span> <i class="bi bi-lock-fill ms-auto small"></i>
                </a>

            @else
                <a class="nav-link" href="{{ url('/dashboard') }}">
                    <i class="bi bi-house me-2"></i> <span>Dashboard</span>
                </a>
            @endif

        </nav>

        <div class="mt-3 pt-3" style="border-top:1px solid rgba(30, 58, 138, 0.08);">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-parroquia btn-sm w-100" type="submit">Salir</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1 class="topbar-title fw-bold" style="font-family: 'Cinzel', serif;">@yield('header_title', 'Dashboard')</h1>
                <p class="topbar-sub">@yield('header_subtitle', '')</p>
            </div>

            @auth
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="chip shadow-sm"><i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }} (Catequista)</span>
                    @yield('header_actions')
                </div>
            @endauth
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="dismiss" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
