<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Secretaria\Alumno;
use App\Models\Secretaria\Comunidad;
use App\Models\Secretaria\Nivel;

class AlumnoComunidadController extends Controller
{
    public function index(Request $request)
    {
        $periodoActivoId = session('periodo_activo_id');

        $query = Alumno::with([
            'comunidad',
            'inscripciones' => function ($q) use ($periodoActivoId) {
                $q->where('periodo_id', $periodoActivoId)
                    ->where('estado', 1)
                    ->with('asignaGrupo.nivel');
            }
        ]);

        if ($request->filled('comunidad_id')) {
            $query->where('comunidad_id', $request->comunidad_id);
        }

        $query->whereHas('inscripciones', function ($q) use ($request, $periodoActivoId) {
            $q->where('periodo_id', $periodoActivoId)
                ->where('estado', 1);

            if ($request->filled('sacramento') || $request->filled('numero_nivel')) {
                $q->whereHas('asignaGrupo.nivel', function ($qNivel) use ($request) {
                    if ($request->filled('sacramento')) {
                        $qNivel->where('sacramento', $request->sacramento);
                    }

                    if ($request->filled('numero_nivel')) {
                        $qNivel->where('numero', $request->numero_nivel);
                    }
                });
            }
        });

        $registros = $query->paginate(15)->withQueryString();

        $comunidades = Comunidad::orderBy('comunidad')->get();

        $nivelesDisponibles = Nivel::select('numero')
            ->distinct()
            ->orderBy('numero')
            ->get();

        return view('secretaria.alumnos_comunidades.index', compact(
            'registros',
            'comunidades',
            'nivelesDisponibles'
        ));
    }
}
