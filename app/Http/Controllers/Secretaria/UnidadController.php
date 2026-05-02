<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnidadController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Unidad::query()
            ->leftJoin('niveles', 'unidades.nivel_id', '=', 'niveles.id')
            ->select(
                'unidades.*',
                'niveles.nivel as nivel_nombre'
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('unidades.nombre', 'LIKE', "%{$search}%")
                        ->orWhere('unidades.numero', 'LIKE', "%{$search}%")
                        ->orWhere('niveles.nivel', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('niveles.nivel')
            ->orderBy('unidades.numero')
            ->paginate($perPage);

        $niveles = DB::table('niveles')
            ->whereNull('deleted_at')
            ->select('id', 'nivel')
            ->orderBy('nivel')
            ->get();

        return view('secretaria.unidades.index', compact('registros', 'niveles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nivel_id' => ['required', 'exists:niveles,id'],
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('unidades', 'numero')
                    ->where('nivel_id', $request->nivel_id)
                    ->whereNull('deleted_at'),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unidades', 'nombre')
                    ->where('nivel_id', $request->nivel_id)
                    ->whereNull('deleted_at'),
            ],
        ], [
            'nivel_id.required' => 'Selecciona un nivel.',
            'nivel_id.exists' => 'El nivel seleccionado no existe.',

            'numero.required' => 'El número de unidad es obligatorio.',
            'numero.integer' => 'El número de unidad debe ser un número entero.',
            'numero.min' => 'El número de unidad debe ser mayor a 0.',
            'numero.unique' => 'Ya existe una unidad con ese número en este nivel.',

            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'nombre.string' => 'El nombre de la unidad debe ser texto.',
            'nombre.max' => 'El nombre de la unidad no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Ya existe una unidad con ese nombre en este nivel.',
        ]);

        Unidad::create($validated);

        return redirect()
            ->route('secretaria.unidades.index')
            ->with('success', 'Unidad registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $unidad = Unidad::findOrFail($id);

        $validated = $request->validate([
            'nivel_id' => ['required', 'exists:niveles,id'],
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('unidades', 'numero')
                    ->where('nivel_id', $request->nivel_id)
                    ->whereNull('deleted_at')
                    ->ignore($unidad->id),
            ],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unidades', 'nombre')
                    ->where('nivel_id', $request->nivel_id)
                    ->whereNull('deleted_at')
                    ->ignore($unidad->id),
            ],
        ], [
            'nivel_id.required' => 'Selecciona un nivel.',
            'nivel_id.exists' => 'El nivel seleccionado no existe.',

            'numero.required' => 'El número de unidad es obligatorio.',
            'numero.integer' => 'El número de unidad debe ser un número entero.',
            'numero.min' => 'El número de unidad debe ser mayor a 0.',
            'numero.unique' => 'Ya existe una unidad con ese número en este nivel.',

            'nombre.required' => 'El nombre de la unidad es obligatorio.',
            'nombre.string' => 'El nombre de la unidad debe ser texto.',
            'nombre.max' => 'El nombre de la unidad no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Ya existe una unidad con ese nombre en este nivel.',
        ]);

        $unidad->update($validated);

        return redirect()
            ->route('secretaria.unidades.index')
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function destroy($id)
    {
        $unidad = Unidad::findOrFail($id);
        $unidad->delete();

        return redirect()
            ->route('secretaria.unidades.index')
            ->with('success', 'Unidad eliminada correctamente.');
    }
}
