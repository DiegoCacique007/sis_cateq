<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado de Primera Comunión</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: #1e293b; background: #fff; display: flex; flex-direction: column; align-items: center; padding: 20px; }

        .cert-container {
            width: 800px;
            min-height: 560px;
            border: 3px solid #b45309;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(145deg, #fffbeb, #fef3c7, #fffbeb);
        }

        .cert-border-inner {
            margin: 12px;
            border: 1px solid #d97706;
            border-radius: 14px;
            padding: 40px 50px;
            text-align: center;
        }

        .cert-cross { font-size: 2.5rem; color: #b45309; margin-bottom: 8px; }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            color: #92400e;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .cert-subtitle {
            font-size: 1rem;
            color: #b45309;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .cert-parish {
            font-family: 'Cinzel', serif;
            font-size: .95rem;
            color: #78350f;
            margin-bottom: 24px;
            letter-spacing: 1px;
        }

        .cert-body { font-size: .95rem; line-height: 1.8; text-align: justify; margin-bottom: 30px; color: #44403c; }
        .cert-body strong { color: #1e293b; }

        .cert-data {
            text-align: left;
            margin: 0 auto 30px;
            max-width: 500px;
            font-size: .88rem;
        }
        .cert-data dt { font-weight: 700; color: #92400e; float: left; width: 160px; }
        .cert-data dd { margin-left: 170px; margin-bottom: 6px; color: #44403c; }

        .cert-footer { font-size: .78rem; color: #a8a29e; margin-top: 20px; }

        .cert-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
        }
        .cert-sig {
            text-align: center;
            min-width: 180px;
        }
        .cert-sig-line {
            border-top: 1px solid #92400e;
            margin-bottom: 4px;
        }
        .cert-sig-label { font-size: .78rem; color: #78350f; font-weight: 600; }

        .no-print { margin-bottom: 20px; }
        .btn-print {
            background: linear-gradient(135deg, #b45309, #d97706);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: .95rem;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir certificado</button>
    </div>

    <div class="cert-container">
        <div class="cert-border-inner">
            <div class="cert-cross">✟</div>
            <h1 class="cert-title">Certificado</h1>
            <h2 class="cert-subtitle">Primera Comunión</h2>
            <p class="cert-parish">Parroquia La Asunción de María</p>

            <div class="cert-body">
                Se certifica que <strong>{{ trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}</strong>,
                perteneciente a la comunidad <strong>{{ $alumno->comunidad_nombre ?? '—' }}</strong>,
                ha recibido el Sacramento de la <strong>Primera Comunión</strong>
                habiendo cumplido satisfactoriamente con los requisitos establecidos por esta parroquia.
            </div>

            <dl class="cert-data">
                <dt>Lugar de Bautizo:</dt>
                <dd>{{ $alumno->bautizo_lugar }}</dd>

                <dt>Fecha de Bautizo:</dt>
                <dd>{{ \Carbon\Carbon::parse($alumno->bautizo_fecha)->format('d/m/Y') }}</dd>

                <dt>Libro / Acta:</dt>
                <dd>Libro {{ $alumno->bautizo_libro }}, Acta {{ $alumno->bautizo_acta }}</dd>
            </dl>

            <div class="cert-signatures">
                <div class="cert-sig">
                    <div class="cert-sig-line"></div>
                    <span class="cert-sig-label">Párroco</span>
                </div>
                <div class="cert-sig">
                    <div class="cert-sig-line"></div>
                    <span class="cert-sig-label">Secretaría</span>
                </div>
            </div>

            <p class="cert-footer">
                Documento generado el {{ now()->format('d/m/Y') }} — SIS CATEQ
            </p>
        </div>
    </div>
</body>
</html>
