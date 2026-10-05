<?php

namespace App\Http\Controllers\Catequista;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Authorization\CatequesisAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MiGrupoController extends Controller
{
    public function index(Request $request)
    {
        return view('catequista.mi_grupo', $this->data($request, C::ViewGroupStudents));
    }

    public function exportarAsistenciaPdf(Request $request)
    {
        $data = $this->data($request, C::ViewAttendanceList);
        abort_unless($data['asignacion'], 409, 'Selecciona una asignación para generar la lista.');

        return Pdf::loadView('catequista.pdf.asistencia', $data)->setPaper('letter', 'landscape')
            ->download('lista_asistencia_catequesis.pdf');
    }

    private function data(Request $request, C $capability): array
    {
        $request->validate(['asignacion_id' => ['nullable', 'integer', 'min:1']]);
        $http = app(CatequesisHttp::class);
        $context = $http->context($request, $capability);
        $asignaciones = app(AccessibleAsignaciones::class)->for($context)
            ->with(['comunidad', 'grupo', 'nivel', 'periodo', 'catequista'])->get()
            ->map(fn ($a) => (object) [
                'asignacion_id' => $a->id, 'grupo_id' => $a->grupo_id, 'periodo_id' => $a->periodo_id,
                'comunidad' => $a->comunidad->comunidad, 'grupo' => $a->grupo->nombre,
                'nivel' => $a->nivel->nivel, 'catequista_nombre' => $a->catequista->name,
                'periodo' => $a->periodo->fecha_inicio->format('Y-m-d').' al '.$a->periodo->fecha_fin->format('Y-m-d'),
                'texto_asignacion' => $a->nivel->nivel.' - Grupo '.$a->grupo->nombre.' ('.$a->comunidad->comunidad.')',
            ]);
        $asignacionId = $request->integer('asignacion_id') ?: ($asignaciones->count() === 1 ? $asignaciones->sole()->asignacion_id : null);
        $asignacion = null;
        $alumnos = collect();
        if ($asignacionId) {
            $http->enforce(app(CatequesisAccess::class)->canUseAsignacion($context, $asignacionId));
            $asignacion = $asignaciones->where('asignacion_id', $asignacionId)->sole();
            $inscripciones = app(AccessibleInscripciones::class)->for($context)->where('grupo_id', $asignacion->grupo_id);
            $alumnos = app(AccessibleAlumnos::class)->for($context)->whereIn('alumnos.id', $inscripciones->select('alumno_id'))
                ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')->get()
                ->map(fn ($a) => (object) ['id' => $a->id, 'alumno' => $a->nombre_completo]);
        }

        return compact('asignaciones', 'asignacionId', 'asignacion', 'alumnos');
    }
}
