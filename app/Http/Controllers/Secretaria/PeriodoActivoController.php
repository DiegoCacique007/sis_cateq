<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodoActivoController extends Controller
{
    public function cambiar(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id'
        ], [
            'periodo_id.required' => 'Selecciona un periodo.',
            'periodo_id.exists' => 'El periodo seleccionado no existe.',
        ]);

        $periodo = DB::table('periodos')->where('id', $request->periodo_id)->first();

        $nombrePeriodo = $periodo->fecha_inicio . ' al ' . $periodo->fecha_fin;

        session([
            'periodo_activo_id' => $periodo->id,
            'periodo_activo_nombre' => $nombrePeriodo
        ]);

        return back()->with('success', "Periodo cambiado correctamente a: {$nombrePeriodo}");
    }
}
