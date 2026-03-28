<?php
namespace App\Http\Controllers\Secretaria;
use App\Http\Controllers\Controller;
use App\Models\Secretaria\Tutor;
use Illuminate\Http\Request;

class TutorController extends Controller {
    public function index() { return response()->json(Tutor::all()); }
    public function store(Request $request) { Tutor::create($request->all()); return response()->json(['success'=>true]); }
    public function update(Request $request, $id) { Tutor::findOrFail($id)->update($request->all()); return response()->json(['success'=>true]); }
    public function destroy($id) { Tutor::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
