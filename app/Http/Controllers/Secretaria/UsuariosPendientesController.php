<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Secretaria\Comunidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuariosPendientesController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        $pendientes = User::where('status', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->get();
        $comunidades = Comunidad::orderBy('comunidad')->get(['id', 'comunidad']);

        return view('secretaria.usuarios_pendientes', compact('pendientes', 'comunidades'));
    }

    public function aprobar(Request $request, User $user)
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        // Solo solicitudes pendientes: aprobar no sirve para cambiar roles ni reactivar cuentas.
        if ($user->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden aprobar solicitudes pendientes.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'comunidad_id' => [
                Rule::requiredIf($request->input('role') === UserRole::CoordinadorComunidades->value),
                'nullable', 'integer', Rule::exists('comunidades', 'id')->whereNull('deleted_at'),
            ],
        ], [
            'comunidad_id.required' => 'Selecciona la comunidad que coordinará.',
        ]);
        $changes = [
            'role' => $data['role'],
            'status' => 'aprobado',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ];
        if ($data['role'] === UserRole::CoordinadorComunidades->value) {
            $changes['comunidad_id'] = (int) $data['comunidad_id'];
        }

        $user->update($changes);

        return back()->with('status', 'Usuario aprobado correctamente.');
    }

    public function bloquear(Request $request, User $user)
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes bloquear tu propia cuenta.');
        }

        $blocked = DB::transaction(function () use ($user) {
            // Las filas de Secretaría aprobadas quedan bloqueadas hasta el final: dos bloqueos
            // simultáneos no pueden dejar el sistema sin ninguna cuenta de Secretaría aprobada.
            $otherSecretarias = User::where('role', UserRole::Secretaria->value)->where('status', 'aprobado')
                ->whereKeyNot($user->getKey())->lockForUpdate()->count();
            $target = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($target->isApprovedForAccess() && $target->operationalRole() === UserRole::Secretaria && $otherSecretarias === 0) {
                return false;
            }

            $target->update([
                'status' => 'bloqueado',
                'approved_at' => null,
                'approved_by' => null,
            ]);

            return true;
        });

        if (! $blocked) {
            return back()->with('error', 'No se puede bloquear: debe quedar al menos una cuenta de Secretaría aprobada.');
        }

        return back()->with('status', 'Usuario bloqueado correctamente.');
    }
}
