<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Tutor;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleTutores;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewTutors);
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }
        app(CatequesisHttp::class)->enforce(app(\App\Services\Authorization\CatequesisAccess::class)->can($context, C::ViewGroupStudents));
        $registros = app(AccessibleTutores::class)->for($context)
            ->leftJoin('alumnos', 'tutores.alumno_id', '=', 'alumnos.id')
            ->select('tutores.*')->with('alumno')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('tutores.nombre', 'LIKE', "%{$search}%")
                ->orWhere('tutores.ap', 'LIKE', "%{$search}%")
                ->orWhere('tutores.am', 'LIKE', "%{$search}%")
                ->orWhere('tutores.telefono', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.nombre', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.apellido_paterno', 'LIKE', "%{$search}%")
                ->orWhere('alumnos.apellido_materno', 'LIKE', "%{$search}%")))
            ->orderBy('tutores.nombre')->orderBy('tutores.ap')->paginate($perPage);
        $registros->getCollection()->each(fn ($t) => $t->setAttribute('alumno_nombre', $t->alumno?->nombre_completo));
        $alumnos = app(AccessibleAlumnos::class)->for($context)->orderBy('nombre')->orderBy('apellido_paterno')->get();
        $alumnos->each(fn ($a) => $a->setAttribute('text', $a->nombre_completo));

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
