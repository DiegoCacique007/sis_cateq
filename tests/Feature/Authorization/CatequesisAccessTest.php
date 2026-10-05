<?php

namespace Tests\Feature\Authorization;

use App\Enums\CatequesisCapability as C;
use App\Enums\UserRole as R;
use App\Models\User;
use App\Queries\AccessibleAlumnos;
use App\Queries\AccessibleAsignaciones;
use App\Queries\AccessibleEvaluaciones;
use App\Queries\AccessibleInscripciones;
use App\Services\Authorization\AccessContext;
use App\Services\Authorization\AccessContextResolver;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisTestCase;

class CatequesisAccessTest extends CatequesisTestCase
{
    private function access(): CatequesisAccess
    {
        return app(CatequesisAccess::class);
    }

    public function test_catequista_can_access_own_records_and_manage_evaluations(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        foreach (['canViewAsignacion' => 'assignment', 'canViewInscripcion' => 'inscription',
            'canViewAlumno' => 'student', 'canViewEvaluacion' => 'evaluation', 'canManageEvaluacion' => 'evaluation'] as $method => $key) {
            $this->assertTrue($this->access()->$method($context, $ids[$key])->isAllowed());
        }
        $this->assertTrue($this->access()->can($context, C::ViewAttendanceList)->isAllowed());
        $this->assertSame('ROLE_NOT_ALLOWED', $this->access()->canViewBoleta($context, $ids['inscription'])->code);
    }

    public function test_foreign_records_and_nonexistent_ids_are_indistinguishable(): void
    {
        $context = $this->context();
        $ids = $this->records($this->context());
        foreach (['canViewAsignacion' => 'assignment', 'canViewInscripcion' => 'inscription',
            'canViewAlumno' => 'student', 'canViewEvaluacion' => 'evaluation', 'canManageEvaluacion' => 'evaluation'] as $method => $key) {
            $this->assertSame('OUTSIDE_SCOPE', $this->access()->$method($context, $ids[$key])->code);
            $this->assertSame('OUTSIDE_SCOPE', $this->access()->$method($context, 99999)->code);
        }
        $this->assertEmpty(app(AccessibleAlumnos::class)->for($context)->get());
        $this->assertEmpty(app(AccessibleEvaluaciones::class)->for($context)->get());
    }

    public function test_own_records_in_another_period_are_excluded(): void
    {
        $context = $this->context();
        $ids = $this->records($context, period: 2);
        $this->assertSame('OUTSIDE_SCOPE', $this->access()->canViewAsignacion($context, $ids['assignment'])->code);
        $this->assertEmpty(app(AccessibleInscripciones::class)->for($context)->get());
        $this->assertEmpty(app(AccessibleEvaluaciones::class)->for($context)->get());
    }

    public function test_missing_period_has_an_explicit_decision_and_empty_queries(): void
    {
        $context = $this->context(period: null);
        $ids = $this->records($context);
        $decision = $this->access()->canViewAsignacion($context, $ids['assignment']);
        $this->assertSame('missing_context', $decision->state);
        $this->assertSame('MISSING_PERIOD', $decision->code);
        foreach ([AccessibleAsignaciones::class, AccessibleInscripciones::class, AccessibleAlumnos::class, AccessibleEvaluaciones::class] as $query) {
            $this->assertEmpty(app($query)->for($context)->get());
        }
    }

    public static function roles(): array
    {
        return array_map(fn (R $role) => [$role], R::cases());
    }

    #[DataProvider('roles')]
    public function test_capability_matrix(R $role): void
    {
        $context = $this->context($role);
        foreach (C::cases() as $capability) {
            if (str_starts_with($capability->value, 'MANAGE_')) {
                $expected = $role === R::Secretaria || ($role === R::Catequista && $capability === C::ManageEvaluations);
                $this->assertSame($expected, $this->access()->can($context, $capability)->isAllowed(), $capability->value);
            }
        }
        foreach ([C::ViewGroups, C::ViewGroupStudents, C::ViewEvaluations] as $capability) {
            $this->assertTrue($this->access()->can($context, $capability)->isAllowed());
        }
        $this->assertSame($role !== R::Catequista, $this->access()->can($context, C::ViewBoletas)->isAllowed());
        foreach ([C::ViewTutors, C::ViewInscriptions, C::ViewLevels] as $capability) {
            $this->assertSame(in_array($role, [R::Secretaria, R::CoordinadorGeneral], true), $this->access()->can($context, $capability)->isAllowed());
        }
    }

    public function test_coordinator_scope_intersects_assignment_and_student_community(): void
    {
        $context = $this->context(R::CoordinadorComunidades);
        $own = $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        foreach (['canViewAsignacion' => 'assignment', 'canViewInscripcion' => 'inscription',
            'canViewAlumno' => 'student', 'canViewEvaluacion' => 'evaluation', 'canViewBoleta' => 'inscription'] as $method => $key) {
            $this->assertTrue($this->access()->$method($context, $own[$key])->isAllowed());
            $this->assertSame('OUTSIDE_SCOPE', $this->access()->$method($context, $other[$key])->code);
        }
        DB::table('alumnos')->where('id', $own['student'])->update(['comunidad_id' => 2]);
        $this->assertFalse($this->access()->canViewEvaluacion($context, $own['evaluation'])->isAllowed());
    }

