<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Models\User;
use App\Services\Authorization\AccessContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

class CatequesisControllersTest extends CatequesisWebTestCase
{
    private function login(AccessContext $context): void
    {
        $this->actingAs(User::findOrFail($context->userId));
    }

    private function payload(array $ids, mixed $grade = 6): array
    {
        return ['asignacion_id' => $ids['assignment'], 'unidad_id' => 1, 'calificaciones' => [$ids['inscription'] => [1 => $grade]]];
    }

    public function test_own_group_and_pdf_preserve_authorized_students(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $this->records($this->context());
        $this->login($context);
        $this->get('/catequista/mi-grupo')->assertOk()->assertViewHas('alumnos', fn ($a) => $a->pluck('id')->all() === [$own['student']]);
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) use ($own) {
            $this->assertSame([$own['student']], $data['alumnos']->pluck('id')->all());
            $this->assertStringContainsString('Registro de Asistencia', view($view, $data)->render());

            return true;
        })->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('letter', 'landscape')->andReturnSelf();
        Pdf::shouldReceive('download')->with('lista_asistencia_catequesis.pdf')->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
        $this->get('/catequista/mi-grupo/exportar-asistencia-pdf?asignacion_id='.$own['assignment'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public static function assignmentEndpoints(): array
    {
        return [['/catequista/mi-grupo'], ['/catequista/mi-grupo/exportar-asistencia-pdf'], ['/catequista/evaluaciones']];
    }

    #[DataProvider('assignmentEndpoints')]
    public function test_foreign_and_other_period_assignments_are_404(string $path): void
    {
        $context = $this->context();
        $foreign = $this->records($this->context());
        $old = $this->records($context, period: 2);
        $this->login($context);
        foreach ([$foreign['assignment'], $old['assignment'], 9999] as $id) {
            $this->get($path.'?asignacion_id='.$id.'&catequista_id='.$context->userId)->assertNotFound();
        }
    }

    public function test_multiple_assignments_do_not_select_first(): void
    {
        $context = $this->context();
        $this->records($context);
        $this->records($context);
        $this->login($context);
        $this->get('/catequista/mi-grupo')->assertOk()->assertViewHas('asignacion', null);
        $this->get('/catequista/mi-grupo/exportar-asistencia-pdf')->assertStatus(409);
    }

    public function test_missing_period_redirects_safely(): void
    {
        $context = $this->context();
        $this->login($context);
        $this->withSession(['periodo_activo_id' => 9999])->get('/catequista/mi-grupo')
            ->assertRedirect(route('welcome'))->assertSessionHas('error');
    }

    public function test_own_evaluations_and_legacy_null_period_are_readable(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $this->records($this->context());
        $this->login($context);
        DB::table('evaluaciones')->where('id', $own['evaluation'])->update(['periodo_id' => null]);
        $this->get('/catequista/evaluaciones?unidad_id=1')->assertOk()->assertViewHas('alumnos', fn ($a) => $a->pluck('inscripcion_id')->all() === [$own['inscription']]);
        $this->get('/catequista/evaluaciones?unidad_id=final')->assertOk();
    }

    public function test_create_update_restore_and_delete_own_evaluation(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->login($context);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->delete();
        $this->post('/catequista/evaluaciones/guardar', $this->payload($ids))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('evaluaciones', ['inscripcion_id' => $ids['inscription'], 'calificacion' => 6, 'periodo_id' => 1]);
        $this->post('/catequista/evaluaciones/guardar', $this->payload($ids, 7))->assertSessionHasNoErrors();
        $this->post('/catequista/evaluaciones/guardar', $this->payload($ids, null))->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('evaluaciones')->value('deleted_at'));
        $this->post('/catequista/evaluaciones/guardar', $this->payload($ids, 8))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('evaluaciones', ['inscripcion_id' => $ids['inscription'], 'calificacion' => 8, 'deleted_at' => null, 'periodo_id' => 1]);
        $this->assertDatabaseCount('evaluaciones', 1);
    }

    public static function attacks(): array
    {
        return [[6], [null]];
    }

    #[DataProvider('attacks')]
    public function test_foreign_write_or_delete_is_rejected_and_batch_rolled_back(mixed $grade): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $other = $this->records($this->context());
        $this->login($context);
        $this->post('/catequista/evaluaciones/guardar', $this->payload($other, $grade))->assertNotFound();
        $payload = $this->payload($own, 9);
        $payload['calificaciones'][$other['inscription']] = [1 => $grade];
        $this->post('/catequista/evaluaciones/guardar', $payload)->assertNotFound();
        $this->assertDatabaseHas('evaluaciones', ['id' => $own['evaluation'], 'calificacion' => 5]);
        $this->assertDatabaseHas('evaluaciones', ['id' => $other['evaluation'], 'calificacion' => 5, 'deleted_at' => null]);
    }

    public function test_invalid_unit_rubric_and_grade_are_rejected(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->login($context);
        $payload = $this->payload($ids);
        $payload['unidad_id'] = 2;
        $this->post('/catequista/evaluaciones/guardar', $payload)->assertNotFound();
        $payload = $this->payload($ids);
        $payload['calificaciones'] = [$ids['inscription'] => [999 => 5]];
        $this->post('/catequista/evaluaciones/guardar', $payload)->assertNotFound();
        foreach ([-1, 11] as $grade) {
            $this->post('/catequista/evaluaciones/guardar', $this->payload($ids, $grade))->assertSessionHasErrors();
        }
        $this->assertDatabaseHas('evaluaciones', ['id' => $ids['evaluation'], 'calificacion' => 5]);
    }

    public function test_coordinator_dashboard_students_teachers_groups_and_communities_are_scoped(): void
    {
        $teacher = $this->context();
        $own = $this->records($teacher);
        $this->records($this->context(), community: 2);
        $this->login($this->context(R::CoordinadorComunidades));
        $this->get('/coordinador-comunidades/dashboard')->assertOk()
            ->assertViewHas('totalComunidades', 1)->assertViewHas('totalAlumnos', 1)->assertViewHas('totalGrupos', 1)
            ->assertViewHas('totalCatequistas', 1)->assertViewHas('totalEvaluaciones', 1)->assertViewHas('totalInscripciones', 1);
        $this->get('/coordinador-comunidades/catequistas')->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$teacher->userId]);
        $this->get('/coordinador-comunidades/alumnos-comunidad')->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$own['student']]);
        $this->get('/coordinador-comunidades/alumnos-comunidad?comunidad_id=2')->assertOk()->assertViewHas('registros', fn ($r) => $r->isEmpty());
        $this->get('/coordinador-comunidades/grupos')->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [$own['group']]);
        $this->get('/coordinador-comunidades/comunidades')->assertOk()->assertViewHas('registros', fn ($r) => $r->pluck('id')->all() === [1]);
    }

    public function test_coordinator_evaluations_and_filters_do_not_expose_other_community(): void
    {
        $own = $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        $this->login($this->context(R::CoordinadorComunidades));
        $query = '?sacramento=primera_comunion&nivel_id=1&unidad_id=1&asignacion_id=';
        $this->get('/coordinador-comunidades/evaluaciones'.$query.$own['assignment'])->assertOk()->assertViewHas('alumnos', fn ($r) => $r->pluck('id')->all() === [$own['inscription']]);
        $this->get('/coordinador-comunidades/evaluaciones'.$query.$other['assignment'])->assertNotFound();
        $this->get('/coordinador-comunidades/boletas?grupo_id='.$other['group'])->assertOk()->assertViewHas('alumnos', fn ($r) => $r->isEmpty());
    }

    public function test_coordinator_without_community_has_safe_response(): void
    {
        $this->login($this->context(R::CoordinadorComunidades, community: null));
        foreach (['dashboard', 'catequistas', 'alumnos-comunidad', 'evaluaciones', 'boletas', 'grupos', 'comunidades'] as $path) {
            $this->get('/coordinador-comunidades/'.$path)->assertRedirect(route('welcome'))->assertSessionHas('error');
        }
    }

    public static function boletaRoles(): array
    {
        return [[R::Secretaria, 'secretaria'], [R::Parroco, 'parroco'], [R::CoordinadorGeneral, 'coordinador-general'], [R::CoordinadorComunidades, 'coordinador-comunidades']];
    }

    #[DataProvider('boletaRoles')]
    public function test_authorized_boleta_and_incompatible_assignment(R $role, string $prefix): void
    {
        $own = $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        $this->login($this->context($role));
        $this->get('/'.$prefix.'/boletas/generar/'.$own['inscription'])->assertOk()->assertViewHas('asignacion', fn ($a) => $a->id === $own['assignment']);
        $this->get('/'.$prefix.'/boletas/generar/'.$own['inscription'].'?asignacion_id='.$other['assignment'])->assertNotFound();
        $this->get('/'.$prefix.'/boletas/generar/9999')->assertNotFound();
    }

    public function test_coordinator_cannot_generate_external_boleta(): void
    {
        $other = $this->records($this->context(), community: 2);
        $this->login($this->context(R::CoordinadorComunidades));
        $this->get('/coordinador-comunidades/boletas/generar/'.$other['inscription'])->assertNotFound();
    }

    public function test_ambiguous_assignment_rejects_group_pdf_capture_and_boleta(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        DB::table('asigna_grupo')->insert(['catequista_id' => $context->userId, 'grupo_id' => $ids['group'], 'nivel_id' => 2, 'periodo_id' => 1, 'comunidad_id' => 1]);
        $this->login($context);
        foreach (['/catequista/mi-grupo', '/catequista/mi-grupo/exportar-asistencia-pdf', '/catequista/evaluaciones'] as $path) {
            $this->get($path.'?asignacion_id='.$ids['assignment'])->assertStatus(409);
        }
        $this->post('/catequista/evaluaciones/guardar', $this->payload($ids))->assertStatus(409);
        $this->login($this->context(R::Secretaria));
        $this->get('/secretaria/boletas/generar/'.$ids['inscription'].'?asignacion_id='.$ids['assignment'])->assertStatus(409);
    }

    public function test_secretaria_report_keeps_both_communities_and_unassigned_students(): void
    {
        $this->records($this->context());
        $other = $this->records($this->context(), community: 2);
        DB::table('asigna_grupo')->where('id', $other['assignment'])->delete();
        $this->login($this->context(R::Secretaria));
        $this->get('/secretaria/alumnos-comunidades')->assertOk()->assertViewHas('registros', fn ($r) => $r->total() === 2);
        $this->post('/secretaria/comunidades', ['comunidad' => 'Nueva'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('comunidades', ['comunidad' => 'Nueva']);
    }

    public function test_guest_and_wrong_role_still_rejected_by_middleware(): void
    {
        $this->get('/catequista/mi-grupo')->assertRedirect(route('login'));
        $this->login($this->context(R::Parroco));
        $this->post('/catequista/evaluaciones/guardar', [])->assertForbidden();
    }

    public function test_real_pdf_renderer_still_generates_pdf(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->login($context);
        $response = $this->get('/catequista/mi-grupo/exportar-asistencia-pdf?asignacion_id='.$ids['assignment']);
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_selected_assignment_cannot_write_another_owned_group_or_period(): void
    {
        $context = $this->context();
        $own = $this->records($context);
        $second = $this->records($context);
        $old = $this->records($context, period: 2);
        $this->login($context);
        $payload = $this->payload($second);
        $payload['asignacion_id'] = $own['assignment'];
        $this->post('/catequista/evaluaciones/guardar', $payload)->assertNotFound();
        $this->post('/catequista/evaluaciones/guardar', $this->payload($old))->assertNotFound();
        $this->assertDatabaseHas('evaluaciones', ['id' => $second['evaluation'], 'calificacion' => 5]);
    }

    public function test_deleted_foreign_evaluation_cannot_be_restored(): void
    {
        $context = $this->context();
        $other = $this->records($this->context());
        DB::table('evaluaciones')->where('id', $other['evaluation'])->update(['deleted_at' => now()]);
        $this->login($context);
        $this->post('/catequista/evaluaciones/guardar', $this->payload($other))->assertNotFound();
        $this->assertNotNull(DB::table('evaluaciones')->where('id', $other['evaluation'])->value('deleted_at'));
    }

    public function test_duplicate_evaluations_are_not_silently_overwritten(): void
    {
        $context = $this->context();
        $ids = $this->records($context);
        $this->login($context);
        DB::table('evaluaciones')->insert(['inscripcion_id' => $ids['inscription'], 'unidad_id' => 1, 'rubro_id' => 1, 'periodo_id' => null, 'calificacion' => 4]);
        $this->post('/catequista/evaluaciones/guardar', $this->payload($ids))->assertStatus(409);
        $this->assertDatabaseHas('evaluaciones', ['id' => $ids['evaluation'], 'calificacion' => 5]);
    }

    public function test_boleta_rejects_student_assignment_community_mismatch(): void
    {
        $ids = $this->records($this->context());
        DB::table('alumnos')->where('id', $ids['student'])->update(['comunidad_id' => 2]);
        $this->login($this->context(R::Secretaria));
        $this->get('/secretaria/boletas/generar/'.$ids['inscription'])->assertNotFound();
    }

    public function test_absent_period_has_visible_message_without_redirect_loop(): void
    {
        DB::table('periodos')->delete();
        $this->login($this->context());
        $this->followingRedirects()->get('/catequista/mi-grupo')->assertOk()->assertSee('Selecciona un periodo válido');
    }

    public function test_student_sql_is_scoped_before_retrieval(): void
    {
        $context = $this->context();
        $this->records($context);
        $this->records($this->context());
        $this->login($context);
        DB::enableQueryLog();
        $this->get('/catequista/mi-grupo')->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($sql) => str_starts_with($sql, 'select * from "alumnos"'));
        $this->assertNotEmpty($queries);
        foreach ($queries as $sql) {
            $this->assertStringContainsString('"alumnos"."id" in (select', $sql);
            $this->assertStringContainsString('"asigna_grupo"."catequista_id" = ?', $sql);
            $this->assertStringContainsString('"asigna_grupo"."periodo_id" = ?', $sql);
        }
        DB::disableQueryLog();
    }
}
