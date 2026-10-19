<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Las migraciones reproducen el esquema académico real y, sobre una base existente, no cambian nada. */
class SchemaMigrationsTest extends TestCase
{
    use RefreshDatabase;

    private const COLUMNS = [
        'comunidades' => ['id', 'comunidad', 'created_at', 'updated_at', 'deleted_at'],
        'periodos' => ['id', 'fecha_inicio', 'fecha_fin', 'estado', 'created_at', 'updated_at', 'deleted_at'],
        'niveles' => ['id', 'nivel', 'sacramento', 'numero', 'created_at', 'updated_at', 'deleted_at'],
        'rubros' => ['id', 'nombre', 'valor', 'created_at', 'updated_at', 'deleted_at'],
        'grupos' => ['id', 'nombre', 'created_at', 'updated_at', 'deleted_at', 'periodo_id'],
        'unidades' => ['id', 'nombre', 'numero', 'nivel_id', 'created_at', 'updated_at', 'deleted_at'],
        'alumnos' => ['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'comunidad_id', 'estado', 'fecha_nacimiento', 'created_at', 'updated_at', 'deleted_at'],
        'tutores' => ['id', 'nombre', 'ap', 'am', 'telefono', 'alumno_id', 'created_at', 'updated_at', 'deleted_at'],
        'asigna_grupo' => ['id', 'comunidad_id', 'grupo_id', 'nivel_id', 'periodo_id', 'catequista_id', 'created_at', 'updated_at', 'deleted_at'],
        'inscripciones' => ['id', 'alumno_id', 'periodo_id', 'grupo_id', 'asigna_grupo_id', 'created_at', 'updated_at', 'deleted_at'],
        'evaluaciones' => ['id', 'inscripcion_id', 'rubro_id', 'calificacion', 'unidad_id', 'created_at', 'updated_at', 'deleted_at', 'periodo_id'],
    ];

    private const UNIQUE = [
        'comunidades' => ['comunidad' => ['comunidad']],
        'periodos' => ['uq_periodos_fechas' => ['fecha_inicio', 'fecha_fin']],
        'niveles' => ['uq_niveles_nombre' => ['nivel']],
        'rubros' => ['uq_rubros_nombre' => ['nombre']],
        'grupos' => ['uq_grupos_nombre' => ['nombre']],
        'unidades' => ['uq_unidades_numero_nivel' => ['nivel_id', 'numero'], 'uq_unidades_nombre_nivel' => ['nivel_id', 'nombre']],
        'alumnos' => ['uq_alumnos_registro' => ['nombre', 'apellido_paterno', 'apellido_materno', 'comunidad_id']],
        'tutores' => ['uq_tutores_registro' => ['nombre', 'ap', 'am', 'alumno_id']],
        'asigna_grupo' => ['uq_asigna_grupo_registro' => ['comunidad_id', 'grupo_id', 'nivel_id', 'periodo_id', 'catequista_id']],
        'inscripciones' => ['uq_inscripciones_registro' => ['alumno_id', 'periodo_id', 'grupo_id']],
        'evaluaciones' => ['uq_evaluaciones_registro' => ['inscripcion_id', 'rubro_id', 'unidad_id']],
    ];

    private const FOREIGN = [
        'grupos' => ['periodo_id→periodos SET NULL'],
        'unidades' => ['nivel_id→niveles NO ACTION'],
        'alumnos' => ['comunidad_id→comunidades NO ACTION'],
        'tutores' => ['alumno_id→alumnos NO ACTION'],
        'asigna_grupo' => ['catequista_id→users NO ACTION', 'comunidad_id→comunidades NO ACTION', 'grupo_id→grupos NO ACTION', 'nivel_id→niveles NO ACTION', 'periodo_id→periodos NO ACTION'],
        'inscripciones' => ['alumno_id→alumnos NO ACTION', 'grupo_id→grupos NO ACTION', 'periodo_id→periodos NO ACTION'],
        'evaluaciones' => ['inscripcion_id→inscripciones NO ACTION', 'periodo_id→periodos SET NULL', 'rubro_id→rubros NO ACTION', 'unidad_id→unidades NO ACTION'],
    ];

    public function test_migrations_create_the_academic_tables_like_the_real_database(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->assertEqualsCanonicalizing($columns, Schema::getColumnListing($table), $table);
        }
        // Columnas que la base real no tiene.
        $this->assertFalse(Schema::hasColumn('inscripciones', 'estado'));
        foreach (['bautizo_lugar', 'bautizo_fecha', 'bautizo_libro', 'bautizo_acta', 'primera_comunion_lugar', 'primera_comunion_fecha', 'primera_comunion_libro', 'primera_comunion_acta'] as $column) {
            $this->assertFalse(Schema::hasColumn('alumnos', $column), $column);
        }
        $this->assertTrue(Schema::hasColumn('users', 'comunidad_id'));
    }

    public function test_unique_indexes_and_foreign_keys_match_the_real_database(): void
    {
        foreach (self::UNIQUE as $table => $expected) {
            $unique = collect(Schema::getIndexes($table))->where('unique', true)->where('primary', false)
                ->mapWithKeys(fn (array $index) => [$index['name'] => $index['columns']])->all();
            $this->assertEquals($expected, $unique, $table);
        }

        foreach (array_keys(self::COLUMNS) as $table) {
            $foreign = collect(Schema::getForeignKeys($table))
                ->map(fn (array $fk) => implode(',', $fk['columns']).'→'.$fk['foreign_table'].' '.strtoupper($fk['on_delete'] ?? 'no action'))
                ->sort()->values()->all();
            $this->assertSame(self::FOREIGN[$table] ?? [], $foreign, $table);
        }
    }

    public function test_creation_migrations_change_nothing_when_the_tables_already_exist(): void
    {
        $comunidad = DB::table('comunidades')->insertGetId(['comunidad' => 'San José']);
        $before = $this->structure();

        foreach (glob(database_path('migrations/2026_01_01_*_create_*_table.php')) as $file) {
            $migration = require $file;
            $migration->up();
            // down() no revierte: un rollback nunca borra tablas base con datos.
            $migration->down();
        }

        $this->assertSame($before, $this->structure());
        $this->assertSame('San José', DB::table('comunidades')->where('id', $comunidad)->value('comunidad'));
        $this->assertCount(11, glob(database_path('migrations/2026_01_01_*_create_*_table.php')));
    }

    private function structure(): array
    {
        return collect(array_keys(self::COLUMNS))->mapWithKeys(fn (string $table) => [$table => [
            Schema::getColumns($table), Schema::getIndexes($table), Schema::getForeignKeys($table),
        ]])->all();
    }
}