    public function test_coordinator_without_community_is_denied(): void
    {
        $context = $this->context(R::CoordinadorComunidades, community: null);
        $this->assertSame('MISSING_COMMUNITY', $this->access()->can($context, C::ViewStudents)->code);
        $this->assertEmpty(app(AccessibleAsignaciones::class)->for($context)->get());
        $this->assertEmpty(app(AccessibleAlumnos::class)->for($context)->get());
    }

    public function test_external_filters_only_reduce_scope(): void
    {
        $context = $this->context(R::CoordinadorComunidades);
        $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        $this->assertSame(1, app(AccessibleAsignaciones::class)->for($context)->count());
        $this->assertEmpty(app(AccessibleAsignaciones::class)->for($context)->where('comunidad_id', 2)->get());
        $this->assertEmpty(app(AccessibleAlumnos::class)->for($context)->whereKey($other['student'])->get());
        $this->assertEmpty(app(AccessibleInscripciones::class)->for($context)->where('periodo_id', 2)->get());
    }

    public static function deletions(): array
    {
        return array_map(fn ($table) => [$table], ['asigna_grupo', 'inscripciones', 'alumnos', 'evaluaciones', 'comunidades', 'grupos', 'periodos', 'niveles', 'unidades', 'rubros']);
    }

    #[DataProvider('deletions')]
    public function test_soft_deleted_records_or_dependencies_exclude_evaluations(string $table): void
    {
        $context = $this->context();
        $this->records($context);
        DB::table($table)->where('id', 1)->update(['deleted_at' => now()]);
        $this->assertEmpty(app(AccessibleEvaluaciones::class)->for($context)->get());
        if (in_array($table, ['asigna_grupo', 'inscripciones', 'alumnos', 'comunidades', 'grupos', 'periodos', 'niveles'], true)) {
            $this->assertEmpty(app(AccessibleInscripciones::class)->for($context)->get());
            $this->assertEmpty(app(AccessibleAlumnos::class)->for($context)->get());
        }
    }

    public function test_competing_assignments_are_ambiguous_even_when_other_teacher_is_outside_scope(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('asigna_grupo')->insert([
            'catequista_id' => $this->context()->userId, 'comunidad_id' => 2,
            'grupo_id' => $ids['group'], 'periodo_id' => 1, 'nivel_id' => 2,
        ]);
        $decision = $this->access()->canViewInscripcion($context, $ids['inscription']);
        $this->assertSame('ambiguous', $decision->state);
        $this->assertSame('AMBIGUOUS_ASSIGNMENT', $decision->code);
        $this->assertEmpty(app(AccessibleInscripciones::class)->for($context)->get());
        $this->assertEmpty(app(AccessibleAlumnos::class)->for($context)->get());
        $this->assertEmpty(app(AccessibleEvaluaciones::class)->for($context)->get());
        $this->assertSame('OUTSIDE_SCOPE', $this->access()->canViewInscripcion($this->context(), $ids['inscription'])->code);
    }

