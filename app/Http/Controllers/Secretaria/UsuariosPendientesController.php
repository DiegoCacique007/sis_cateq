<?php

namespace App\Http\Controllers\Secretaria;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Accounts\AccountActionRejected;
use App\Services\Accounts\AccountManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuariosPendientesController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        // Alias compatible para enlaces antiguos y orientación del chatbot.
        $request->merge(['seccion' => 'pendientes']);

        return app(UsuariosController::class)->index($request);
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

    public function bloquear(Request $request, User $user, AccountManager $accounts)
    {
        abort_unless(auth()->user()->operationalRole() === UserRole::Secretaria, 403);

        // Mismas protecciones y registro que en «Cuentas de usuario».
        try {
            return back()->with('status', $accounts->block($request->user(), $user));
        } catch (AccountActionRejected $rejected) {
            return back()->with('error', $rejected->getMessage());
        }
    }
}
