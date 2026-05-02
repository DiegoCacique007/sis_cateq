<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Grupo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Grupo::query()
            ->where('periodo_id', session('periodo_activo_id'))
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
        $grupo->delete();

        return redirect()
            ->route('secretaria.grupos.index')
            ->with('success', 'Grupo eliminado correctamente.');
    }
}
