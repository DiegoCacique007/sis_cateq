<?php

namespace App\Http\Controllers\Catequista;

use App\Enums\CatequesisCapability as C;
use App\Http\Controllers\Controller;
use App\Http\Support\CatequesisHttp;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleEvaluaciones;
use Illuminate\Http\Request;

class CatequistaController extends Controller
{
    public function index(Request $request)
    {
        $context = app(CatequesisHttp::class)->context($request, C::ViewGroupStudents);
        $totalGruposAsignados = app(AccessibleAsignaciones::class)->for($context)->count();
        $totalAlumnosGrupo = app(AccessibleAlumnos::class)->for($context)->count();
        $totalNivelesAsignados = app(AccessibleAsignaciones::class)->for($context)->distinct()->count('nivel_id');
        $totalEvaluacionesRegistradas = app(AccessibleEvaluaciones::class)->for($context)->count();

        return view('catequista.dashboard', compact('totalGruposAsignados', 'totalAlumnosGrupo', 'totalNivelesAsignados', 'totalEvaluacionesRegistradas'));
    }
}
