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
      border:1px solid rgba(255, 216, 138, 0.65);
      border-radius:50%;
      color:var(--gold);
      box-shadow:0 0 22px rgba(255, 177, 85, 0.12);
      background: rgba(0,0,0,.10);
    }

    .brand-title{
      margin:0;
      font-family:'Cinzel', serif;
      color: var(--gold);
      letter-spacing: 2px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 1.15rem;
      line-height: 1.1;
    }
    .brand-sub{
      margin:0;
      color: rgba(244,241,234,.70);
      font-size: .90rem;
    }

    .btn-parroquia{
      background: linear-gradient(180deg, var(--gold-bright) 0%, var(--gold) 100%);
      color: #241a0a;
      border: 0;
      border-radius: 10px;
      font-weight: 800;
      letter-spacing: .3px;
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

    .card-parroquia{
      background: var(--panel);
      border: 1px solid var(--border);
      border-left: 4px solid var(--gold);
      border-radius: 12px;
      box-shadow: 0 40px 90px rgba(0,0,0,.55);
      overflow: hidden;
    }

    .card-parroquia .card-header{
      background: rgba(0,0,0,.15);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      color: rgba(244,241,234,.90);
    }

    .badge-gold{
      background: var(--gold);
      color: #241a0a;
      font-weight: 800;
    }
    .badge-muted{
      background: rgba(255,255,255,.10);
      border: 1px solid rgba(255,255,255,.12);
      color: rgba(244,241,234,.85);
    }

    .table-dark{
      --bs-table-bg: rgba(0,0,0,.35);
      --bs-table-color: rgba(244,241,234,.95);
    }

    .table td, .table th{
      border-color: rgba(255,255,255,0.06) !important;
      color: rgba(244,241,234,.90);
    }

    .table-hover tbody tr:hover{
      background: rgba(255, 216, 138, 0.06) !important;
    }

    .input-group-text{
      background: rgba(0,0,0,.20);
      border-color: rgba(255, 216, 138, 0.25);
      color: rgba(244,241,234,.85);
    }
    .form-control{
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 216, 138, 0.25);
      color: var(--ink);
    }
    .form-control:focus{
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(255, 216, 138, 0.70);
      box-shadow: 0 0 0 .25rem rgba(255, 177, 85, 0.12);
      color: var(--ink);
    }

    .alert-success{
      background: rgba(46, 204, 113, 0.10);
      border: 1px solid rgba(46, 204, 113, 0.25);
      color: rgba(244,241,234,.95);
    }
    .alert-info{
      background: rgba(255, 216, 138, 0.10);
      border: 1px solid rgba(255, 216, 138, 0.25);
      color: rgba(244,241,234,.95);
    }

    .text-muted{ color: rgba(244,241,234,.70) !important; }



    /* ===== Forzar texto negro en las filas ===== */
#pendientesTable tbody td{
  color: #000 !important;
  opacity: 1 !important;
}

/* Por si hay spans/a/small con text-muted o estilos heredados */
#pendientesTable tbody td span,
#pendientesTable tbody td small,
#pendientesTable tbody td a,
#pendientesTable tbody td div{
  color: #000 !important;
  opacity: 1 !important;
}

/* Mantener badges con su estilo (solo ajusto texto del badge si hiciera falta) */
#pendientesTable tbody .badge{
  color: #241a0a !important;
  opacity: 1 !important;
}

/* Encabezado sigue claro */
#pendientesTable thead th{
  color: rgba(244,241,234,.95) !important;
}
  </style>
</head>
<body>

<div class="container parroquia-shell">

  <div class="parroquia-topbar">
    <div class="brand">
      <div class="logo-container" aria-hidden="true">
        <svg width="20" height="26" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M10 0H14V8H24V12H14V32H10V12H0V8H10V0Z" fill="currentColor"/>
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