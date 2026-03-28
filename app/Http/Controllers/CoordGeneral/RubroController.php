<?php
namespace App\Http\Controllers\CoordGeneral;
use App\Http\Controllers\Controller;
use App\Models\CoordGeneral\Rubro;
use Illuminate\Http\Request;

class RubroController extends Controller {
    public function index() { return response()->json(Rubro::all()); }
    public function store(Request $request) { Rubro::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Rubro::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Rubro::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
