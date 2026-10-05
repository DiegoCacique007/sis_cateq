<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Inscripcion;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleGrupos;
use App\Queries\AccessibleInscripciones;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewInscriptions);
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }
        app(CatequesisHttp::class)->enforce(app(\App\Services\Authorization\CatequesisAccess::class)->can($context, C::ViewGroupStudents));
        $registros = app(AccessibleInscripciones::class)->forIndex($context)
            ->leftJoin('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->leftJoin('grupos', 'inscripciones.grupo_id', '=', 'grupos.id')
            ->select('inscripciones.*', 'grupos.nombre as grupo_nombre')->with('alumno')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('alumnos.nombre', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%")
                ->orWhere('grupos.nombre', 'LIKE', "%{$search}%")))
            ->orderBy('alumnos.nombre')->orderBy('alumnos.apellido_paterno')->paginate($perPage)->withQueryString();
        $registros->getCollection()->each(fn ($i) => $i->setAttribute('alumno_nombre', $i->alumno?->nombre_completo));
        $alumnos = app(AccessibleAlumnos::class)->for($context)->orderBy('nombre')->orderBy('apellido_paterno')->get();
        $alumnos->each(fn ($a) => $a->setAttribute('text', $a->nombre_completo));
        $grupos = app(AccessibleGrupos::class)->for($context)->orderBy('nombre')->get();

        return view('secretaria.inscripciones.index', compact('registros', 'alumnos', 'grupos'));
    }

    public function store(Request $request)
    {
        $periodoActivoId = session('periodo_activo_id');

        $validated = $request->validate([
            'alumno_id' => [
                'required',
                'exists:alumnos,id',
            ],

            'grupo_id' => [
                'required',
                'exists:grupos,id',
                Rule::unique('inscripciones', 'grupo_id')
                    ->where(function ($query) use ($request, $periodoActivoId) {
                        return $query
                            ->where('alumno_id', $request->alumno_id)
                            ->where('periodo_id', $periodoActivoId)
                            ->whereNull('deleted_at');
                    }),
            ],
        ], [
            'alumno_id.required' => 'Selecciona un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'grupo_id.unique' => 'Este alumno ya está inscrito en este grupo y periodo.',
        ]);

        $validated['periodo_id'] = $periodoActivoId;

        Inscripcion::create($validated);

        return redirect()
            ->route('secretaria.inscripciones.index')
            ->with('success', 'Inscripción registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $periodoActivoId = session('periodo_activo_id');

        $inscripcion = Inscripcion::findOrFail($id);

        $validated = $request->validate([
            'alumno_id' => [
                'required',
                'exists:alumnos,id',
            ],

            'grupo_id' => [
                'required',
                'exists:grupos,id',
                Rule::unique('inscripciones', 'grupo_id')
                    ->where(function ($query) use ($request, $periodoActivoId) {
                        return $query
                            ->where('alumno_id', $request->alumno_id)
                            ->where('periodo_id', $periodoActivoId)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($inscripcion->id),
            ],
        ], [
            'alumno_id.required' => 'Selecciona un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'grupo_id.unique' => 'Este alumno ya está inscrito en este grupo y periodo.',
        ]);

        $validated['periodo_id'] = $periodoActivoId;

        $inscripcion->update($validated);

        return redirect()
            ->route('secretaria.inscripciones.index')
            ->with('success', 'Inscripción actualizada correctamente.');
    }

    public function destroy($id)
    {
        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->delete();

        return redirect()
            ->route('secretaria.inscripciones.index')
            ->with('success', 'Inscripción eliminada correctamente.');
    }
}
