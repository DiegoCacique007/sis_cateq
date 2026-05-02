<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Rubro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RubroController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Rubro::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('valor', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate($perPage);

        return view('secretaria.rubros.index', compact('registros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rubros', 'nombre')->whereNull('deleted_at'),
            ],
            'valor' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'nombre.required' => 'El nombre del rubro es obligatorio.',
            'nombre.string' => 'El nombre del rubro debe ser texto.',
            'nombre.max' => 'El nombre del rubro no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Este rubro ya está registrado.',
            'valor.required' => 'El valor del rubro es obligatorio.',
            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.min' => 'El valor no puede ser menor a 0.',
            'valor.max' => 'El valor no puede ser mayor a 100.',
        ]);

        Rubro::create($validated);

        return redirect()
            ->route('secretaria.rubros.index')
            ->with('success', 'Rubro registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $rubro = Rubro::findOrFail($id);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rubros', 'nombre')
                    ->whereNull('deleted_at')
                    ->ignore($rubro->id),
            ],
            'valor' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'nombre.required' => 'El nombre del rubro es obligatorio.',
            'nombre.string' => 'El nombre del rubro debe ser texto.',
            'nombre.max' => 'El nombre del rubro no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Este rubro ya está registrado.',
            'valor.required' => 'El valor del rubro es obligatorio.',
            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.min' => 'El valor no puede ser menor a 0.',
            'valor.max' => 'El valor no puede ser mayor a 100.',
        ]);

        $rubro->update($validated);

        return redirect()
            ->route('secretaria.rubros.index')
            ->with('success', 'Rubro actualizado correctamente.');
    }

    public function destroy($id)
    {
        $rubro = Rubro::findOrFail($id);
        $rubro->delete();

        return redirect()
            ->route('secretaria.rubros.index')
            ->with('success', 'Rubro eliminado correctamente.');
    }
}
