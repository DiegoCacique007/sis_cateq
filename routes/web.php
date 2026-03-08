<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UsuariosPendientesController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ParrocoController;
use App\Http\Controllers\CoordGeneralController;
use App\Http\Controllers\CoordComunidadController;
use App\Http\Controllers\CatequistaController;

// 1. RUTA RAÍZ (Enviar a login por defecto)
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. DISTRIBUIDOR PRINCIPAL (Redirige según el rol al iniciar sesión)
Route::get('/dashboard', function () {
    $role = auth()->user()->role;

    // Redirecciones según el rol exacto
    if ($role === 'secretaria') return redirect()->route('secretaria.dashboard');
    if ($role === 'parroco') return redirect()->route('parroco.dashboard');
    if ($role === 'coord_general') return redirect()->route('coord_general.dashboard');
    if ($role === 'coord_comunidad') return redirect()->route('coord_comunidad.dashboard');
    if ($role === 'catequista') return redirect()->route('catequista.dashboard');

    // Si es un usuario recién registrado (rol 'usuario' o 'pendiente')
    return view('dashboard'); 
})->middleware(['auth'])->name('dashboard');


// 3. RUTAS GENERALES AUTENTICADAS (Perfil y Secretaría)
Route::middleware(['auth'])->group(function () {

    // Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Secretaría
    Route::get('/secretaria/dashboard', function () {
        abort_unless(auth()->user()->role === 'secretaria', 403);
        return view('secretaria.dashboard');
    })->name('secretaria.dashboard');

    Route::get('/secretaria/usuarios/pendientes', [UsuariosPendientesController::class, 'index'])
        ->name('secretaria.usuarios.pendientes');

    Route::post('/secretaria/usuarios/{user}/aprobar', [UsuariosPendientesController::class, 'aprobar'])
        ->name('secretaria.usuarios.aprobar');

    Route::post('/secretaria/usuarios/{user}/bloquear', [UsuariosPendientesController::class, 'bloquear'])
        ->name('secretaria.usuarios.bloquear');
});


// 4. RUTAS PROTEGIDAS POR ROL ESPECÍFICO

// Rutas Párroco
Route::middleware(['auth', 'role:parroco'])->group(function () {
    Route::get('/parroco/dashboard', [ParrocoController::class, 'index'])->name('parroco.dashboard');
});

// Rutas Coordinador General
Route::middleware(['auth', 'role:coord_general'])->group(function () {
    Route::get('/coordinador-general/dashboard', [CoordGeneralController::class, 'index'])->name('coord_general.dashboard');
});

// Rutas Coordinador de Comunidad
Route::middleware(['auth', 'role:coord_comunidad'])->group(function () {
    Route::get('/coordinador-comunidad/dashboard', [CoordComunidadController::class, 'index'])->name('coord_comunidad.dashboard');
});

// Rutas Catequista
Route::middleware(['auth', 'role:catequista'])->group(function () {
    Route::get('/catequista/dashboard', [CatequistaController::class, 'index'])->name('catequista.dashboard');
});


// Archivo de rutas de autenticación de Breeze
require __DIR__.'/auth.php';