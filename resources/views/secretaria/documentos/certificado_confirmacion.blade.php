<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado de Confirmación</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: #1e293b; background: #fff; display: flex; flex-direction: column; align-items: center; padding: 20px; }

        .cert-container {
            width: 800px;
            min-height: 620px;
            border: 3px solid #7c3aed;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(145deg, #f5f3ff, #ede9fe, #f5f3ff);
        }

        .cert-border-inner {
            margin: 12px;
            border: 1px solid #8b5cf6;
            border-radius: 14px;
            padding: 36px 50px;
            text-align: center;
        }

        .cert-cross { font-size: 2.5rem; color: #7c3aed; margin-bottom: 8px; }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            color: #5b21b6;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .cert-subtitle {
            font-size: 1rem;
            color: #7c3aed;
            font-weight: 600;
            margin-bottom: 26px;
        }

        .cert-parish {
            font-family: 'Cinzel', serif;
            font-size: .95rem;
            color: #4c1d95;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        .cert-body { font-size: .95rem; line-height: 1.8; text-align: justify; margin-bottom: 24px; color: #44403c; }
        .cert-body strong { color: #1e293b; }

        .cert-section-title {
            font-weight: 800;
            color: #5b21b6;
            font-size: .88rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 16px 0 8px;
            text-align: left;
        }

        .cert-data {
            text-align: left;
            margin: 0 auto 16px;
            max-width: 500px;
            font-size: .86rem;
        }
        .cert-data dt { font-weight: 700; color: #5b21b6; float: left; width: 160px; }
        .cert-data dd { margin-left: 170px; margin-bottom: 5px; color: #44403c; }

        .cert-footer { font-size: .78rem; color: #a8a29e; margin-top: 16px; }

        .cert-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 36px;
        }
        .cert-sig { text-align: center; min-width: 180px; }
        .cert-sig-line { border-top: 1px solid #5b21b6; margin-bottom: 4px; }
        .cert-sig-label { font-size: .78rem; color: #4c1d95; font-weight: 600; }

        .no-print { margin-bottom: 20px; }
        .btn-print {
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
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
            <h2 class="cert-subtitle">Sacramento de la Confirmación</h2>
            <p class="cert-parish">Parroquia La Asunción de María</p>

            <div class="cert-body">
                Se certifica que <strong>{{ trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}</strong>,
                perteneciente a la comunidad <strong>{{ $alumno->comunidad_nombre ?? '—' }}</strong>,
                ha recibido el <strong>Sacramento de la Confirmación</strong>,
                habiendo cumplido satisfactoriamente con todos los requisitos sacramentales establecidos por esta parroquia.
            </div>

            <div style="max-width: 500px; margin: 0 auto;">
                <p class="cert-section-title">Datos de Bautizo</p>
                <dl class="cert-data">
                    <dt>Lugar:</dt>
                    <dd>{{ $alumno->bautizo_lugar }}</dd>
                    <dt>Fecha:</dt>
                    <dd>{{ \Carbon\Carbon::parse($alumno->bautizo_fecha)->format('d/m/Y') }}</dd>
                    <dt>Libro / Acta:</dt>
                    <dd>Libro {{ $alumno->bautizo_libro }}, Acta {{ $alumno->bautizo_acta }}</dd>
                </dl>

                <p class="cert-section-title">Datos de Primera Comunión</p>
                <dl class="cert-data">
                    <dt>Lugar:</dt>
                    <dd>{{ $alumno->primera_comunion_lugar }}</dd>
                    <dt>Fecha:</dt>
                    <dd>{{ \Carbon\Carbon::parse($alumno->primera_comunion_fecha)->format('d/m/Y') }}</dd>
                    <dt>Libro / Acta:</dt>
                    <dd>Libro {{ $alumno->primera_comunion_libro }}, Acta {{ $alumno->primera_comunion_acta }}</dd>
                </dl>
            </div>

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
