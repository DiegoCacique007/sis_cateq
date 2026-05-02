<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Tutor::query()
            ->leftJoin('alumnos', 'tutores.alumno_id', '=', 'alumnos.id')
            ->select(
                'tutores.*',
                DB::raw("TRIM(CONCAT(alumnos.nombre, ' ', alumnos.apellido_paterno, ' ', COALESCE(alumnos.apellido_materno, ''))) as alumno_nombre")
            )
            ->where(function ($query) {
                $query->whereHas('alumno.inscripciones', function ($q) {
                    $q->where('periodo_id', session('periodo_activo_id'));
                })->orWhereDoesntHave('alumno.inscripciones');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tutores.nombre', 'LIKE', "%{$search}%")
                        ->orWhere('tutores.ap', 'LIKE', "%{$search}%")
                        ->orWhere('tutores.am', 'LIKE', "%{$search}%")
                        ->orWhere('tutores.telefono', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.nombre', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                        ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('tutores.nombre')
            ->orderBy('tutores.ap')
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

        return view('secretaria.tutores.index', compact('registros', 'alumnos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'ap' => ['required', 'string', 'max:255'],
            'am' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'alumno_id' => [
                'required',
                'exists:alumnos,id',
                Rule::unique('tutores', 'alumno_id')
                    ->where('nombre', $request->nombre)
                    ->where('ap', $request->ap)
                    ->where('am', $request->am)
                    ->whereNull('deleted_at'),
            ],
        ], [
            'nombre.required' => 'El nombre del tutor es obligatorio.',
            'nombre.string' => 'El nombre del tutor debe ser texto.',
            'nombre.max' => 'El nombre del tutor no puede tener más de 255 caracteres.',

            'ap.required' => 'El apellido paterno del tutor es obligatorio.',
            'ap.string' => 'El apellido paterno debe ser texto.',
            'ap.max' => 'El apellido paterno no puede tener más de 255 caracteres.',

            'am.string' => 'El apellido materno debe ser texto.',
            'am.max' => 'El apellido materno no puede tener más de 255 caracteres.',

            'telefono.string' => 'El teléfono debe ser texto.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',

            'alumno_id.required' => 'Selecciona un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'alumno_id.unique' => 'Este tutor ya está registrado para este alumno.',
        ]);

        Tutor::create($validated);

        return redirect()
            ->route('secretaria.tutores.index')
            ->with('success', 'Tutor registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $tutor = Tutor::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'ap' => ['required', 'string', 'max:255'],
            'am' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'alumno_id' => [
                'required',
                'exists:alumnos,id',
                Rule::unique('tutores', 'alumno_id')
                    ->where('nombre', $request->nombre)
                    ->where('ap', $request->ap)
                    ->where('am', $request->am)
                    ->whereNull('deleted_at')
                    ->ignore($tutor->id),
            ],
        ], [
            'nombre.required' => 'El nombre del tutor es obligatorio.',
            'nombre.string' => 'El nombre del tutor debe ser texto.',
            'nombre.max' => 'El nombre del tutor no puede tener más de 255 caracteres.',

            'ap.required' => 'El apellido paterno del tutor es obligatorio.',
            'ap.string' => 'El apellido paterno debe ser texto.',
            'ap.max' => 'El apellido paterno no puede tener más de 255 caracteres.',

            'am.string' => 'El apellido materno debe ser texto.',
            'am.max' => 'El apellido materno no puede tener más de 255 caracteres.',

            'telefono.string' => 'El teléfono debe ser texto.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',

            'alumno_id.required' => 'Selecciona un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'alumno_id.unique' => 'Este tutor ya está registrado para este alumno.',
        ]);

        $tutor->update($validated);

        return redirect()
            ->route('secretaria.tutores.index')
            ->with('success', 'Tutor actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tutor = Tutor::findOrFail($id);
        $tutor->delete();

        return redirect()
            ->route('secretaria.tutores.index')
            ->with('success', 'Tutor eliminado correctamente.');
    }
}
