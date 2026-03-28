<?php
namespace App\Http\Controllers\CoordComunidad;

use App\Http\Controllers\Controller;
use App\Models\CoordComunidad\Comunidad; // <--- AQUÍ ESTÁ LA MAGIA QUE FALTABA
use Illuminate\Http\Request;

class ComunidadController extends Controller {
    public function index() { return response()->json(Comunidad::all()); }
    public function store(Request $request) { Comunidad::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Comunidad::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Comunidad::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
