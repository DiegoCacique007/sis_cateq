<?php
namespace App\Http\Controllers\CoordGeneral;
use App\Http\Controllers\Controller;
use App\Models\CoordGeneral\Periodo;
use Illuminate\Http\Request;

class PeriodoController extends Controller {
    public function index() { return response()->json(Periodo::all()); }
    public function store(Request $request) { Periodo::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Periodo::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Periodo::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
