<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Models\User;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleCatequistas;
use App\Queries\AccessibleComunidades;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleGrupos;
use App\Queries\AccessibleInscripciones;
use App\Queries\AccessibleNiveles;
use App\Queries\AccessibleTutores;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

class RemainingReadControllersTest extends CatequesisWebTestCase
{
    private function login(AccessContext $context): void
    {
        $this->actingAs(User::findOrFail($context->userId));
    }

    private function tutor(int $student): int
    {
        return DB::table('tutores')->insertGetId(['alumno_id' => $student, 'nombre' => 'Tutor', 'ap' => 'Prueba']);
    }

    public static function supervisors(): array
    {
        return [[R::Parroco, 'parroco'], [R::CoordinadorGeneral, 'coordinador-general']];
    }

    #[DataProvider('supervisors')]
    public function test_dashboard_counts_authorized_records_and_matches_lists(R $role, string $prefix): void
    {
        $own = $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        $this->records($this->context(), period: 2);
        $this->tutor($own['student']);
        $this->tutor($other['student']);
        DB::table('evaluaciones')->where('id', $other['evaluation'])->update(['unidad_id' => 2]);
        $context = $this->context($role);
        $this->login($context);
        $response = $this->get('/'.$prefix.'/dashboard')->assertOk()
            ->assertViewHas('totalAlumnos', 2)->assertViewHas('totalComunidades', 2)
            ->assertViewHas('totalCatequistas', 2)->assertViewHas('totalEvaluaciones', 1)
            ->assertViewHas('totalInscripciones', 2);
        $response->assertViewHas($role === R::Parroco ? 'totalGrupos' : 'totalGruposAsignados', 2);
        $this->assertFalse(app(CatequesisAccess::class)->canViewEvaluacion($context, $other['evaluation'])->isAllowed());
        $this->assertSame(app(AccessibleEvaluaciones::class)->for($context)->count(), $response->viewData('totalEvaluaciones'));
        $list = $this->get('/'.$prefix.'/alumnos')->assertOk();
        $this->assertSame($list->viewData('registros')->total(), $response->viewData('totalAlumnos'));
        if ($role === R::CoordinadorGeneral) {
            $response->assertViewHas('totalNiveles', app(AccessibleNiveles::class)->for($context)->count())
                ->assertViewHas('totalTutores', 2);
            $this->assertSame($this->get('/'.$prefix.'/tutores')->viewData('registros')->total(), $response->viewData('totalTutores'));
            $this->assertSame($this->get('/'.$prefix.'/inscripciones')->viewData('registros')->total(), $response->viewData('totalInscripciones'));
        }
    }

