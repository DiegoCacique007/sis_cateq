<?php

namespace App\Http\Controllers\CoordComunidad;

use App\Http\Controllers\Controller;

class CoordComunidadController extends Controller
{
    public function index()
    {
        return view('coord_comunidad.dashboard');
    }
}
