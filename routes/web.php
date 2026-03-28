<?php

use App\Http\Controllers\ProfileController;

// CONTROLADORES SECRETARÍA
use App\Http\Controllers\Secretaria\UsuariosPendientesController;
use App\Http\Controllers\Secretaria\CatalogoController;
use App\Http\Controllers\Secretaria\AlumnoController;
use App\Http\Controllers\Secretaria\TutorController;
use App\Http\Controllers\Secretaria\InscripcionController;
use App\Http\Controllers\Secretaria\AsignaGrupoController;

// OTROS ROLES
use App\Http\Controllers\Catequista\CatequistaController;
use App\Http\Controllers\Parroco\ParrocoController;

// CONTROLADORES COORDINADOR DE COMUNIDAD
use App\Http\Controllers\CoordComunidad\ComunidadController;

// CONTROLADORES COORDINADOR GENERAL
use App\Http\Controllers\CoordGeneral\NivelController;
use App\Http\Controllers\CoordGeneral\PeriodoController;
use App\Http\Controllers\CoordGeneral\RubroController;
use App\Http\Controllers\CoordGeneral\UnidadController;
use App\Http\Controllers\CoordGeneral\GrupoController;

// CONTROLADORES CATEQUISTA
use App\Http\Controllers\Catequista\EvaluacionController;
use App\Http\Controllers\Catequista\MiGrupoController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('welcome'); });

// Rutas generales protegidas sin caché
Route::middleware(['auth', \App\Http\Middleware\NoCacheHeaders::class])->group(function () {
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;
        if ($role === 'secretaria') return redirect()->route('secretaria.dashboard');
        if ($role === 'parroco') return redirect()->route('parroco.dashboard');
        if ($role === 'coord_general') return redirect()->route('coord_general.dashboard');
        if ($role === 'coord_comunidad') return redirect()->route('coord_comunidad.dashboard');
        if ($role === 'catequista') return redirect()->route('catequista.dashboard');
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // MÓDULO SECRETARÍA
    Route::get('/secretaria/dashboard', function () {
        abort_unless(auth()->user()->role === 'secretaria', 403);
        return view('secretaria.dashboard');
    })->name('secretaria.dashboard');

    Route::get('/secretaria/usuarios/pendientes', [UsuariosPendientesController::class, 'index'])->name('secretaria.usuarios.pendientes');
    Route::post('/secretaria/usuarios/{user}/aprobar', [UsuariosPendientesController::class, 'aprobar'])->name('secretaria.usuarios.aprobar');
    Route::post('/secretaria/usuarios/{user}/bloquear', [UsuariosPendientesController::class, 'bloquear'])->name('secretaria.usuarios.bloquear');

    Route::get('/secretaria/catalogos', [CatalogoController::class, 'getSelects']);
    Route::apiResource('/secretaria/alumnos', AlumnoController::class);
    Route::apiResource('/secretaria/tutores', TutorController::class);
    Route::apiResource('/secretaria/inscripciones', InscripcionController::class);
    Route::apiResource('/secretaria/asigna_grupo', AsignaGrupoController::class);
});

