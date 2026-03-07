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

    .parroquia-shell{
      position: relative;
      z-index: 1;
      padding: 26px 16px;
    }

    .parroquia-topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom: 16px;
    }

    .brand{
      display:flex; align-items:center; gap:12px;
    }

    .logo-container{
      display:inline-grid;
      place-items:center;
      width:44px; height:44px;
      border:1px solid rgba(79, 172, 254, 0.5);
      border-radius:50%;
      color:var(--blue-main);
      box-shadow:0 0 22px rgba(79, 172, 254, 0.2);
      background: #ffffff;
    }

    .brand-title{
      margin:0;
      font-family:'Cinzel', serif;
      color: var(--blue-dark);
      letter-spacing: 2px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 1.15rem;
      line-height: 1.1;
    }
    .brand-sub{
      margin:0;
      color: var(--muted);
      font-size: .90rem;
    }

    .btn-parroquia{
      background: linear-gradient(180deg, var(--blue-light) 0%, var(--blue-main) 100%);
      color: #ffffff;
      border: 0;
      border-radius: 10px;
      font-weight: 800;
      letter-spacing: .3px;
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

    .card-parroquia{
      background: var(--panel);
      backdrop-filter: blur(12px);
      border: 1px solid var(--border);
      border-left: 4px solid var(--blue-main);
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(30, 58, 138, 0.08);
      overflow: hidden;
    }

    .card-parroquia .card-header{
      background: rgba(79, 172, 254, 0.08);
      border-bottom: 1px solid var(--border);
      color: var(--blue-dark);
      font-weight: 600;
    }

    /* Mantuve el nombre de clase badge-gold para que no se rompan tus vistas, 
       pero le di estilos azules acordes al nuevo diseño */
    .badge-gold{
      background: var(--blue-main);
      color: #ffffff;
      font-weight: 800;
    }
    .badge-muted{
      background: rgba(79, 172, 254, 0.1);
      border: 1px solid rgba(79, 172, 254, 0.2);
      color: var(--blue-dark);
    }

    /* Estilos de tablas adaptados al tema claro */
    .table{
      --bs-table-bg: transparent;
      --bs-table-color: var(--text-main);
    }
    .table td, .table th{
      border-color: rgba(79, 172, 254, 0.15) !important;
      color: var(--text-main);
    }
    .table thead th{
      color: var(--blue-dark) !important;
      background: rgba(79, 172, 254, 0.05) !important;
    }
    .table-hover tbody tr:hover{
      background: rgba(79, 172, 254, 0.06) !important;
    }

    .input-group-text{
      background: rgba(79, 172, 254, 0.08);
      border-color: rgba(79, 172, 254, 0.3);
      color: var(--blue-dark);
      font-weight: 600;
    }
    .form-control{
      background: #ffffff;
      border: 1px solid rgba(79, 172, 254, 0.3);
      color: var(--text-main);
    }
    .form-control:focus{
      background: #ffffff;
      border-color: var(--blue-main);
      box-shadow: 0 0 0 .25rem rgba(79, 172, 254, 0.15);
      color: var(--text-main);
    }

    .alert-success{
      background: rgba(46, 204, 113, 0.10);
      border: 1px solid rgba(46, 204, 113, 0.25);
      color: #1e6b3b;
    }
    .alert-info{
      background: rgba(79, 172, 254, 0.10);
      border: 1px solid rgba(79, 172, 254, 0.25);
      color: var(--blue-dark);
    }

    .text-muted{ color: var(--muted) !important; }

    /* ===== Forzar texto de tablas al color del tema (reemplazo del anterior) ===== */
    #pendientesTable tbody td{
      color: var(--text-main) !important;
      opacity: 1 !important;
    }

    #pendientesTable tbody td span,
    #pendientesTable tbody td small,
    #pendientesTable tbody td a,
    #pendientesTable tbody td div{
      color: var(--text-main) !important;
      opacity: 1 !important;
    }

    #pendientesTable tbody .badge{
      color: #ffffff !important;
      opacity: 1 !important;
    }
    #pendientesTable tbody .badge.badge-muted{
      color: var(--blue-dark) !important;
    }

    #pendientesTable thead th{
      color: var(--blue-dark) !important;
    }
  </style>
</head>
<body>

<div class="container parroquia-shell">

  <div class="parroquia-topbar">
    <div class="brand">
      <div class="logo-container" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
      </div>
      <div>
        <p class="brand-title mb-0">SIS_CATEQ</p>
        <p class="brand-sub mb-0">@yield('subtitle', 'Panel del sistema')</p>
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center">
      @auth
        <span class="text-muted small d-none d-md-inline">
          {{ auth()->user()->name }} ({{ auth()->user()->role }})
        </span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-outline-parroquia btn-sm" type="submit">Salir</button>
        </form>
      @endauth
    </div>
  </div>

  @yield('content')

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>