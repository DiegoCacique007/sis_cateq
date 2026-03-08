<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CatequistaController extends Controller
{
    public function index()
    {
        return view('catequista.dashboard');
    }
}