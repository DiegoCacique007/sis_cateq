<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lista de Asistencia Catequesis</title>

    <style>
        @page {
            size: letter landscape;
            margin: 6mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7.4pt;
            color: #111827;
            background: #ffffff;
            line-height: 1.1;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .printed-name {
            font-size: 7.2pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .header-logo,
        .header-center {
            display: table-cell;
            vertical-align: middle;
        }

        .header-logo {
            width: 74px;
            text-align: center;
        }

        .logo-placeholder {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            border: 1px solid #6b7280;
            background: #f3f4f6;
            display: inline-block;
            text-align: center;
            line-height: 62px;
            font-size: 6.5px;
            color: #6b7280;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .header-center {
            text-align: center;
            padding: 0 10px;
        }

        .parroquia {
            font-family: DejaVu Serif, serif;
            font-size: 10.7pt;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .iglesia {
            font-family: DejaVu Serif, serif;
            font-size: 8.8pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }

        .reporte {
            font-size: 9.8pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .periodo {
            font-size: 7.5pt;
            font-weight: bold;
            color: #4b5563;
            letter-spacing: 0.2px;
        }

        .datos-header {
            background-color: #B98535;
            color: #111827;
            text-align: center;
            font-weight: bold;
            font-size: 7pt;
            padding: 3px 0;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            border: 1px solid #8f6423;
        }

        .datos-row {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-top: 5px;
            margin-bottom: 7px;
        }

        .datos-cell {
            display: table-cell;
            text-align: center;
            padding: 0 4px;
        }

        .line-container {
            border-bottom: 1px solid #111827;
            min-height: 17px;
            padding-bottom: 2px;
            margin-bottom: 2px;
            text-align: center;
        }

        .label {
            font-size: 6pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .asistencia-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 6px;
            border: 1.3px solid #8f6423;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .asistencia-table th {
            border: 1px solid #8f6423;
            text-align: center;
            vertical-align: middle;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-weight: bold;
            font-size: 6pt;
            padding: 3px 1px;
            letter-spacing: 0.1px;
            text-transform: uppercase;
        }

        .asistencia-table td {
            border: 1px solid #9f7736;
            text-align: center;
            vertical-align: middle;
            height: 20px;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 6.3pt;
            font-weight: normal;
            color: #111827;
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

        .col-num {
            width: 28px;
            font-weight: normal;
            font-size: 6.2pt;
        }

        .col-alumno {
            width: 270px;
            text-align: left !important;
            padding-left: 6px !important;
        }

        .alumno-name {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 6.3pt;
            font-weight: normal;
            text-transform: uppercase;
            letter-spacing: 0;
            line-height: 1.05;
        }

        .col-asistencia {
            width: auto;
        }

        .mini-line {
            display: block;
            width: 82%;
            margin: 0 auto;
            border-bottom: 0.6px solid rgba(17, 24, 39, 0.35);
            height: 13px;
        }

        @media print {
            body {
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: letter landscape;
                margin: 6mm;
            }
        }
    </style>
</head>

<body>

<div class="header">
    <div class="header-logo">
        <div class="logo-placeholder">ESCUDO</div>
    </div>

    <div class="header-center">
        <div class="parroquia">PARROQUIA DE "LA ASUNCIÓN DE MARÍA" PIPIOLTEPEC.</div>
        <div class="iglesia">IGLESIA DE PIPIOLTEPEC</div>
        <div class="reporte">LISTA DE ASISTENCIA CATEQUESIS</div>
        <div class="periodo">CATEQUESIS &nbsp;&nbsp; {{ $asignacion->periodo ?? '—' }}</div>
    </div>

    <div class="header-logo">
        <div class="logo-placeholder">VIRGEN</div>
    </div>
</div>

<div class="datos-header">DATOS GENERALES DEL GRUPO</div>

<div class="datos-row">
    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $asignacion->comunidad ?? '—' }}</span>
        </div>
        <span class="label">Comunidad</span>
    </div>

    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $asignacion->grupo ?? '—' }}</span>
        </div>
        <span class="label">Grupo</span>
    </div>

    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $asignacion->nivel ?? '—' }}</span>
        </div>
        <span class="label">Nivel</span>
    </div>

    <div class="datos-cell">
        <div class="line-container">
            <span class="printed-name">{{ $asignacion->catequista_nombre ?? '—' }}</span>
        </div>
        <span class="label">Catequista</span>
    </div>
</div>

<table class="asistencia-table">
    <thead>
    <tr>
        <th class="bg-orange col-num" rowspan="2">No.</th>
        <th class="bg-orange col-alumno" rowspan="2">Nombre del Alumno</th>
        <th class="bg-orange" colspan="15">Registro de Asistencia</th>
    </tr>
    <tr>
        @for($i = 1; $i <= 15; $i++)
            <th class="bg-light-orange col-asistencia">&nbsp;</th>
        @endfor
    </tr>
    </thead>

    <tbody>
    @forelse($alumnos as $index => $alumno)
        <tr class="{{ $index % 2 == 0 ? 'bg-dark-beige' : 'bg-light-beige' }}">
            <td class="col-num">{{ $index + 1 }}</td>

            <td class="col-alumno">
                <span class="alumno-name">{{ $alumno->alumno }}</span>
            </td>

            @for($i = 1; $i <= 15; $i++)
                <td class="col-asistencia">

                </td>
            @endfor
        </tr>
    @empty
        <tr class="bg-light-beige">
            <td colspan="17" style="height: 32px; font-weight: bold; text-align: center;">
                No hay alumnos inscritos en este grupo.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
