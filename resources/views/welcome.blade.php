<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Institucional | Parroquia La Asunción de María</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --blue-primary: #0056b3;
            --blue-light: #e6f2ff;
            --blue-dark: #003d82;
            --blue-soft: #4facfe;
            --white: #ffffff;
            --text-dark: #1a2634;
            --text-muted: #596a7b;
            --border-color: rgba(0, 86, 179, 0.14);
            --shadow-soft: 0 8px 25px rgba(0, 86, 179, 0.06);
            --shadow-card: 0 15px 35px rgba(0, 86, 179, 0.12);

            --font-brand: 'Playfair Display', serif;
            --font-body: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        /* Ajuste Dinámico: 100% del alto, sin scroll en escritorio normal */
        html, body {
            height: 100%;
            min-height: 100vh;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background:
                radial-gradient(circle at top left, rgba(79, 172, 254, 0.10), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #eef6ff 45%, #e6f2ff 100%);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
        }

        .brand-font {
            font-family: var(--font-brand);
        }

        /* ================= NAVBAR ================= */
        .navbar-custom {
            height: clamp(65px, 8vh, 85px);
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 86, 179, 0.10);
            display: flex;
            align-items: center;
            z-index: 50;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            transform: translateY(-100%);
            opacity: 0;
            animation: slideDown 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: clamp(1.1rem, 1.5vw, 1.3rem);
        }

        .brand-logo {
            width: clamp(36px, 4vh, 42px);
            height: clamp(36px, 4vh, 42px);
            border: 1px solid var(--border-color);
            background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);
            box-shadow: 0 6px 14px rgba(0, 86, 179, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .navbar-brand:hover .brand-logo {
            transform: rotate(15deg) scale(1.1);
            background: var(--blue-primary);
        }
        .navbar-brand:hover .brand-logo i { color: var(--white) !important; }

        /* ================= BOTONES ================= */
        .btn-login, .btn-register {
            border-radius: 50px;
            padding: clamp(8px, 1.2vh, 10px) clamp(16px, 2vw, 22px);
            font-weight: 700;
            font-size: clamp(0.85rem, 1vw, 0.95rem);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--blue-primary), var(--blue-dark));
            color: var(--white);
            border: none;
            box-shadow: 0 8px 18px rgba(0, 86, 179, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-login::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, var(--blue-soft), var(--blue-primary));
            z-index: -1; opacity: 0; transition: opacity 0.4s ease;
        }
        .btn-login:hover { color: var(--white); transform: translateY(-3px); }
        .btn-login:hover::before { opacity: 1; }

        .btn-register {
            background-color: rgba(255, 255, 255, 0.8);
            color: var(--blue-primary);
            border: 1.5px solid rgba(0, 86, 179, 0.22);
        }
        .btn-register:hover {
            background-color: var(--blue-light); color: var(--blue-dark);
            transform: translateY(-2px); border-color: var(--blue-primary);
        }

        /* ================= HERO & ORBES ANIMADOS ================= */
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .bg-orb {
            position: absolute; border-radius: 50%; filter: blur(50px);
            z-index: 0; animation: floatOrb 15s infinite alternate ease-in-out;
        }
        .orb-1 { top: 5%; right: 10%; width: 280px; height: 280px; background: rgba(79, 172, 254, 0.2); animation-duration: 18s; }
        .orb-2 { bottom: 10%; left: 5%; width: 320px; height: 320px; background: rgba(0, 86, 179, 0.12); animation-duration: 22s; animation-delay: -5s; }
        .orb-3 { top: 40%; left: 50%; width: 200px; height: 200px; background: rgba(79, 172, 254, 0.15); animation-duration: 15s; animation-delay: -10s; }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(20px, -30px) scale(1.05); }
            100% { transform: translate(-15px, 15px) scale(0.95); }
        }

        .hero-section .container { position: relative; z-index: 2; }

        /* Acento Institucional (Reemplazo de la etiqueta) */
        .title-accent {
            width: clamp(50px, 5vw, 70px);
            height: 4px;
            background: linear-gradient(90deg, var(--blue-primary), var(--blue-soft));
            border-radius: 2px;
            margin-bottom: clamp(12px, 2vh, 20px);
        }

        /* Espaciado fluido en Textos */
        .hero-title {
            font-size: clamp(2rem, 3.8vw, 3.2rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: clamp(10px, 1.5vh, 20px);
            color: var(--blue-primary);
        }

        .hero-subtitle {
            font-size: clamp(0.95rem, 1.1vw, 1.05rem);
            color: var(--text-muted);
            margin-bottom: clamp(20px, 3.5vh, 40px);
            line-height: 1.6;
            max-width: 600px;
            font-weight: 500;
        }

        .hero-subheading {
            color: var(--blue-soft);
            font-size: clamp(0.9rem, 1vw, 1.05rem);
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: clamp(8px, 1.5vh, 16px);
        }

        /* ================= CONTACTO ================= */
        .contact-info-block {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color); border-radius: 16px;
            padding: clamp(16px, 2.5vh, 24px);
            max-width: 500px; box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
        }

        .contact-info-block:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0, 86, 179, 0.12); }

        .contact-item {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: clamp(10px, 1.5vh, 16px);
            font-size: clamp(0.85rem, 1vw, 0.95rem);
            color: var(--text-dark); line-height: 1.4; font-weight: 500;
        }
        .contact-item:last-child { margin-bottom: 0; }

        .icon-wrapper { position: relative; min-width: clamp(38px, 4.5vh, 44px); width: clamp(38px, 4.5vh, 44px); height: clamp(38px, 4.5vh, 44px); }
        .contact-icon {
            display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;
            background: linear-gradient(135deg, #edf6ff 0%, #dbeeff 100%); color: var(--blue-primary);
            border-radius: 50%; font-size: clamp(0.9rem, 1.2vw, 1.1rem); box-shadow: inset 0 0 0 1px rgba(0, 86, 179, 0.08);
            position: relative; z-index: 2; transition: all 0.3s ease;
        }
        .icon-wrapper::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 50%;
            border: 2px solid var(--blue-soft); opacity: 0; z-index: 1;
            animation: iconPulse 2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
        }
        .contact-item:nth-child(1) .icon-wrapper::after { animation-delay: 0s; }
        .contact-item:nth-child(3) .icon-wrapper::after { animation-delay: 0.6s; }
        .contact-item:nth-child(5) .icon-wrapper::after { animation-delay: 1.2s; }

        @keyframes iconPulse { 0% { transform: scale(0.9); opacity: 0.8; } 70%, 100% { transform: scale(1.5); opacity: 0; } }
        .contact-item:hover .contact-icon { background: var(--blue-primary); color: var(--white); transform: rotate(15deg) scale(1.05); }

        .contact-label { color: var(--blue-primary); font-weight: 800; font-size: clamp(0.75rem, 0.9vw, 0.85rem); text-transform: uppercase; margin-bottom: 2px; }

        /* ================= COMPOSICIÓN FLOTANTE ================= */
        .floating-composition {
            position: relative; height: clamp(350px, 60vh, 480px);
            width: 100%; display: flex; align-items: center; justify-content: center; perspective: 1200px;
        }

        .floating-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 18px; padding: clamp(25px, 4vh, 35px) clamp(30px, 3vw, 40px);
            box-shadow: var(--shadow-card); border: 1px solid rgba(0, 86, 179, 0.15); border-top: 4px solid var(--blue-primary);
            display: flex; flex-direction: column; width: 100%; max-width: 420px;
            transform-style: preserve-3d; animation: floatCard 6s ease-in-out infinite; transition: transform 0.15s ease-out;
        }
        .floating-card::before {
            content: ''; position: absolute; top: -4px; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--blue-primary), var(--blue-soft), var(--blue-primary)); background-size: 200% auto;
            border-radius: 18px 18px 0 0; animation: gradientMove 3s linear infinite;
        }
        @keyframes gradientMove { 0% { background-position: 0% center; } 100% { background-position: 200% center; } }
        .floating-card:hover { animation-play-state: paused; }

        .floating-card h4 { font-weight: 800; font-size: clamp(1.15rem, 1.5vw, 1.35rem); transform: translateZ(40px); margin-bottom: 4px !important; }

        .roles-list { margin: 0; padding-left: 0; list-style: none; transform: translateZ(25px); transform-style: preserve-3d; }
        .roles-list li {
            position: relative; padding: clamp(6px, 1vh, 10px) 12px clamp(6px, 1vh, 10px) 30px;
            margin-bottom: clamp(4px, 0.8vh, 8px); border-radius: 8px;
            font-size: clamp(0.9rem, 1.1vw, 1.05rem); color: var(--text-muted); font-weight: 600;
            transition: all 0.3s ease; opacity: 0; transform: translateX(-20px);
        }
        .roles-list.list-visible li { opacity: 1; transform: translateX(0); }
        .roles-list li:nth-child(1) { transition-delay: 0.1s; } .roles-list li:nth-child(2) { transition-delay: 0.2s; }
        .roles-list li:nth-child(3) { transition-delay: 0.3s; } .roles-list li:nth-child(4) { transition-delay: 0.4s; }
        .roles-list li:nth-child(5) { transition-delay: 0.5s; }

        .roles-list li::before {
            content: '\F26E'; font-family: 'bootstrap-icons'; position: absolute; left: 8px;
            color: var(--blue-soft); font-size: clamp(0.9rem, 1vw, 1rem); transition: transform 0.3s ease;
        }
        .roles-list li:hover { color: var(--blue-primary); background: var(--blue-light); transform: translateX(8px) translateZ(10px); }
        .roles-list li:hover::before { color: var(--blue-primary); transform: scale(1.2); }

        /* ================= CLASES PARA ANIMACIONES JS ================= */
        .js-reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .js-reveal.is-visible { opacity: 1; transform: translateY(0); }
        .delay-1 { transition-delay: 0.1s; } .delay-2 { transition-delay: 0.2s; } .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; } .delay-5 { transition-delay: 0.5s; }

        @keyframes floatCard { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        @keyframes slideDown { from { transform: translateY(-100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* ================= FOOTER ================= */
        .footer {
            min-height: clamp(45px, 6vh, 60px);
            display: flex; align-items: center; background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-top: 1px solid rgba(0, 86, 179, 0.10); font-size: clamp(0.8rem, 0.9vw, 0.9rem);
            position: relative; z-index: 10;
        }
        .footer p { color: var(--text-muted) !important; font-weight: 600; text-align: center; }

        /* Pantallas muy pequeñas y móviles */
        @media (max-height: 650px), (max-width: 991.98px) {
            html, body { height: auto; overflow-y: auto; overflow-x: hidden; min-height: 100vh; }
            .hero-section { padding: 40px 0; }
            .floating-composition { height: auto; padding: 30px 0; }
            .floating-card:hover { animation-play-state: running; }
            /* Ajuste para móvil para centrar el acento */
            .title-accent { margin: 0 auto clamp(12px, 2vh, 20px); }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-custom w-100 position-relative">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand d-flex align-items-center m-0 text-decoration-none" href="#">
            <div class="brand-logo rounded-circle me-2">
                <i class="bi bi-shield-lock-fill text-primary fs-6"></i>
            </div>
            <span class="brand-font">Portal Institucional</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-login text-decoration-none">
                        Ir al Panel <i class="bi bi-arrow-right"></i>
                    </a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-register text-decoration-none d-none d-sm-inline-flex">
                            Solicitar Acceso <i class="bi bi-person-plus"></i>
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="btn-login text-decoration-none">
                        Ingresar <i class="bi bi-box-arrow-in-right"></i>
                    </a>
                @endauth
            @endif
        </div>
    </div>
</nav>

<section class="hero-section">
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>

    <div class="container">
        <div class="row align-items-center w-100">

            <div class="col-lg-6 text-center text-lg-start">

                <div class="title-accent js-reveal delay-1"></div>

                <h1 class="hero-title brand-font js-reveal delay-1">
                    Sistema Integral de Gestión Parroquial
                </h1>

                <h4 class="hero-subheading js-reveal delay-2">
                    Parroquia "La Asunción de María" • Pipioltepec
                </h4>

                <p class="hero-subtitle js-reveal delay-3">
                    Plataforma digital integral diseñada para centralizar la información comunitaria, agilizar los trámites de secretaría y mantener un control académico riguroso de la catequesis en un entorno estructurado y seguro.
                </p>

                <div class="contact-info-block mx-auto mx-lg-0 text-start js-reveal delay-4">
                    <div class="contact-item">
                        <div class="icon-wrapper">
                            <div class="contact-icon"><i class="bi bi-building"></i></div>
                        </div>
                        <div>
                            <div class="contact-label">Sede Administrativa</div>
                            <div>Iglesia de la Candelaria. Pipioltepec, Valle de Bravo.</div>
                        </div>
                    </div>

                    <hr class="my-2" style="opacity: 0.08;">

                    <div class="contact-item">
                        <div class="icon-wrapper">
                            <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                        </div>
                        <div>
                            <div class="contact-label">Atención y Soporte</div>
                            <div>(729) 131 7536</div>
                        </div>
                    </div>

                    <hr class="my-2" style="opacity: 0.08;">

                    <div class="contact-item">
                        <div class="icon-wrapper">
                            <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
                        </div>
                        <div>
                            <div class="contact-label">Horario de Servicio</div>
                            <div>Lunes a Viernes — Matutino y Vespertino</div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-6 d-none d-lg-block js-reveal delay-2">
                <div class="floating-composition" id="card-container">
                    <div class="floating-card" id="tilt-card">
                        <div class="d-flex align-items-center gap-3 mb-2" style="transform: translateZ(50px);">
                            <div style="color: var(--blue-primary);">
                                <i class="bi bi-server" style="font-size: clamp(2rem, 2.5vw, 2.5rem); filter: drop-shadow(0 4px 6px rgba(0,86,179,0.2));"></i>
                            </div>
                            <div>
                                <h4 class="mb-0" style="color: var(--blue-primary);">Módulos de Acceso</h4>
                                <p class="text-muted small mb-0 fw-semibold" style="font-size: clamp(0.75rem, 0.9vw, 0.85rem);">Sistema basado en privilegios</p>
                            </div>
                        </div>

                        <ul class="roles-list mt-2" id="roles-list">
                            <li>Dirección Parroquial</li>
                            <li>Coordinación General</li>
                            <li>Gestión de Comunidades</li>
                            <li>Control de Secretaría</li>
                            <li>Panel de Catequistas</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<footer class="footer js-reveal delay-5">
    <div class="container d-flex justify-content-center align-items-center">
        <p class="mb-0">
            © {{ date('Y') }} Sistema de Gestión Parroquial. Arquidiócesis de Toluca.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        setTimeout(() => {
            const reveals = document.querySelectorAll('.js-reveal');
            reveals.forEach(el => el.classList.add('is-visible'));
        }, 100);

        setTimeout(() => {
            const rolesList = document.getElementById('roles-list');
            if(rolesList) rolesList.classList.add('list-visible');
        }, 500);

        const cardContainer = document.getElementById('card-container');
        const tiltCard = document.getElementById('tilt-card');

        if (cardContainer && tiltCard) {
            cardContainer.addEventListener('mousemove', (e) => {
                const rect = cardContainer.getBoundingClientRect();
                const x = (e.clientX - rect.left - rect.width / 2) / (rect.width / 2);
                const y = (e.clientY - rect.top - rect.height / 2) / (rect.height / 2);

                const maxRotation = 12;
                tiltCard.style.transform = `rotateY(${x * maxRotation}deg) rotateX(${-y * maxRotation}deg)`;
            });

            cardContainer.addEventListener('mouseleave', () => {
                tiltCard.style.transition = 'transform 0.6s cubic-bezier(0.23, 1, 0.32, 1)';
                tiltCard.style.transform = 'rotateY(0deg) rotateX(0deg)';
                setTimeout(() => tiltCard.style.transition = 'transform 0.15s ease-out', 600);
            });

            cardContainer.addEventListener('mouseenter', () => {
                tiltCard.style.transition = 'transform 0.15s ease-out';
            });
        }
    });
</script>
</body>
</html>
