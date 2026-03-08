<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CoordGeneralController extends Controller
{
    public function index()
    {
        return view('coord_general.dashboard');
    }
}