<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UsuariosPendientesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/dashboard', function () {
    // Si es secretaria, manda a su dashboard
    if (auth()->user()->role === 'secretaria') {
        return redirect()->route('secretaria.dashboard');
    }

    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {

    // PERFIL (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SECRETARIA
    Route::post('/secretaria/usuarios/{user}/bloquear', [UsuariosPendientesController::class, 'bloquear'])
    ->name('secretaria.usuarios.bloquear');

    
    Route::get('/secretaria/dashboard', function () {
        abort_unless(auth()->user()->role === 'secretaria', 403);
        return view('secretaria.dashboard');
    })->name('secretaria.dashboard');

    Route::get('/secretaria/usuarios/pendientes', [UsuariosPendientesController::class, 'index'])
        ->name('secretaria.usuarios.pendientes');

    Route::post('/secretaria/usuarios/{user}/aprobar', [UsuariosPendientesController::class, 'aprobar'])
        ->name('secretaria.usuarios.aprobar');
});

require __DIR__.'/auth.php';