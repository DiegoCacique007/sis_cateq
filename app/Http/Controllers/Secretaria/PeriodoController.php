<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Periodo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeriodoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Periodo::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('fecha_inicio', 'LIKE', "%{$search}%")
                        ->orWhere('fecha_fin', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CASE WHEN estado = 1 THEN 'Activo' ELSE 'Inactivo' END LIKE ?", ["%{$search}%"]);
                });
            })
            ->orderBy('fecha_inicio', 'desc')
            ->paginate($perPage);

        return view('secretaria.periodos.index', compact('registros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => [
                'required',
                'date',
                Rule::unique('periodos', 'fecha_inicio')
                    ->where('fecha_fin', $request->fecha_fin)
                    ->whereNull('deleted_at'),
            ],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', 'in:0,1'],
        ], [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.unique' => 'Este periodo ya está registrado.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser menor que la fecha de inicio.',
            'estado.required' => 'Selecciona el estado del periodo.',
            'estado.in' => 'El estado solo puede ser activo o inactivo.',
        ]);

        Periodo::create($validated);

        return redirect()
            ->route('secretaria.periodos.index')
            ->with('success', 'Periodo registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $periodo = Periodo::findOrFail($id);

        $validated = $request->validate([
            'fecha_inicio' => [
                'required',
                'date',
                Rule::unique('periodos', 'fecha_inicio')
                    ->where('fecha_fin', $request->fecha_fin)
                    ->whereNull('deleted_at')
                    ->ignore($periodo->id),
            ],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', 'in:0,1'],
        ], [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.unique' => 'Este periodo ya está registrado.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser menor que la fecha de inicio.',
            'estado.required' => 'Selecciona el estado del periodo.',
            'estado.in' => 'El estado solo puede ser activo o inactivo.',
        ]);

        $periodo->update($validated);

        return redirect()
            ->route('secretaria.periodos.index')
            ->with('success', 'Periodo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $periodo = Periodo::findOrFail($id);
        $periodo->delete();

        return redirect()
            ->route('secretaria.periodos.index')
            ->with('success', 'Periodo eliminado correctamente.');
    }
}
