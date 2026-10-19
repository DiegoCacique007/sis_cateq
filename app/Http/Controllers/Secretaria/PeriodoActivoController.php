<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Secretaria\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PeriodoActivoController extends Controller
{
    public function cambiar(Request $request)
    {
        // Los periodos inactivos se pueden elegir (historial); los eliminados, no.
        $request->validate([
            'periodo_id' => ['required', Rule::exists('periodos', 'id')->whereNull('deleted_at')],
        ], [
            'periodo_id.required' => 'Selecciona un periodo.',
            'periodo_id.exists' => 'El periodo seleccionado no existe o fue eliminado.',
        ]);

        $periodo = DB::table('periodos')->where('id', $request->periodo_id)->whereNull('deleted_at')->first();

        $nombrePeriodo = Periodo::nombreLegible($periodo->fecha_inicio, $periodo->fecha_fin);

        session([
            'periodo_activo_id' => $periodo->id,
            'periodo_activo_nombre' => $nombrePeriodo
        ]);

        return back()->with('success', "Periodo cambiado correctamente a: {$nombrePeriodo}.");
    }
}
