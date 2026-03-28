<?php
namespace App\Http\Controllers\CoordGeneral;
use App\Http\Controllers\Controller;
use App\Models\CoordGeneral\Nivel;
use Illuminate\Http\Request;

class NivelController extends Controller {
    public function index() { return response()->json(Nivel::all()); }
    public function store(Request $request) { Nivel::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Nivel::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Nivel::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
