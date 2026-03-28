<?php
namespace App\Http\Controllers\CoordGeneral;
use App\Http\Controllers\Controller;
use App\Models\CoordGeneral\Grupo;
use Illuminate\Http\Request;

class GrupoController extends Controller {
    public function index() { return response()->json(Grupo::all()); }
    public function store(Request $request) { Grupo::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Grupo::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Grupo::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
