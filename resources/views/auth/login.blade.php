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
      /* Nueva paleta de colores azules y blancos */
      --blue-main: #4facfe;
      --blue-light: #8fd3f4;
      --blue-dark: #1e3a8a;
      --bg-light: #f0f8ff;
      --text-main: #2c3e50;
      --muted: rgba(44, 62, 80, 0.65);
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
      overflow:hidden;
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
    }

    /* Se eliminó el body::before que contenía la cruz de fondo */

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
      /* Fondo de tarjeta claro y translúcido */
      background:rgba(255, 255, 255, 0.85);
      backdrop-filter:blur(12px);
      border-radius:12px;

      border-left:4px solid var(--blue-main);
      border-right:1px solid rgba(79, 172, 254, 0.2);
      border-top:1px solid rgba(79, 172, 254, 0.2);
      border-bottom:1px solid rgba(79, 172, 254, 0.2);

      /* Sombra más suave adaptada a tonos claros */
      box-shadow:0 30px 60px rgba(30, 58, 138, 0.12);
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
      border:1px solid rgba(79, 172, 254, 0.5);
      padding:12px;
      border-radius:50%;
      color:var(--blue-main);
      box-shadow:0 0 22px rgba(79, 172, 254, 0.2);
      background: #ffffff;
    }

    .system-name{
      font-family:'Cinzel', serif;
      color:var(--blue-dark);
      letter-spacing:2px;
      font-weight:700;
      font-size:1.65rem;
      text-transform:uppercase;
      margin:0;
    }

    .form-label{
      color:var(--blue-dark);
      font-size:0.85rem;
      font-weight:700;
      text-transform:uppercase;
      margin-bottom:8px;
    }

    .form-control{
      background:rgba(79, 172, 254, 0.04);
      border:1px solid rgba(79, 172, 254, 0.3);
      border-radius:6px;
      color:var(--text-main);
      padding:12px 12px;
      transition:all 0.25s ease;
    }

    .form-control:focus{
      background:rgba(255, 255, 255, 0.9);
      border-color:var(--blue-main);
      box-shadow:0 0 0 4px rgba(79, 172, 254, 0.15);
      color:var(--text-main);
    }

    .form-control::placeholder{
      color:rgba(44, 62, 80, 0.45) !important;
      opacity:1;
      font-style:italic;
      font-size:0.92rem;
    }

    .btn-church{
      /* Degradado azul claro a un azul un poco más vivo */
      background:linear-gradient(180deg, var(--blue-light) 0%, var(--blue-main) 100%);
      color:#ffffff;
      border:none;
      border-radius:8px;
      padding:12px;
      font-family:'Cinzel', serif;
      font-weight:800;
      letter-spacing:1px;
      transition:0.25s ease;
      margin-top:10px;
      box-shadow:0 8px 20px rgba(79, 172, 254, 0.3);
    }

    .btn-church:hover{
      filter:brightness(1.05);
      transform:translateY(-2px);
      box-shadow:0 12px 26px rgba(79, 172, 254, 0.4);
      color:#ffffff;
    }

    .footer-links{
      margin-top:24px;
      text-align:center;
      font-size:0.9rem;
      border-top:1px solid rgba(30, 58, 138, 0.08);
      padding-top:18px;
    }

    .footer-links a{ color:var(--muted); text-decoration:none; transition: 0.2s; }
    .footer-links a:hover{ color:var(--blue-main); }

    .form-check-label{ color:var(--text-main) !important; }
    .text-white-50{ color:var(--muted) !important; text-decoration: none; }
    .text-white-50:hover{ color:var(--blue-main) !important; }

    .corner{
      position:absolute; width:16px; height:16px;
      border:2px solid rgba(79, 172, 254, 0.4);
      pointer-events:none;
    }
    .top-left{ top:12px; left:12px; border-right:none; border-bottom:none; border-radius: 4px 0 0 0; }
    .bottom-right{ bottom:12px; right:12px; border-left:none; border-top:none; border-radius: 0 0 4px 0; }

    /* Alerts con estética coherente */
    .alert-parroquia{
      background: rgba(79, 172, 254, 0.1);
      border: 1px solid rgba(79, 172, 254, 0.3);
      color: var(--blue-dark);
      border-radius: 8px;
    }
    .alert-error{
      background: rgba(255, 70, 70, 0.08);
      border: 1px solid rgba(255, 120, 120, 0.25);
      color: #b91c1c;
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
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
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
              placeholder="ejemplo@correo.com"
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
                style="background-color: transparent; border-color: var(--blue-main);"
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