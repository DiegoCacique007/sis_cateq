<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Authorization\CatequesisAccess;
use Illuminate\Support\Facades\DB;
use Tests\Support\CatequesisWebTestCase;

class BoletasTest extends CatequesisWebTestCase
{
    private function secretary(): void
    {
        $context = $this->context(UserRole::Secretaria);
        $this->actingAs(User::findOrFail($context->userId));
    }

    public function test_filtered_cards_link_to_the_inscription_and_generate_the_printable_document(): void
    {
        DB::table('alumnos')->insert(['comunidad_id' => 1]);
        $teacher = $this->context();
        $ids = $this->records($teacher);
        $this->assertNotSame($ids['student'], $ids['inscription']);
        $this->secretary();
        foreach (['catequista_id' => $teacher->userId, 'nivel_id' => 1, 'grupo_id' => $ids['group']] as $filter => $value) {
            $response = $this->get(route('secretaria.boletas.index', [$filter => $value]))->assertOk();
            $url = route('secretaria.boletas.generar', ['inscripcion' => $ids['inscription'], 'asignacion_id' => $ids['assignment']]);
            $response->assertSee($url, false)->assertViewHas('alumnos', fn ($rows) => $rows->count() === 1);
            $this->get($url)->assertOk()->assertViewIs('secretaria.boletas.boleta_pdf')
                ->assertSee('window.print()', false)->assertSee(User::findOrFail($teacher->userId)->name);
        }
    }

