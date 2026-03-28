<?php
namespace App\Http\Controllers\CoordGeneral;
use App\Http\Controllers\Controller;
use App\Models\CoordGeneral\Unidad;
use Illuminate\Http\Request;

class UnidadController extends Controller {
    public function index() { return response()->json(Unidad::all()); }
    public function store(Request $request) { Unidad::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Unidad::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Unidad::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
