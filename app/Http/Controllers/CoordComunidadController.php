<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CoordComunidadController extends Controller
{
    public function index()
    {
        return view('coord_comunidad.dashboard');
    }
}