<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Models\Secretaria\Grupo;
use App\Queries\AccessibleGrupos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewGroups);
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = app(AccessibleGrupos::class)->for($context)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('nombre', 'LIKE', "%{$search}%");
            })
            ->orderBy('nombre')
            ->paginate($perPage);

        return view('secretaria.grupos.index', compact('registros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grupos', 'nombre')->where(function ($query) {
                    return $query->where('periodo_id', session('periodo_activo_id'))
                        ->whereNull('deleted_at');
                }),
            ],
        ], [
            'nombre.required' => 'El nombre del grupo es obligatorio.',
            'nombre.string' => 'El nombre del grupo debe ser texto.',
            'nombre.max' => 'El nombre del grupo no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Este grupo ya está registrado en este periodo.',
        ]);

        $validated['periodo_id'] = session('periodo_activo_id');

        Grupo::create($validated);

        return redirect()
            ->route('secretaria.grupos.index')
            ->with('success', 'Grupo registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grupos', 'nombre')->where(function ($query) {
                    return $query->where('periodo_id', session('periodo_activo_id'))
                        ->whereNull('deleted_at');
                })->ignore($grupo->id),
            ],
        ], [
            'nombre.required' => 'El nombre del grupo es obligatorio.',
            'nombre.string' => 'El nombre del grupo debe ser texto.',
            'nombre.max' => 'El nombre del grupo no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Este grupo ya está registrado en este periodo.',
        ]);

        $grupo->update($validated);

        return redirect()
            ->route('secretaria.grupos.index')
            ->with('success', 'Grupo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $grupo = Grupo::findOrFail($id);

        // El modelo también lo impide; aquí se explica qué datos tiene y cómo proceder sin perderlos.
        if ($grupo->hasDependents()) {
            $modulos = array_values(array_intersect_key(['inscripciones' => 'Inscripciones', 'asignacionesGrupo' => 'Asignar grupos'], $grupo->dependentCounts()));

            return redirect()->route('secretaria.grupos.index')->with('error', 'No se puede eliminar el grupo '.$grupo->nombre
                .' porque tiene '.$grupo->dependentsSummary().'. Si solo quieres corregir el nombre, edítalo.'
                .' Para eliminarlo, primero cambia esos registros a otro grupo en '.implode(' y en ', $modulos).'.');
        }

        $grupo->delete();

        return redirect()
            ->route('secretaria.grupos.index')
            ->with('success', 'Grupo eliminado correctamente.');
    }
}
