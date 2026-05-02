<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Comunidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComunidadController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = Comunidad::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('comunidad', 'LIKE', "%{$search}%");
            })
            ->orderBy('comunidad')
            ->paginate($perPage);

        return view('secretaria.comunidades.index', compact('registros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'comunidad' => [
                'required',
                'string',
                'max:255',
                Rule::unique('comunidades', 'comunidad')->whereNull('deleted_at'),
            ],
        ], [
            'comunidad.required' => 'El nombre de la comunidad es obligatorio.',
            'comunidad.string' => 'El nombre de la comunidad debe ser texto.',
            'comunidad.max' => 'El nombre de la comunidad no puede tener más de 255 caracteres.',
            'comunidad.unique' => 'Esta comunidad ya está registrada.',
        ]);

        Comunidad::create($validated);

        return redirect()
            ->route('secretaria.comunidades.index')
            ->with('success', 'Comunidad registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $comunidad = Comunidad::findOrFail($id);

        $validated = $request->validate([
            'comunidad' => [
                'required',
                'string',
                'max:255',
                Rule::unique('comunidades', 'comunidad')
                    ->whereNull('deleted_at')
                    ->ignore($comunidad->id),
            ],
        ], [
            'comunidad.required' => 'El nombre de la comunidad es obligatorio.',
            'comunidad.string' => 'El nombre de la comunidad debe ser texto.',
            'comunidad.max' => 'El nombre de la comunidad no puede tener más de 255 caracteres.',
            'comunidad.unique' => 'Esta comunidad ya está registrada.',
        ]);

        $comunidad->update($validated);

        return redirect()
            ->route('secretaria.comunidades.index')
            ->with('success', 'Comunidad actualizada correctamente.');
    }

    public function destroy($id)
    {
        $comunidad = Comunidad::findOrFail($id);
        $comunidad->delete();

        return redirect()
            ->route('secretaria.comunidades.index')
            ->with('success', 'Comunidad eliminada correctamente.');
    }
}
