<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ParrocoController extends Controller
{
    public function index()
    {
        return view('parroco.dashboard');
    }
}