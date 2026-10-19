<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole as R;
use App\Exceptions\DeletionBlockedException;
use App\Models\Secretaria\Comunidad;
use App\Models\Secretaria\Grupo;
use App\Models\Secretaria\Nivel;
use App\Models\Secretaria\Rubro;
use App\Models\Secretaria\Unidad;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatequesisWebTestCase;

/** Comunidades, niveles, grupos, unidades y rubros con datos asociados no se pueden eliminar. */
class CatalogDeletionTest extends CatequesisWebTestCase
{
    /** Registros propios, sin datos de partida: comunidad 2, nivel 3, grupo nuevo, unidad 2 y rubro 2. */
    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('niveles')->insert(['id' => 3]);
        $this->ids = [
            'comunidades' => 2, 'niveles' => 3, 'grupos' => DB::table('grupos')->insertGetId(['periodo_id' => 1]),
            'unidades' => 2, 'rubros' => 2,
        ];
    }

    private function login(R $role = R::Secretaria): void
    {
        $this->actingAs(User::findOrFail($this->context($role)->userId));
    }

    private function addDependent(string $table, string $column, string $parent, array $extra = []): void
    {
        if ($table === 'users') {
            // Cualquier rol y estado cuenta: users no usa borrado lógico.
            User::factory()->create([$column => $this->ids[$parent], 'role' => 'usuario', 'status' => 'bloqueado']);

            return;
        }
        DB::table($table)->insert([$column => $this->ids[$parent]] + $extra);
    }

    private function isDeleted(string $table): bool
    {
        return DB::table($table)->where('id', $this->ids[$table])->whereNotNull('deleted_at')->exists();
    }

    /** [catálogo, tabla dependiente, columna, mensaje exacto con un registro]. */
    public static function dependencies(): array
    {
        $community = 'No se puede eliminar la comunidad Prueba porque tiene ';
        $level = 'No se puede eliminar el nivel Prueba porque tiene ';
        $group = 'No se puede eliminar el grupo Prueba porque tiene ';
        $kept = '. Las calificaciones registradas se conservan. Si solo quieres corregir el nombre, ';

        return [
            'comunidad: alumnos' => ['comunidades', 'alumnos', 'comunidad_id',
                $community.'1 alumno. Si solo quieres corregir el nombre, edítala. Para eliminarla, primero cambia esos registros a otra comunidad en Alumnos.'],
            'comunidad: asignaciones' => ['comunidades', 'asigna_grupo', 'comunidad_id',
                $community.'1 asignación de grupo. Si solo quieres corregir el nombre, edítala. Para eliminarla, primero cambia esos registros a otra comunidad en Asignar grupos.'],
            'comunidad: cuentas de usuario' => ['comunidades', 'users', 'comunidad_id',
                $community.'1 cuenta de usuario. Si solo quieres corregir el nombre, edítala. Las cuentas de usuario con esta comunidad las cambia el encargado del sistema.'],
            'nivel: unidades' => ['niveles', 'unidades', 'nivel_id',
                $level.'1 unidad. Si solo quieres corregir el nombre, edítalo. Para eliminarlo, primero cambia esos registros a otro nivel en Unidades.'],
            'nivel: asignaciones' => ['niveles', 'asigna_grupo', 'nivel_id',
                $level.'1 asignación de grupo. Si solo quieres corregir el nombre, edítalo. Para eliminarlo, primero cambia esos registros a otro nivel en Asignar grupos.'],
            'grupo: inscripciones' => ['grupos', 'inscripciones', 'grupo_id',
                $group.'1 inscripción. Si solo quieres corregir el nombre, edítalo. Para eliminarlo, primero cambia esos registros a otro grupo en Inscripciones.'],
            'grupo: asignaciones' => ['grupos', 'asigna_grupo', 'grupo_id',
                $group.'1 asignación de grupo. Si solo quieres corregir el nombre, edítalo. Para eliminarlo, primero cambia esos registros a otro grupo en Asignar grupos.'],
            'unidad: evaluaciones' => ['unidades', 'evaluaciones', 'unidad_id',
                'No se puede eliminar la Unidad 1 - Prueba porque tiene 1 evaluación'.$kept.'edítala.'],
            'rubro: evaluaciones' => ['rubros', 'evaluaciones', 'rubro_id',
                'No se puede eliminar el rubro Prueba porque tiene 1 evaluación'.$kept.'edítalo.'],
        ];
    }

    #[DataProvider('dependencies')]
    public function test_1_each_dependency_blocks_deletion_with_exact_message(string $catalog, string $table, string $column, string $message): void
    {
        $this->login();
        $this->addDependent($table, $column, $catalog);

        $this->delete("/secretaria/$catalog/{$this->ids[$catalog]}")
            ->assertRedirect(route("secretaria.$catalog.index"))
            ->assertSessionHas('error', $message);
        $this->assertFalse($this->isDeleted($catalog));
    }

    public function test_2_combined_dependencies_list_every_type_and_screen(): void
    {
        $this->login();
        $this->addDependent('alumnos', 'comunidad_id', 'comunidades');
        $this->addDependent('alumnos', 'comunidad_id', 'comunidades');
        $this->addDependent('asigna_grupo', 'comunidad_id', 'comunidades');
        $this->addDependent('users', 'comunidad_id', 'comunidades');

        $this->delete('/secretaria/comunidades/2')->assertSessionHas('error',
            'No se puede eliminar la comunidad Prueba porque tiene 2 alumnos, 1 asignación de grupo y 1 cuenta de usuario.'
            .' Si solo quieres corregir el nombre, edítala. Para eliminarla, primero cambia esos registros a otra comunidad en Alumnos y en Asignar grupos.'
            .' Las cuentas de usuario con esta comunidad las cambia el encargado del sistema.');

        $this->addDependent('unidades', 'nivel_id', 'niveles');
        $this->addDependent('unidades', 'nivel_id', 'niveles');
        $this->addDependent('asigna_grupo', 'nivel_id', 'niveles');
        $this->delete('/secretaria/niveles/3')->assertSessionHas('error',
            'No se puede eliminar el nivel Prueba porque tiene 2 unidades y 1 asignación de grupo.'
            .' Si solo quieres corregir el nombre, edítalo. Para eliminarlo, primero cambia esos registros a otro nivel en Unidades y en Asignar grupos.');
        $this->assertFalse($this->isDeleted('comunidades'));
        $this->assertFalse($this->isDeleted('niveles'));
    }

    /** Dependencias con borrado lógico (users no lo tiene). */
    public static function softDeletableDependencies(): array
    {
        return array_filter(self::dependencies(), fn ($case) => $case[1] !== 'users');
    }

    /** [catálogo, mensaje de éxito actual]. */
    public static function successMessages(): array
    {
        return ['comunidades' => ['comunidades', 'Comunidad eliminada correctamente.'], 'niveles' => ['niveles', 'Nivel eliminado correctamente.'],
            'grupos' => ['grupos', 'Grupo eliminado correctamente.'], 'unidades' => ['unidades', 'Unidad eliminada correctamente.'],
            'rubros' => ['rubros', 'Rubro eliminado correctamente.']];
    }

    #[DataProvider('softDeletableDependencies')]
    public function test_3_already_deleted_dependents_do_not_block(string $catalog, string $table, string $column): void
    {
        $this->login();
        $this->addDependent($table, $column, $catalog, ['deleted_at' => now()]);

        $this->delete("/secretaria/$catalog/{$this->ids[$catalog]}")
            ->assertSessionHas('success', self::successMessages()[$catalog][1]);
        $this->assertTrue($this->isDeleted($catalog));
    }

    #[DataProvider('successMessages')]
    public function test_4_catalog_without_data_is_deleted_as_before(string $catalog, string $message): void
    {
        $this->login();

        $this->delete("/secretaria/$catalog/{$this->ids[$catalog]}")
            ->assertRedirect(route("secretaria.$catalog.index"))->assertSessionHas('success', $message);
        $this->assertTrue($this->isDeleted($catalog));
    }

    public function test_5_models_block_delete_and_force_delete_outside_controllers(): void
    {
        $this->addDependent('users', 'comunidad_id', 'comunidades');
        $this->addDependent('unidades', 'nivel_id', 'niveles');
        $this->addDependent('inscripciones', 'grupo_id', 'grupos');
        $this->addDependent('evaluaciones', 'unidad_id', 'unidades');
        $this->addDependent('evaluaciones', 'rubro_id', 'rubros');
        $models = ['comunidades' => [Comunidad::class, '1 cuenta de usuario'], 'niveles' => [Nivel::class, '1 unidad'],
            'grupos' => [Grupo::class, '1 inscripción'], 'unidades' => [Unidad::class, '1 evaluación'], 'rubros' => [Rubro::class, '1 evaluación']];

        foreach ($models as $catalog => [$class, $summary]) {
            $model = $class::findOrFail($this->ids[$catalog]);
            foreach (['delete', 'forceDelete'] as $method) {
                try {
                    $model->{$method}();
                    $this->fail("$class::$method debía lanzar DeletionBlockedException.");
                } catch (DeletionBlockedException $error) {
                    $this->assertSame($summary, $error->summary);
                }
            }
            $this->assertFalse($this->isDeleted($catalog));
            $this->assertTrue(DB::table($catalog)->where('id', $this->ids[$catalog])->exists());
        }
    }

    public static function otherRoles(): array
    {
        return [[R::Catequista], [R::Parroco], [R::CoordinadorGeneral], [R::CoordinadorComunidades]];
    }

    #[DataProvider('otherRoles')]
    public function test_6_only_secretary_can_delete_catalogs(R $role): void
    {
        $this->login($role);
        foreach (array_keys($this->ids) as $catalog) {
            $this->delete("/secretaria/$catalog/{$this->ids[$catalog]}")->assertForbidden();
            $this->assertFalse($this->isDeleted($catalog));
        }
    }

    public function test_7_confirmation_dialogs_warn_that_records_with_data_are_kept(): void
    {
        foreach (['comunidades' => 'la comunidad seleccionada', 'niveles' => 'el nivel seleccionado', 'grupos' => 'el grupo seleccionado',
            'unidades' => 'la unidad seleccionada', 'rubros' => 'el rubro seleccionado'] as $view => $text) {
            $this->assertStringContainsString("data-message=\"Esta acción eliminará $text. Si tiene datos asociados, no se eliminará.\"",
                file_get_contents(resource_path("views/secretaria/$view/index.blade.php")));
        }
    }
}
