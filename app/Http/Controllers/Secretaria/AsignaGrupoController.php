<?php
namespace App\Http\Controllers\Secretaria;
use App\Http\Controllers\Controller;
use App\Models\Secretaria\AsignaGrupo;
use Illuminate\Http\Request;

class AsignaGrupoController extends Controller {
    public function index() { return response()->json(AsignaGrupo::all()); }
    public function store(Request $request) { AsignaGrupo::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { AsignaGrupo::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { AsignaGrupo::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
