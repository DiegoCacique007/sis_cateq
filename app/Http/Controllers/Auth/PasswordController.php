<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Display the change password form inside the user's role layout.
     */
    public function edit(): View
    {
        return view('cuenta.password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'different:current_password', 'confirmed'],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.different' => 'La nueva contraseña debe ser diferente de la actual.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        // Rotar remember_token invalida las cookies de «Recordarme» de otros equipos;
        // AuthenticateSession cierra las demás sesiones y conserva la actual.
        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->route('cuenta.password.edit')
            ->with('success', 'Tu contraseña se actualizó correctamente.');
    }
}
