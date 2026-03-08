<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsuariosPendientesController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'secretaria', 403);

        $pendientes = User::where('status', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('secretaria.usuarios_pendientes', compact('pendientes'));
    }

    public function aprobar(Request $request, User $user)
    {
        abort_unless(auth()->user()->role === 'secretaria', 403);

        // si usas el select para rol en la vista, descomenta estas líneas:
       $data = $request->validate([
  'role' => ['required', 'in:catequista,coord_comunidad,coord_general,parroco'],
]);

$user->update([
  'role' => $data['role'],         
  'status' => 'aprobado',
  'approved_at' => now(),
  'approved_by' => auth()->id(),
]);

        return back()->with('status', 'Usuario aprobado correctamente.');
    }


public function bloquear(Request $request, \App\Models\User $user)
{
    abort_unless(auth()->user()->role === 'secretaria', 403);

    $user->update([
        'status' => 'bloqueado',
        'approved_at' => null,
        'approved_by' => null,
    ]);

    return back()->with('status', 'Usuario bloqueado correctamente.');
}
    
}