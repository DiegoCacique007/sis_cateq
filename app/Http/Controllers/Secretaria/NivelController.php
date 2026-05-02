<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Nivel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NivelController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Nivel::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('nivel', 'LIKE', "%{$search}%");
            })
            ->orderBy('nivel')
            ->paginate($perPage);

        return view('secretaria.niveles.index', compact('registros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nivel' => [
                'required',
                'string',
                'max:255',
                Rule::unique('niveles', 'nivel')->whereNull('deleted_at'),
            ],
        ], [
            'nivel.required' => 'El nombre del nivel es obligatorio.',
            'nivel.string' => 'El nombre del nivel debe ser texto.',
            'nivel.max' => 'El nombre del nivel no puede tener más de 255 caracteres.',
            'nivel.unique' => 'Este nivel ya está registrado.',
        ]);

        Nivel::create($validated);

        return redirect()
            ->route('secretaria.niveles.index')
            ->with('success', 'Nivel registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $nivel = Nivel::findOrFail($id);

        $validated = $request->validate([
            'nivel' => [
                'required',
                'string',
                'max:255',
                Rule::unique('niveles', 'nivel')
                    ->whereNull('deleted_at')
                    ->ignore($nivel->id),
            ],
        ], [
            'nivel.required' => 'El nombre del nivel es obligatorio.',
            'nivel.string' => 'El nombre del nivel debe ser texto.',
            'nivel.max' => 'El nombre del nivel no puede tener más de 255 caracteres.',
            'nivel.unique' => 'Este nivel ya está registrado.',
        ]);

        $nivel->update($validated);

        return redirect()
            ->route('secretaria.niveles.index')
            ->with('success', 'Nivel actualizado correctamente.');
    }

    public function destroy($id)
    {
        $nivel = Nivel::findOrFail($id);
        $nivel->delete();

        return redirect()
            ->route('secretaria.niveles.index')
            ->with('success', 'Nivel eliminado correctamente.');
    }
}
