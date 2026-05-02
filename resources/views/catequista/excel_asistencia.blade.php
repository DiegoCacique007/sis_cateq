<?php
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"Asistencia_" . str_replace(' ', '_', $asignacion->grupo) . ".xls\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000000; padding: 5px; text-align: left; }
        .header { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .title { font-size: 16px; font-weight: bold; background-color: #d9edf7; text-align: center; }
        .info { font-weight: bold; }
        .blank-cell { min-width: 40px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="17" class="title">LISTA DE ASISTENCIA CATEQUESIS</td>
        </tr>
        <tr>
            <td colspan="2" class="info">Comunidad:</td>
            <td colspan="15">{{ $asignacion->comunidad }}</td>
        </tr>
        <tr>
            <td colspan="2" class="info">Grupo:</td>
            <td colspan="15">{{ $asignacion->grupo }}</td>
        </tr>
        <tr>
            <td colspan="2" class="info">Nivel:</td>
            <td colspan="15">{{ $asignacion->nivel }}</td>
        </tr>
        <tr>
            <td colspan="2" class="info">Periodo:</td>
            <td colspan="15">{{ $asignacion->periodo }}</td>
        </tr>
        <tr>
            <td colspan="17"></td>
        </tr>
        <tr>
            <th class="header" style="width: 40px;">No.</th>
            <th class="header" style="width: 300px;">Nombre del Alumno</th>
            <!-- Agregamos 15 columnas en blanco para que la catequista ponga las fechas/asistencias -->
            @for ($i = 1; $i <= 15; $i++)
                <th class="header blank-cell">Clase {{ $i }}</th>
            @endfor
        </tr>
        @foreach($alumnos as $index => $alumno)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $alumno->alumno }}</td>
                @for ($i = 1; $i <= 15; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
