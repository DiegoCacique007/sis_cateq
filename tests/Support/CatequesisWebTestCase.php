<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class CatequesisWebTestCase extends CatequesisTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['comunidades' => ['comunidad'], 'niveles' => ['nivel', 'sacramento'],
            'grupos' => ['nombre'], 'unidades' => ['nombre'], 'rubros' => ['nombre'],
            'alumnos' => ['nombre', 'apellido_paterno', 'apellido_materno']] as $table => $fields) {
            Schema::table($table, function (Blueprint $t) use ($fields) {
                foreach ($fields as $field) {
                    $t->string($field)->default('Prueba');
                }
            });
        }
        Schema::table('periodos', function (Blueprint $t) {
            $t->date('fecha_inicio')->default('2026-01-01');
            $t->date('fecha_fin')->default('2026-12-31');
        });
        foreach (['niveles', 'unidades'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->integer('numero')->default(1));
        }
        Schema::table('rubros', fn (Blueprint $t) => $t->decimal('valor', 8, 2)->default(10));
        Schema::table('evaluaciones', fn (Blueprint $t) => $t->decimal('calificacion', 8, 2)->default(5));
        Schema::table('inscripciones', fn (Blueprint $t) => $t->integer('estado')->nullable()->default(1));
        Schema::table('alumnos', fn (Blueprint $t) => $t->date('fecha_nacimiento')->nullable());
        Schema::create('tutores', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('alumno_id');
            $t->string('nombre');
            $t->string('ap')->nullable();
            $t->string('am')->nullable();
            $t->string('telefono')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('niveles')->update(['sacramento' => 'primera_comunion']);
        $this->withSession(['periodo_activo_id' => 1]);
    }
}
