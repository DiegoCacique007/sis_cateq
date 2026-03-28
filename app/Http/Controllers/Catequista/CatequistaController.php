<?php

namespace App\Http\Controllers\Catequista;

use App\Http\Controllers\Controller;

class CatequistaController extends Controller
{
    public function index()
    {
        return view('catequista.dashboard');
    }
}
