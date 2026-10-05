<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Nivel;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleComunidades;
use App\Queries\AccessibleInscripciones;
use Illuminate\Http\Request;

class AlumnoComunidadController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'comunidad_id' => ['nullable', 'integer', 'min:1'],
            'sacramento' => ['nullable', 'string', 'max:255'],
            'numero_nivel' => ['nullable', 'integer', 'min:1'],
        ]);
        $context = app(CatequesisHttp::class)->context($request, C::ViewGroupStudents);
        $assignments = app(AccessibleAsignaciones::class)->for($context);
        $inscriptions = app(AccessibleInscripciones::class)->forStudentReport($context);
        if ($request->filled('sacramento') || $request->filled('numero_nivel')) {
            $matching = (clone $assignments)->whereHas('nivel', function ($q) use ($request) {
                $q->when($request->filled('sacramento'), fn ($q) => $q->where('sacramento', $request->input('sacramento')))
                    ->when($request->filled('numero_nivel'), fn ($q) => $q->where('numero', $request->integer('numero_nivel')));
            });
            $inscriptions->whereIn('grupo_id', $matching->select('grupo_id'));
        }
        $registros = app(AccessibleAlumnos::class)->for($context)
            ->whereIn('alumnos.id', (clone $inscriptions)->select('alumno_id'))
            ->when($request->filled('comunidad_id'), fn ($q) => $q->where('comunidad_id', $request->integer('comunidad_id')))
            ->with(['comunidad', 'inscripciones' => fn ($q) => $q->whereIn('inscripciones.id', (clone $inscriptions)->select('inscripciones.id'))])
            ->paginate(15)->withQueryString();
        $groupIds = $registros->getCollection()->flatMap(fn ($a) => $a->inscripciones->pluck('grupo_id'));
        $byGroup = (clone $assignments)->whereIn('grupo_id', $groupIds)->with('nivel')->get()->groupBy('grupo_id');
        foreach ($registros as $alumno) {
            foreach ($alumno->inscripciones as $inscripcion) {
                $candidates = $byGroup->get($inscripcion->grupo_id, collect());
                $inscripcion->setRelation('asignaGrupo', $candidates->count() === 1 ? $candidates->sole() : null);
            }
        }
        $comunidades = app(AccessibleComunidades::class)->for($context)->orderBy('comunidad')->get();
        $nivelesDisponibles = Nivel::whereIn('id', (clone $assignments)->select('nivel_id'))->select('numero')->distinct()->orderBy('numero')->get();

        return view('secretaria.alumnos_comunidades.index', compact('registros', 'comunidades', 'nivelesDisponibles'));
    }
}
