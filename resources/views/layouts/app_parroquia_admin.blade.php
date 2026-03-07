<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Sis_Cateq')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

  <style>
    :root{
      /* Nueva paleta de colores azules y blancos */
      --blue-main: #4facfe;
      --blue-light: #8fd3f4;
      --blue-dark: #1e3a8a;
      --text-main: #2c3e50;
      --muted: rgba(44, 62, 80, 0.65);
      --panel: rgba(255, 255, 255, 0.85);
      --border: rgba(79, 172, 254, 0.25);
      --sidebar: rgba(255, 255, 255, 0.95);
    }

    body{
      min-height:100vh;
      margin:0;
      font-family:'Lora', serif;
      /* Fondo degradado claro */
      background:
        radial-gradient(circle at 35% 25%, #ffffff 0%, #e0f2fe 55%),
        radial-gradient(circle at center, #f0f8ff 0%, #bae6fd 100%);
      background-attachment:fixed;
      color: var(--text-main);
    }

    .app-shell{
      position: relative;
      z-index: 1;
      display:flex;
      min-height:100vh;
    }

    /* Sidebar */
    .sidebar{
      width: 280px;
      background: var(--sidebar);
      border-right: 1px solid var(--border);
      border-left: 4px solid var(--blue-main);
      padding: 22px 16px;
      backdrop-filter: blur(10px);
    }

    .brand{
      display:flex; align-items:center; gap:12px;
      padding: 10px 10px 18px 10px;
      border-bottom: 1px solid rgba(30, 58, 138, 0.1);
      margin-bottom: 14px;
    }
    .logo{
      display:inline-grid; place-items:center;
      width:44px; height:44px;
      border:1px solid rgba(79, 172, 254, 0.5);
      border-radius:50%;
      color:var(--blue-main);
      box-shadow:0 0 22px rgba(79, 172, 254, 0.2);
      background: #ffffff;
      flex: 0 0 auto;
    }
    .brand-title{
      margin:0;
      font-family:'Cinzel', serif;
      color: var(--blue-dark);
      letter-spacing: 2px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 1.05rem;
      line-height: 1.1;
    }
    .brand-sub{
      margin:0;
      color: var(--muted);
      font-size: .88rem;
    }

    .nav-parroquia .nav-link{
      color: var(--text-main);
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 6px;
      border: 1px solid transparent;
      background: transparent;
      display:flex;
      align-items:center;
      gap:10px;
      transition: all 0.2s ease;
    }
    .nav-parroquia .nav-link:hover{
      color: var(--blue-dark);
      background: rgba(79, 172, 254, 0.1);
      border-color: var(--border);
    }
    .nav-parroquia .nav-link.active{
      background: rgba(79, 172, 254, 0.15);
      border-color: rgba(79, 172, 254, 0.4);
      color: var(--blue-dark);
      font-weight: 600;
    }

    /* Main */
    .main{
      flex:1;
      padding: 22px 22px 28px 22px;
    }

    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .topbar-title{
      font-family:'Cinzel', serif;
      color: var(--blue-dark);
      letter-spacing: 1px;
      font-weight: 700;
      margin:0;
    }
    .topbar-sub{
      color: var(--muted);
      margin:0;
      font-size: .92rem;
    }

    .chip{
      border: 1px solid rgba(79, 172, 254, 0.4);
      background: #ffffff;
      color: var(--text-main);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: .85rem;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(30, 58, 138, 0.05);
    }

    .btn-parroquia{
      background: linear-gradient(180deg, var(--blue-light) 0%, var(--blue-main) 100%);
      color: #ffffff;
      border: 0;
      border-radius: 10px;
      font-weight: 800;
      box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
      transition: all 0.25s ease;
    }
    .btn-parroquia:hover{ 
      filter: brightness(1.05); 
      transform: translateY(-1px); 
      color: #ffffff;
    }

    .btn-outline-parroquia{
      border: 1px solid rgba(79, 172, 254, 0.6);
      color: var(--blue-dark);
      border-radius: 10px;
      background: transparent;
      transition: all 0.25s ease;
      font-weight: 600;
    }
    .btn-outline-parroquia:hover{
      border-color: var(--blue-main);
      background: var(--blue-main);
      color: #ffffff;
    }

    /* Cards */
    .card-parroquia{
      background: var(--panel);
      backdrop-filter: blur(12px);
      border: 1px solid var(--border);
      border-left: 4px solid var(--blue-main);
      border-radius: 14px;
      box-shadow: 0 15px 35px rgba(30, 58, 138, 0.08);
      overflow:hidden;
    }
    .card-parroquia .card-body{ color: var(--text-main); }

    .kpi{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .kpi .label{ color: var(--muted); font-size: .9rem; font-weight: 600;}
    .kpi .value{ font-family:'Cinzel', serif; font-weight:700; color: var(--blue-dark); font-size: 1.5rem; }
    .kpi .mini{ color: var(--muted); font-size: .85rem; }

    /* Alertas */
    .alert-success {
      background: rgba(79, 172, 254, 0.1);
      border: 1px solid rgba(79, 172, 254, 0.3);
      color: var(--blue-dark);
      border-radius: 8px;
    }

    /* Responsive */
    @media (max-width: 992px){
      .sidebar{ width: 92px; padding: 18px 10px; }
      .brand-sub, .brand-title span{ display:none; }
      .nav-parroquia .nav-link span{ display:none; }
      .main{ padding: 18px 14px; }
    }
  </style>
</head>
<body>

<div class="app-shell">

  {{-- Sidebar --}}
  <aside class="sidebar">
    <div class="brand">
      <div class="logo" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
      </div>
      <div>
        <p class="brand-title mb-0">SIS_CATEQ</p>
        <p class="brand-sub mb-0">@yield('subtitle', 'Panel')</p>
      </div>
    </div>

    <nav class="nav flex-column nav-parroquia">
      <a class="nav-link {{ request()->routeIs('secretaria.dashboard') ? 'active' : '' }}" href="{{ route('secretaria.dashboard') }}">
        <span>Dashboard</span>
      </a>
      <a class="nav-link {{ request()->routeIs('secretaria.usuarios.pendientes') ? 'active' : '' }}" href="{{ route('secretaria.usuarios.pendientes') }}">
        <span>Registros pendientes</span>
      </a>
    </nav>

    <div class="mt-3 pt-3" style="border-top:1px solid rgba(30, 58, 138, 0.1);">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-parroquia btn-sm w-100" type="submit">Salir</button>
      </form>
    </div>
  </aside>

  {{-- Main --}}
  <main class="main">
    <div class="topbar">
      <div>
        <h1 class="topbar-title">@yield('header_title', 'Dashboard')</h1>
        <p class="topbar-sub">@yield('header_subtitle', '')</p>
      </div>

      @auth
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="chip">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
          @yield('header_actions')
        </div>
      @endauth
    </div>

    @if(session('status'))
      <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif

    @yield('content')
  </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>