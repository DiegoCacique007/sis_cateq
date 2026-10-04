<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuariosPendientesController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        $pendientes = User::where('status', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('secretaria.usuarios_pendientes', compact('pendientes'));
    }

    public function aprobar(Request $request, User $user)
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $user->update([
            'role' => $data['role'],
            'status' => 'aprobado',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('status', 'Usuario aprobado correctamente.');
    }

    public function bloquear(Request $request, User $user)
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        $user->update([
            'status' => 'bloqueado',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('status', 'Usuario bloqueado correctamente.');
    }
}