    public function test_multiple_assignments_require_selection_and_only_include_the_selected_level(): void
    {
        $teacher = $this->context();
        $ids = $this->records($teacher);
        $second = DB::table('asigna_grupo')->insertGetId([
            'catequista_id' => $teacher->userId, 'grupo_id' => $ids['group'],
            'periodo_id' => 1, 'nivel_id' => 2, 'comunidad_id' => 1,
        ]);
        DB::table('evaluaciones')->insert([
            'inscripcion_id' => $ids['inscription'], 'unidad_id' => 2,
            'rubro_id' => 1, 'periodo_id' => 1, 'calificacion' => 8,
        ]);
        $this->secretary();
        $this->get(route('secretaria.boletas.index', ['grupo_id' => $ids['group']]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->sole()->opcionesBoleta->count() === 2)
            ->assertSee('Selecciona una asignación');
        $url = route('secretaria.boletas.generar', $ids['inscription']);
        $this->get($url)->assertOk()->assertViewIs('secretaria.boletas.seleccionar');
        $this->get($url.'?asignacion_id='.$second)->assertOk()
            ->assertViewHas('unidades', fn ($u) => $u->pluck('id')->all() === [2])
            ->assertViewHas('calificacionesMap', [2 => [1 => '8.00']]);
        $this->get(route('secretaria.boletas.index', ['nivel_id' => 1]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->sole()->opcionesBoleta->count() === 1);
    }

    public function test_student_can_generate_boleta_when_residence_differs_from_academic_community(): void
    {
        $ids = $this->records($this->context());
        DB::table('alumnos')->where('id', $ids['student'])->update(['comunidad_id' => 2]);
        $this->secretary();
        $this->get(route('secretaria.boletas.index', ['grupo_id' => $ids['group']]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->count() === 1 &&
                (int) $rows->first()->id === (int) $ids['inscription'] &&
                $rows->first()->opcionesBoleta->pluck('id')->contains($ids['assignment']));
        $this->get(route('secretaria.boletas.generar', [
            'inscripcion' => $ids['inscription'], 'asignacion_id' => $ids['assignment'],
        ]))->assertOk()->assertViewIs('secretaria.boletas.boleta_pdf');
    }

    public function test_community_coordinator_uses_academic_community_not_student_residence(): void
    {
        $ids = $this->records($this->context());
        DB::table('alumnos')->where('id', $ids['student'])->update(['comunidad_id' => 2]);
        $authorized = $this->context(UserRole::CoordinadorComunidades, community: 1);
        $this->actingAs(User::findOrFail($authorized->userId));
        $this->get(route('coordinador_comunidades.boletas.index', ['grupo_id' => $ids['group']]))
            ->assertOk()->assertViewHas('alumnos', fn ($rows) => $rows->count() === 1);
        $this->get(route('coordinador_comunidades.boletas.generar', $ids['inscription']))
            ->assertOk()->assertViewIs('secretaria.boletas.boleta_pdf');
        $unauthorized = $this->context(UserRole::CoordinadorComunidades, community: 2);
        $this->actingAs(User::findOrFail($unauthorized->userId));
        $this->get(route('coordinador_comunidades.boletas.generar', $ids['inscription']))->assertNotFound();
    }

    public function test_teacher_filter_cannot_pair_an_inscription_with_another_academic_community(): void
    {
        $teacher = $this->context();
        $ids = $this->records($teacher);
        DB::table('inscripciones')->where('id', $ids['inscription'])
            ->update(['asigna_grupo_id' => $ids['assignment']]);
        $other = $this->context();
        $foreign = DB::table('asigna_grupo')->insertGetId([
            'catequista_id' => $other->userId, 'grupo_id' => $ids['group'],
            'periodo_id' => 1, 'nivel_id' => 2, 'comunidad_id' => 2,
        ]);
        $this->secretary();
        $this->get(route('secretaria.boletas.index', ['catequista_id' => $other->userId]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->isEmpty());
        $this->get(route('secretaria.boletas.index', ['grupo_id' => $ids['group']]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->sole()->opcionesBoleta->pluck('id')->all() === [$ids['assignment']]);
        $this->get(route('secretaria.boletas.generar', [
            'inscripcion' => $ids['inscription'], 'asignacion_id' => $foreign,
        ]))->assertNotFound();
        $contextoSecretaria = $this->context(UserRole::Secretaria);
        $this->assertFalse(app(CatequesisAccess::class)->canViewBoleta(
            $contextoSecretaria, (int) $ids['inscription'], (int) $foreign
        )->isAllowed());
    }

    public function test_unlinked_inscription_is_not_assigned_arbitrarily_between_academic_communities(): void
    {
        $teacher = $this->context();
        $ids = $this->records($teacher);
        $other = $this->context();
        DB::table('asigna_grupo')->insert([
            'catequista_id' => $other->userId, 'grupo_id' => $ids['group'],
            'periodo_id' => 1, 'nivel_id' => 2, 'comunidad_id' => 2,
        ]);
        $this->secretary();
        $this->get(route('secretaria.boletas.index', ['grupo_id' => $ids['group']]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->isEmpty());
        $this->get(route('secretaria.boletas.generar', $ids['inscription']))->assertNotFound();
    }

    public function test_missing_grades_are_blank_and_zero_is_a_real_grade(): void
    {
        $ids = $this->records($this->context());
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['deleted_at' => now()]);
        $this->secretary();
        $url = route('secretaria.boletas.generar', $ids['inscription']);
        $this->get($url)->assertOk()->assertViewHas('promedioFinal', null)->assertViewHas('promediosUnidad', [1 => null]);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['deleted_at' => null, 'calificacion' => 0]);
        $this->get($url)->assertOk()->assertViewHas('promedioFinal', 0.0);
    }

    public function test_summary_matches_catechist_without_rounding_intermediate_results(): void
    {
        $teacher = $this->context();
        $ids = $this->records($teacher);
        DB::table('rubros')->where('id', 1)->update(['valor' => 3]);
        DB::table('rubros')->where('id', 2)->update(['valor' => 4]);
        DB::table('unidades')->insert(['id' => 3, 'nivel_id' => 1, 'numero' => 2]);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['calificacion' => 1, 'periodo_id' => null]);
        DB::table('evaluaciones')->insert([
            'inscripcion_id' => $ids['inscription'], 'unidad_id' => 3,
            'rubro_id' => 1, 'periodo_id' => 1, 'calificacion' => 2,
        ]);
        $this->actingAs(User::findOrFail($teacher->userId));
        $student = $this->get(route('catequista.evaluaciones.index', [
            'asignacion_id' => $ids['assignment'], 'unidad_id' => 'final',
        ]))->assertOk()->viewData('alumnos')->sole();
        $this->secretary();
        $this->get(route('secretaria.boletas.generar', $ids['inscription']))->assertOk()
            ->assertViewHas('promediosUnidad', $student->promedios_unidad)
            ->assertViewHas('promedioFinal', $student->promedio_final)
            ->assertViewHas('promediosUnidad', [1 => 1.43, 3 => 2.86])
            ->assertViewHas('promedioFinal', 2.14);
    }

    public function test_all_authorized_roles_keep_period_and_community_boundaries(): void
    {
        $ids = $this->records($this->context());
        $external = $this->records($this->context(), community: 2);
        $past = $this->records($this->context(), period: 2);
        foreach ([UserRole::Secretaria, UserRole::Parroco, UserRole::CoordinadorGeneral, UserRole::CoordinadorComunidades] as $role) {
            $context = $this->context($role);
            $this->actingAs(User::findOrFail($context->userId));
            $route = $role->value.'.boletas.generar';
            $this->get(route($route, $ids['inscription']))->assertOk();
            $this->get(route($route, $past['inscription']))->assertNotFound();
            $this->get(route($route, 999999))->assertNotFound();
            $this->get(route($route, [
                'inscripcion' => $ids['inscription'], 'asignacion_id' => $external['assignment'],
            ]))->assertNotFound();
            $response = $this->get(route($route, $external['inscription']));
            $role === UserRole::CoordinadorComunidades ? $response->assertNotFound() : $response->assertOk();
        }
        $teacher = $this->context();
        $this->actingAs(User::findOrFail($teacher->userId))
            ->get(route('secretaria.boletas.generar', $ids['inscription']))->assertForbidden();
    }

    public function test_foreign_period_and_deleted_grades_are_excluded_and_duplicates_are_reported(): void
    {
        $ids = $this->records($this->context());
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['periodo_id' => 2]);
        $this->secretary();
        $url = route('secretaria.boletas.generar', $ids['inscription']);
        $this->get($url)->assertOk()->assertViewHas('promedioFinal', null);
        DB::table('evaluaciones')->where('id', $ids['evaluation'])->update(['periodo_id' => 1]);
        DB::table('evaluaciones')->insert([
            'inscripcion_id' => $ids['inscription'], 'unidad_id' => 1,
            'rubro_id' => 1, 'periodo_id' => 1,
        ]);
        $this->get($url)->assertStatus(409);
    }

    public function test_missing_units_and_assignments_do_not_produce_unexpected_server_errors(): void
    {
        $ids = $this->records($this->context());
        $this->secretary();
        $url = route('secretaria.boletas.generar', $ids['inscription']);
        DB::table('unidades')->where('id', 1)->update(['deleted_at' => now()]);
        $this->get($url)->assertOk()->assertSee('No hay unidades registradas')->assertViewHas('promedioFinal', null);
        DB::table('asigna_grupo')->where('id', $ids['assignment'])->update(['deleted_at' => now()]);
        $this->get($url)->assertNotFound();
        $this->get(route('secretaria.boletas.index', ['grupo_id' => $ids['group']]))->assertOk()
            ->assertViewHas('alumnos', fn ($rows) => $rows->isEmpty());
    }
}
