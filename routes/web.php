<?php

use Illuminate\Support\Facades\Route;

// Secretaría
use App\Http\Controllers\Secretaria\AlumnoController;
use App\Http\Controllers\Secretaria\AsignaGrupoController;
use App\Http\Controllers\Secretaria\BoletaController;
use App\Http\Controllers\Secretaria\ComunidadController;
use App\Http\Controllers\Secretaria\DocumentoController;
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


    return redirect()->route('welcome');
})->name('dashboard');

// ==========================================
// SECRETARÍA - ADMIN TOTAL (CRUD completo)
// ==========================================
Route::middleware(['auth', 'role:secretaria', \App\Http\Middleware\NoCacheHeaders::class, 'periodo.activo'])->prefix('secretaria')->name('secretaria.')->group(function () {
    Route::view('/dashboard', 'secretaria.dashboard')->name('dashboard');

    Route::get('/usuarios/pendientes', [UsuariosPendientesController::class, 'index'])->name('usuarios.pendientes');
    Route::post('/usuarios/{user}/aprobar', [UsuariosPendientesController::class, 'aprobar'])->name('usuarios.aprobar');
    Route::post('/usuarios/{user}/bloquear', [UsuariosPendientesController::class, 'bloquear'])->name('usuarios.bloquear');

    Route::resource('alumnos', AlumnoController::class)->only(['index', 'store', 'update', 'destroy']);
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

    // ── Módulo de Emisión de Documentos (Solo Admin/Secretaria) ──
    Route::prefix('documentos')->name('documentos.')->group(function () {
        Route::get('/', [DocumentoController::class, 'index'])->name('index');
        Route::post('/boletas', [DocumentoController::class, 'boletas'])->name('boletas');
        Route::post('/certificado-primera-comunion', [DocumentoController::class, 'certificadoPrimeraComunion'])->name('certificado.primera_comunion');
        Route::post('/certificado-confirmacion', [DocumentoController::class, 'certificadoConfirmacion'])->name('certificado.confirmacion');
        Route::get('/buscar-alumnos', [DocumentoController::class, 'buscarAlumnos'])->name('buscar_alumnos');
    });

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
        Route::get('/mi-grupo/exportar-asistencia', [MiGrupoController::class, 'exportarAsistencia'])->name('mi_grupo.exportar_asistencia');

        Route::get('/evaluaciones', [CatequistaEvaluacionController::class, 'index'])->name('evaluaciones.index');
        Route::post('/evaluaciones/guardar', [CatequistaEvaluacionController::class, 'guardar'])->name('evaluaciones.guardar');
    });





require __DIR__ . '/auth.php';
