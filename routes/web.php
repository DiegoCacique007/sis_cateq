<?php

use Illuminate\Support\Facades\Route;

// Secretaría
use App\Http\Controllers\Secretaria\DashboardController;
use App\Http\Controllers\Secretaria\AlumnoController;
use App\Http\Controllers\Secretaria\AlumnoComunidadController;
use App\Http\Controllers\Secretaria\AsignaGrupoController;
use App\Http\Controllers\Secretaria\BoletaController;
use App\Http\Controllers\Secretaria\ComunidadController;
use App\Http\Controllers\Secretaria\EvaluacionController;
use App\Http\Controllers\Secretaria\GrupoController;
use App\Http\Controllers\Secretaria\InscripcionController;
use App\Http\Controllers\Secretaria\NivelController;
use App\Http\Controllers\Secretaria\PeriodoController;
use App\Http\Controllers\Secretaria\RubroController;
use App\Http\Controllers\Secretaria\TutorController;
use App\Http\Controllers\Secretaria\UnidadController;
use App\Http\Controllers\Secretaria\UsuariosPendientesController;

// Catequista
use App\Http\Controllers\Catequista\CatequistaController;
use App\Http\Controllers\Catequista\EvaluacionController as CatequistaEvaluacionController;
use App\Http\Controllers\Catequista\MiGrupoController;

// Párroco
use App\Http\Controllers\Parroco\ParrocoController;

// Coordinador General
use App\Http\Controllers\CoordGeneral\CoordGeneralController;

// Coordinador de Comunidades
use App\Http\Controllers\CoordComunidad\CoordComunidadController;



Route::view('/', 'welcome')->name('welcome');

// ==========================================
// RUTAS GENERALES PROTEGIDAS
// ==========================================
Route::middleware(['auth'])->group(function () {
    Route::post('/periodo-activo/cambiar', [\App\Http\Controllers\Secretaria\PeriodoActivoController::class, 'cambiar'])->name('periodo-activo.cambiar');
});

// ==========================================
// DASHBOARD GENERAL DESPUÉS DEL LOGIN
// ==========================================
Route::middleware(['auth', \App\Http\Middleware\NoCacheHeaders::class])->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === 'secretaria') {
        return redirect()->route('secretaria.dashboard');
    }

    if ($user->role === 'catequista') {
        return redirect()->route('catequista.dashboard');
    }

    if ($user->role === 'parroco') {
        return redirect()->route('parroco.dashboard');
    }

    if ($user->role === 'coordinador_general') {
        return redirect()->route('coordinador_general.dashboard');
    }

    if ($user->role === 'coordinador_comunidades') {
        return redirect()->route('coordinador_comunidades.dashboard');
    }

    return redirect()->route('welcome');
})->name('dashboard');

