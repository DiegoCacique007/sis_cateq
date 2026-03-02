<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // bloquear si NO aprobado
        if ($user && $user->status !== 'aprobado') {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    $msg = $user->status === 'bloqueado'
        ? 'Tu cuenta fue bloqueada. Contacta a Secretaría.'
        : 'Tu cuenta está pendiente de aprobación por Secretaría.';

    return back()->withErrors(['email' => $msg])->onlyInput('email');
}



        //redirect por rol
        $defaultRedirect = match ($user->role ?? null) {
            'secretaria' => route('secretaria.dashboard', absolute: false),
            default      => route('dashboard', absolute: false),
        };

        return redirect()->intended($defaultRedirect);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}