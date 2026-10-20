<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** inscripciones.estado no existe en la base real: los paneles y las evaluaciones cuentan las inscripciones no eliminadas. */
class InscripcionesEstadoTest extends CatequesisWebTestCase
{
    public static function supervisors(): array
    {
        return [
            'parroco' => [R::Parroco, 'parroco'],
            'coordinador_general' => [R::CoordinadorGeneral, 'coordinador-general'],
            'coordinador_comunidades' => [R::CoordinadorComunidades, 'coordinador-comunidades'],
        ];
    }

    #[DataProvider('supervisors')]
    public function test_dashboard_counts_only_inscriptions_not_deleted(R $role, string $prefix): void
    {
        [$active, $deleted] = $this->activeAndDeletedInscriptions();
        $this->actingAs(User::findOrFail($this->context($role)->userId));

        $this->get("/{$prefix}/dashboard")->assertOk()->assertViewHas('totalInscripciones', 1);
        $this->assertNotNull(DB::table('inscripciones')->where('id', $deleted)->value('deleted_at'));
    }

    #[DataProvider('supervisors')]
    public function test_evaluations_list_students_of_inscriptions_not_deleted(R $role, string $prefix): void
    {
        [$active, $deleted, $assignment] = $this->activeAndDeletedInscriptions();
        $this->actingAs(User::findOrFail($this->context($role)->userId));

        $response = $this->get("/{$prefix}/evaluaciones?sacramento=primera_comunion&nivel_id=1&unidad_id=1&asignacion_id={$assignment}")
            ->assertOk();

        $this->assertSame([$active], $response->viewData('alumnos')->pluck('id')->values()->all());
    }

    public function test_test_schema_has_no_inscripciones_estado_like_the_real_database(): void
    {
        $this->assertFalse(Schema::hasColumn('inscripciones', 'estado'));
    }

    public function test_secretaria_inscriptions_list_has_no_alta_baja_status(): void
    {
        $this->activeAndDeletedInscriptions();
        $this->actingAs(User::findOrFail($this->context(R::Secretaria)->userId));

        $this->get('/secretaria/inscripciones')->assertOk()
            ->assertDontSee('status-badge-baja', false)
            ->assertDontSee('status-badge-alta', false)
            ->assertDontSee('name="estado"', false)
            ->assertDontSee('Estado de inscripción');
    }

    /** Una inscripción activa y otra eliminada (borrado lógico) en el mismo grupo y periodo activo. */
    private function activeAndDeletedInscriptions(): array
    {
        $ids = $this->records($this->context(R::Catequista));
        $student = DB::table('alumnos')->insertGetId(['comunidad_id' => 1, 'nombre' => 'Alumno Eliminado']);
        $deleted = DB::table('inscripciones')->insertGetId([
            'alumno_id' => $student, 'grupo_id' => $ids['group'], 'periodo_id' => 1, 'deleted_at' => now(),
        ]);

        return [$ids['inscription'], $deleted, $ids['assignment']];
    }
}
