<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro | Parroquia La Asunción de María</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --blue-main: #0056b3;
            --blue-light: #e6f2ff;
            --blue-dark: #003d82;
            --blue-soft: #4facfe;
            --bg-light: #f0f8ff;
            --text-main: #1a2634;
            --text-soft: #596a7b;
            --muted: rgba(26, 38, 52, 0.6);
            --font-brand: 'Cinzel', serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
        }

        *{ box-sizing:border-box; }

        html, body{
            height: 100%;
            overflow: hidden; /* Evita el scroll */
            margin: 0;
            padding: 0;
        }

        body{
            font-family: var(--font-body);
            background:
                radial-gradient(circle at top left, rgba(79, 172, 254, 0.10), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #eef6ff 45%, #e6f2ff 100%);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
        }

        .stage{
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1200px;
            padding: 20px;
        }

        /* Diseño Horizontal (Split Card) */
        .card-horizontal{
            width: min(960px, 100%);
            max-height: 95vh; /* Se adapta a la pantalla */
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 86, 179, 0.15);
            border: 1px solid rgba(0, 86, 179, 0.15);
            display: flex;
            flex-direction: row;
            overflow: hidden;
            transform-style: preserve-3d;
            transition: transform 0.25s ease-out;
            /* Animación de entrada */
            opacity: 0;
            transform: translateY(30px);
            animation: slideUpFade 0.8s ease forwards;
        }

        /* Panel Izquierdo (Branding) */
        .brand-panel{
            flex: 0.8;
            background: linear-gradient(135deg, var(--blue-main), var(--blue-dark));
            color: #fff;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Adornos del panel izquierdo */
        .brand-panel::before{
            content: ""; position: absolute; top: -50px; left: -50px;
            width: 200px; height: 200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        }
        .brand-panel::after{
            content: ""; position: absolute; bottom: -80px; right: -50px;
            width: 250px; height: 250px; border-radius: 50%;
            background: radial-gradient(circle, rgba(79, 172, 254, 0.2) 0%, transparent 70%);
        }

        .logo-container{
            width: 70px; height: 70px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
            font-size: 2rem;
            backdrop-filter: blur(5px);
            z-index: 2;
        }

        .system-name{
            font-family: var(--font-brand);
            font-size: 1.6rem;
            line-height: 1.2;
            font-weight: 700;
            margin-bottom: 10px;
            z-index: 2;
            text-wrap: balance;
        }

        .system-desc{
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
            font-weight: 400;
            z-index: 2;
        }

        /* Panel Derecho (Formulario) */
        .form-panel{
            flex: 1.2;
            padding: clamp(20px, 4vh, 40px) clamp(25px, 3vw, 40px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title{
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--blue-main);
            margin-bottom: 5px;
        }

        .form-subtitle{
            color: var(--text-soft);
            font-size: 0.9rem;
            margin-bottom: clamp(15px, 3vh, 25px);
        }

        .form-label{
            color: var(--blue-main);
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .form-control, .form-select{
            background: rgba(0, 86, 179, 0.03);
            border: 1px solid rgba(0, 86, 179, 0.15);
            border-radius: 8px;
            color: var(--text-main);
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus{
            background: #fff;
            border-color: var(--blue-main);
            box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.1);
        }

        .form-control::placeholder{
            color: rgba(26, 38, 52, 0.4) !important;
            font-style: italic;
        }

        /* Contenedor de Password para el Ojo */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-soft);
            font-size: 1.1rem;
            transition: color 0.2s;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--blue-main);
        }

        /* Botón */
        .btn-church{
            background: linear-gradient(135deg, var(--blue-main), var(--blue-dark));
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 86, 179, 0.2);
            margin-top: 10px;
        }

        .btn-church:hover{
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(0, 86, 179, 0.3);
            color: #ffffff;
        }

        .footer-links{
            margin-top: 15px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-soft);
        }

        .footer-links a{
            color: var(--blue-main);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .footer-links a:hover{
            color: var(--blue-dark);
            text-decoration: underline;
        }

        /* Animaciones en cascada */
        .anim-item {
            opacity: 0;
            transform: translateY(15px);
            animation: slideUpFade 0.6s ease forwards;
        }

        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.3s; }
        .delay-3 { animation-delay: 0.4s; }
        .delay-4 { animation-delay: 0.5s; }
        .delay-5 { animation-delay: 0.6s; }

        @keyframes slideUpFade {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Ajuste para móviles (Se vuelve vertical si la pantalla es muy pequeña) */
        @media (max-width: 768px){
            html, body { overflow-y: auto; height: auto; min-height: 100vh; }
            .card-horizontal{ flex-direction: column; max-height: none; }
            .brand-panel{ padding: 30px 20px; flex: none; }
            .system-name { font-size: 1.3rem; }
            .logo-container { width: 50px; height: 50px; font-size: 1.5rem; margin-bottom: 15px; }
        }
    </style>
</head>

<body>
<div class="stage" id="stage">
    <div class="card-horizontal" id="tilt-card">

        <div class="brand-panel">
            <div class="logo-container">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1 class="system-name">Parroquia La Asunción de María</h1>
            <p class="system-desc">Sistema Integral de Gestión Parroquial. Solicite su acceso para ingresar al portal operativo.</p>
        </div>

        <div class="form-panel">

            <div class="anim-item delay-1">
                <h2 class="form-title">Crear Cuenta</h2>
                <p class="form-subtitle">Complete sus datos para solicitar acceso.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 mb-3 anim-item delay-1" style="font-size: 0.85rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="row g-3 mb-3 anim-item delay-2">
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Ej. Juan Pérez" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="ejemplo@correo.com" required>
                    </div>
                </div>

                <div class="mb-3 anim-item delay-3">
                    <label class="form-label">Solicita acceso como</label>
                    <select name="requested_role" class="form-select" required>
                        <option value="" disabled {{ old('requested_role') ? '' : 'selected' }}>Selecciona el perfil solicitado...</option>
                        <option value="catequista" {{ old('requested_role')=='catequista' ? 'selected' : '' }}>Catequista</option>
                        <option value="coord_general" {{ old('requested_role')=='coord_general' ? 'selected' : '' }}>Coordinador General</option>
                        <option value="parroco" {{ old('requested_role')=='parroco' ? 'selected' : '' }}>Párroco</option>
                        <option value="coord_comunidad" {{ old('requested_role')=='coord_comunidad' ? 'selected' : '' }}>Coordinador de Comunidad</option>
                        <option value="secretaria" {{ old('requested_role')=='secretaria' ? 'selected' : '' }}>Secretaría</option>
                    </select>
                </div>

                <div class="row g-3 mb-4 anim-item delay-4">
                    <div class="col-md-6">
                        <label class="form-label">Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Crea tu contraseña" required>
                            <i class="bi bi-eye-slash password-toggle" onclick="togglePassword('password', this)"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar</label>
                        <div class="password-wrapper">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repite la contraseña" required>
                            <i class="bi bi-eye-slash password-toggle" onclick="togglePassword('password_confirmation', this)"></i>
                        </div>
                    </div>
                </div>

                <div class="anim-item delay-5">
                    <button type="submit" class="btn btn-church w-100">Enviar Solicitud de Acceso</button>

                    <div class="footer-links mt-3">
                        ¿Ya tienes cuenta activa? <a href="{{ route('login') }}">Inicia sesión aquí</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    // 1. Efecto 3D sutil para escritorio
    const stage = document.getElementById('stage');
    const tiltCard = document.getElementById('tilt-card');

    if (stage && tiltCard && window.innerWidth > 768) {
        stage.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 8; // Max rotación: 4 grados
            const y = (e.clientY / window.innerHeight - 0.5) * 8;
            tiltCard.style.transform = `rotateY(${x}deg) rotateX(${-y}deg)`;
        });

        stage.addEventListener('mouseleave', () => {
            tiltCard.style.transform = 'rotateX(0deg) rotateY(0deg)';
        });
    }

    // 2. Función para mostrar/ocultar contraseña
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
            icon.style.color = "var(--blue-main)"; // Se pinta azul cuando está visible
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
            icon.style.color = "var(--text-soft)"; // Vuelve a gris
        }
    }
</script>
</body>
</html>
