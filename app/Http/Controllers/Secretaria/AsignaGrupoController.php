<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\AsignaGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AsignaGrupoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }

        $registros = AsignaGrupo::query()
            ->leftJoin('comunidades', 'asigna_grupo.comunidad_id', '=', 'comunidades.id')
            ->leftJoin('grupos', 'asigna_grupo.grupo_id', '=', 'grupos.id')
            ->leftJoin('niveles', 'asigna_grupo.nivel_id', '=', 'niveles.id')
            ->leftJoin('periodos', 'asigna_grupo.periodo_id', '=', 'periodos.id')
            ->leftJoin('users', 'asigna_grupo.catequista_id', '=', 'users.id')
            ->select(
                'asigna_grupo.*',
                'comunidades.comunidad as comunidad_nombre',
                'grupos.nombre as grupo_nombre',
                'niveles.nivel as nivel_nombre',
                DB::raw("CONCAT(periodos.fecha_inicio, ' al ', periodos.fecha_fin) as periodo_nombre"),
                'users.name as catequista_nombre'
            )
            ->where('asigna_grupo.periodo_id', session('periodo_activo_id'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('comunidades.comunidad', 'LIKE', "%{$search}%")
                        ->orWhere('grupos.nombre', 'LIKE', "%{$search}%")
                        ->orWhere('niveles.nivel', 'LIKE', "%{$search}%")
                        ->orWhere('periodos.fecha_inicio', 'LIKE', "%{$search}%")
                        ->orWhere('periodos.fecha_fin', 'LIKE', "%{$search}%")
                        ->orWhere('users.name', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('comunidades.comunidad')
            ->orderBy('grupos.nombre')
            ->paginate($perPage);

        $comunidades = DB::table('comunidades')
            ->whereNull('deleted_at')
            ->select('id', 'comunidad')
            ->orderBy('comunidad')
            ->get();

        $grupos = DB::table('grupos')
            ->whereNull('deleted_at')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $niveles = DB::table('niveles')
            ->whereNull('deleted_at')
            ->select('id', 'nivel')
            ->orderBy('nivel')
            ->get();

        $periodos = DB::table('periodos')
            ->whereNull('deleted_at')
            ->select(
                'id',
                'fecha_inicio',
                'fecha_fin',
                DB::raw("CONCAT(fecha_inicio, ' al ', fecha_fin) as text")
            )
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        $catequistas = DB::table('users')
            ->where('role', 'catequista')
            ->where('status', 'aprobado')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('secretaria.asigna_grupo.index', compact(
            'registros',
            'comunidades',
            'grupos',
            'niveles',
            'periodos',
            'catequistas'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'comunidad_id' => ['required', 'exists:comunidades,id'],
            'grupo_id' => ['required', 'exists:grupos,id'],
            'nivel_id' => ['required', 'exists:niveles,id'],
            'catequista_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'catequista')
                        ->where('status', 'aprobado');
                }),
                Rule::unique('asigna_grupo', 'catequista_id')
                    ->where('comunidad_id', $request->comunidad_id)
                    ->where('grupo_id', $request->grupo_id)
                    ->where('nivel_id', $request->nivel_id)
                    ->where('periodo_id', session('periodo_activo_id'))
                    ->whereNull('deleted_at'),
            ],
        ], [
            'comunidad_id.required' => 'Selecciona una comunidad.',
            'comunidad_id.exists' => 'La comunidad seleccionada no existe.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'nivel_id.required' => 'Selecciona un nivel.',
            'nivel_id.exists' => 'El nivel seleccionado no existe.',
            'catequista_id.required' => 'Selecciona un catequista.',
            'catequista_id.exists' => 'El catequista seleccionado no existe o no está aprobado.',
            'catequista_id.unique' => 'Esta asignación de grupo ya existe.',
        ]);

        $validated['periodo_id'] = session('periodo_activo_id');

        AsignaGrupo::create($validated);

        return redirect()
            ->route('secretaria.asigna_grupo.index')
            ->with('success', 'Asignación registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $asignaGrupo = AsignaGrupo::findOrFail($id);

        $validated = $request->validate([
            'comunidad_id' => ['required', 'exists:comunidades,id'],
            'grupo_id' => ['required', 'exists:grupos,id'],
            'nivel_id' => ['required', 'exists:niveles,id'],
            'catequista_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'catequista')
                        ->where('status', 'aprobado');
                }),
                Rule::unique('asigna_grupo', 'catequista_id')
                    ->where('comunidad_id', $request->comunidad_id)
                    ->where('grupo_id', $request->grupo_id)
                    ->where('nivel_id', $request->nivel_id)
                    ->where('periodo_id', session('periodo_activo_id'))
                    ->whereNull('deleted_at')
                    ->ignore($asignaGrupo->id),
            ],
        ], [
            'comunidad_id.required' => 'Selecciona una comunidad.',
            'comunidad_id.exists' => 'La comunidad seleccionada no existe.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'nivel_id.required' => 'Selecciona un nivel.',
            'nivel_id.exists' => 'El nivel seleccionado no existe.',
            'catequista_id.required' => 'Selecciona un catequista.',
            'catequista_id.exists' => 'El catequista seleccionado no existe o no está aprobado.',
            'catequista_id.unique' => 'Esta asignación de grupo ya existe.',
        ]);

        $validated['periodo_id'] = session('periodo_activo_id');

        $asignaGrupo->update($validated);

        return redirect()
            ->route('secretaria.asigna_grupo.index')
            ->with('success', 'Asignación actualizada correctamente.');
    }

    public function destroy($id)
    {
        $asignaGrupo = AsignaGrupo::findOrFail($id);
        $asignaGrupo->delete();

        return redirect()
            ->route('secretaria.asigna_grupo.index')
            ->with('success', 'Asignación eliminada correctamente.');
    }
}
