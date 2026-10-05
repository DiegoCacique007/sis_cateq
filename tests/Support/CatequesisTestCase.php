<?php

namespace Tests\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Authorization\AccessContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class CatequesisTestCase extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Esquema de pruebas, no reemplaza ni ejecuta migraciones académicas de producción.
        Schema::table('users', fn (Blueprint $t) => $t->unsignedBigInteger('comunidad_id')->nullable());
        foreach (['comunidades', 'periodos', 'niveles', 'rubros'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        foreach ([
            'grupos' => ['periodo_id'],
            'unidades' => ['nivel_id'],
            'alumnos' => ['comunidad_id'],
            'asigna_grupo' => ['catequista_id', 'comunidad_id', 'grupo_id', 'nivel_id', 'periodo_id'],
            'inscripciones' => ['alumno_id', 'grupo_id', 'periodo_id'],
            'evaluaciones' => ['inscripcion_id', 'unidad_id', 'rubro_id', 'periodo_id'],
        ] as $table => $columns) {
            Schema::create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    $t->unsignedBigInteger($column)->nullable();
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        foreach (['comunidades', 'periodos', 'niveles', 'rubros'] as $table) {
            DB::table($table)->insert([['id' => 1], ['id' => 2]]);
        }
        DB::table('unidades')->insert([['id' => 1, 'nivel_id' => 1], ['id' => 2, 'nivel_id' => 2]]);
    }

    protected function context(UserRole $role = UserRole::Catequista, ?int $period = 1, ?int $community = 1): AccessContext
    {
        $user = User::factory()->create(['role' => $role->value, 'status' => 'aprobado', 'comunidad_id' => $community]);

        return new AccessContext($user->id, $role, 'aprobado', $community, $period);
    }

    protected function records(AccessContext $context, int $community = 1, int $period = 1): array
    {
        $group = DB::table('grupos')->insertGetId(['periodo_id' => $period]);
        $assignment = DB::table('asigna_grupo')->insertGetId([
            'catequista_id' => $context->userId, 'comunidad_id' => $community,
            'grupo_id' => $group, 'nivel_id' => 1, 'periodo_id' => $period,
        ]);
        $student = DB::table('alumnos')->insertGetId(['comunidad_id' => $community]);
        $inscription = DB::table('inscripciones')->insertGetId([
            'alumno_id' => $student, 'grupo_id' => $group, 'periodo_id' => $period,
        ]);
        $evaluation = DB::table('evaluaciones')->insertGetId([
            'inscripcion_id' => $inscription, 'unidad_id' => 1, 'rubro_id' => 1, 'periodo_id' => $period,
        ]);

        return compact('group', 'assignment', 'student', 'inscription', 'evaluation');
    }
}
