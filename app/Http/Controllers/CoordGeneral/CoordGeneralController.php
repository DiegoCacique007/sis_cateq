<?php

namespace App\Http\Controllers\CoordGeneral;

use App\Http\Controllers\Controller;

class CoordGeneralController extends Controller
{
    public function index()
    {
        return view('coord_general.dashboard');
    }
}

