<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boleta - {{ $grupo->nombre ?? 'Grupo' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: #1e293b; background: #fff; padding: 20px; }

        .boleta {
            max-width: 800px;
            margin: 0 auto 40px;
            border: 2px solid #1e3a8a;
            border-radius: 16px;
            overflow: hidden;
            page-break-after: always;
        }
        .boleta:last-child { page-break-after: auto; }

        .boleta-header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: #fff;
            padding: 24px 30px;
            text-align: center;
        }
        .boleta-header h1 { font-size: 1.3rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .boleta-header p { font-size: .85rem; opacity: .85; margin-top: 4px; }

        .boleta-info {
            padding: 20px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 12px 30px;
        }
        .boleta-info .info-item { font-size: .88rem; }
        .boleta-info .info-label { font-weight: 700; color: #1e3a8a; }

        .boleta-body { padding: 20px 30px; }

        .boleta-body table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .boleta-body th {
            background: #1e3a8a;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-weight: 700;
        }
        .boleta-body td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .boleta-body tr:last-child td { border-bottom: 0; }
        .boleta-body tr:nth-child(even) { background: #f8fafc; }

        .boleta-footer {
            padding: 16px 30px;
            background: #f0f9ff;
            text-align: center;
            font-size: .82rem;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }

        .no-print { margin-bottom: 20px; text-align: center; }
        .btn-print {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: .95rem;
        }
        .btn-print:hover { opacity: .9; }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .boleta { border-radius: 0; border: 1px solid #999; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir boleta{{ $alumnos->count() > 1 ? 's' : '' }}
        </button>
    </div>

    @foreach($alumnos as $alumno)
        @php
            $evals = $evaluaciones->get($alumno->inscripcion_id, collect());
        @endphp

        <div class="boleta">
            <div class="boleta-header">
                <h1>Boleta de Calificaciones</h1>
                <p>Parroquia La Asunción de María — Sistema de Catequesis</p>
            </div>

            <div class="boleta-info">
                <div class="info-item">
                    <span class="info-label">Alumno:</span>
                    {{ trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? '')) }}
                </div>
                <div class="info-item">
                    <span class="info-label">Comunidad:</span>
                    {{ $alumno->comunidad_nombre ?? '—' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Grupo:</span>
                    {{ $grupo->nombre ?? '—' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Periodo:</span>
                    {{ $periodo ? ($periodo->fecha_inicio . ' al ' . $periodo->fecha_fin) : '—' }}
                </div>
            </div>

            <div class="boleta-body">
                @if($evals->isEmpty())
                    <p style="text-align: center; color: #94a3b8; padding: 30px;">Sin evaluaciones registradas para este alumno.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Unidad</th>
                                <th>Rubro</th>
                                <th>Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evals as $eval)
                                <tr>
                                    <td>Unidad {{ $eval->unidad_numero }} — {{ $eval->unidad_nombre }}</td>
                                    <td>{{ $eval->rubro_nombre }}</td>
                                    <td><strong>{{ number_format($eval->calificacion, 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="boleta-footer">
                Documento generado el {{ now()->format('d/m/Y H:i') }} — SIS CATEQ
            </div>
        </div>
    @endforeach
</body>
</html>