    public function test_evaluation_requires_matching_level_and_period_but_null_period_inherits_inscription(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        foreach ([['unidad_id' => 2], ['unidad_id' => 1, 'periodo_id' => 2]] as $change) {
            DB::table('evaluaciones')->where('id', $ids['evaluation'])->update($change);
            $this->assertFalse($this->access()->canViewEvaluacion($context, $ids['evaluation'])->isAllowed());
        }
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['periodo_id' => null]);
        $this->assertTrue($this->access()->canViewEvaluacion($context, $ids['evaluation'])->isAllowed());
    }

    public function test_resolver_ignores_request_identity_role_community_and_period(): void
    {
        $context = $this->context(R::CoordinadorComunidades);
        $user = User::findOrFail($context->userId);
        $request = Request::create('/', 'POST', ['user_id' => 999, 'role' => 'secretaria', 'status' => 'aprobado', 'comunidad_id' => 2, 'periodo_activo_id' => 2]);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('periodo_activo_id', 1);
        $resolved = app(AccessContextResolver::class)->resolve($request);
        $this->assertEquals($context, $resolved);
        $this->assertSame(1, $resolved->communityId);
        $this->assertSame(1, $resolved->activePeriodId);
    }

    public static function invalidAccounts(): array
    {
        return [['aprobado', 'unknown'], ['pendiente', 'catequista'], ['bloqueado', 'catequista']];
    }

    #[DataProvider('invalidAccounts')]
    public function test_resolver_rejects_invalid_or_unapproved_account(string $status, string $role): void
    {
        $user = User::factory()->create(['status' => $status, 'role' => $role]);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $this->expectException(AuthorizationException::class);
        app(AccessContextResolver::class)->resolve($request);
    }

    public function test_resolver_reloads_status_and_rejects_revoked_user(): void
    {
        $context = $this->context();
        $user = User::findOrFail($context->userId);
        DB::table('users')->where('id', $user->id)->update(['status' => 'bloqueado']);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $this->expectException(AuthorizationException::class);
        app(AccessContextResolver::class)->resolve($request);
    }

    public function test_resolver_does_not_choose_a_period_and_ignores_deleted_period(): void
    {
        $context = $this->context();
        $user = User::findOrFail($context->userId);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));
        $this->assertNull(app(AccessContextResolver::class)->resolve($request)->activePeriodId);
        DB::table('periodos')->where('id', 1)->update(['deleted_at' => now()]);
        $request->session()->put('periodo_activo_id', 1);
        $this->assertNull(app(AccessContextResolver::class)->resolve($request)->activePeriodId);
    }

    public function test_unapproved_context_never_grants_capabilities_or_records(): void
    {
        $original = $this->context();
        $this->records($original);
        $context = new AccessContext($original->userId, R::Secretaria, 'bloqueado', 1, 1);
        foreach (C::cases() as $capability) {
            $this->assertFalse($this->access()->can($context, $capability)->isAllowed());
        }
        $this->assertEmpty(app(AccessibleAlumnos::class)->for($context)->get());
    }

    public function test_new_evaluation_capture_checks_inscription_unit_rubric_and_role(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->assertTrue($this->access()->canCaptureEvaluacion($context, $ids['inscription'], 1, 1)->isAllowed());
        $this->assertFalse($this->access()->canCaptureEvaluacion($context, $ids['inscription'], 2, 1)->isAllowed());
        $this->assertFalse($this->access()->canCaptureEvaluacion($context, $ids['inscription'], 1, 999)->isAllowed());
        $this->assertFalse($this->access()->canCaptureEvaluacion($this->context(), $ids['inscription'], 1, 1)->isAllowed());
        $this->assertSame('ROLE_NOT_ALLOWED', $this->access()->canCaptureEvaluacion($this->context(R::Parroco), $ids['inscription'], 1, 1)->code);
    }

    public function test_deleted_competing_assignment_does_not_cause_ambiguity(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('asigna_grupo')->insert([
            'catequista_id' => $context->userId, 'comunidad_id' => 1,
            'grupo_id' => $ids['group'], 'periodo_id' => 1, 'nivel_id' => 2, 'deleted_at' => now(),
        ]);
        $this->assertTrue($this->access()->canViewInscripcion($context, $ids['inscription'])->isAllowed());
        $this->assertSame(1, app(AccessibleAsignaciones::class)->for($context)->count());
    }

    public function test_multiple_assignments_owned_by_same_teacher_remain_ambiguous(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('asigna_grupo')->insert([
            'catequista_id' => $context->userId, 'comunidad_id' => 1,
            'grupo_id' => $ids['group'], 'periodo_id' => 1, 'nivel_id' => 2,
        ]);
        $this->assertSame('AMBIGUOUS_ASSIGNMENT', $this->access()->canViewInscripcion($context, $ids['inscription'])->code);
        $this->assertSame('AMBIGUOUS_ASSIGNMENT', $this->access()->canViewBoleta($this->context(R::Secretaria), $ids['inscription'])->code);
        $this->assertEmpty(app(AccessibleEvaluaciones::class)->for($context)->get());
    }

    public function test_global_readers_can_read_both_communities_without_write_permissions(): void
    {
        $first = $this->records($this->context());
        $second = $this->records($this->context(), community: 2);
        foreach ([R::Secretaria, R::Parroco, R::CoordinadorGeneral] as $role) {
            $context = $this->context($role);
            foreach ([$first, $second] as $ids) {
                $this->assertTrue($this->access()->canViewBoleta($context, $ids['inscription'])->isAllowed());
                $this->assertTrue($this->access()->canViewEvaluacion($context, $ids['evaluation'])->isAllowed());
                $this->assertSame($role === R::Secretaria, $this->access()->canManageEvaluacion($context, $ids['evaluation'])->isAllowed());
            }
        }
    }

    public function test_assignment_query_preserves_complete_identity(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $assignment = app(AccessibleAsignaciones::class)->for($context)->sole();
        foreach (['id' => $ids['assignment'], 'catequista_id' => $context->userId,
            'comunidad_id' => 1, 'grupo_id' => $ids['group'], 'nivel_id' => 1, 'periodo_id' => 1] as $field => $value) {
            $this->assertEquals($value, $assignment->$field);
        }
    }

    public function test_resolver_rejects_guest(): void
    {
        $this->expectException(AuthorizationException::class);
        app(AccessContextResolver::class)->resolve(Request::create('/'));
    }

    public function test_null_inscription_period_never_inherits_working_period(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('inscripciones')->where('id', $ids['inscription'])->update(['periodo_id' => null]);
        $this->assertEmpty(app(AccessibleInscripciones::class)->for($context)->get());
        $this->assertEmpty(app(AccessibleEvaluaciones::class)->for($context)->get());
    }
}
