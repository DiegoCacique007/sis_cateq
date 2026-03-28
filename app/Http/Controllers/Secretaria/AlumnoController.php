<?php
namespace App\Http\Controllers\Secretaria;
use App\Http\Controllers\Controller;
use App\Models\Secretaria\Alumno;
use Illuminate\Http\Request;

class AlumnoController extends Controller {
    public function index() { return response()->json(Alumno::all()); }
    public function store(Request $request) { Alumno::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Alumno::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Alumno::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
