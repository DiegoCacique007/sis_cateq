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
      --gold:#e6c15a;
      --gold-bright:#ffd88a;
      --deep-black:#0b0a08;
      --ink:#f4f1ea;
      --muted:rgba(244,241,234,.72);
      --panel: rgba(22, 18, 14, 0.92);
      --border: rgba(255, 216, 138, 0.20);
      --sidebar: rgba(10, 9, 7, 0.92);
    }

    body{
      min-height:100vh;
      margin:0;
      font-family:'Lora', serif;
      background:
        radial-gradient(circle at 35% 25%, rgba(255, 177, 85, 0.18) 0%, rgba(11,10,8,0) 55%),
        radial-gradient(circle at center, #1a1712 0%, var(--deep-black) 100%);
      background-attachment:fixed;
      color: var(--ink);
    }

    body::before{
      content:"";
      position: fixed;
      inset:0;
      opacity:.05;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 40 40'%3E%3Cpath d='M18 4h4v10h10v4H22v18h-4V18H8v-4h10V4z' fill='%23ffffff'/%3E%3C/svg%3E");
      pointer-events:none;
      z-index:0;
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
      border-left: 4px solid var(--gold);
      padding: 22px 16px;
    }

    .brand{
      display:flex; align-items:center; gap:12px;
      padding: 10px 10px 18px 10px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      margin-bottom: 14px;
    }
    .logo{
      display:inline-grid; place-items:center;
      width:44px; height:44px;
      border:1px solid rgba(255, 216, 138, 0.65);
      border-radius:50%;
      color:var(--gold);
      box-shadow:0 0 22px rgba(255, 177, 85, 0.12);
      background: rgba(0,0,0,.10);
      flex: 0 0 auto;
    }
    .brand-title{
      margin:0;
      font-family:'Cinzel', serif;
      color: var(--gold);
      letter-spacing: 2px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 1.05rem;
      line-height: 1.1;
    }
    .brand-sub{
      margin:0;
      color: rgba(244,241,234,.70);
      font-size: .88rem;
    }

    .nav-parroquia .nav-link{
      color: rgba(244,241,234,.80);
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 6px;
      border: 1px solid transparent;
      background: transparent;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .nav-parroquia .nav-link:hover{
      color: var(--ink);
      background: rgba(255,216,138,0.06);
      border-color: rgba(255,216,138,0.20);
    }
    .nav-parroquia .nav-link.active{
      background: rgba(255,216,138,0.10);
      border-color: rgba(255,216,138,0.35);
      color: var(--ink);
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
      color: var(--gold);
      letter-spacing: 1px;
      font-weight: 700;
      margin:0;
    }
    .topbar-sub{
      color: rgba(244,241,234,.70);
      margin:0;
      font-size: .92rem;
    }

    .chip{
      border: 1px solid rgba(255,216,138,0.30);
      background: rgba(0,0,0,.12);
      color: rgba(244,241,234,.85);
      padding: 8px 10px;
      border-radius: 999px;
      font-size: .85rem;
    }

    .btn-parroquia{
      background: linear-gradient(180deg, var(--gold-bright) 0%, var(--gold) 100%);
      color: #241a0a;
      border: 0;
      border-radius: 10px;
      font-weight: 800;
      box-shadow: 0 12px 28px rgba(0,0,0,.35);
    }
    .btn-parroquia:hover{ filter: brightness(1.03); transform: translateY(-1px); }

    .btn-outline-parroquia{
      border: 1px solid rgba(255, 216, 138, 0.55);
      color: rgba(244,241,234,.85);
      border-radius: 10px;
      background: rgba(0,0,0,.12);
    }
    .btn-outline-parroquia:hover{
      border-color: rgba(255, 216, 138, 0.90);
      color: var(--ink);
    }

    /* Cards */
    .card-parroquia{
      background: var(--panel);
      border: 1px solid var(--border);
      border-left: 4px solid var(--gold);
      border-radius: 14px;
      box-shadow: 0 40px 90px rgba(0,0,0,.45);
      overflow:hidden;
    }
    .card-parroquia .card-body{ color: rgba(244,241,234,.92); }

    .kpi{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .kpi .label{ color: rgba(244,241,234,.70); font-size: .9rem; }
    .kpi .value{ font-family:'Cinzel', serif; font-weight:700; color: var(--gold); font-size: 1.5rem; }
    .kpi .mini{ color: rgba(244,241,234,.70); font-size: .85rem; }

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
        <svg width="20" height="26" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M10 0H14V8H24V12H14V32H10V12H0V8H10V0Z" fill="currentColor"/>
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

    <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06);">
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
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @yield('content')
  </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>