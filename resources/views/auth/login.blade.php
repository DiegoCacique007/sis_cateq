<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sis_Cateq | Acceso</title>

  {{-- Bootstrap --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  {{-- Google Fonts --}}
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

  <style>
    :root{
      --gold:#e6c15a;
      --gold-bright:#ffd88a;
      --amber:#ffb155;
      --deep-black:#0b0a08;
      --ink:#f4f1ea;
      --muted:rgba(244,241,234,.72);
    }

    body{
      min-height:100vh;
      margin:0;
      font-family:'Lora', serif;
      background:
        radial-gradient(circle at 35% 25%, rgba(255, 177, 85, 0.18) 0%, rgba(11,10,8,0) 55%),
        radial-gradient(circle at center, #1a1712 0%, var(--deep-black) 100%);
      background-attachment:fixed;
      overflow:hidden;
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
    }

    body::before{
      content:"";
      position:absolute;
      inset:0;
      opacity:.05;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 40 40'%3E%3Cpath d='M18 4h4v10h10v4H22v18h-4V18H8v-4h10V4z' fill='%23ffffff'/%3E%3C/svg%3E");
      z-index:1;
      pointer-events:none;
    }

    .stage{
      perspective:1200px;
      width:100%;
      display:flex;
      justify-content:center;
      position:relative;
      z-index:2;
      padding: 18px;
    }

    .card-3d{
      width:min(440px, 92%);
      background:rgba(22, 18, 14, 0.92);
      backdrop-filter:blur(10px);
      border-radius:8px;

      border-left:4px solid var(--gold);
      border-right:1px solid rgba(255, 216, 138, 0.14);
      border-top:1px solid rgba(255, 216, 138, 0.14);
      border-bottom:1px solid rgba(255, 216, 138, 0.14);

      box-shadow:0 40px 90px rgba(0, 0, 0, 0.88);
      transform-style:preserve-3d;
      transition:transform 0.4s ease-out;
      position:relative;
      overflow:hidden;
    }

    .layer{
      padding:42px;
      transform:translateZ(30px);
    }

    .church-header{ text-align:center; margin-bottom:22px; }

    .logo-container{
      display:inline-block;
      margin-bottom:14px;
      border:1px solid rgba(255, 216, 138, 0.65);
      padding:10px;
      border-radius:50%;
      color:var(--gold);
      box-shadow:0 0 22px rgba(255, 177, 85, 0.12);
    }

    .system-name{
      font-family:'Cinzel', serif;
      color:var(--gold);
      letter-spacing:2px;
      font-weight:700;
      font-size:1.65rem;
      text-transform:uppercase;
      margin:0;
    }

    .form-label{
      color:var(--gold);
      font-size:0.85rem;
      font-weight:700;
      text-transform:uppercase;
      margin-bottom:8px;
    }

    .form-control{
      background:rgba(255, 255, 255, 0.08);
      border:1px solid rgba(255, 216, 138, 0.35);
      border-radius:6px;
      color:var(--ink);
      padding:12px 12px;
      transition:all 0.25s ease;
    }

    .form-control:focus{
      background:rgba(255, 255, 255, 0.12);
      border-color:var(--gold-bright);
      box-shadow:0 0 0 4px rgba(255, 177, 85, 0.10);
      color:var(--ink);
    }

    .form-control::placeholder{
      color:rgba(244, 241, 234, 0.65) !important;
      opacity:1;
      font-style:italic;
      font-size:0.92rem;
    }

    .btn-church{
      background:linear-gradient(180deg, var(--gold-bright) 0%, var(--gold) 100%);
      color:#241a0a;
      border:none;
      border-radius:8px;
      padding:12px;
      font-family:'Cinzel', serif;
      font-weight:800;
      letter-spacing:1px;
      transition:0.25s ease;
      margin-top:10px;
      box-shadow:0 12px 28px rgba(0,0,0,.35);
    }

    .btn-church:hover{
      filter:brightness(1.03);
      transform:translateY(-2px);
      box-shadow:0 16px 34px rgba(0,0,0,.42);
    }

    .footer-links{
      margin-top:24px;
      text-align:center;
      font-size:0.9rem;
      border-top:1px solid rgba(255,255,255,0.06);
      padding-top:18px;
    }

    .footer-links a{ color:var(--muted); text-decoration:none; }
    .footer-links a:hover{ color:var(--gold-bright); }

    .form-check-label{ color:rgba(244,241,234,.78) !important; }
    .text-white-50{ color:rgba(244,241,234,.70) !important; }

    .corner{
      position:absolute; width:16px; height:16px;
      border:1px solid rgba(255, 216, 138, 0.35);
      pointer-events:none;
    }
    .top-left{ top:12px; left:12px; border-right:none; border-bottom:none; }
    .bottom-right{ bottom:12px; right:12px; border-left:none; border-top:none; }

    /* Alerts con estética coherente */
    .alert-parroquia{
      background: rgba(255, 216, 138, 0.10);
      border: 1px solid rgba(255, 216, 138, 0.35);
      color: var(--ink);
      border-radius: 8px;
    }
    .alert-error{
      background: rgba(255, 0, 0, 0.08);
      border: 1px solid rgba(255, 120, 120, 0.25);
      color: var(--ink);
      border-radius: 8px;
    }
  </style>
</head>

<body>
  <div class="stage">
    <div class="card-3d">
      <div class="top-left corner"></div>
      <div class="bottom-right corner"></div>

      <div class="layer">
        <div class="church-header">
          <div class="logo-container" aria-hidden="true">
            <svg width="24" height="32" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 0H14V8H24V12H14V32H10V12H0V8H10V0Z" fill="currentColor"/>
            </svg>
          </div>
          <h1 class="system-name">Sis_Cateq</h1>
        </div>

        {{-- Mensaje (ej. registro enviado) --}}
        @if (session('status'))
          <div class="alert alert-parroquia py-2 mb-3" role="alert">
            {{ session('status') }}
          </div>
        @endif

        {{-- Errores (pendiente, credenciales, etc.) --}}
        @if ($errors->any())
          <div class="alert alert-error py-2 mb-3" role="alert">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="mb-4">
            <label class="form-label">Correo</label>
            <input
              type="email"
              name="email"
              class="form-control"
              value="{{ old('email') }}"
              placeholder="ejemplo@parroquia.org.mx"
              required
              autofocus
              autocomplete="username"
            >
          </div>

          <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input
              type="password"
              name="password"
              class="form-control"
              placeholder="********"
              required
              autocomplete="current-password"
            >
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                id="remember"
                name="remember"
                style="background-color: transparent; border-color: var(--gold);"
                {{ old('remember') ? 'checked' : '' }}
              >
              <label class="form-check-label small" for="remember">Mantener sesión</label>
            </div>

            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="small text-white-50">¿Olvidó su acceso?</a>
            @endif
          </div>

          <button type="submit" class="btn btn-church w-100">Iniciar Sesión</button>
        </form>

        <div class="footer-links">
          @if (Route::has('register'))
            <a href="{{ route('register') }}">Solicitar acceso al sistema</a>
          @endif
        </div>
      </div>
    </div>
  </div>

  <script>
    const card = document.querySelector('.card-3d');
    const stage = document.body;

    stage.addEventListener('mousemove', (e) => {
      const x = (e.clientX / window.innerWidth - 0.5) * 10;
      const y = (e.clientY / window.innerHeight - 0.5) * 10;
      card.style.transform = `rotateY(${x}deg) rotateX(${-y}deg)`;
    });

    stage.addEventListener('mouseleave', () => {
      card.style.transform = 'rotateX(0deg) rotateY(0deg)';
    });
  </script>
</body>
</html>