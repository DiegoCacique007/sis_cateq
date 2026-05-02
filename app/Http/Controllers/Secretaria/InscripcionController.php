<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Inscripcion::query()
            ->leftJoin('alumnos', 'inscripciones.alumno_id', '=', 'alumnos.id')
            ->leftJoin('periodos', 'inscripciones.periodo_id', '=', 'periodos.id')
            ->leftJoin('grupos', 'inscripciones.grupo_id', '=', 'grupos.id')
            ->select(
                'inscripciones.*',
                DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno_nombre"),
                'alumnos.fecha_nacimiento',
                'grupos.nombre as grupo_nombre'
            )
            ->where('inscripciones.periodo_id', session('periodo_activo_id'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('alumnos.nombre', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%")
                        ->orWhere('grupos.nombre', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('alumnos.nombre')
            ->orderBy('alumnos.apellido_paterno')
            ->paginate($perPage);

        $alumnos = DB::table('alumnos')
            ->whereNull('deleted_at')
            ->select(
                'id',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                DB::raw("TRIM(CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno, ''))) as text")
            )
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->get();



        $grupos = DB::table('grupos')
            ->whereNull('deleted_at')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return view('secretaria.inscripciones.index', compact(
            'registros',
            'alumnos',
            'grupos'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alumno_id' => ['required', 'exists:alumnos,id'],
            'grupo_id' => [
                'required',
                'exists:grupos,id',
                Rule::unique('inscripciones', 'grupo_id')
                    ->where('alumno_id', $request->alumno_id)
                    ->where('periodo_id', session('periodo_activo_id'))
                    ->whereNull('deleted_at'),
            ],
        ], [
            'alumno_id.required' => 'Selecciona un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'grupo_id.unique' => 'Este alumno ya está inscrito en este grupo y periodo.',
        ]);

        $validated['periodo_id'] = session('periodo_activo_id');

        Inscripcion::create($validated);

        return redirect()
            ->route('secretaria.inscripciones.index')
            ->with('success', 'Inscripción registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);

        $validated = $request->validate([
            'alumno_id' => ['required', 'exists:alumnos,id'],
            'grupo_id' => [
                'required',
                'exists:grupos,id',
                Rule::unique('inscripciones', 'grupo_id')
                    ->where('alumno_id', $request->alumno_id)
                    ->where('periodo_id', session('periodo_activo_id'))
                    ->whereNull('deleted_at')
                    ->ignore($inscripcion->id),
            ],
        ], [
            'alumno_id.required' => 'Selecciona un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'grupo_id.unique' => 'Este alumno ya está inscrito en este grupo y periodo.',
        ]);

        $validated['periodo_id'] = session('periodo_activo_id');

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
