<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {


        // 1) Normalizar lo que venga del form (por si viene texto o distintos formatos)
        $raw = (string) $request->input('requested_role', '');
        $key = mb_strtolower(trim($raw));

        // Mapeo de entradas posibles -> valor canónico que guardamos
        $map = [
            // canónicos
            'catequista' => 'catequista',
            'coord_general' => 'coord_general',
            'parroco' => 'parroco',
            'coord_comunidad' => 'coord_comunidad',

            // variaciones comunes (texto visible)
            'coordinador general' => 'coord_general',
            'coordinador comunidad' => 'coord_comunidad',
            'coordinador de comunidad' => 'coord_comunidad',
            'párroco' => 'parroco',
            'parroco' => 'parroco',

            // abreviaciones posibles
            'coord. general' => 'coord_general',
            'coord general' => 'coord_general',
            'coord. comunidad' => 'coord_comunidad',
            'coord comunidad' => 'coord_comunidad',
        ];

        $normalizedRequestedRole = $map[$key] ?? null;

        // 2) Validación (ya con valor normalizado)
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Si no se pudo normalizar, regresamos error claro
        if (!$normalizedRequestedRole) {
            return back()
                ->withErrors(['requested_role' => 'Selecciona un tipo de acceso válido.'])
                ->withInput();
        }

        // 3) Debug para confirmar qué llega y qué se normaliza
        Log::info('REGISTER requested_role', [
            'email' => $validated['email'],
            'raw_requested_role' => $raw,
            'normalized_requested_role' => $normalizedRequestedRole,
            'all' => $request->all(),
        ]);

        // 4) Crear usuario
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),

            // rol real base (no dar privilegios desde registro)
            'role' => 'usuario',

            // ahora sí se guarda lo elegido (ya normalizado)
            'requested_role' => $normalizedRequestedRole,

            // estado
            'status' => 'pendiente',
        ]);

        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', 'Registro enviado. Espera la aprobación de Secretaría.');
    }
}