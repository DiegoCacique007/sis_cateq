<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentoController extends Controller
{
    /**
     * Vista principal del módulo de emisión de documentos.
     */
    public function index(Request $request)
    {
        $periodoId = session('periodo_activo_id');

        $grupos = DB::table('grupos')
            ->whereNull('deleted_at')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $comunidades = DB::table('comunidades')
            ->whereNull('deleted_at')
            ->select('id', 'comunidad')
            ->orderBy('comunidad')
            ->get();

        return view('secretaria.documentos.index', compact('grupos', 'comunidades'));
    }

    /**
     * Generar boletas generales (sin restricciones de datos extra).
     */
    public function boletas(Request $request)
    {
        $validated = $request->validate([
            'grupo_id' => ['required', 'exists:grupos,id'],
            'alumno_id' => ['nullable', 'exists:alumnos,id'],
        ]);

        $periodoId = session('periodo_activo_id');
        $grupoId = $validated['grupo_id'];
        $alumnoId = $validated['alumno_id'] ?? null;

        $query = DB::table('inscripciones')
            ->join('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->leftJoin('comunidades', 'alumnos.comunidad_id', '=', 'comunidades.id')
            ->where('inscripciones.periodo_id', $periodoId)
            ->where('inscripciones.grupo_id', $grupoId)
            ->whereNull('inscripciones.deleted_at')
            ->whereNull('alumnos.deleted_at');

        if ($alumnoId) {
            $query->where('alumnos.id', $alumnoId);
        }

        $alumnos = $query->select(
                'alumnos.id',
                'alumnos.nombre',
                'alumnos.apellido_paterno',
                'alumnos.apellido_materno',
                'comunidades.comunidad as comunidad_nombre',
                'inscripciones.id as inscripcion_id'
            )
            ->orderBy('alumnos.nombre')
            ->orderBy('alumnos.apellido_paterno')
            ->get();

        // Obtener evaluaciones para cada alumno
        $inscripcionIds = $alumnos->pluck('inscripcion_id')->toArray();

        $evaluaciones = DB::table('evaluaciones')
            ->join('unidades', 'evaluaciones.unidad_id', '=', 'unidades.id')
            ->join('rubros', 'evaluaciones.rubro_id', '=', 'rubros.id')
            ->whereIn('evaluaciones.inscripcion_id', $inscripcionIds)
            ->whereNull('evaluaciones.deleted_at')
            ->select(
                'evaluaciones.inscripcion_id',
                'evaluaciones.calificacion',
                'unidades.nombre as unidad_nombre',
                'unidades.numero as unidad_numero',
                'rubros.nombre as rubro_nombre',
                'rubros.valor as rubro_valor'
            )
            ->orderBy('unidades.numero')
            ->orderBy('rubros.nombre')
            ->get()
            ->groupBy('inscripcion_id');

        $grupo = DB::table('grupos')->where('id', $grupoId)->first();
        $periodo = DB::table('periodos')->where('id', $periodoId)->first();

        $rubros = DB::table('rubros')
            ->whereNull('deleted_at')
            ->select('id', 'nombre', 'valor')
            ->orderBy('nombre')
            ->get();

        $unidades = DB::table('unidades')
            ->whereNull('deleted_at')
            ->select('id', 'numero', 'nombre')
            ->orderBy('numero')
            ->get();

        return view('secretaria.documentos.boletas', compact(
            'alumnos',
            'evaluaciones',
            'grupo',
            'periodo',
            'rubros',
            'unidades'
        ));
    }

    /**
     * Generar certificado de Primera Comunión.
     * Validación: Datos de bautizo deben estar completos.
     */
    public function certificadoPrimeraComunion(Request $request)
    {
        $validated = $request->validate([
            'alumno_id' => ['required', 'exists:alumnos,id'],
        ]);

        $alumno = DB::table('alumnos')
            ->leftJoin('comunidades', 'alumnos.comunidad_id', '=', 'comunidades.id')
            ->where('alumnos.id', $validated['alumno_id'])
            ->whereNull('alumnos.deleted_at')
            ->select(
                'alumnos.*',
                'comunidades.comunidad as comunidad_nombre'
            )
            ->first();

        if (!$alumno) {
            return redirect()->back()->with('error', 'El alumno no fue encontrado.');
        }

        // Validación: Datos de bautizo completos
        $datosBautizo = [
            $alumno->bautizo_lugar,
            $alumno->bautizo_fecha,
            $alumno->bautizo_libro,
            $alumno->bautizo_acta,
        ];

        $bautizoIncompleto = collect($datosBautizo)->contains(function ($valor) {
            return empty($valor);
        });

        if ($bautizoIncompleto) {
            return redirect()
                ->back()
                ->with('error', 'No se puede emitir el certificado. Faltan los datos de Bautizo del alumno: '
                    . trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? ''))
                    . '.');
        }

        return view('secretaria.documentos.certificado_primera_comunion', compact('alumno'));
    }

    /**
     * Generar certificado de Confirmación.
     * Validación: Datos de bautizo Y primera comunión deben estar completos.
     */
    public function certificadoConfirmacion(Request $request)
    {
        $validated = $request->validate([
            'alumno_id' => ['required', 'exists:alumnos,id'],
        ]);

        $alumno = DB::table('alumnos')
            ->leftJoin('comunidades', 'alumnos.comunidad_id', '=', 'comunidades.id')
            ->where('alumnos.id', $validated['alumno_id'])
            ->whereNull('alumnos.deleted_at')
            ->select(
                'alumnos.*',
                'comunidades.comunidad as comunidad_nombre'
            )
            ->first();

        if (!$alumno) {
            return redirect()->back()->with('error', 'El alumno no fue encontrado.');
        }

        $nombreCompleto = trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . ($alumno->apellido_materno ?? ''));

        // Validación: Datos de bautizo
        $datosBautizo = [
            $alumno->bautizo_lugar,
            $alumno->bautizo_fecha,
            $alumno->bautizo_libro,
            $alumno->bautizo_acta,
        ];

        $bautizoIncompleto = collect($datosBautizo)->contains(function ($valor) {
            return empty($valor);
        });

        if ($bautizoIncompleto) {
            return redirect()
                ->back()
                ->with('error', 'No se puede emitir el certificado de Confirmación. Faltan los datos de Bautizo del alumno: ' . $nombreCompleto . '.');
        }

        // Validación: Datos de Primera Comunión
        $datosPrimeraComunion = [
            $alumno->primera_comunion_lugar,
            $alumno->primera_comunion_fecha,
            $alumno->primera_comunion_libro,
            $alumno->primera_comunion_acta,
        ];

        $primeraComunionIncompleta = collect($datosPrimeraComunion)->contains(function ($valor) {
            return empty($valor);
        });

        if ($primeraComunionIncompleta) {
            return redirect()
                ->back()
                ->with('error', 'No se puede emitir el certificado de Confirmación. Faltan los datos de Primera Comunión del alumno: ' . $nombreCompleto . '.');
        }

        return view('secretaria.documentos.certificado_confirmacion', compact('alumno'));
    }

    /**
     * API: Buscar alumnos para los selects de certificados.
     */
    public function buscarAlumnos(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $grupoId = $request->input('grupo_id');
        $periodoId = session('periodo_activo_id');

        $query = DB::table('alumnos')
            ->whereNull('alumnos.deleted_at')
            ->where('alumnos.estado', 1);

        if ($grupoId && $periodoId) {
            $query->join('inscripciones', function ($join) use ($grupoId, $periodoId) {
                $join->on('inscripciones.alumno_id', '=', 'alumnos.id')
                    ->where('inscripciones.grupo_id', '=', $grupoId)
                    ->where('inscripciones.periodo_id', '=', $periodoId)
                    ->whereNull('inscripciones.deleted_at');
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('alumnos.nombre', 'LIKE', "%{$search}%")
                    ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                    ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%");
            });
        }

        $alumnos = $query->select(
                'alumnos.id',
                DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as nombre_completo")
            )
            ->orderBy('alumnos.nombre')
            ->limit(50)
            ->get();

        return response()->json($alumnos);
    }
}