    public function test_parroco_catechists_match_authorized_query_and_dashboard(): void
    {
        $teacher = $this->context();
        $this->records($teacher);
        $this->context(); // Sin asignación: no está en el alcance del catálogo compartido.
        $this->records($this->context(), period: 2);
        $context = $this->context(R::Parroco);
        $this->login($context);
        $this->get('/parroco/catequistas')->assertOk()
            ->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$teacher->userId]);
        $this->get('/parroco/dashboard')->assertViewHas('totalCatequistas', app(AccessibleCatequistas::class)->for($context)->count());
    }

    #[DataProvider('supervisors')]
    public function test_evaluations_use_authorized_records_and_keep_legacy_period(R $role, string $prefix): void
    {
        $ids = $this->records($this->context());
        $this->records($this->context(), period: 2);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['periodo_id' => null]);
        $this->login($this->context($role));
        $url = '/'.$prefix.'/evaluaciones?sacramento=primera_comunion&nivel_id=1&unidad_id=1&asignacion_id='.$ids['assignment'];
        $this->get($url)->assertOk()->assertViewHas('alumnos', fn ($r) => $r->pluck('id')->all() === [$ids['inscription']])
            ->assertViewHas('calificacionesMap', fn ($m) => (float) $m[$ids['inscription']][1] === 5.0);
        $this->get('/'.$prefix.'/dashboard')->assertViewHas('totalEvaluaciones', 1);
    }

    #[DataProvider('supervisors')]
    public function test_old_unknown_and_incompatible_evaluation_filters_fail_safely(R $role, string $prefix): void
    {
        $ids = $this->records($this->context());
        $old = $this->records($this->context(), period: 2);
        $this->login($this->context($role));
        foreach ([$old['assignment'], 999] as $id) {
            $this->get('/'.$prefix.'/evaluaciones?asignacion_id='.$id.'&periodo_id=2')->assertNotFound();
        }
        $this->get('/'.$prefix.'/evaluaciones?asignacion_id='.$ids['assignment'].'&unidad_id=2')->assertNotFound();
        $this->get('/'.$prefix.'/evaluaciones?nivel_id=999')->assertNotFound();
    }

    #[DataProvider('supervisors')]
    public function test_ambiguous_assignments_return_409_without_counting_evaluations(R $role, string $prefix): void
    {
        $teacher = $this->context();
        $ids = $this->records($teacher);
        DB::table('asigna_grupo')->insert(['catequista_id' => $teacher->userId, 'grupo_id' => $ids['group'], 'periodo_id' => 1, 'comunidad_id' => 1, 'nivel_id' => 2]);
        $this->login($this->context($role));
        $this->get('/'.$prefix.'/evaluaciones?asignacion_id='.$ids['assignment'])->assertStatus(409);
        $this->get('/'.$prefix.'/dashboard')->assertViewHas('totalEvaluaciones', 0);
    }

    #[DataProvider('supervisors')]
    public function test_supervisors_cannot_write_administrative_resources(R $role, string $prefix): void
    {
        $this->login($this->context($role));
        foreach (['alumnos', 'tutores', 'inscripciones', 'niveles', 'grupos', 'comunidades'] as $resource) {
            $this->post('/secretaria/'.$resource, [])->assertForbidden();
            $this->put('/secretaria/'.$resource.'/1', [])->assertForbidden();
            $this->delete('/secretaria/'.$resource.'/1')->assertForbidden();
        }
        $this->get('/'.$prefix.'/alumnos')->assertOk()->assertDontSee('action="'.route('secretaria.alumnos.store').'"', false);
    }

    public static function generalLists(): array
    {
        return [['alumnos', AccessibleAlumnos::class], ['tutores', AccessibleTutores::class],
            ['inscripciones', AccessibleInscripciones::class], ['grupos', AccessibleGrupos::class],
            ['niveles', AccessibleNiveles::class], ['comunidades', AccessibleComunidades::class]];
    }

    #[DataProvider('generalLists')]
    public function test_general_shared_lists_use_authorized_queries(string $path, string $query): void
    {
        $ids = $this->records($this->context());
        $this->tutor($ids['student']);
        $old = $this->records($this->context(), period: 2);
        $this->tutor($old['student']);
        $context = $this->context(R::CoordinadorGeneral);
        $this->login($context);
        $builder = in_array($query, [AccessibleAlumnos::class, AccessibleInscripciones::class], true)
            ? app($query)->forIndex($context) : app($query)->for($context);
        $expected = $builder->pluck('id')->sort()->values()->all();
        $this->get('/coordinador-general/'.$path.'?periodo_id=2&role=secretaria&user_id=999')
            ->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->sort()->values()->all() === $expected);
        $this->get('/coordinador-general/'.$path.'?search=NoExiste')->assertOk()->assertViewHas('registros', fn ($r) => $r->isEmpty());
    }

    public function test_catechist_counts_are_unchanged_by_other_teacher_or_period(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $this->login($context);
        $before = $this->get('/catequista/dashboard')->assertOk();
        $this->records($this->context());
        $this->records($context, period: 2);
        $after = $this->get('/catequista/dashboard?catequista_id=999&periodo_activo_id=2')->assertOk();
        foreach (['totalGruposAsignados', 'totalAlumnosGrupo', 'totalNivelesAsignados', 'totalEvaluacionesRegistradas'] as $key) {
            $this->assertSame(1, $before->viewData($key));
            $this->assertSame($before->viewData($key), $after->viewData($key));
        }
        $this->get('/catequista/mi-grupo?asignacion_id='.$own['assignment'])->assertViewHas('alumnos', fn ($a) => $a->count() === $after->viewData('totalAlumnosGrupo'));
    }

    public function test_catechist_does_not_count_inaccessible_or_deleted_evaluations(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->login($context);
        foreach ([['unidad_id' => 2], ['unidad_id' => 1, 'periodo_id' => 2], ['periodo_id' => 1, 'deleted_at' => now()]] as $change) {
            DB::table('evaluaciones')->where('id', $ids['evaluation'])->update($change);
            $this->assertFalse(app(CatequesisAccess::class)->canViewEvaluacion($context, $ids['evaluation'])->isAllowed());
            $this->get('/catequista/dashboard')->assertOk()->assertViewHas('totalEvaluacionesRegistradas', 0);
        }
    }

    public static function readerRoles(): array
    {
        return [[R::Secretaria, 'secretaria'], [R::Parroco, 'parroco'], [R::CoordinadorGeneral, 'coordinador-general']];
    }

    #[DataProvider('readerRoles')]
    public function test_students_outside_accessible_query_never_appear_in_shared_list(R $role, string $prefix): void
    {
        $valid = $this->records($this->context());
        $invalid = $this->records($this->context(), community: 2);
        DB::table('comunidades')->where('id', 2)->update(['deleted_at' => now()]);
        $context = $this->context($role);
        $this->login($context);
        $this->assertFalse(app(AccessibleAlumnos::class)->for($context)->whereKey($invalid['student'])->exists());
        $this->get('/'.$prefix.'/alumnos?search=Prueba')->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$valid['student']]);
    }

    public function test_secretaria_keeps_unassigned_inscriptions_unenrolled_students_and_crud(): void
    {
        $ids = $this->records($this->context());
        $this->tutor($ids['student']);
        DB::table('asigna_grupo')->where('id', $ids['assignment'])->delete();
        $unenrolled = DB::table('alumnos')->insertGetId(['comunidad_id' => 1]);
        $this->tutor($unenrolled);
        $this->login($this->context(R::Secretaria));
        $this->get('/secretaria/alumnos')->assertOk()->assertViewHas('registros', fn ($r) => $r->total() === 2);
        $this->get('/secretaria/tutores')->assertOk()->assertViewHas('registros', fn ($r) => $r->total() === 2);
        $this->get('/secretaria/inscripciones')->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$ids['inscription']]);
        $this->post('/secretaria/niveles', ['nivel' => 'Nuevo'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('niveles', ['nivel' => 'Nuevo']);
    }

    public function test_community_supervisor_shared_gets_remain_confined(): void
    {
        $own = $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        $this->login($this->context(R::CoordinadorComunidades));
        $this->get('/coordinador-comunidades/alumnos-comunidad?comunidad_id=2')->assertOk()->assertViewHas('registros', fn ($r) => $r->isEmpty());
        $this->get('/coordinador-comunidades/grupos')->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$own['group']]);
        $this->get('/coordinador-comunidades/comunidades')->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [1]);
        $this->get('/coordinador-comunidades/boletas?grupo_id='.$other['group'])->assertViewHas('alumnos', fn ($r) => $r->isEmpty());
        $this->get('/coordinador-comunidades/boletas/generar/'.$other['inscription'])->assertNotFound();
    }

    public static function periodRoutes(): array
    {
        return [[R::Parroco, '/parroco/dashboard'], [R::CoordinadorGeneral, '/coordinador-general/dashboard'],
            [R::Catequista, '/catequista/dashboard'], [R::CoordinadorGeneral, '/coordinador-general/inscripciones'],
            [R::Parroco, '/parroco/evaluaciones']];
    }

    #[DataProvider('periodRoutes')]
    public function test_invalid_session_period_uses_existing_safe_contract(R $role, string $path): void
    {
        $this->records($this->context());
        $this->login($this->context($role));
        $this->withSession(['periodo_activo_id' => 9999])->get($path)->assertRedirect(route('welcome'))->assertSessionHas('error');
    }

    public function test_soft_deleted_students_tutors_and_inscriptions_are_not_counted_or_listed(): void
    {
        $ids = $this->records($this->context());
        $this->tutor($ids['student']);
        $this->login($this->context(R::CoordinadorGeneral));
        DB::table('alumnos')->where('id', $ids['student'])->update(['deleted_at' => now()]);
        $this->get('/coordinador-general/dashboard')->assertOk()->assertViewHas('totalAlumnos', 0)
            ->assertViewHas('totalTutores', 0)->assertViewHas('totalInscripciones', 0)->assertViewHas('totalEvaluaciones', 0);
        foreach (['alumnos', 'tutores', 'inscripciones'] as $path) {
            $this->get('/coordinador-general/'.$path)->assertOk()->assertViewHas('registros', fn ($r) => $r->isEmpty());
        }
    }
}