// ==========================================
// SECRETARÍA - ADMIN TOTAL (CRUD completo)
// ==========================================
Route::middleware(['auth', 'role:secretaria', \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo'])->prefix('secretaria')->name('secretaria.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/usuarios/pendientes', [UsuariosPendientesController::class, 'index'])->name('usuarios.pendientes');
    Route::post('/usuarios/{user}/aprobar', [UsuariosPendientesController::class, 'aprobar'])->name('usuarios.aprobar');
    Route::post('/usuarios/{user}/bloquear', [UsuariosPendientesController::class, 'bloquear'])->name('usuarios.bloquear');

    Route::resource('alumnos', AlumnoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/alumnos-comunidades', [AlumnoComunidadController::class, 'index'])->name('alumnos_comunidades.index');
    Route::resource('tutores', TutorController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('inscripciones', InscripcionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('asigna_grupo', AsignaGrupoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('comunidades', ComunidadController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('niveles', NivelController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('periodos', PeriodoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('rubros', RubroController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('unidades', UnidadController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('grupos', GrupoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/evaluaciones', [EvaluacionController::class, 'index'])->name('evaluaciones.index');
    Route::post('/evaluaciones/guardar-masivo', [EvaluacionController::class, 'guardarMasivo'])->name('evaluaciones.guardarMasivo');
    Route::delete('/evaluaciones/{evaluacion}', [EvaluacionController::class, 'destroy'])->name('evaluaciones.destroy');



    // ── Módulo de Boletas ──
    Route::prefix('boletas')->name('boletas.')->group(function () {
        Route::get('/', [BoletaController::class, 'index'])->name('index');
        Route::get('/generar/{inscripcion}', [BoletaController::class, 'generar'])->name('generar');
    });
});

// ==========================================
// CATEQUISTA (UPDATE evaluaciones de su grupo, READ listas propias)
// ==========================================
Route::middleware(['auth', 'role:catequista', \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo'])
    ->prefix('catequista')
    ->name('catequista.')
    ->group(function () {
        Route::get('/dashboard', [CatequistaController::class, 'index'])->name('dashboard');

        Route::get('/mi-grupo', [MiGrupoController::class, 'index'])->name('mi_grupo');
        Route::get('/mi-grupo/exportar-asistencia-pdf', [MiGrupoController::class, 'exportarAsistenciaPdf'])->name('asistencia.pdf');

        Route::get('/evaluaciones', [CatequistaEvaluacionController::class, 'index'])->name('evaluaciones.index');
        Route::post('/evaluaciones/guardar', [CatequistaEvaluacionController::class, 'guardar'])->name('evaluaciones.guardar');
    });

// ==========================================
// PÁRROCO - SUPERVISIÓN GENERAL (Solo lectura)
// ==========================================
Route::middleware(['auth', 'role:parroco', \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo'])
    ->prefix('parroco')
    ->name('parroco.')
    ->group(function () {
        Route::get('/dashboard', [ParrocoController::class, 'index'])->name('dashboard');
        Route::get('/comunidades', [ComunidadController::class, 'index'])->name('comunidades.index');
        Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('/catequistas', [ParrocoController::class, 'catequistas'])->name('catequistas.index');
        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
        Route::get('/evaluaciones', [ParrocoController::class, 'evaluaciones'])->name('evaluaciones.index');
        Route::get('/boletas', [BoletaController::class, 'index'])->name('boletas.index');
        Route::get('/boletas/generar/{inscripcion}', [BoletaController::class, 'generar'])->name('boletas.generar');
    });

// ==========================================
// COORDINADOR GENERAL - SUPERVISIÓN ACADÉMICA/PASTORAL
// ==========================================
Route::middleware(['auth', 'role:coordinador_general', \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo'])
    ->prefix('coordinador-general')
    ->name('coordinador_general.')
    ->group(function () {
        Route::get('/dashboard', [CoordGeneralController::class, 'index'])->name('dashboard');
        Route::get('/comunidades', [ComunidadController::class, 'index'])->name('comunidades.index');
        Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('/tutores', [TutorController::class, 'index'])->name('tutores.index');
        Route::get('/inscripciones', [InscripcionController::class, 'index'])->name('inscripciones.index');
        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
        Route::get('/niveles', [NivelController::class, 'index'])->name('niveles.index');
        Route::get('/evaluaciones', [CoordGeneralController::class, 'evaluaciones'])->name('evaluaciones.index');
        Route::get('/boletas', [BoletaController::class, 'index'])->name('boletas.index');
        Route::get('/boletas/generar/{inscripcion}', [BoletaController::class, 'generar'])->name('boletas.generar');
    });

// ==========================================
// COORDINADOR DE COMUNIDADES - SUPERVISIÓN POR COMUNIDAD
// ==========================================
Route::middleware(['auth', 'role:coordinador_comunidades', \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo'])
    ->prefix('coordinador-comunidades')
    ->name('coordinador_comunidades.')
    ->group(function () {
        Route::get('/dashboard', [CoordComunidadController::class, 'index'])->name('dashboard');
        Route::get('/comunidades', [ComunidadController::class, 'index'])->name('comunidades.index');
        Route::get('/alumnos-comunidad', [AlumnoComunidadController::class, 'index'])->name('alumnos_comunidad.index');
        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
        Route::get('/catequistas', [CoordComunidadController::class, 'catequistas'])->name('catequistas.index');
        Route::get('/evaluaciones', [CoordComunidadController::class, 'evaluaciones'])->name('evaluaciones.index');
        Route::get('/boletas', [BoletaController::class, 'index'])->name('boletas.index');
        Route::get('/boletas/generar/{inscripcion}', [BoletaController::class, 'generar'])->name('boletas.generar');
    });

require __DIR__ . '/auth.php';
