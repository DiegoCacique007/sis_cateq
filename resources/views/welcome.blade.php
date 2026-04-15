<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Institucional | Parroquia La Asunción de María</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* Variables exactas de tu pantalla de Login */
        :root{
            --blue-main: #0056b3;
            --blue-light: #e6f2ff;
            --blue-dark: #003d82;
            --blue-soft: #4facfe;
            --bg-light: #f0f8ff;
            --text-main: #1a2634;
            --text-soft: #596a7b;
            --font-brand: 'Cinzel', serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; }

        body, html {
            height: 100%;
            margin: 0;
            font-family: var(--font-body);
            /* Fondo exacto del Login */
            background:
                radial-gradient(circle at top left, rgba(79, 172, 254, 0.10), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #eef6ff 45%, #e6f2ff 100%);
            color: var(--text-main);
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .brand-font { font-family: var(--font-brand); }

        /* ================= ANIMACIONES ================= */
        @keyframes slideInLeft {
            0% { opacity: 0; transform: translateX(-40px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            0% { opacity: 0; transform: translateX(40px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInDown {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* ================= ESTRUCTURA PRINCIPAL ================= */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ================= BOTONES SUPERIORES ================= */
        .top-actions {
            width: 100%;
            max-width: 1100px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 20px;
            z-index: 10;
            animation: fadeInDown 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        }

        .btn-top-primary, .btn-top-outline {
            border-radius: 50px;
            padding: 10px 24px;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-top-primary {
            background: linear-gradient(135deg, var(--blue-main), var(--blue-dark));
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 86, 179, 0.15);
        }
        .btn-top-primary:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 86, 179, 0.25);
        }

        .btn-top-outline {
            background-color: #ffffff;
            color: var(--blue-main);
            border: 2px solid rgba(0, 86, 179, 0.15);
        }
        .btn-top-outline:hover {
            background-color: var(--blue-light);
            border-color: var(--blue-main);
            color: var(--blue-dark);
            transform: translateY(-2px);
        }

        /* ================= TARJETA PRINCIPAL ================= */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 86, 179, 0.15);
            border: 1px solid rgba(0, 86, 179, 0.15);
            width: 100%;
            max-width: 1100px;
            display: flex;
            z-index: 2;
        }

        /* ================= PANEL IZQUIERDO (AZUL) ================= */
        .side-blue {
            background: linear-gradient(135deg, var(--blue-main), var(--blue-dark));
            color: #ffffff;
            padding: 4rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            animation: slideInLeft 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
            overflow: hidden;
            /* Asegura las esquinas redondeadas en el lado izquierdo */
            border-radius: 20px 0 0 20px;
        }

        /* Efectos circulares exactos del Login */
        .side-blue::before{
            content: ""; position: absolute; top: -50px; left: -50px;
            width: 200px; height: 200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            z-index: 0;
            pointer-events: none;
        }
        .side-blue::after{
            content: ""; position: absolute; bottom: -80px; right: -50px;
            width: 250px; height: 250px; border-radius: 50%;
            background: radial-gradient(circle, rgba(79, 172, 254, 0.2) 0%, transparent 70%);
            z-index: 0;
            pointer-events: none;
        }

        .side-blue-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .title-accent {
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--blue-soft), #ffffff, var(--blue-soft));
            border-radius: 2px;
            margin: 0 auto 1.5rem auto;
        }

        .hero-title {
            font-size: clamp(2rem, 3.5vw, 3rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .hero-subheading {
            color: var(--blue-soft);
            font-size: clamp(0.9rem, 1vw, 1rem);
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.6;
            font-weight: 400;
            max-width: 90%;
            margin: 0 auto;
        }

        /* ================= PANEL DERECHO (BLANCO) ================= */
        .side-white {
            padding: 4rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            animation: slideInRight 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
            /* Asegura las esquinas redondeadas en el lado derecho */
            border-radius: 0 20px 20px 0;
        }

        /* Bloque de contacto */
        .contact-info-block {
            width: 100%;
            max-width: 440px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: var(--text-main);
            line-height: 1.4;
            font-weight: 500;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid transparent;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .contact-item:hover {
            background: var(--blue-light);
            border-color: rgba(0, 86, 179, 0.15);
            transform: translateX(5px);
        }

        .icon-wrapper { position: relative; min-width: 46px; width: 46px; height: 46px; }

        .contact-icon {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #edf6ff 0%, #dbeeff 100%);
            color: var(--blue-main);
            border-radius: 50%;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        .contact-item:hover .contact-icon { background: var(--blue-main); color: #ffffff; }

        .contact-label {
            color: var(--blue-main); font-weight: 800; font-size: 0.85rem;
            text-transform: uppercase; margin-bottom: 2px;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: 20px;
            font-size: 0.85rem;
            color: var(--text-soft);
            font-weight: 500;
            text-align: center;
        }
        .footer a { color: var(--blue-main); text-decoration: none; font-weight: 700; }
        .footer a:hover { text-decoration: underline; color: var(--blue-dark); }

        /* Responsividad Móvil */
        @media (max-width: 991px) {
            /* En pantallas pequeñas, el panel azul va arriba y el blanco abajo */
            .side-blue {
                padding: 3rem 2rem;
                border-radius: 20px 20px 0 0;
            }
            .side-white {
                padding: 3rem 2rem;
                border-radius: 0 0 20px 20px;
            }
            .top-actions {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <div class="top-actions">
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-top-primary">
                    Ir al Panel <i class="bi bi-arrow-right"></i>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-top-primary">
                    Ingresar <i class="bi bi-box-arrow-in-right"></i>
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-top-outline">
                        Solicitar Acceso <i class="bi bi-person-plus"></i>
                    </a>
                @endif
            @endauth
        @endif
    </div>

    <div class="content-card row g-0">

        <div class="col-lg-6 side-blue">
            <div class="side-blue-content">
                <div class="title-accent"></div>
                <h1 class="hero-title brand-font">Escuela de la Fe</h1>
                <h4 class="hero-subheading">Parroquia "La Asunción de María" • Pipioltepec</h4>
                <p class="hero-subtitle mb-0">
                    Plataforma digital integral diseñada para centralizar la información comunitaria, agilizar los trámites de secretaría y mantener un control académico riguroso de la catequesis en un entorno estructurado y seguro.
                </p>
            </div>
        </div>

        <div class="col-lg-6 side-white">
            <div class="contact-info-block">
                <div class="contact-item">
                    <div class="icon-wrapper">
                        <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    </div>
                    <div>
                        <div class="contact-label">Sede Administrativa</div>
                        <div>Iglesia de la Candelaria. Pipioltepec, Valle de Bravo.</div>
                    </div>
                </div>

                <a href="https://wa.me/527291317536" target="_blank" class="contact-item">
                    <div class="icon-wrapper">
                        <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
                    </div>
                    <div>
                        <div class="contact-label">Atención y Soporte (WhatsApp)</div>
                        <div>(729) 131 7536 <span style="font-size: 0.8rem; color: var(--blue-soft); margin-left: 5px;">¡Escríbenos!</span></div>
                    </div>
                </a>

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
    </div>

    <footer class="footer">
        <p class="mb-1">© {{ date('Y') }} Sistema de Gestión Parroquial. Arquidiócesis de Toluca.</p>
        <p class="mb-0" style="font-size: 0.75rem;">
            <a href="#">Aviso de Privacidad</a> • <a href="#">Términos y Condiciones</a>
        </p>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
