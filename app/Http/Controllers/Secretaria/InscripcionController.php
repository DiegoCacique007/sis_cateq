<?php
namespace App\Http\Controllers\Secretaria;
use App\Http\Controllers\Controller;
use App\Models\Secretaria\Inscripcion;
use Illuminate\Http\Request;

class InscripcionController extends Controller {
    public function index() { return response()->json(Inscripcion::all()); }
    public function store(Request $request) { Inscripcion::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Inscripcion::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Inscripcion::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