// ==========================================
// MÓDULO PÁRROCO (SUPER ADMIN)
// ==========================================
Route::middleware(['auth', 'role:parroco', \App\Http\Middleware\NoCacheHeaders::class])->group(function () {

    // Vista Dashboard
    Route::get('/parroco/dashboard', function () { return view('parroco.dashboard'); })->name('parroco.dashboard');

    // Mega Catálogo del Párroco
    Route::get('/parroco/catalogos', function () {
        return response()->json([
            'comunidades' => DB::table('comunidades')->whereNull('deleted_at')->select('id', 'comunidad as text')->get(),
            'niveles' => DB::table('niveles')->whereNull('deleted_at')->select('id', 'nivel as text')->get(),
            'grupos' => DB::table('grupos')->whereNull('deleted_at')->select('id', 'nombre as text')->get(),
            'periodos' => DB::table('periodos')->whereNull('deleted_at')->select('id', DB::raw("CONCAT(fecha_inicio, ' al ', fecha_fin) as text"))->get(),
            'users' => DB::table('users')->where('role', 'catequista')->select('id', 'name as text')->get(),
            'alumnos' => DB::table('alumnos')->whereNull('deleted_at')->select('id', DB::raw("CONCAT(nombre, ' ', apellido_paterno) as text"))->get(),
            'unidades' => DB::table('unidades')->whereNull('deleted_at')->select('id', 'nombre as text')->get(),
            'rubros' => DB::table('rubros')->whereNull('deleted_at')->select('id', 'nombre as text')->get(),
        ]);
    });

    // Reutilizamos TODA la lógica que ya programaste en otros roles
    Route::apiResource('/parroco/comunidades', \App\Http\Controllers\CoordComunidad\ComunidadController::class);
    Route::apiResource('/parroco/alumnos', \App\Http\Controllers\Secretaria\AlumnoController::class);
    Route::apiResource('/parroco/tutores', \App\Http\Controllers\Secretaria\TutorController::class);
    Route::apiResource('/parroco/inscripciones', \App\Http\Controllers\Secretaria\InscripcionController::class);
    Route::apiResource('/parroco/asigna_grupo', \App\Http\Controllers\Secretaria\AsignaGrupoController::class);

    Route::apiResource('/parroco/niveles', \App\Http\Controllers\CoordGeneral\NivelController::class);
    Route::apiResource('/parroco/periodos', \App\Http\Controllers\CoordGeneral\PeriodoController::class);
    Route::apiResource('/parroco/rubros', \App\Http\Controllers\CoordGeneral\RubroController::class);
    Route::apiResource('/parroco/unidades', \App\Http\Controllers\CoordGeneral\UnidadController::class);
    Route::apiResource('/parroco/grupos', \App\Http\Controllers\CoordGeneral\GrupoController::class);
    Route::apiResource('/parroco/evaluaciones', \App\Http\Controllers\Catequista\EvaluacionController::class);
});

// ==========================================
// MÓDULO CATEQUISTA
// ==========================================
Route::middleware(['auth', 'role:catequista', \App\Http\Middleware\NoCacheHeaders::class])->group(function () {
    Route::get('/catequista/dashboard', [CatequistaController::class, 'index'])->name('catequista.dashboard');

    // El CRUD de las Evaluaciones
    Route::apiResource('/catequista/evaluaciones', EvaluacionController::class);
    // RUTA PARA EL SELECT DE SU LISTA DE GRUPO (Solo lectura)
    Route::get('/catequista/mi_grupo', [MiGrupoController::class, 'index']);
});

// ==========================================
// MÓDULO COORDINADOR DE COMUNIDAD
// ==========================================
Route::middleware(['auth', 'role:coord_comunidad', \App\Http\Middleware\NoCacheHeaders::class])->group(function () {
    Route::get('/coordinador-comunidad/dashboard', function () {
        return view('coord_comunidad.dashboard');
    })->name('coord_comunidad.dashboard');
    Route::apiResource('/coordinador-comunidad/comunidades', ComunidadController::class);
});

// ==========================================
// MÓDULO COORDINADOR GENERAL
// ==========================================
Route::middleware(['auth', 'role:coord_general', \App\Http\Middleware\NoCacheHeaders::class])->group(function () {

    // Vista del Dashboard principal
    Route::get('/coordinador-general/dashboard', function () {
        return view('coord_general.dashboard');
    })->name('coord_general.dashboard');

    // API RESTful para los CRUDs del Coordinador General
    Route::apiResource('/coordinador-general/niveles', NivelController::class);
    Route::apiResource('/coordinador-general/periodos', PeriodoController::class);
    Route::apiResource('/coordinador-general/rubros', RubroController::class);
    Route::apiResource('/coordinador-general/unidades', UnidadController::class);
    Route::apiResource('/coordinador-general/grupos', GrupoController::class);
});

require __DIR__.'/auth.php';
