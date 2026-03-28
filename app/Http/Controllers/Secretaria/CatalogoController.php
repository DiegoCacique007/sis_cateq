<?php
namespace App\Http\Controllers\Secretaria;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller {
    public function getSelects() {
        return response()->json([
            'comunidades' => DB::table('comunidades')->whereNull('deleted_at')->select('id', 'comunidad as text')->get(),
            'niveles' => DB::table('niveles')->whereNull('deleted_at')->select('id', 'nivel as text')->get(),
            'grupos' => DB::table('grupos')->whereNull('deleted_at')->select('id', 'nombre as text')->get(),
            'periodos' => DB::table('periodos')->whereNull('deleted_at')->select('id', DB::raw("CONCAT(fecha_inicio, ' al ', fecha_fin) as text"))->get(),
            'alumnos' => DB::table('alumnos')->whereNull('deleted_at')->select('id', DB::raw("CONCAT(nombre, ' ', apellido_paterno) as text"))->get(),
            'users' => DB::table('users')->where('role', 'catequista')->select('id', 'name as text')->get(),
        ]);
    }
}
