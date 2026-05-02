<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Alumno::query()
            ->leftJoin('comunidades', 'alumnos.comunidad_id', '=', 'comunidades.id')
            ->select(
                'alumnos.*',
                'comunidades.comunidad as comunidad_nombre'
            )
            ->where(function ($query) {
                $query->whereHas('inscripciones', function ($q) {
                    $q->where('periodo_id', session('periodo_activo_id'));
                })->orWhereDoesntHave('inscripciones');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('alumnos.nombre', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%")
                        ->orWhere('comunidades.comunidad', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('alumnos.nombre')
            ->orderBy('alumnos.apellido_paterno')
            ->paginate($perPage);

        $comunidades = DB::table('comunidades')
            ->whereNull('deleted_at')
            ->select('id', 'comunidad')
            ->orderBy('comunidad')
            ->get();

        return view('secretaria.alumnos.index', compact('registros', 'comunidades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'comunidad_id' => [
                'required',
                'exists:comunidades,id',
                Rule::unique('alumnos', 'comunidad_id')
                    ->where('nombre', $request->nombre)
                    ->where('apellido_paterno', $request->apellido_paterno)
                    ->where('apellido_materno', $request->apellido_materno)
                    ->whereNull('deleted_at'),
            ],
            'estado' => ['required', 'in:0,1'],
            'fecha_nacimiento' => ['nullable', 'date'],
        ], [
            'nombre.required' => 'El nombre del alumno es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'comunidad_id.required' => 'Selecciona una comunidad.',
            'comunidad_id.exists' => 'La comunidad seleccionada no existe.',
            'comunidad_id.unique' => 'Este alumno ya está registrado en esta comunidad.',
            'estado.required' => 'Selecciona el estado del alumno.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
        ]);

        Alumno::create($validated);

        return redirect()
            ->route('secretaria.alumnos.index')
            ->with('success', 'Alumno registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $alumno = Alumno::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'comunidad_id' => [
                'required',
                'exists:comunidades,id',
                Rule::unique('alumnos', 'comunidad_id')
                    ->where('nombre', $request->nombre)
                    ->where('apellido_paterno', $request->apellido_paterno)
                    ->where('apellido_materno', $request->apellido_materno)
                    ->whereNull('deleted_at')
                    ->ignore($alumno->id),
            ],
            'estado' => ['required', 'in:0,1'],
            'fecha_nacimiento' => ['nullable', 'date'],
        ], [
            'nombre.required' => 'El nombre del alumno es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'comunidad_id.required' => 'Selecciona una comunidad.',
            'comunidad_id.exists' => 'La comunidad seleccionada no existe.',
            'comunidad_id.unique' => 'Este alumno ya está registrado en esta comunidad.',
            'estado.required' => 'Selecciona el estado del alumno.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
        ]);

        $alumno->update($validated);

        return redirect()
            ->route('secretaria.alumnos.index')
            ->with('success', 'Alumno actualizado correctamente.');
    }

    public function destroy($id)
    {
        $alumno = Alumno::findOrFail($id);
        $alumno->delete();

        return redirect()
            ->route('secretaria.alumnos.index')
            ->with('success', 'Alumno eliminado correctamente.');
    }
}
