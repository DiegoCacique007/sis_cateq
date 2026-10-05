<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un formato de correo electrónico válido.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        // La respuesta pública no distingue cuentas inexistentes ni solicitudes recientes.
        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return back()->with('status', 'Si existe una cuenta con ese correo, recibirás un enlace para restablecer tu contraseña. Si ya lo solicitaste, espera un minuto antes de intentarlo de nuevo.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'No fue posible procesar la solicitud. Inténtalo más tarde.']);
    }
}
