<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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
        // 1) Normalizar requested_role
        $raw = (string) $request->input('requested_role', '');
        $key = mb_strtolower(trim($raw));

        $map = [
            'catequista' => 'catequista',
            'secretaria' => 'secretaria',
            'parroco' => 'parroco',
            'coordinador_general' => 'coordinador_general',
            'coordinador_comunidades' => 'coordinador_comunidades',

            'secretaría' => 'secretaria',

            'secre' => 'secretaria',
        ];

        $normalizedRequestedRole = $map[$key] ?? null;

        // 2) Validación principal con mensajes personalizados
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('users', 'name')
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            'requested_role' => ['required', 'string'],
        ], [
            'name.unique' => 'Este nombre completo ya se encuentra registrado en el sistema.',
            'name.required' => 'El nombre completo es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema. Si ya tienes cuenta, por favor inicia sesión.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'requested_role.required' => 'Debes seleccionar el perfil que deseas solicitar.',
        ]);

        // 3) Validar rol solicitado
        $rolesPermitidos = [
            'catequista',
            'secretaria',
            'parroco',
            'coordinador_general',
            'coordinador_comunidades',
        ];

        if (!$normalizedRequestedRole || !in_array($normalizedRequestedRole, $rolesPermitidos, true)) {
            return back()
                ->withErrors(['requested_role' => 'Selecciona un tipo de acceso válido.'])
                ->withInput();
        }

        // 4) Log opcional
        Log::info('REGISTER requested_role', [
            'email' => $validated['email'],
            'raw_requested_role' => $raw,
            'normalized_requested_role' => $normalizedRequestedRole,
        ]);

        // 5) Crear usuario pendiente
        $user = User::create([
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => 'usuario',
            'requested_role' => $normalizedRequestedRole,
            'status' => 'pendiente',
        ]);

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with('status', 'Registro enviado. Espera la aprobación de Secretaría.');
    }
}
