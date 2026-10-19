<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Exceptions\DeletionBlockedException;
use App\Models\Secretaria\Periodo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** Un periodo con datos asociados no se puede eliminar, ni desde la pantalla ni desde el modelo. */
class PeriodoDeletionTest extends CatequesisWebTestCase
{
    private const NAME = 'Enero 2026 - Diciembre 2026';

    private function login(R $role = R::Secretaria): void
    {
        $this->actingAs(User::findOrFail($this->context($role)->userId));
    }

    private function blockedMessage(string $summary): string
    {
        return 'No se puede eliminar el periodo '.self::NAME.' porque tiene '.$summary.'. Si ya no se usará, márcalo como inactivo.';
    }

    private function isDeleted(int $id): bool
    {
        return DB::table('periodos')->where('id', $id)->whereNotNull('deleted_at')->exists();
    }

    /** [tabla, resumen esperado con un registro]. */
    public static function dependentTypes(): array
    {
        return [
            'grupo' => ['grupos', '1 grupo'],
            'inscripción' => ['inscripciones', '1 inscripción'],
            'asignación' => ['asigna_grupo', '1 asignación de grupo'],
            'evaluación' => ['evaluaciones', '1 evaluación'],
        ];
    }

    #[DataProvider('dependentTypes')]
    public function test_1_period_with_any_dependent_type_is_not_deleted(string $table, string $summary): void
    {
        $this->login();
        DB::table($table)->insert(['periodo_id' => 2]);

        $this->delete('/secretaria/periodos/2')
            ->assertRedirect(route('secretaria.periodos.index'))
            ->assertSessionHas('error', $this->blockedMessage($summary));
        $this->assertFalse($this->isDeleted(2));
    }

    public function test_2_message_lists_only_present_types_with_plurals(): void
    {
        $this->login();
        DB::table('grupos')->insert([['periodo_id' => 2], ['periodo_id' => 2], ['periodo_id' => 2]]);
        DB::table('inscripciones')->insert([['periodo_id' => 2], ['periodo_id' => 2]]);
        DB::table('evaluaciones')->insert(['periodo_id' => 2]);

        $this->delete('/secretaria/periodos/2')
            ->assertSessionHas('error', $this->blockedMessage('3 grupos, 2 inscripciones y 1 evaluación'));
        $this->assertFalse($this->isDeleted(2));
    }

    public function test_3_already_deleted_records_do_not_block(): void
    {
        $this->login();
        DB::table('grupos')->insert(['periodo_id' => 2, 'deleted_at' => now()]);

        $this->delete('/secretaria/periodos/2')
            ->assertRedirect(route('secretaria.periodos.index'))
            ->assertSessionHas('success', 'Periodo eliminado correctamente.');
        $this->assertTrue($this->isDeleted(2));
    }

    public function test_4_period_without_data_is_deleted_as_before(): void
    {
        $this->login();

        $this->delete('/secretaria/periodos/2')->assertSessionHas('success', 'Periodo eliminado correctamente.');
        $this->assertTrue($this->isDeleted(2));
    }

    public function test_5_model_blocks_delete_and_force_delete_outside_the_controller(): void
    {
        DB::table('inscripciones')->insert(['periodo_id' => 2]);
        $periodo = Periodo::findOrFail(2);

        foreach (['delete', 'forceDelete'] as $method) {
            try {
                $periodo->{$method}();
                $this->fail("$method debía lanzar DeletionBlockedException.");
            } catch (DeletionBlockedException $error) {
                $this->assertSame('1 inscripción', $error->summary);
            }
        }
        $this->assertSame(1, DB::table('periodos')->where('id', 2)->whereNull('deleted_at')->count());
    }

    public static function otherRoles(): array
    {
        return [[R::Catequista], [R::Parroco], [R::CoordinadorGeneral], [R::CoordinadorComunidades]];
    }

    #[DataProvider('otherRoles')]
    public function test_6_only_secretary_can_delete_periods(R $role): void
    {
        $this->login($role);

        $this->delete('/secretaria/periodos/2')->assertForbidden();
        $this->assertFalse($this->isDeleted(2));
    }

    public function test_7_deleting_the_active_period_without_data_falls_back_to_default(): void
    {
        $this->login();
        DB::table('periodos')->where('id', 2)->update(['fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-12-31']);
        $this->withSession(['periodo_activo_id' => 2]);

        $this->delete('/secretaria/periodos/2')->assertSessionHas('success', 'Periodo eliminado correctamente.');
        $notice = 'El periodo que tenías seleccionado ya no está disponible. Ahora trabajas en '.self::NAME.'.';
        $this->get('/secretaria/periodos')->assertOk()->assertSee(json_encode($notice, 15), false);
        $this->assertSame(1, session('periodo_activo_id'));
    }

    public function test_confirmation_dialog_warns_that_periods_with_data_are_kept(): void
    {
        $this->assertStringContainsString('data-message="Esta acción eliminará el periodo seleccionado. Si tiene datos asociados, no se eliminará."',
            file_get_contents(resource_path('views/secretaria/periodos/index.blade.php')));
    }
}
