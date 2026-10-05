<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Alumno;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleComunidades;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewStudents);
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }
        app(CatequesisHttp::class)->enforce(app(\App\Services\Authorization\CatequesisAccess::class)->can($context, C::ViewGroupStudents));
        $registros = app(AccessibleAlumnos::class)->forIndex($context)
            ->leftJoin('comunidades', 'alumnos.comunidad_id', '=', 'comunidades.id')
            ->select('alumnos.*', 'comunidades.comunidad as comunidad_nombre')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('alumnos.nombre', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%")
                ->orWhere('comunidades.comunidad', 'LIKE', "%{$search}%")))
            ->orderBy('alumnos.nombre')->orderBy('alumnos.apellido_paterno')->paginate($perPage);
        $comunidades = app(AccessibleComunidades::class)->for($context)->orderBy('comunidad')->get();

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
            'fecha_nacimiento' => ['nullable', 'date'],
        ], [
            'nombre.required' => 'El nombre del alumno es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'comunidad_id.required' => 'Selecciona una comunidad.',
            'comunidad_id.exists' => 'La comunidad seleccionada no existe.',
            'comunidad_id.unique' => 'Este alumno ya está registrado en esta comunidad.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
        ]);

        if (! empty($validated['fecha_nacimiento'])) {
            $fechaNacimiento = Carbon::parse($validated['fecha_nacimiento']);
            $hoy = Carbon::now();

            if (($hoy->year - $fechaNacimiento->year) < 7) {
                return back()
                    ->withInput()
                    ->with('error', 'No se puede continuar: El alumno tiene menos de 7 años y no cumple los 7 en el año en curso.');
            }

            if ($fechaNacimiento->age >= 15) {
                return back()
                    ->withInput()
                    ->with('error', 'No se puede continuar: El alumno ya tiene 15 años cumplidos o más.');
            }
        }

        Alumno::create($validated);

        return redirect()
            ->route('secretaria.alumnos.index')
            ->with('success', 'Alumno registrado correctamente. Se puede continuar en cualquiera de los niveles.');
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
            'fecha_nacimiento' => ['nullable', 'date'],
        ], [
            'nombre.required' => 'El nombre del alumno es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'comunidad_id.required' => 'Selecciona una comunidad.',
            'comunidad_id.exists' => 'La comunidad seleccionada no existe.',
            'comunidad_id.unique' => 'Este alumno ya está registrado en esta comunidad.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
        ]);

        if (! empty($validated['fecha_nacimiento'])) {
            $fechaNacimiento = Carbon::parse($validated['fecha_nacimiento']);
            $hoy = Carbon::now();

            if (($hoy->year - $fechaNacimiento->year) < 7) {
                return back()
                    ->withInput()
                    ->with('error', 'No se puede continuar: El alumno tiene menos de 7 años y no cumple los 7 en el año en curso.');
            }

            if ($fechaNacimiento->age >= 15) {
                return back()
                    ->withInput()
                    ->with('error', 'No se puede continuar: El alumno ya tiene 15 años cumplidos o más.');
            }
        }

        $alumno->update($validated);

        return redirect()
            ->route('secretaria.alumnos.index')
            ->with('success', 'Alumno actualizado correctamente. Se puede continuar en cualquiera de los niveles.');
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
