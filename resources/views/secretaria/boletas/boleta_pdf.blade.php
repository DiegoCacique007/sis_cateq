<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Boleta - {{ $inscripcion->alumno->apellido_paterno }} {{ $inscripcion->alumno->nombre }}</title>

    <!-- Fuentes de Google de Alta Gama -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Lato:wght@400;700;900&display=swap" rel="stylesheet">

    <style>
        @page {
            size: letter;
            margin: 12mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lato', Arial, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            background: #fff;
            line-height: 1.3;
            -webkit-font-smoothing: antialiased;
        }

        .printed-name {
            font-family: 'Lato', Arial, sans-serif;
            font-size: 9pt;
            font-weight: 900;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .formal-grade {
            font-family: 'Lato', Arial, sans-serif !important;
            font-size: 10.5pt !important;
            font-weight: 700 !important;
            color: #111827 !important;
            letter-spacing: 0.5px;
        }

        /* Encabezado institucional */
        .header {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 12px;
            padding-top: 2px;
            padding-bottom: 8px;
            border-bottom: 1.5px solid #d4af37;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left,
        .header-right {
            width: 95px;
            text-align: center;
        }

        .header-center {
            text-align: center;
            padding: 0 10px;
        }

        .header-logo {
            width: 82px;
            height: 82px;
            margin: 0 auto;
            text-align: center;
        }

        .header-logo img {
            width: 82px;
            height: 82px;
            max-width: 82px;
            max-height: 82px;
        }

        .logo-placeholder {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.1;
            padding: 6px;
        }

        .header-center .parroquia {
            font-family: 'Cinzel', serif;
            font-size: 11pt;
            font-weight: 700;
            color: #111827;
            letter-spacing: 0.2px;
            margin-bottom: 4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .header-center .iglesia {
            font-family: 'Cinzel', serif;
            font-size: 10pt;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .header-center .reporte {
            font-size: 10pt;
            font-weight: 900;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 4px;
        }

        .header-center .periodo {
            font-size: 8.7pt;
            font-weight: 700;
            color: #4b5563;
            letter-spacing: 0.3px;
        }

        .datos-header {
            background-color: #B98535;
            color: #111827;
            text-align: center;
            font-weight: 900;
            font-size: 8.5pt;
            padding: 6px 0;
            margin-top: 15px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .datos-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            margin-bottom: 20px;
            padding: 0 15px;
        }

        .datos-cell {
            flex: 1;
            text-align: center;
            padding: 0 15px;
        }

        .datos-cell .line-container {
            border-bottom: 1px solid #111827;
            min-height: 28px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 2px;
            margin-bottom: 5px;
        }

        .datos-cell .label {
            font-size: 7.5pt;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 0 15px;
        }

        .meta-libro {
            display: flex;
            align-items: flex-end;
        }

        .meta-libro .line-container {
            border-bottom: 1px solid #111827;
            min-width: 250px;
            text-align: left;
            line-height: 0.8;
            padding-bottom: 4px;
        }

        .meta-promedio {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
            font-size: 9pt;
            color: #111827;
            margin-right: 165px;
            letter-spacing: 0.5px;
        }

        .prom-box {
            width: 55px;
            height: 30px;
            border: 1px solid #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .cal-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .cal-table th {
            font-family: 'Lato', Arial, sans-serif;
            border: 1px solid rgba(255, 255, 255, 0.4);
            text-align: center;
            vertical-align: middle;
            font-weight: 900 !important;
            font-size: 8pt;
            padding: 4px 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cal-table td {
            border: 1px solid rgba(255, 255, 255, 0.4);
            text-align: center;
            vertical-align: middle;
            height: 28px;
        }

        .bg-orange {
            background-color: #B98535 !important;
            color: #111827 !important;
        }

        .bg-light-orange {
            background-color: #D4A351 !important;
            color: #111827 !important;
        }

        .bg-dark-beige {
            background-color: #DAB982 !important;
        }

        .bg-light-beige {
            background-color: #EFE2C6 !important;
        }

        .cal-table tbody td.unidad-cell {
            font-family: 'Lato', Arial, sans-serif;
            background-color: #B98535 !important;
            color: #111827 !important;
            font-weight: 900 !important;
            font-size: 10pt;
            text-align: left;
            padding-left: 10px;
            width: 75px;
        }

        .asistencias-cell {
            font-family: 'Lato', Arial, sans-serif;
            background-color: #B98535 !important;
            color: #111827 !important;
            font-weight: 900 !important;
            font-size: 8.5pt;
            text-align: left;
            padding-left: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .total-inasistencias {
            font-family: 'Lato', Arial, sans-serif;
            font-size: 7.5pt;
            font-weight: 900 !important;
            text-align: center;
            vertical-align: middle;
            color: #111827;
            letter-spacing: 0.5px;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .observaciones {
            margin-top: 20px;
        }

        .observaciones h4 {
            font-size: 9pt;
            font-weight: 900;
            color: #111827;
            margin-bottom: 6px;
            letter-spacing: 0.8px;
        }

        .obs-box {
            border: 1px solid #B98535;
            border-radius: 4px;
            min-height: 55px;
            background: #fff;
        }

        .firmas {
            margin-top: 45px;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .firmas-top {
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
        }

        .firmas-bottom {
            display: flex;
            justify-content: center;
        }

        .firma-block {
            width: 40%;
            text-align: center;
        }

        .firma-block-center {
            width: 45%;
            text-align: center;
        }

        .firma-line {
            border-top: 1px solid #111827;
            margin-bottom: 6px;
            padding-top: 4px;
        }

        .firma-label {
            font-size: 7pt;
            font-weight: 700;
            color: #374151;
            line-height: 1.4;
            letter-spacing: 0.5px;
        }

        .firma-name {
            font-size: 8.5pt;
            font-weight: 900;
            color: #111827;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.8px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
@php
    $logoArquidiocesisPath = public_path('logos/logo_arquidiocesis.png');
    $logoAsuncionPath = public_path('logos/logo_asuncion_de_maria.png');

    $convertirLogoBase64 = function ($path) {
        if (!file_exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };

    $logoArquidiocesis = $convertirLogoBase64($logoArquidiocesisPath);
    $logoAsuncion = $convertirLogoBase64($logoAsuncionPath);
@endphp

    <!-- Panel de Control Oculto en Impresion -->
<div class="no-print" style="text-align:center; padding:16px; background:#f8fafc; border-bottom:1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
    <button onclick="window.print()" style="padding:10px 28px; font-family:'Lato', Arial, sans-serif; font-size:13px; font-weight:900; background:#B98535; color:#111827; border:0; border-radius:4px; cursor:pointer; text-transform:uppercase; letter-spacing:1px;">
        🖨️ Imprimir Boleta
    </button>
</div>

<!-- Encabezado Principal -->
<div class="header">
    <div class="header-left">
        <div class="header-logo">
            @if($logoArquidiocesis)
                <img src="{{ $logoArquidiocesis }}" alt="Logo Arquidiócesis">
            @else
                <div class="logo-placeholder">Arquidiócesis</div>
            @endif
        </div>
    </div>

    <div class="header-center">
        <div class="parroquia">PARROQUIA DE "LA ASUNCIÓN DE MARÍA" PIPIOLTEPEC.</div>
        <div class="iglesia">IGLESIA DE PIPIOLTEPEC</div>
        <div class="reporte">REPORTE DE EVALUACIÓN</div>
        <div class="periodo">CATEQUESIS &nbsp;&nbsp; {{ $periodoTexto ?? '2025 - 2026' }}</div>
    </div>

    <div class="header-right">
        <div class="header-logo">
            @if($logoAsuncion)
                <img src="{{ $logoAsuncion }}" alt="Logo Parroquia La Asunción de María">
            @else
                <div class="logo-placeholder">Asunción de María</div>
            @endif
        </div>
    </div>
</div>

<!-- Seccion de Datos Generales -->
<div class="datos-header">DATOS DEL (DE LA) ALUMNO (A)</div>

<div class="datos-row">
    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $inscripcion->alumno->apellido_paterno ?? 'FLORES' }}</span>
        </div>
        <span class="label">PRIMER APELLIDO</span>
    </div>

    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $inscripcion->alumno->apellido_materno ?? 'URBINA' }}</span>
        </div>
        <span class="label">SEGUNDO APELLIDO</span>
    </div>

    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $inscripcion->alumno->nombre ?? 'JOSUE EMMANUEL' }}</span>
        </div>
        <span class="label">NOMBRE (S)</span>
    </div>
</div>

<!-- Informacion de Libro y Promedio -->
<div class="meta-row">
    <div class="meta-libro">
        <div class="line-container">
                <span class="printed-name">
                    {{ $asignacion?->nivel?->nivel ?? '' }} "{{ $inscripcion->grupo->nombre ?? '' }}"
                </span>
        </div>
    </div>

    <div class="meta-promedio">
        PROMEDIO FINAL
        <div class="prom-box formal-grade" style="font-size: 13pt !important;">
            {{ $promedioFinal ?? '' }}
        </div>
    </div>
</div>

@php
    $totalUnidades = $unidades->count();
    $romanos = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
@endphp

    <!-- Tabla de Calificaciones e Inasistencias -->
<table class="cal-table">
    <thead>
    <tr>
        <th class="bg-orange" rowspan="2" style="text-align: left; padding-left: 10px;">UNIDAD</th>
        <th class="bg-orange" colspan="{{ $totalUnidades }}">EVALUACIÓN</th>
        <th class="bg-orange" rowspan="2" style="width: 90px;">PROMEDIO<br>FINAL</th>
        <th class="bg-orange" rowspan="2" style="width: 140px; font-size: 6.5pt; padding: 2px;">FIRMA DE LA<br>MADRE PADRE DE<br>FAMILIA O<br>TUTOR(A)</th>
    </tr>

    <tr>
        @for($col = 0; $col < $totalUnidades; $col++)
            <th class="bg-light-orange" style="width: 48px;">{{ $romanos[$col] ?? ($col + 1) }}</th>
        @endfor
    </tr>
    </thead>

    <tbody>
    @for($i = 0; $i < $totalUnidades; $i++)
        @php
            $unidad = $unidades[$i] ?? null;
            $rowClass = ($i % 2 == 0) ? 'bg-dark-beige' : 'bg-light-beige';
            $promUnidad = ($unidad && isset($promediosUnidad[$unidad->id])) ? $promediosUnidad[$unidad->id] : null;
        @endphp

        <tr class="{{ $rowClass }}">
            <td class="unidad-cell">{{ $i + 1 }}</td>

            {{-- Solo mostrar la calificación final en la columna diagonal correspondiente --}}
            @for($col = 0; $col < $totalUnidades; $col++)
                <td class="formal-grade">
                    @if($col === $i && $promUnidad !== null)
                        {{ $promUnidad }}
                    @endif
                </td>
            @endfor

            <td class="formal-grade">
                @if($promUnidad !== null)
                    {{ $promUnidad }}
                @endif
            </td>

            <td></td>
        </tr>
    @endfor

    <tr class="bg-light-beige">
        <td class="asistencias-cell">INASISTENCIAS</td>

        @for($col = 0; $col < $totalUnidades; $col++)
            <td class="formal-grade"></td>
        @endfor

        <td class="total-inasistencias">TOTAL DE<br>INASISTENCIAS</td>
        <td></td>
    </tr>
    </tbody>
</table>

<!-- Bloque de Observaciones -->
<div class="observaciones">
    <h4>OBSERVACIONES</h4>
    <div class="obs-box"></div>
</div>

<!-- Bloque de Firmas -->
<div class="firmas">
    <div class="firmas-top">
        <div class="firma-block">
            <div class="printed-name" style="margin-bottom: 2px;">
                {{ $asignacion?->catequista?->name ?? 'DEYCI VELAZQUEZ G.' }}
            </div>
            <div class="firma-line"></div>
            <div class="firma-label">
                NOMBRE Y FIRMA DE LA CATEQUISTA
            </div>
        </div>

        <div class="firma-block">
            <div style="height: 18px;"></div>
            <div class="firma-line"></div>
            <div class="firma-label">
                NOMBRE Y FIRMA DE COORDINACIÓN DE CATEQUESIS
            </div>
        </div>
    </div>

    <div class="firmas-bottom">
        <div class="firma-block-center">
            <div class="firma-name">PBRO. GUSTAVO IGNACIO DE LA CRUZ</div>
            <div class="firma-line"></div>
            <div class="firma-label">
                NOMBRE Y FIRMA DEL PÁRROCO
            </div>
        </div>
    </div>
</div>
</body>
</html>
